<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CartService
{
    /**
     * Get complete cart data for checkout and voucher calculations
     */
    public function getCartData(): array
{
    $cart = Session::get('cart', []);
    $cartItems = [];
    $subtotal = 0;
    $totalWeight = 0;
    $totalQuantity = 0;

    foreach ($cart as $cartKey => $details) {
        $productId = $details['product_id'] ?? null;
        
        if (!$productId) {
            continue;
        }

        $product = Product::find($productId);
        
        if (!$product || !$product->is_active || $product->stock_quantity <= 0) {
            continue;
        }

        // Calculate price (use sale_price if available)
        $price = $product->sale_price ?? $product->price;
        $quantity = $details['quantity'] ?? 1;
        $itemSubtotal = $price * $quantity;

        // Get categories safely
        $categories = [];
        try {
            if ($product->categories) {
                $categories = $product->categories->pluck('id')->toArray();
            }
        } catch (\Exception $e) {
            \Log::warning('Error getting product categories', [
                'product_id' => $product->id,
                'error' => $e->getMessage()
            ]);
            $categories = [];
        }

        // Prepare cart item data
        $cartItem = [
            'cart_key' => $cartKey,
            'product_id' => $product->id,
            'product' => $product, // Include full product for voucher validation
            'name' => $this->getCleanProductName($product->name, $details['size'] ?? null),
            'price' => $price,
            'quantity' => $quantity,
            'subtotal' => $itemSubtotal,
            'size' => $details['size'] ?? 'One Size',
            'image' => $this->getProductImageUrl($product),
            'sku' => $product->sku,
            'weight' => $product->weight ?? 250, // Default weight 250g
            'categories' => $categories // FIXED: Safe category handling
        ];

        $cartItems[] = $cartItem;
        $subtotal += $itemSubtotal;
        $totalWeight += ($product->weight ?? 250) * $quantity;
        $totalQuantity += $quantity;
    }

    return [
        'items' => $cartItems,
        'subtotal' => $subtotal,
        'total_weight' => $totalWeight,
        'total_quantity' => $totalQuantity,
        'item_count' => count($cartItems),
        'formatted_subtotal' => 'Rp ' . number_format($subtotal, 0, ',', '.')
    ];
}

    /**
     * Get cart count for header badge
     */
    public function getCartCount(): int
    {
        $cart = Session::get('cart', []);
        $count = 0;

        foreach ($cart as $details) {
            $productId = $details['product_id'] ?? null;
            
            if (!$productId) {
                continue;
            }

            $product = Product::find($productId);
            
            if ($product && $product->is_active && $product->stock_quantity > 0) {
                $count += $details['quantity'] ?? 1;
            }
        }

        return $count;
    }

    /**
     * Clean product name for display
     */
    private function getCleanProductName($originalName, $size = null): string
    {
        $cleanName = $originalName;
        
        // Remove size patterns from name
        $cleanName = preg_replace('/\s*-\s*Size\s+[A-Z0-9.]+\s*$/i', '', $cleanName);
        $cleanName = preg_replace('/\s*Size\s+[A-Z0-9.]+\s*$/i', '', $cleanName);
        $cleanName = preg_replace('/\s*-\s*(XS|S|M|L|XL|XXL|XXXL|[0-9]+|[0-9]+\.[0-9]+)\s*$/i', '', $cleanName);
        
        return trim($cleanName, ' -');
    }

    /**
     * Get product image URL with fallback
     */
    private function getProductImageUrl($product): string
    {
        if (empty($product->featured_image)) {
            return asset('images/default-product.jpg');
        }

        $imagePath = $product->featured_image;
        
        // Handle different image path formats
        if (filter_var($imagePath, FILTER_VALIDATE_URL)) {
            return $imagePath;
        } elseif (str_starts_with($imagePath, '/storage/')) {
            return config('app.url') . $imagePath;
        } elseif (str_starts_with($imagePath, 'products/')) {
            return config('app.url') . '/storage/' . $imagePath;
        } elseif (str_starts_with($imagePath, 'assets/') || str_starts_with($imagePath, 'images/')) {
            return asset($imagePath);
        } else {
            return config('app.url') . '/storage/products/' . $imagePath;
        }
    }

    /**
     * Add item to cart
     */
    public function addToCart($productId, $quantity = 1, $size = null): array
    {
        try {
            $product = Product::find($productId);
            
            if (!$product || !$product->is_active) {
                return [
                    'success' => false,
                    'message' => 'Product not found or inactive'
                ];
            }

            if ($product->stock_quantity < $quantity) {
                return [
                    'success' => false,
                    'message' => 'Insufficient stock. Available: ' . $product->stock_quantity
                ];
            }

            $cart = Session::get('cart', []);
            
            // Create cart key
            $cartKey = $productId . '_' . ($size ?: 'default');
            
            if (isset($cart[$cartKey])) {
                // Update existing item
                $newQuantity = $cart[$cartKey]['quantity'] + $quantity;
                
                if ($newQuantity > $product->stock_quantity) {
                    return [
                        'success' => false,
                        'message' => 'Cannot add more items. Stock limit: ' . $product->stock_quantity
                    ];
                }
                
                $cart[$cartKey]['quantity'] = $newQuantity;
            } else {
                // Add new item
                $cart[$cartKey] = [
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'size' => $size,
                    'added_at' => now()->toISOString()
                ];
            }

            Session::put('cart', $cart);
            
            Log::info('Item added to cart', [
                'product_id' => $productId,
                'quantity' => $quantity,
                'size' => $size,
                'cart_key' => $cartKey
            ]);

            return [
                'success' => true,
                'message' => 'Item added to cart',
                'cart_count' => $this->getCartCount()
            ];

        } catch (\Exception $e) {
            Log::error('Error adding to cart', [
                'product_id' => $productId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to add item to cart'
            ];
        }
    }

    /**
     * Update cart item quantity
     */
    public function updateCartItem($identifier, $quantity): array
    {
        try {
            $cart = Session::get('cart', []);
            
            if (!isset($cart[$identifier])) {
                return [
                    'success' => false,
                    'message' => 'Cart item not found'
                ];
            }

            $productId = $cart[$identifier]['product_id'];
            $product = Product::find($productId);
            
            if (!$product || !$product->is_active) {
                // Remove invalid item
                unset($cart[$identifier]);
                Session::put('cart', $cart);
                
                return [
                    'success' => false,
                    'message' => 'Product no longer available. Item removed from cart.'
                ];
            }

            if ($quantity <= 0) {
                // Remove item if quantity is 0 or negative
                unset($cart[$identifier]);
            } else {
                if ($quantity > $product->stock_quantity) {
                    return [
                        'success' => false,
                        'message' => 'Insufficient stock. Available: ' . $product->stock_quantity
                    ];
                }
                
                $cart[$identifier]['quantity'] = $quantity;
            }

            Session::put('cart', $cart);
            
            // Clear applied coupon since cart changed
            \App\Http\Controllers\Frontend\CouponController::clearCouponOnCartChange();

            return [
                'success' => true,
                'message' => 'Cart updated',
                'cart_count' => $this->getCartCount()
            ];

        } catch (\Exception $e) {
            Log::error('Error updating cart item', [
                'identifier' => $identifier,
                'quantity' => $quantity,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to update cart'
            ];
        }
    }

    /**
     * Remove item from cart
     */
    public function removeFromCart($identifier): array
    {
        try {
            $cart = Session::get('cart', []);
            
            if (!isset($cart[$identifier])) {
                return [
                    'success' => false,
                    'message' => 'Cart item not found'
                ];
            }

            unset($cart[$identifier]);
            Session::put('cart', $cart);
            
            // Clear applied coupon since cart changed
            \App\Http\Controllers\Frontend\CouponController::clearCouponOnCartChange();

            return [
                'success' => true,
                'message' => 'Item removed from cart',
                'cart_count' => $this->getCartCount()
            ];

        } catch (\Exception $e) {
            Log::error('Error removing from cart', [
                'identifier' => $identifier,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to remove item'
            ];
        }
    }

    /**
     * Clear entire cart
     */
    public function clearCart(): array
    {
        try {
            Session::forget('cart');
            Session::forget('applied_coupon');
            Session::forget('shipping_cost');
            
            return [
                'success' => true,
                'message' => 'Cart cleared',
                'cart_count' => 0
            ];

        } catch (\Exception $e) {
            Log::error('Error clearing cart', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to clear cart'
            ];
        }
    }

    /**
     * Validate cart items (remove inactive/out of stock items)
     */
    public function validateCart(): array
    {
        $cart = Session::get('cart', []);
        $removedItems = [];
        $updatedCart = [];

        foreach ($cart as $cartKey => $details) {
            $productId = $details['product_id'] ?? null;
            
            if (!$productId) {
                $removedItems[] = 'Invalid item';
                continue;
            }

            $product = Product::find($productId);
            
            if (!$product || !$product->is_active) {
                $removedItems[] = $product ? $product->name : 'Unknown product';
                continue;
            }

            if ($product->stock_quantity <= 0) {
                $removedItems[] = $product->name . ' (out of stock)';
                continue;
            }

            // Check if quantity exceeds stock
            $quantity = $details['quantity'] ?? 1;
            if ($quantity > $product->stock_quantity) {
                $details['quantity'] = $product->stock_quantity;
                $removedItems[] = $product->name . ' (quantity reduced to ' . $product->stock_quantity . ')';
            }

            $updatedCart[$cartKey] = $details;
        }

        Session::put('cart', $updatedCart);

        if (!empty($removedItems)) {
            // Clear applied coupon since cart changed
            \App\Http\Controllers\Frontend\CouponController::clearCouponOnCartChange();
        }

        return [
            'removed_items' => $removedItems,
            'cart_count' => $this->getCartCount()
        ];
    }

    /**
     * Sync cart for authenticated users
     */
    public function syncCartForUser($userId): void
    {
        // This method can be used to sync session cart with database cart
        // for authenticated users if you implement persistent cart storage
        
        Log::info('Cart sync requested for user', ['user_id' => $userId]);
        
        // Implementation depends on your business requirements
        // You might want to:
        // 1. Save session cart to database
        // 2. Merge database cart with session cart
        // 3. Replace session cart with database cart
    }

    /**
     * Get cart summary for order creation
     */
    public function getCartSummary(): array
    {
        $cartData = $this->getCartData();
        
        return [
            'items' => collect($cartData['items'])->map(function ($item) {
                return [
                    'product_id' => $item['product_id'],
                    'product_name' => $item['name'],
                    'product_sku' => $item['sku'],
                    'product_price' => $item['price'],
                    'quantity' => $item['quantity'],
                    'size' => $item['size'],
                    'total_price' => $item['subtotal']
                ];
            })->toArray(),
            'subtotal' => $cartData['subtotal'],
            'total_weight' => $cartData['total_weight'],
            'total_quantity' => $cartData['total_quantity']
        ];
    }
}