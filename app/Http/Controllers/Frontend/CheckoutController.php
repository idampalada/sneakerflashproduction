<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    public function index()
    {
        $cart = Session::get('cart', []);
        
        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty!');
        }

        $cartItems = $this->getCartItems($cart);
        $subtotal = $cartItems->sum('subtotal');
        
        // Simple provinces array (nanti bisa diganti dengan Raja Ongkir)
        $provinces = [
            ['province_id' => '1', 'province' => 'DI Yogyakarta'],
            ['province_id' => '2', 'province' => 'DKI Jakarta'], 
            ['province_id' => '3', 'province' => 'Jawa Barat'],
            ['province_id' => '4', 'province' => 'Jawa Tengah'],
            ['province_id' => '5', 'province' => 'Jawa Timur'],
            ['province_id' => '6', 'province' => 'Banten'],
            ['province_id' => '7', 'province' => 'Bali'],
            ['province_id' => '8', 'province' => 'Sumatera Utara'],
            ['province_id' => '9', 'province' => 'Sumatera Barat'],
            ['province_id' => '10', 'province' => 'Sumatera Selatan'],
        ];
        
        return view('frontend.checkout.index', compact('cartItems', 'subtotal', 'provinces'));
    }

    public function getCities(Request $request)
    {
        // Simple cities array (nanti bisa diganti dengan Raja Ongkir)
        $cities = [
            ['city_id' => '155', 'city_name' => 'Jakarta Pusat'],
            ['city_id' => '156', 'city_name' => 'Jakarta Utara'],
            ['city_id' => '157', 'city_name' => 'Jakarta Barat'],
            ['city_id' => '158', 'city_name' => 'Jakarta Selatan'],
            ['city_id' => '159', 'city_name' => 'Jakarta Timur'],
            ['city_id' => '160', 'city_name' => 'Bandung'],
            ['city_id' => '161', 'city_name' => 'Surabaya'],
            ['city_id' => '162', 'city_name' => 'Yogyakarta'],
            ['city_id' => '163', 'city_name' => 'Semarang'],
            ['city_id' => '164', 'city_name' => 'Medan'],
        ];
        
        return response()->json($cities);
    }

    public function calculateShipping(Request $request)
    {
        // Simple shipping calculation (nanti bisa diganti dengan Raja Ongkir)
        $shippingOptions = [
            [
                'courier' => 'JNE',
                'service' => 'REG',
                'description' => 'Regular Service',
                'cost' => 15000,
                'etd' => '1-2',
                'formatted_cost' => 'Rp 15.000',
                'formatted_etd' => '1-2 hari'
            ],
            [
                'courier' => 'JNE',
                'service' => 'YES',
                'description' => 'Yakin Esok Sampai',
                'cost' => 25000,
                'etd' => '1',
                'formatted_cost' => 'Rp 25.000',
                'formatted_etd' => '1 hari'
            ],
            [
                'courier' => 'POS',
                'service' => 'Reguler',
                'description' => 'Pos Reguler',
                'cost' => 12000,
                'etd' => '2-3',
                'formatted_cost' => 'Rp 12.000',
                'formatted_etd' => '2-3 hari'
            ]
        ];

        return response()->json($shippingOptions);
    }

    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'required|string|max:20',
            'address' => 'required|string',
            'city_id' => 'required',
            'province_id' => 'required',
            'postal_code' => 'required|string|max:10',
            'shipping_method' => 'required|string',
            'shipping_cost' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500'
        ]);

        $cart = Session::get('cart', []);
        
        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty!');
        }

        DB::beginTransaction();

        try {
            // Create order
            $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(Str::random(6));
            $cartItems = $this->getCartItems($cart);
            $subtotal = $cartItems->sum('subtotal');
            $shippingAmount = $request->shipping_cost;
            $taxAmount = $subtotal * 0.11; // 11% PPN
            $totalAmount = $subtotal + $shippingAmount + $taxAmount;

            $order = Order::create([
                'order_number' => $orderNumber,
                'user_id' => null, // Guest order - no authentication required
                'customer_name' => $request->first_name . ' ' . $request->last_name,
                'customer_email' => $request->email,
                'customer_phone' => $request->phone,
                'status' => 'pending',
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'shipping_amount' => $shippingAmount,
                'discount_amount' => 0,
                'total_amount' => $totalAmount,
                'currency' => 'IDR',
                'shipping_address' => [
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'address' => $request->address,
                    'city_id' => $request->city_id,
                    'province_id' => $request->province_id,
                    'postal_code' => $request->postal_code,
                    'phone' => $request->phone
                ],
                'billing_address' => [
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'address' => $request->address,
                    'city_id' => $request->city_id,
                    'province_id' => $request->province_id,
                    'postal_code' => $request->postal_code,
                    'phone' => $request->phone
                ],
                'payment_method' => 'bank_transfer', // Simple payment method
                'payment_status' => 'pending',
                'notes' => $request->notes,
            ]);

            // Create order items
            foreach ($cartItems as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['id'],
                    'product_name' => $item['name'],
                    'product_price' => $item['price'],
                    'quantity' => $item['quantity'],
                    'total_price' => $item['subtotal']
                ]);

                // Update stock
                Product::where('id', $item['id'])->decrement('stock_quantity', $item['quantity']);
            }

            DB::commit();

            // Clear cart
            Session::forget('cart');

            // Simple success page (nanti bisa diganti dengan payment gateway)
            return view('frontend.checkout.success', [
                'order' => $order
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            
            return back()->withInput()->with('error', 'Failed to process checkout: ' . $e->getMessage());
        }
    }

    public function finish(Request $request)
    {
        $orderId = $request->order_id;
        
        if ($orderId) {
            $order = Order::where('order_number', $orderId)->first();
            
            if ($order) {
                return view('frontend.checkout.success', compact('order'));
            }
        }

        return redirect()->route('home')->with('error', 'Order not found');
    }

    private function getCartItems($cart)
    {
        $cartItems = collect();
        
        foreach ($cart as $id => $details) {
            $cartItems->push([
                'id' => $id,
                'name' => $details['name'],
                'price' => $details['price'],
                'quantity' => $details['quantity'],
                'image' => $details['image'],
                'subtotal' => $details['price'] * $details['quantity']
            ]);
        }
        
        return $cartItems;
    }
}