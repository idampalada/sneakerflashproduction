<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Wishlist;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;

class WishlistController extends Controller
{
    /**
     * Display user's wishlist
     */
    public function index()
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please login to view your wishlist.');
        }

        // Get user's wishlist with products
        $wishlists = Wishlist::where('user_id', Auth::id())
            ->with(['product' => function($query) {
                $query->where('is_active', true)
                      ->whereNotNull('published_at')
                      ->where('published_at', '<=', now());
            }])
            ->orderBy('created_at', 'desc')
            ->get()
            ->filter(function($wishlist) {
                return $wishlist->product !== null; // Remove items with deleted products
            });

        return view('frontend.wishlist.index', compact('wishlists'));
    }

    /**
     * Add/Remove product to/from wishlist (AJAX)
     */
    public function toggle(Request $request, $productId): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Please login to add items to wishlist.',
                'redirect' => route('login')
            ], 401);
        }

        // Validate product exists and is active
        $product = Product::where('id', $productId)
                         ->where('is_active', true)
                         ->whereNotNull('published_at')
                         ->where('published_at', '<=', now())
                         ->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found or not available.'
            ], 404);
        }

        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            
            $isAdded = $user->toggleWishlist((int)$productId);
            $wishlistCount = $user->getWishlistCount();

            return response()->json([
                'success' => true,
                'is_added' => $isAdded,
                'message' => $isAdded 
                    ? 'Product added to wishlist!' 
                    : 'Product removed from wishlist!',
                'wishlist_count' => $wishlistCount,
                'product_id' => $productId,
                'product_name' => $product->name
            ]);

        } catch (\Exception $e) {
            Log::error('Wishlist toggle error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred. Please try again.'
            ], 500);
        }
    }

    /**
     * Remove specific item from wishlist
     */
    public function remove(Request $request, $productId): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access.'
            ], 401);
        }

        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            
            $removed = $user->removeFromWishlist((int)$productId);

            if ($removed > 0) {
                $wishlistCount = $user->getWishlistCount();

                return response()->json([
                    'success' => true,
                    'message' => 'Product removed from wishlist!',
                    'wishlist_count' => $wishlistCount
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found in wishlist.'
                ], 404);
            }

        } catch (\Exception $e) {
            Log::error('Wishlist remove error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred. Please try again.'
            ], 500);
        }
    }

    /**
     * Clear all wishlist items
     */
    public function clear(): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access.'
            ], 401);
        }

        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            
            $user->wishlists()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Wishlist cleared!',
                'wishlist_count' => 0
            ]);

        } catch (\Exception $e) {
            Log::error('Wishlist clear error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred. Please try again.'
            ], 500);
        }
    }

    /**
     * Get wishlist count for header badge (AJAX)
     */
    public function getCount(): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json(['count' => 0]);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $count = $user->getWishlistCount();

        return response()->json(['count' => $count]);
    }

    /**
     * Check if products are in wishlist (for product listing)
     */
    public function checkProducts(Request $request): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json(['wishlist_products' => []]);
        }

        $productIds = $request->input('product_ids', []);
        
        if (empty($productIds) || !is_array($productIds)) {
            return response()->json(['wishlist_products' => []]);
        }

        try {
            $wishlistProductIds = Wishlist::where('user_id', Auth::id())
                                        ->whereIn('product_id', $productIds)
                                        ->pluck('product_id')
                                        ->toArray();

            return response()->json(['wishlist_products' => $wishlistProductIds]);
            
        } catch (\Exception $e) {
            Log::error('Wishlist check products error: ' . $e->getMessage());
            return response()->json(['wishlist_products' => []]);
        }
    }

    /**
     * Move wishlist items to cart
     */
    public function moveToCart(Request $request, $productId): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Please login first.'
            ], 401);
        }

        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            
            // Check if product is in wishlist
            if (!$user->hasInWishlist((int)$productId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found in wishlist.'
                ], 404);
            }

            // Get product
            $product = Product::find($productId);
            
            if (!$product || !$product->is_active || $product->stock_quantity <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product is not available for purchase.'
                ], 400);
            }

            // Add to cart (session-based cart)
            $cart = session()->get('cart', []);
            
            if (isset($cart[$productId])) {
                $cart[$productId]['quantity']++;
            } else {
                $cart[$productId] = [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->sale_price ?? $product->price,
                    'quantity' => 1,
                    'image' => $product->images[0] ?? null
                ];
            }
            
            session()->put('cart', $cart);

            // Remove from wishlist
            $user->removeFromWishlist((int)$productId);

            return response()->json([
                'success' => true,
                'message' => 'Product moved to cart successfully!',
                'wishlist_count' => $user->getWishlistCount(),
                'cart_count' => count($cart)
            ]);

        } catch (\Exception $e) {
            Log::error('Wishlist move to cart error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred. Please try again.'
            ], 500);
        }
    }
}