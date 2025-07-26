<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class CartController extends Controller
{
    public function index()
    {
        $cartItems = $this->getCartItems();
        $total = $this->calculateTotal($cartItems);
        
        return view('frontend.cart.index', compact('cartItems', 'total'));
    }

    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1'
        ]);

        $product = Product::find($request->product_id);
        
        // Check stock
        if ($product->stock_quantity < $request->quantity) {
            return back()->with('error', 'Insufficient stock available');
        }

        $cart = Session::get('cart', []);
        $productId = $request->product_id;
        $quantity = $request->quantity;

        if (isset($cart[$productId])) {
            // Update quantity if product already in cart
            $newQuantity = $cart[$productId]['quantity'] + $quantity;
            
            if ($newQuantity > $product->stock_quantity) {
                return back()->with('error', 'Cannot add more items. Stock limit reached.');
            }
            
            $cart[$productId]['quantity'] = $newQuantity;
        } else {
            // Add new product to cart
            $cart[$productId] = [
                'name' => $product->name,
                'price' => $product->sale_price ?: $product->price,
                'original_price' => $product->price,
                'quantity' => $quantity,
                'image' => $product->images[0] ?? null,
                'slug' => $product->slug,
                'stock' => $product->stock_quantity
            ];
        }

        Session::put('cart', $cart);
        
        return back()->with('success', 'Product added to cart successfully!');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1'
        ]);

        $cart = Session::get('cart', []);
        
        if (!isset($cart[$id])) {
            return back()->with('error', 'Product not found in cart');
        }

        $product = Product::find($id);
        
        if ($request->quantity > $product->stock_quantity) {
            return back()->with('error', 'Quantity exceeds available stock');
        }

        $cart[$id]['quantity'] = $request->quantity;
        Session::put('cart', $cart);
        
        return back()->with('success', 'Cart updated successfully!');
    }

    public function remove($id)
    {
        $cart = Session::get('cart', []);
        
        if (isset($cart[$id])) {
            unset($cart[$id]);
            Session::put('cart', $cart);
        }
        
        return back()->with('success', 'Item removed from cart!');
    }

    public function clear()
    {
        Session::forget('cart');
        return redirect()->route('cart.index')->with('success', 'Cart cleared successfully!');
    }

    // Helper methods
    private function getCartItems()
    {
        $cart = Session::get('cart', []);
        $cartItems = collect();
        
        foreach ($cart as $id => $details) {
            $cartItems->push([
                'id' => $id,
                'name' => $details['name'],
                'price' => $details['price'],
                'original_price' => $details['original_price'],
                'quantity' => $details['quantity'],
                'image' => $details['image'],
                'slug' => $details['slug'],
                'subtotal' => $details['price'] * $details['quantity']
            ]);
        }
        
        return $cartItems;
    }

    private function calculateTotal($cartItems)
    {
        return $cartItems->sum('subtotal');
    }

    // API method untuk AJAX calls
    public function getCartCount()
    {
        $cart = Session::get('cart', []);
        $count = array_sum(array_column($cart, 'quantity'));
        
        return response()->json(['count' => $count]);
    }
}