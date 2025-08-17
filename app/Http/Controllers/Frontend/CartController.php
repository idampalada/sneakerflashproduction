<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class CartController extends Controller
{
    public function index()
    {
        $cartItems = $this->getCartItems();
        $total = $this->calculateTotal($cartItems);
        
        return view('frontend.cart.index', compact('cartItems', 'total'));
    }

    // ⭐ ENHANCED: Method signature untuk handle size selection
    public function add(Request $request, $productId = null)
    {
        try {
            // Jika productId tidak ada di URL parameter, ambil dari request body
            if (!$productId) {
                $productId = $request->input('product_id');
            }

            if (!$productId) {
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Product ID is required.'
                    ], 400);
                }
                return back()->with('error', 'Product ID is required.');
            }

            // ⭐ ENHANCED: Validate request with size support
            $request->validate([
                'quantity' => 'nullable|integer|min:1|max:10',
                'size' => 'nullable|string|max:50'
            ]);
            
            $quantity = $request->quantity ?? 1;
            $selectedSize = $request->size;
            
            // Get product with safety check
            $product = Product::find($productId);
            
            if (!$product || !$product->is_active) {
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Product not found or not available.'
                    ], 404);
                }
                return back()->with('error', 'Product not found or not available.');
            }
            
            // Check stock availability
            $currentStock = $product->stock_quantity ?? 0;
            if ($currentStock < $quantity) {
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Insufficient stock. Available: ' . $currentStock
                    ], 400);
                }
                return back()->with('error', 'Insufficient stock. Available: ' . $currentStock);
            }

            // Get cart from session
            $cart = Session::get('cart', []);
            
            // ⭐ ENHANCED: Create unique cart key for size variants
            $cartKey = $this->getCartKey($productId, $selectedSize);
            
            if (isset($cart[$cartKey])) {
                $newQuantity = $cart[$cartKey]['quantity'] + $quantity;
                
                if ($newQuantity > $currentStock) {
                    if ($request->ajax()) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Cannot add more items. Stock limit reached.'
                        ], 400);
                    }
                    return back()->with('error', 'Cannot add more items. Stock limit reached.');
                }
                
                $cart[$cartKey]['quantity'] = $newQuantity;
                $cart[$cartKey]['stock'] = $currentStock;
            } else {
                // ⭐ ENHANCED: Add new product to cart with size information
                $cart[$cartKey] = [
                    'cart_key' => $cartKey, // ⭐ ADDED: Store cart key for reference
                    'product_id' => $product->id,
                    'name' => $product->name ?? 'Unknown Product',
                    'price' => $product->sale_price ?: ($product->price ?? 0),
                    'original_price' => $product->price ?? 0,
                    'quantity' => $quantity,
                    'image' => $product->images[0] ?? '/images/default-product.jpg',
                    'slug' => $product->slug ?? '',
                    'stock' => $currentStock,
                    'brand' => $product->brand ?? 'Unknown Brand',
                    'category' => $product->category->name ?? 'Unknown Category',
                    'sku' => $product->sku ?? '',
                    'sku_parent' => $product->sku_parent ?? '',
                    // ⭐ ENHANCED: Size information
                    'size' => $selectedSize ?: ($product->available_sizes ?? 'One Size'),
                    'color' => $request->color ?? 'Default',
                    'weight' => $product->weight ?? 500,
                    // Product options for detailed tracking
                    'product_options' => [
                        'size' => $selectedSize ?: ($product->available_sizes ?? 'One Size'),
                        'color' => $request->color ?? 'Default',
                        'material' => $product->material ?? null,
                        'variant' => $request->variant ?? null
                    ]
                ];
            }

            Session::put('cart', $cart);
            
            Log::info('Product added to cart successfully', [
                'product_id' => $productId,
                'size' => $selectedSize,
                'quantity' => $quantity,
                'cart_key' => $cartKey,
                'cart_count' => $this->getCartItemCount()
            ]);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $selectedSize ? 
                        "Product (Size: {$selectedSize}) added to cart successfully!" :
                        'Product added to cart successfully!',
                    'cart_count' => $this->getCartItemCount()
                ]);
            }
            
            return back()->with('success', 'Product added to cart successfully!');
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Cart add validation error', ['errors' => $e->errors()]);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors()
                ], 422);
            }
            
            return back()->withInput()->withErrors($e->errors());
            
        } catch (\Exception $e) {
            Log::error('Cart add error: ' . $e->getMessage(), [
                'product_id' => $productId ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Something went wrong. Please try again.'
                ], 500);
            }
            
            return back()->with('error', 'Something went wrong. Please try again.');
        }
    }

    // ⭐ FIXED: Update method to handle both product ID and cart key
    public function update(Request $request, $identifier)
    {
        try {
            $request->validate([
                'quantity' => 'required|integer|min:1'
            ]);

            $cart = Session::get('cart', []);
            
            // ⭐ NEW: Try to find cart item by product ID or cart key
            $cartKey = $this->findCartKey($cart, $identifier);
            
            if (!$cartKey || !isset($cart[$cartKey])) {
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Product not found in cart'
                    ], 404);
                }
                return back()->with('error', 'Product not found in cart');
            }

            // Get fresh product data
            $productId = $cart[$cartKey]['product_id'];
            $product = Product::find($productId);
            
            if (!$product || !$product->is_active) {
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Product is no longer available'
                    ], 404);
                }
                return back()->with('error', 'Product is no longer available');
            }
            
            // Check current stock
            $currentStock = $product->stock_quantity ?? 0;
            
            if ($currentStock <= 0) {
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Product is out of stock'
                    ], 400);
                }
                return back()->with('error', 'Product is out of stock');
            }
            
            if ($request->quantity > $currentStock) {
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => "Quantity exceeds available stock ({$currentStock} left)"
                    ], 400);
                }
                return back()->with('error', "Quantity exceeds available stock ({$currentStock} left)");
            }

            // Update cart with fresh data
            $cart[$cartKey]['quantity'] = $request->quantity;
            $cart[$cartKey]['price'] = $product->sale_price ?: $product->price;
            $cart[$cartKey]['original_price'] = $product->price;
            $cart[$cartKey]['stock'] = $currentStock;
            
            Session::put('cart', $cart);
            
            if ($request->ajax()) {
                $cartItems = $this->getCartItems();
                $total = $this->calculateTotal($cartItems);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Cart updated successfully!',
                    'cart_count' => $this->getCartItemCount(),
                    'subtotal' => ($product->sale_price ?: $product->price) * $request->quantity,
                    'total' => $total,
                    'stock' => $currentStock
                ]);
            }
            
            return back()->with('success', 'Cart updated successfully!');
            
        } catch (\Exception $e) {
            Log::error('Cart update error: ' . $e->getMessage());
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Something went wrong. Please try again.'
                ], 500);
            }
            
            return back()->with('error', 'Something went wrong. Please try again.');
        }
    }

    // ⭐ FIXED: Remove method to handle both product ID and cart key
    public function remove($identifier)
    {
        try {
            $cart = Session::get('cart', []);
            
            // ⭐ NEW: Try to find cart item by product ID or cart key
            $cartKey = $this->findCartKey($cart, $identifier);
            
            if ($cartKey && isset($cart[$cartKey])) {
                $productName = $cart[$cartKey]['name'];
                $productSize = $cart[$cartKey]['size'] ?? null;
                
                unset($cart[$cartKey]);
                Session::put('cart', $cart);
                
                $message = $productSize && $productSize !== 'One Size' ? 
                    "'{$productName}' (Size: {$productSize}) removed from cart!" :
                    "'{$productName}' removed from cart!";
                
                if (request()->ajax()) {
                    return response()->json([
                        'success' => true,
                        'message' => $message,
                        'cart_count' => $this->getCartItemCount()
                    ]);
                }
                
                return back()->with('success', $message);
            }
            
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Item not found in cart'
                ], 404);
            }
            
            return back()->with('error', 'Item not found in cart');
            
        } catch (\Exception $e) {
            Log::error('Cart remove error: ' . $e->getMessage());
            
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Something went wrong. Please try again.'
                ], 500);
            }
            
            return back()->with('error', 'Something went wrong. Please try again.');
        }
    }

    public function clear()
    {
        try {
            Session::forget('cart');
            
            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Cart cleared successfully!',
                    'cart_count' => 0
                ]);
            }
            
            return redirect()->route('cart.index')->with('success', 'Cart cleared successfully!');
            
        } catch (\Exception $e) {
            Log::error('Cart clear error: ' . $e->getMessage());
            
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Something went wrong. Please try again.'
                ], 500);
            }
            
            return back()->with('error', 'Something went wrong. Please try again.');
        }
    }

    // ⭐ NEW: Generate unique cart key for product variants
    private function getCartKey($productId, $size = null)
    {
        if ($size && $size !== 'One Size') {
            return $productId . '_' . str_replace([' ', '.'], '_', strtolower($size));
        }
        
        return (string) $productId;
    }

    // ⭐ NEW: Find cart key by product ID or existing cart key
    private function findCartKey($cart, $identifier)
    {
        // First, try exact cart key match
        if (isset($cart[$identifier])) {
            return $identifier;
        }
        
        // Then, try to find by product ID
        foreach ($cart as $cartKey => $item) {
            if (isset($item['product_id']) && $item['product_id'] == $identifier) {
                return $cartKey;
            }
        }
        
        // Finally, try partial cart key match (for backward compatibility)
        foreach ($cart as $cartKey => $item) {
            if (str_starts_with($cartKey, $identifier . '_')) {
                return $cartKey;
            }
        }
        
        return null;
    }

    // ⭐ ENHANCED: Helper methods with size support
    private function getCartItems()
    {
        $cart = Session::get('cart', []);
        $cartItems = collect();
        
        foreach ($cart as $cartKey => $details) {
            // Get fresh product data for accurate stock
            $productId = $details['product_id'] ?? null;
            $product = null;
            
            if ($productId) {
                $product = Product::find($productId);
            }
            
            $currentStock = $product ? ($product->stock_quantity ?? 0) : 0;
            
            // Remove items that are no longer available
            if (!$product || !$product->is_active) {
                continue;
            }
            
            // ⭐ SAFE: Ensure all required keys exist with defaults and proper handling
            $itemName = $details['name'] ?? ($product->name ?? 'Unknown Product');
            $itemPrice = $details['price'] ?? ($product->sale_price ?: ($product->price ?? 0));
            $itemOriginalPrice = $details['original_price'] ?? ($product->price ?? 0);
            $itemQuantity = min($details['quantity'] ?? 1, $currentStock);
            $itemImage = $details['image'] ?? ($product->images[0] ?? '/images/default-product.jpg');
            $itemSlug = $details['slug'] ?? ($product->slug ?? '');
            $itemBrand = $details['brand'] ?? ($product->brand ?? 'Unknown Brand');
            $itemCategory = $details['category'] ?? ($product->category->name ?? 'Unknown Category');
            $itemSku = $details['sku'] ?? ($product->sku ?? '');
            $itemSkuParent = $details['sku_parent'] ?? ($product->sku_parent ?? '');
            
            // ⭐ SAFE: Size handling with proper fallbacks
            $itemSize = 'One Size'; // Default fallback
            if (isset($details['size']) && !empty($details['size'])) {
                if (is_array($details['size'])) {
                    $itemSize = $details['size'][0] ?? 'One Size';
                } else {
                    $itemSize = (string) $details['size'];
                }
            } elseif (isset($details['product_options']['size'])) {
                $itemSize = $details['product_options']['size'] ?? 'One Size';
            } elseif ($product && !empty($product->available_sizes)) {
                if (is_array($product->available_sizes)) {
                    $itemSize = $product->available_sizes[0] ?? 'One Size';
                } else {
                    $itemSize = (string) $product->available_sizes;
                }
            }
            
            // ⭐ SAFE: Product options handling
            $productOptions = $details['product_options'] ?? [];
            if (!is_array($productOptions)) {
                $productOptions = [
                    'size' => $itemSize,
                    'color' => $details['color'] ?? 'Default'
                ];
            }
            
            $cartItems->push([
                'cart_key' => $cartKey,
                'id' => $productId,
                'name' => $itemName,
                'price' => $itemPrice,
                'original_price' => $itemOriginalPrice,
                'quantity' => $itemQuantity,
                'image' => $itemImage,
                'slug' => $itemSlug,
                'brand' => $itemBrand,
                'category' => $itemCategory,
                'stock' => $currentStock,
                'sku' => $itemSku,
                'sku_parent' => $itemSkuParent,
                'size' => $itemSize,
                'color' => $details['color'] ?? 'Default',
                'weight' => $details['weight'] ?? ($product->weight ?? 500),
                'product_options' => $productOptions,
                'subtotal' => $itemPrice * $itemQuantity
            ]);
        }
        
        return $cartItems;
    }

    private function calculateTotal($cartItems)
    {
        return $cartItems->sum('subtotal');
    }

    private function getCartItemCount()
    {
        $cart = Session::get('cart', []);
        return array_sum(array_column($cart, 'quantity'));
    }

    // API method untuk AJAX calls
    public function getCartCount()
    {
        $count = $this->getCartItemCount();
        
        return response()->json(['count' => $count]);
    }

    // Get cart data for API calls
    public function getCartData()
    {
        $cartItems = $this->getCartItems();
        $total = $this->calculateTotal($cartItems);
        $count = $this->getCartItemCount();
        
        return response()->json([
            'items' => $cartItems,
            'total' => $total,
            'count' => $count,
            'formatted_total' => 'Rp ' . number_format($total, 0, ',', '.')
        ]);
    }

    // ⭐ ENHANCED: Sync cart with size variant support
    public function syncCart()
    {
        try {
            $cart = Session::get('cart', []);
            $updated = false;
            $removedItems = [];
            
            foreach ($cart as $cartKey => $details) {
                $productId = $details['product_id'] ?? null;
                
                if (!$productId) {
                    // Remove invalid cart items
                    $removedItems[] = $details['name'] ?? 'Unknown Product';
                    unset($cart[$cartKey]);
                    $updated = true;
                    continue;
                }
                
                $product = Product::find($productId);
                
                if (!$product || !$product->is_active) {
                    // Remove inactive products
                    $removedItems[] = $details['name'] ?? 'Unknown Product';
                    unset($cart[$cartKey]);
                    $updated = true;
                    continue;
                }
                
                $currentStock = $product->stock_quantity ?? 0;
                
                // Update missing keys
                if (!isset($cart[$cartKey]['size'])) {
                    $cart[$cartKey]['size'] = $product->available_sizes ?? 'One Size';
                    $updated = true;
                }
                
                if (!isset($cart[$cartKey]['sku'])) {
                    $cart[$cartKey]['sku'] = $product->sku ?? '';
                    $updated = true;
                }
                
                if (!isset($cart[$cartKey]['sku_parent'])) {
                    $cart[$cartKey]['sku_parent'] = $product->sku_parent ?? '';
                    $updated = true;
                }
                
                if (!isset($cart[$cartKey]['product_id'])) {
                    $cart[$cartKey]['product_id'] = $product->id;
                    $updated = true;
                }
                
                if (!isset($cart[$cartKey]['cart_key'])) {
                    $cart[$cartKey]['cart_key'] = $cartKey;
                    $updated = true;
                }
                
                // Update price if changed
                $currentPrice = $product->sale_price ?: $product->price;
                if ($cart[$cartKey]['price'] != $currentPrice) {
                    $cart[$cartKey]['price'] = $currentPrice;
                    $cart[$cartKey]['original_price'] = $product->price;
                    $updated = true;
                }
                
                // Update stock
                $cart[$cartKey]['stock'] = $currentStock;
                
                // Adjust quantity if exceeds stock
                if ($cart[$cartKey]['quantity'] > $currentStock) {
                    $cart[$cartKey]['quantity'] = $currentStock;
                    $updated = true;
                }
                
                // Remove items with zero stock
                if ($currentStock <= 0) {
                    $itemName = $details['name'] ?? 'Unknown Product';
                    $size = $details['size'] ?? null;
                    
                    if ($size && $size !== 'One Size') {
                        $itemName .= " (Size: {$size})";
                    }
                    
                    $removedItems[] = $itemName;
                    unset($cart[$cartKey]);
                    $updated = true;
                }
            }
            
            if ($updated) {
                Session::put('cart', $cart);
            }
            
            $message = 'Cart synchronized successfully.';
            if (!empty($removedItems)) {
                $message .= ' Removed unavailable items: ' . implode(', ', $removedItems);
            }
            
            return response()->json([
                'success' => true,
                'message' => $message,
                'updated' => $updated,
                'removed_items' => $removedItems,
                'cart_count' => $this->getCartItemCount()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Cart sync error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to sync cart.'
            ], 500);
        }
    }
}