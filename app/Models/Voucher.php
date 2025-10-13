<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Carbon\Carbon;

class Voucher extends Model
{
    use HasUuids;

    protected $fillable = [
        'code_product', 'voucher_code', 'name_voucher', 'start_date', 'end_date',
        'min_purchase', 'quota', 'claim_per_customer', 'voucher_type', 'value',
        'discount_max', 'category_customer', 'is_active', 'spreadsheet_row_id', 'sync_status'
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'min_purchase' => 'decimal:2',
        'discount_max' => 'decimal:2',
        'quota' => 'integer',
        'claim_per_customer' => 'integer',
        'total_used' => 'integer',
        'is_active' => 'boolean',
    ];

    protected $appends = ['current_status', 'discount_percentage', 'formatted_value'];

    // Relationships
    public function usages()
    {
        return $this->hasMany(VoucherUsage::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeValid($query)
    {
        return $query->active()
                    ->where('start_date', '<=', now())
                    ->where('end_date', '>=', now())
                    ->whereRaw('quota > total_used');
    }

    public function scopeForCategory($query, $category)
    {
        return $query->where(function ($q) use ($category) {
            $q->where('category_customer', 'all customer')
              ->orWhere('category_customer', $category);
        });
    }

    // Accessors
    public function getCurrentStatusAttribute()
    {
        if (!$this->is_active) return 'inactive';
        if (now()->lt($this->start_date)) return 'pending';
        if (now()->gt($this->end_date)) return 'expired';
        if ($this->total_used >= $this->quota) return 'quota_full';
        return 'active';
    }

    public function getDiscountPercentageAttribute()
    {
        if ($this->voucher_type === 'PERCENT') {
            return (int) filter_var($this->value, FILTER_SANITIZE_NUMBER_INT);
        }
        return 0;
    }

    public function getFormattedValueAttribute()
    {
        return $this->value;
    }

    // Methods
    public function parseRupiah($rupiahString)
    {
        $cleaned = preg_replace('/[Rp\s\.]/', '', $rupiahString);
        return (float) preg_replace('/[^\d]/', '', $cleaned);
    }

    public function calculateDiscount($orderTotal)
    {
        if ($this->voucher_type === 'NOMINAL') {
            return $this->parseRupiah($this->value);
        } else {
            $percentage = $this->discount_percentage;
            $discount = $orderTotal * ($percentage / 100);
            return min($discount, $this->discount_max);
        }
    }

    public function isValidForUser($customerId, $orderTotal = 0)
    {
        if ($this->current_status !== 'active') {
            return [
                'valid' => false,
                'message' => 'Voucher tidak aktif atau sudah expired'
            ];
        }

        if ($orderTotal < $this->min_purchase) {
            return [
                'valid' => false,
                'message' => 'Minimum pembelian ' . number_format($this->min_purchase, 0, ',', '.')
            ];
        }

        $userUsageCount = $this->usages()->where('customer_id', $customerId)->count();
        if ($userUsageCount >= $this->claim_per_customer) {
            return [
                'valid' => false,
                'message' => 'Anda sudah menggunakan voucher ini maksimal'
            ];
        }

        return [
            'valid' => true,
            'discount' => $this->calculateDiscount($orderTotal)
        ];
    }
}