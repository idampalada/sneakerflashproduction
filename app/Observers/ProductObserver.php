<?php

namespace App\Observers;

use App\Models\Product;

class ProductObserver
{
    /**
     * Handle the Product "created" event.
     */
    public function created(Product $product): void
    {
        // Auto set published_at if not set
        if (!$product->published_at && $product->is_active) {
            $product->published_at = now();
            $product->saveQuietly();
        }
    }

    /**
     * Handle the Product "updated" event.
     */
    public function updated(Product $product): void
    {
        // Auto deactivate if stock is 0
        if ($product->isDirty('stock_quantity') && $product->stock_quantity <= 0) {
            $product->is_active = false;
            $product->saveQuietly();
        }

        // Auto activate if stock is restored
        if ($product->isDirty('stock_quantity') && 
            $product->stock_quantity > 0 && 
            !$product->is_active) {
            $product->is_active = true;
            $product->saveQuietly();
        }

        // Low stock alert
        if ($product->isDirty('stock_quantity') && 
            $product->stock_quantity > 0 && 
            $product->stock_quantity <= 5) {
            // Trigger low stock notification
            // You can dispatch an event here
            // event(new LowStockAlert($product));
        }
    }

    /**
     * Handle the Product "deleted" event.
     */
    public function deleted(Product $product): void
    {
        // Auto remove from carts when product is deleted
        $product->cartItems()->delete();
        $product->wishlists()->delete();
    }
}