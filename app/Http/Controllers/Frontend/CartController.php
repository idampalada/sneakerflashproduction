<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ShoppingCart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    public function index()
    {
        // Sync cart on page load if user is authenticated
        if (Auth::check()) {
            $this->syncCartOnPageLoad();
        }
        
        $cartItems = $this->getCartItems();
        $total = $this->calculateTotal($cartItems);
        
        return view('frontend.cart.index', compact('cartItems', 'total'));
    }

    public function add(Request $request, $productId = null)
    {
        try {
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

            $request->validate([
                'quantity' => 'nullable|integer|min:1|max:10',
                'size' => 'nullable|string|max:50'
            ]);
            
            $quantity = $request->quantity ?? 1;
            $selectedSize = $request->size;
            
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

            // Add to session cart
            $this->addToSessionCart($productId, $quantity, $selectedSize, $product);
            
            // Add to database cart if user is authenticated
            if (Auth::check()) {
                $this->addToDatabaseCart(Auth::id(), $productId, $quantity, $selectedSize);
            }
            
            Log::info('Product added to cart successfully', [
                'product_id' => $productId,
                'size' => $selectedSize,
                'quantity' => $quantity,
                'user_id' => Auth::id(),
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

    public function update(Request $request, $identifier)
    {
        try {
            $request->validate([
                'quantity' => 'required|integer|min:1'
            ]);

            $cart = Session::get('cart', []);
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

            // Update session cart
            $cart[$cartKey]['quantity'] = $request->quantity;
            $cart[$cartKey]['price'] = $product->sale_price ?: $product->price;
            $cart[$cartKey]['original_price'] = $product->price;
            $cart[$cartKey]['stock'] = $currentStock;
            Session::put('cart', $cart);
            
            // Update database cart if user is authenticated
            if (Auth::check()) {
                $this->updateDatabaseCartItem(Auth::id(), $productId, $cart[$cartKey]['size'] ?? null, $request->quantity);
            }
            
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

    public function remove($identifier)
    {
        try {
            $cart = Session::get('cart', []);
            $cartKey = $this->findCartKey($cart, $identifier);
            
            if ($cartKey && isset($cart[$cartKey])) {
                $productName = $cart[$cartKey]['name'];
                $productSize = $cart[$cartKey]['size'] ?? null;
                $productId = $cart[$cartKey]['product_id'];
                
                unset($cart[$cartKey]);
                Session::put('cart', $cart);
                
                // Remove from database if user is authenticated
                if (Auth::check()) {
                    $this->removeDatabaseCartItem(Auth::id(), $productId, $productSize);
                }
                
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
            
            // Clear database cart if user is authenticated
            if (Auth::check()) {
                ShoppingCart::where('user_id', Auth::id())->delete();
            }
            
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

    // ===============================================
    // DATABASE CART METHODS
    // ===============================================

    private function addToSessionCart($productId, $quantity, $selectedSize, $product)
    {
        $cart = Session::get('cart', []);
        $cartKey = $this->getCartKey($productId, $selectedSize);
        
        if (isset($cart[$cartKey])) {
            $cart[$cartKey]['quantity'] += $quantity;
        } else {
            $cart[$cartKey] = [
                'cart_key' => $cartKey,
                'product_id' => $product->id,
                'name' => $product->name ?? 'Unknown Product',
                'price' => $product->sale_price ?: ($product->price ?? 0),
                'original_price' => $product->price ?? 0,
                'quantity' => $quantity,
                'image' => $product->images[0] ?? '/images/default-product.jpg',
                'slug' => $product->slug ?? '',
                'stock' => $product->stock_quantity ?? 0,
                'brand' => $product->brand ?? 'Unknown Brand',
                'category' => $product->category->name ?? 'Unknown Category',
                'sku' => $product->sku ?? '',
                'sku_parent' => $product->sku_parent ?? '',
                'size' => $selectedSize ?: ($product->available_sizes ?? 'One Size'),
                'color' => 'Default',
                'weight' => $product->weight ?? 500,
                'product_options' => [
                    'size' => $selectedSize ?: ($product->available_sizes ?? 'One Size'),
                    'color' => 'Default'
                ]
            ];
        }

        Session::put('cart', $cart);
    }

    private function addToDatabaseCart($userId, $productId, $quantity, $size)
    {
        $productOptions = $size ? json_encode(['size' => $size]) : null;
        
        // For PostgreSQL JSON comparison, we need to use JSON operators
        $existingItem = ShoppingCart::where('user_id', $userId)
            ->where('product_id', $productId)
            ->where(function($query) use ($productOptions) {
                if ($productOptions) {
                    // Use JSON containment operator for PostgreSQL
                    $query->whereRaw('product_options::jsonb @> ?::jsonb', [$productOptions]);
                } else {
                    $query->whereNull('product_options');
                }
            })
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
                'product_options' => $productOptions ? json_decode($productOptions, true) : null
            ]);
        }
    }

    private function updateDatabaseCartItem($userId, $productId, $size, $quantity)
    {
        $productOptions = $size ? json_encode(['size' => $size]) : null;
        
        $item = ShoppingCart::where('user_id', $userId)
            ->where('product_id', $productId)
            ->where(function($query) use ($productOptions) {
                if ($productOptions) {
                    $query->whereRaw('product_options::jsonb @> ?::jsonb', [$productOptions]);
                } else {
                    $query->whereNull('product_options');
                }
            })
            ->first();

        if ($item) {
            $item->update(['quantity' => $quantity]);
        }
    }

    private function removeDatabaseCartItem($userId, $productId, $size)
    {
        $productOptions = $size ? json_encode(['size' => $size]) : null;
        
        ShoppingCart::where('user_id', $userId)
            ->where('product_id', $productId)
            ->where(function($query) use ($productOptions) {
                if ($productOptions) {
                    $query->whereRaw('product_options::jsonb @> ?::jsonb', [$productOptions]);
                } else {
                    $query->whereNull('product_options');
                }
            })
            ->delete();
    }

    public function syncCartOnLogin($userId)
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
                $size = $sessionItem['size'] ?? null;
                $quantity = $sessionItem['quantity'];
                
                $productOptions = $size ? json_encode(['size' => $size]) : null;
                
                // Find if item exists in database using PostgreSQL-compatible query
                $dbItem = $databaseCartItems
                    ->where('product_id', $productId)
                    ->filter(function($item) use ($productOptions) {
                        if ($productOptions) {
                            return json_encode($item->product_options ?? []) === $productOptions;
                        }
                        return empty($item->product_options);
                    })
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
                        'product_options' => $productOptions ? json_decode($productOptions, true) : null
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

    public function loadDatabaseCartToSession($userId)
    {
        $databaseCartItems = ShoppingCart::where('user_id', $userId)
            ->with('product')
            ->get();

        $sessionCart = [];
        
        foreach ($databaseCartItems as $item) {
            if ($item->product && $item->product->is_active) {
                $size = $item->product_options['size'] ?? null;
                $cartKey = $this->getCartKey($item->product_id, $size);
                
                $sessionCart[$cartKey] = [
                    'cart_key' => $cartKey,
                    'product_id' => $item->product_id,
                    'name' => $item->product->name,
                    'price' => $item->product->sale_price ?: $item->product->price,
                    'original_price' => $item->product->price,
                    'quantity' => $item->quantity,
                    'image' => $item->product->images[0] ?? '/images/default-product.jpg',
                    'slug' => $item->product->slug,
                    'stock' => $item->product->stock_quantity ?? 0,
                    'brand' => $item->product->brand ?? 'Unknown Brand',
                    'category' => $item->product->category->name ?? 'Unknown Category',
                    'sku' => $item->product->sku ?? '',
                    'sku_parent' => $item->product->sku_parent ?? '',
                    'size' => $size ?: 'One Size',
                    'color' => 'Default',
                    'weight' => $item->product->weight ?? 500,
                    'product_options' => $item->product_options ?? ['size' => $size ?: 'One Size']
                ];
            }
        }

        Session::put('cart', $sessionCart);
    }

    public function saveSessionCartToDatabase($userId)
    {
        try {
            $sessionCart = Session::get('cart', []);
            
            foreach ($sessionCart as $cartKey => $item) {
                $productId = $item['product_id'];
                $quantity = $item['quantity'];
                $size = $item['size'] ?? null;
                
                $productOptions = $size ? json_encode(['size' => $size]) : null;
                
                // Try to find existing item with PostgreSQL-compatible comparison
                $existingItem = ShoppingCart::where('user_id', $userId)
                    ->where('product_id', $productId)
                    ->where(function($query) use ($productOptions) {
                        if ($productOptions) {
                            $query->whereRaw('product_options::jsonb @> ?::jsonb', [$productOptions]);
                        } else {
                            $query->whereNull('product_options');
                        }
                    })
                    ->first();

                if ($existingItem) {
                    $existingItem->update(['quantity' => $quantity]);
                } else {
                    ShoppingCart::create([
                        'user_id' => $userId,
                        'product_id' => $productId,
                        'quantity' => $quantity,
                        'product_options' => $productOptions ? json_decode($productOptions, true) : null
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

    private function syncCartOnPageLoad()
    {
        if (Auth::check()) {
            $this->loadDatabaseCartToSession(Auth::id());
        }
    }

    // ===============================================
    // EXISTING METHODS (KEPT SAME)
    // ===============================================

    private function getCartKey($productId, $size = null)
    {
        if ($size && $size !== 'One Size') {
            return $productId . '_' . str_replace([' ', '.'], '_', strtolower($size));
        }
        
        return (string) $productId;
    }

    private function findCartKey($cart, $identifier)
    {
        if (isset($cart[$identifier])) {
            return $identifier;
        }
        
        foreach ($cart as $cartKey => $item) {
            if (isset($item['product_id']) && $item['product_id'] == $identifier) {
                return $cartKey;
            }
        }
        
        foreach ($cart as $cartKey => $item) {
            if (str_starts_with($cartKey, $identifier . '_')) {
                return $cartKey;
            }
        }
        
        return null;
    }

    private function getCartItems()
    {
        $cart = Session::get('cart', []);
        $cartItems = collect();
        
        foreach ($cart as $cartKey => $details) {
            $productId = $details['product_id'] ?? null;
            $product = null;
            
            if ($productId) {
                $product = Product::find($productId);
            }
            
            $currentStock = $product ? ($product->stock_quantity ?? 0) : 0;
            
            if (!$product || !$product->is_active) {
                continue;
            }
            
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
            
            $itemSize = 'One Size';
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

    public function getCartCount()
    {
        $count = $this->getCartItemCount();
        return response()->json(['count' => $count]);
    }

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

    public function syncCart()
    {
        try {
            $cart = Session::get('cart', []);
            $updated = false;
            $removedItems = [];
            
            foreach ($cart as $cartKey => $details) {
                $productId = $details['product_id'] ?? null;
                
                if (!$productId) {
                    $removedItems[] = $details['name'] ?? 'Unknown Product';
                    unset($cart[$cartKey]);
                    $updated = true;
                    continue;
                }
                
                $product = Product::find($productId);
                
                if (!$product || !$product->is_active) {
                    $removedItems[] = $details['name'] ?? 'Unknown Product';
                    unset($cart[$cartKey]);
                    $updated = true;
                    continue;
                }
                
                $currentStock = $product->stock_quantity ?? 0;
                
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
                
                $currentPrice = $product->sale_price ?: $product->price;
                if ($cart[$cartKey]['price'] != $currentPrice) {
                    $cart[$cartKey]['price'] = $currentPrice;
                    $cart[$cartKey]['original_price'] = $product->price;
                    $updated = true;
                }
                
                $cart[$cartKey]['stock'] = $currentStock;
                
                if ($cart[$cartKey]['quantity'] > $currentStock) {
                    $cart[$cartKey]['quantity'] = $currentStock;
                    $updated = true;
                }
                
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
                
                // Update database cart if user is authenticated
                if (Auth::check()) {
                    $this->saveSessionCartToDatabase(Auth::id());
                }
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