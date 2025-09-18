<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ShoppingCart;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class CartService
{
    /**
     * Add item to cart (both session and database)
     */
    public function addToCart($productId, $quantity = 1, $size = null): array
    {
        try {
            $product = Product::find($productId);
            
            if (!$product || !$product->is_active) {
                return [
                    'success' => false,
                    'message' => 'Product not found or not available'
                ];
            }

            if ($product->stock_quantity < $quantity) {
                return [
                    'success' => false,
                    'message' => 'Insufficient stock. Available: ' . $product->stock_quantity
                ];
            }

            // Add to session cart
            $this->addToSessionCart($productId, $quantity, $size);
            
            // Add to database cart if user is authenticated
            if (Auth::check()) {
                $this->addToDatabaseCart(Auth::id(), $productId, $quantity, $size);
            }

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
     * Add to session cart
     */
    private function addToSessionCart($productId, $quantity, $size): void
    {
        $cart = Session::get('cart', []);
        $cartKey = $productId . '_' . ($size ?: 'default');
        
        if (isset($cart[$cartKey])) {
            $cart[$cartKey]['quantity'] += $quantity;
        } else {
            $cart[$cartKey] = [
                'product_id' => $productId,
                'quantity' => $quantity,
                'size' => $size,
                'added_at' => now()->toISOString()
            ];
        }

        Session::put('cart', $cart);
    }

    /**
     * Add to database cart
     */
    private function addToDatabaseCart($userId, $productId, $quantity, $size): void
    {
        $productOptions = $size ? ['size' => $size] : null;
        
        $existingItem = ShoppingCart::where('user_id', $userId)
            ->where('product_id', $productId)
            ->where('product_options', $productOptions)
            ->first();

        if ($existingItem) {
            $existingItem->update([
                'quantity' => $existingItem->quantity + $quantity
            ]);
        } else {
            ShoppingCart::create([
                'user_id' => $userId,
                'product_id' => $productId,
                'quantity' => $quantity,
                'product_options' => $productOptions
            ]);
        }
    }

    /**
     * Sync cart on login - merge database cart with session cart
     */
    public function syncCartOnLogin($userId): void
    {
        try {
            // Get session cart
            $sessionCart = Session::get('cart', []);
            
            // Get database cart
            $databaseCartItems = ShoppingCart::where('user_id', $userId)
                ->with('product')
                ->get();

            // Merge carts - prioritize session cart (more recent)
            foreach ($sessionCart as $cartKey => $sessionItem) {
                $productId = $sessionItem['product_id'];
                $size = $sessionItem['size'];
                $quantity = $sessionItem['quantity'];
                
                $productOptions = $size ? ['size' => $size] : null;
                
                // Find if item exists in database
                $dbItem = $databaseCartItems->where('product_id', $productId)
                    ->where('product_options', $productOptions)
                    ->first();

                if ($dbItem) {
                    // Update database with session quantity (session is more recent)
                    $dbItem->update(['quantity' => $quantity]);
                } else {
                    // Add new item to database
                    ShoppingCart::create([
                        'user_id' => $userId,
                        'product_id' => $productId,
                        'quantity' => $quantity,
                        'product_options' => $productOptions
                    ]);
                }
            }

            // Update session cart with complete merged cart
            $this->loadDatabaseCartToSession($userId);
            
            Log::info('Cart synced successfully for user', ['user_id' => $userId]);

        } catch (\Exception $e) {
            Log::error('Cart sync failed', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Load database cart to session
     */
    public function loadDatabaseCartToSession($userId): void
    {
        $databaseCartItems = ShoppingCart::where('user_id', $userId)
            ->with('product')
            ->get();

        $sessionCart = [];
        
        foreach ($databaseCartItems as $item) {
            if ($item->product && $item->product->is_active) {
                $size = $item->product_options['size'] ?? null;
                $cartKey = $item->product_id . '_' . ($size ?: 'default');
                
                $sessionCart[$cartKey] = [
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'size' => $size,
                    'added_at' => $item->created_at->toISOString()
                ];
            }
        }

        Session::put('cart', $sessionCart);
    }

    /**
     * Save session cart to database on logout
     */
    public function saveSessionCartToDatabase($userId): void
    {
        try {
            $sessionCart = Session::get('cart', []);
            
            foreach ($sessionCart as $cartKey => $item) {
                $productId = $item['product_id'];
                $quantity = $item['quantity'];
                $size = $item['size'] ?? null;
                
                $productOptions = $size ? ['size' => $size] : null;
                
                $existingItem = ShoppingCart::where('user_id', $userId)
                    ->where('product_id', $productId)
                    ->where('product_options', $productOptions)
                    ->first();

                if ($existingItem) {
                    $existingItem->update(['quantity' => $quantity]);
                } else {
                    ShoppingCart::create([
                        'user_id' => $userId,
                        'product_id' => $productId,
                        'quantity' => $quantity,
                        'product_options' => $productOptions
                    ]);
                }
            }

            Log::info('Session cart saved to database', ['user_id' => $userId]);

        } catch (\Exception $e) {
            Log::error('Failed to save session cart to database', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Update cart item quantity
     */
    public function updateCartItem($identifier, $quantity): array
    {
        try {
            // Update session cart
            $cart = Session::get('cart', []);
            
            if (!isset($cart[$identifier])) {
                return [
                    'success' => false,
                    'message' => 'Cart item not found'
                ];
            }

            $productId = $cart[$identifier]['product_id'];
            $size = $cart[$identifier]['size'];
            $product = Product::find($productId);
            
            if (!$product || !$product->is_active) {
                // Remove invalid item
                unset($cart[$identifier]);
                Session::put('cart', $cart);
                
                if (Auth::check()) {
                    $this->removeDatabaseCartItem(Auth::id(), $productId, $size);
                }
                
                return [
                    'success' => false,
                    'message' => 'Product no longer available. Item removed from cart.'
                ];
            }

            if ($quantity > $product->stock_quantity) {
                return [
                    'success' => false,
                    'message' => 'Insufficient stock. Available: ' . $product->stock_quantity
                ];
            }

            // Update session
            $cart[$identifier]['quantity'] = $quantity;
            Session::put('cart', $cart);

            // Update database if user is authenticated
            if (Auth::check()) {
                $this->updateDatabaseCartItem(Auth::id(), $productId, $size, $quantity);
            }

            return [
                'success' => true,
                'message' => 'Cart updated',
                'cart_count' => $this->getCartCount()
            ];

        } catch (\Exception $e) {
            Log::error('Error updating cart item', [
                'identifier' => $identifier,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to update cart'
            ];
        }
    }

    /**
     * Update database cart item
     */
    private function updateDatabaseCartItem($userId, $productId, $size, $quantity): void
    {
        $productOptions = $size ? ['size' => $size] : null;
        
        $item = ShoppingCart::where('user_id', $userId)
            ->where('product_id', $productId)
            ->where('product_options', $productOptions)
            ->first();

        if ($item) {
            $item->update(['quantity' => $quantity]);
        }
    }

    /**
     * Remove database cart item
     */
    private function removeDatabaseCartItem($userId, $productId, $size): void
    {
        $productOptions = $size ? ['size' => $size] : null;
        
        ShoppingCart::where('user_id', $userId)
            ->where('product_id', $productId)
            ->where('product_options', $productOptions)
            ->delete();
    }

    /**
     * Remove cart item
     */
    public function removeCartItem($identifier): array
    {
        try {
            $cart = Session::get('cart', []);
            
            if (isset($cart[$identifier])) {
                $productId = $cart[$identifier]['product_id'];
                $size = $cart[$identifier]['size'];
                
                unset($cart[$identifier]);
                Session::put('cart', $cart);

                // Remove from database if user is authenticated
                if (Auth::check()) {
                    $this->removeDatabaseCartItem(Auth::id(), $productId, $size);
                }
            }

            return [
                'success' => true,
                'message' => 'Item removed from cart',
                'cart_count' => $this->getCartCount()
            ];

        } catch (\Exception $e) {
            Log::error('Error removing cart item', [
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
            
            // Clear database cart if user is authenticated
            if (Auth::check()) {
                ShoppingCart::where('user_id', Auth::id())->delete();
            }
            
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
     * Get cart count
     */
    public function getCartCount(): int
    {
        $cart = Session::get('cart', []);
        return array_sum(array_column($cart, 'quantity'));
    }

    /**
     * Get cart data with product details
     */
    public function getCartData(): array
    {
        $cart = Session::get('cart', []);
        $items = [];
        $subtotal = 0;
        $totalWeight = 0;
        $totalQuantity = 0;

        foreach ($cart as $cartKey => $details) {
            $product = Product::find($details['product_id']);
            
            if (!$product || !$product->is_active) {
                continue;
            }

            $price = $product->sale_price ?? $product->price;
            $itemSubtotal = $price * $details['quantity'];
            $itemWeight = ($product->weight ?? 0) * $details['quantity'];

            $items[] = [
                'cart_key' => $cartKey,
                'product_id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'sku' => $product->sku,
                'price' => $price,
                'original_price' => $product->price,
                'quantity' => $details['quantity'],
                'size' => $details['size'],
                'subtotal' => $itemSubtotal,
                'weight' => $itemWeight,
                'stock_quantity' => $product->stock_quantity,
                'image' => $product->images[0] ?? null,
                'added_at' => $details['added_at'] ?? null
            ];

            $subtotal += $itemSubtotal;
            $totalWeight += $itemWeight;
            $totalQuantity += $details['quantity'];
        }

        return [
            'items' => $items,
            'subtotal' => $subtotal,
            'total_weight' => $totalWeight,
            'total_quantity' => $totalQuantity,
            'coupon_discount' => Session::get('coupon_discount', 0),
            'shipping_cost' => Session::get('shipping_cost', 0),
            'final_total' => $subtotal - Session::get('coupon_discount', 0) + Session::get('shipping_cost', 0)
        ];
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
                
                // Remove from database too
                if (Auth::check()) {
                    $this->removeDatabaseCartItem(Auth::id(), $productId, $details['size'] ?? null);
                }
                continue;
            }

            if ($product->stock_quantity <= 0) {
                $removedItems[] = $product->name . ' (out of stock)';
                
                // Remove from database too
                if (Auth::check()) {
                    $this->removeDatabaseCartItem(Auth::id(), $productId, $details['size'] ?? null);
                }
                continue;
            }

            // Check if quantity exceeds stock
            $quantity = $details['quantity'] ?? 1;
            if ($quantity > $product->stock_quantity) {
                $details['quantity'] = $product->stock_quantity;
                $removedItems[] = $product->name . ' (quantity reduced to ' . $product->stock_quantity . ')';
                
                // Update database quantity too
                if (Auth::check()) {
                    $this->updateDatabaseCartItem(Auth::id(), $productId, $details['size'] ?? null, $product->stock_quantity);
                }
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