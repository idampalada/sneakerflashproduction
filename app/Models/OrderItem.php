<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class OrderItem extends Model
{
    use HasFactory;

    protected $table = 'order_items';

    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'product_sku',
        'product_price',     // ✅ Required field untuk PostgreSQL
        'quantity',
        'total_price'
    ];

    protected $casts = [
        'product_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'quantity' => 'integer'
    ];



    // ========================================
    // RELATIONSHIPS
    // ========================================

    /**
     * Get the order that owns this item
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the product associated with this order item
     * Note: Product bisa null jika produk sudah dihapus
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // ========================================
    // ACCESSORS & MUTATORS
    // ========================================

    /**
     * Get subtotal (calculated field)
     */
    public function getSubtotalAttribute(): float
    {
        return (float) ($this->product_price * $this->quantity);
    }

    /**
     * Get formatted price with currency
     */
    public function getFormattedPriceAttribute(): string
    {
        return 'Rp ' . number_format($this->product_price, 0, ',', '.');
    }

    /**
     * Get formatted total with currency
     */
    public function getFormattedTotalAttribute(): string
    {
        return 'Rp ' . number_format($this->total_price, 0, ',', '.');
    }

    /**
     * Get formatted subtotal with currency
     */
    public function getFormattedSubtotalAttribute(): string
    {
        return 'Rp ' . number_format($this->subtotal, 0, ',', '.');
    }

    /**
     * Get product image from snapshot or current product
     */
    public function getProductImageAttribute(): ?string
    {
        // Try from snapshot first
        if ($this->product_snapshot && isset($this->product_snapshot['image'])) {
            return $this->product_snapshot['image'];
        }

        // Fallback to current product if exists
        if ($this->product && $this->product->featured_image) {
            return $this->product->featured_image;
        }

        // Check product images array
        if ($this->product && $this->product->images && count($this->product->images) > 0) {
            return $this->product->images[0];
        }

        return null;
    }

    /**
     * Get product brand from snapshot or current product
     */
    public function getProductBrandAttribute(): ?string
    {
        // Try from snapshot first
        if ($this->product_snapshot && isset($this->product_snapshot['brand'])) {
            return $this->product_snapshot['brand'];
        }

        // Fallback to current product
        return $this->product?->brand;
    }

    /**
     * Get product category from snapshot or current product
     */
    public function getProductCategoryAttribute(): ?string
    {
        // Try from snapshot first
        if ($this->product_snapshot && isset($this->product_snapshot['category'])) {
            return $this->product_snapshot['category'];
        }

        // Fallback to current product
        return $this->product?->category?->name;
    }

    // ========================================
    // SCOPES
    // ========================================

    /**
     * Scope untuk filter berdasarkan order ID
     */
    public function scopeForOrder($query, int $orderId)
    {
        return $query->where('order_id', $orderId);
    }

    /**
     * Scope untuk filter berdasarkan product ID
     */
    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Scope dengan relationship loading
     */
    public function scopeWithRelations($query)
    {
        return $query->with(['order', 'product']);
    }

    // ========================================
    // HELPER METHODS
    // ========================================

    /**
     * Get product snapshot data
     */
    public function getProductSnapshot(): array
    {
        return $this->product_snapshot ?? [];
    }

    /**
     * Check if product still exists and active
     */
    public function isProductStillAvailable(): bool
    {
        return $this->product && $this->product->is_active;
    }

    /**
     * Get the original product price at time of order
     */
    public function getOriginalProductPrice(): ?float
    {
        if ($this->product_snapshot && isset($this->product_snapshot['original_price'])) {
            return (float) $this->product_snapshot['original_price'];
        }

        return null;
    }

    /**
     * Get the sale price at time of order
     */
    public function getSaleProductPrice(): ?float
    {
        if ($this->product_snapshot && isset($this->product_snapshot['sale_price'])) {
            return (float) $this->product_snapshot['sale_price'];
        }

        return null;
    }

    /**
     * Check if item was purchased with discount
     */
    public function wasPurchasedWithDiscount(): bool
    {
        $originalPrice = $this->getOriginalProductPrice();
        
        if (!$originalPrice) {
            return false;
        }

        return $this->product_price < $originalPrice;
    }

    /**
     * Get discount amount if purchased with discount
     */
    public function getDiscountAmount(): float
    {
        if (!$this->wasPurchasedWithDiscount()) {
            return 0;
        }

        $originalPrice = $this->getOriginalProductPrice();
        return ($originalPrice - $this->product_price) * $this->quantity;
    }

    /**
     * Get formatted discount amount
     */
    public function getFormattedDiscountAmount(): string
    {
        return 'Rp ' . number_format($this->getDiscountAmount(), 0, ',', '.');
    }

    // ========================================
    // VALIDATION & BUSINESS LOGIC
    // ========================================

    /**
     * Validate order item data before save
     */
    public function validateData(): array
    {
        $errors = [];

        if ($this->quantity <= 0) {
            $errors[] = 'Quantity must be greater than 0';
        }

        if ($this->product_price <= 0) {
            $errors[] = 'Product price must be greater than 0';
        }

        if ($this->total_price <= 0) {
            $errors[] = 'Total price must be greater than 0';
        }

        if (empty($this->product_name)) {
            $errors[] = 'Product name is required';
        }

        // Validate total_price calculation
        $calculatedTotal = $this->product_price * $this->quantity;
        if (abs($calculatedTotal - $this->total_price) > 0.01) {
            $errors[] = 'Total price calculation mismatch';
        }

        return $errors;
    }

    /**
     * Check if order item is valid
     */
    public function isValid(): bool
    {
        return empty($this->validateData());
    }

    // ========================================
    // BOOT METHOD
    // ========================================

    protected static function boot()
    {
        parent::boot();

        // Auto-calculate total_price before saving
        static::saving(function ($orderItem) {
            if ($orderItem->product_price && $orderItem->quantity) {
                $orderItem->total_price = $orderItem->product_price * $orderItem->quantity;
            }
        });

        // Validate data before creating
        static::creating(function ($orderItem) {
            $errors = $orderItem->validateData();
            if (!empty($errors)) {
                throw new \InvalidArgumentException('OrderItem validation failed: ' . implode(', ', $errors));
            }
        });

        // Log order item creation
        static::created(function ($orderItem) {
            Log::info("OrderItem created: ID {$orderItem->id} for Order {$orderItem->order_id}");
        });
    }
}