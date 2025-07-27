<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class CheckoutController extends Controller
{
    public function index()
    {
        // Get cart from session
        $cart = Session::get('cart', []);
        
        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        // Get cart items
        $cartItems = $this->getCartItems($cart);
        $subtotal = $cartItems->sum('subtotal');

        // Get provinces for shipping
        $provinces = $this->getProvinces();

        Log::info('Session cart data: ' . json_encode($cart));
        Log::info('Checkout Debug', [
            'cart_count' => count($cart),
            'cart_items_count' => $cartItems->count(),
            'subtotal' => $subtotal
        ]);

        return view('frontend.checkout.index', compact('cartItems', 'subtotal', 'provinces'));
    }

    public function store(Request $request)
    {
        // Enhanced validation
        $request->validate([
            // Personal Information
            'social_title' => 'nullable|in:Mr.,Mrs.',
            'first_name' => 'required|string|max:255|regex:/^[a-zA-Z.\s]+$/',
            'last_name' => 'required|string|max:255|regex:/^[a-zA-Z.\s]+$/',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'birthdate' => 'nullable|date|before:today',
            
            // Account Creation (Optional)
            'create_account' => 'nullable|boolean',
            'password' => 'nullable|required_if:create_account,1|min:8|max:72|confirmed',
            'newsletter_subscribe' => 'nullable|boolean',
            'privacy_accepted' => 'required|accepted',
            
            // Address Information
            'address' => 'required|string',
            'province_id' => 'required|string',
            'city_id' => 'required|string',
            'postal_code' => 'required|string|max:10',
            
            // Shipping & Payment
            'shipping_method' => 'nullable|string',
            'shipping_cost' => 'nullable|numeric|min:0',
            'payment_method' => 'required|in:bank_transfer,credit_card,cod,ewallet',
        ]);

        Log::info('Enhanced checkout request data:', $request->all());

        try {
            DB::beginTransaction();

            // Handle user creation if requested
            $user = null;
            if ($request->create_account && !Auth::check()) {
                // Check if email already exists
                if (User::where('email', $request->email)->exists()) {
                    return back()->withErrors(['email' => 'Email already exists. Please login or use different email.'])->withInput();
                }

                // Create new user account
                $userData = [
                    'name' => trim($request->first_name . ' ' . $request->last_name),
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'email_verified_at' => now(),
                ];

                $user = User::create($userData);
                Auth::login($user);
                
                Log::info('New user account created during checkout:', ['user_id' => $user->id, 'email' => $user->email]);
            } elseif (Auth::check()) {
                $user = Auth::user();
            }

            // Get cart dari session
            $cart = Session::get('cart', []);
            if (empty($cart)) {
                return back()->with('error', 'Your cart is empty.');
            }

            // Get cart items dengan validasi
            $cartItems = $this->getCartItems($cart);
            if ($cartItems->isEmpty()) {
                return back()->with('error', 'No valid items in cart.');
            }

            Log::info('Cart items for checkout:', $cartItems->toArray());

            // Calculate totals
            $subtotal = $cartItems->sum('subtotal');
            $shippingAmount = (float) ($request->shipping_cost ?? 0);
            $taxAmount = $subtotal * 0.11; // 11% tax
            $totalAmount = $subtotal + $shippingAmount + $taxAmount;

            // Generate order number
            $orderNumber = 'SF' . date('Ymd') . strtoupper(Str::random(6));

            // Store origin data
            $storeOrigin = [
                'city_name' => 'Jakarta Selatan',
                'province_name' => 'DKI Jakarta',
                'postal_code' => '12310',
                'city_id' => '158'
            ];

            // Prepare customer name with social title
            $customerName = trim(($request->social_title ? $request->social_title . ' ' : '') . $request->first_name . ' ' . $request->last_name);

            // Create order dengan enhanced data
            $orderData = [
                'order_number' => $orderNumber,
                'user_id' => $user ? $user->id : null,
                'customer_name' => $customerName,
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
                    'social_title' => $request->social_title,
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'address' => $request->address,
                    'city_id' => $request->city_id,
                    'province_id' => $request->province_id,
                    'postal_code' => $request->postal_code,
                    'phone' => $request->phone
                ],
                'billing_address' => [
                    'social_title' => $request->social_title,
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'address' => $request->address,
                    'city_id' => $request->city_id,
                    'province_id' => $request->province_id,
                    'postal_code' => $request->postal_code,
                    'phone' => $request->phone
                ],
                'store_origin' => [
                    'address' => 'Jl. Bank Exim No.37, RT.6/RW.1, Pd. Pinang, Kec. Kby. Lama',
                    'city' => $storeOrigin['city_name'],
                    'province' => $storeOrigin['province_name'],
                    'postal_code' => $storeOrigin['postal_code'],
                    'city_id' => $storeOrigin['city_id']
                ],
                'payment_method' => $request->payment_method,
                'payment_status' => 'pending',
                'notes' => $request->notes ?? '',
                'shipping_method' => $request->shipping_method ?? 'Standard Shipping',
            ];

            Log::info('Creating order with enhanced data:', $orderData);

            // Create order
            $order = Order::create($orderData);

            Log::info('Order created successfully with ID: ' . $order->id);

            // Create order items dengan validasi ketat
            foreach ($cartItems as $item) {
                try {
                    // Validate product exists and is available
                    $product = Product::find($item['id']);
                    if (!$product) {
                        throw new \Exception("Product with ID {$item['id']} not found");
                    }

                    if (!$product->is_active) {
                        throw new \Exception("Product {$product->name} is not active");
                    }

                    if ($product->stock_quantity < $item['quantity']) {
                        throw new \Exception("Insufficient stock for product {$product->name}. Available: {$product->stock_quantity}, Requested: {$item['quantity']}");
                    }

                    // Prepare order item data
                    $orderItemData = [
                        'order_id' => $order->id,
                        'product_id' => $item['id'],
                        'product_name' => $item['name'],
                        'product_sku' => $item['sku'] ?? '',
                        'product_price' => (float) $item['price'],
                        'quantity' => (int) $item['quantity'],
                        'total_price' => (float) $item['subtotal']
                    ];

                    // Validate numeric values
                    if ($orderItemData['product_price'] <= 0) {
                        throw new \Exception("Invalid product price for {$item['name']}: {$orderItemData['product_price']}");
                    }

                    if ($orderItemData['total_price'] <= 0) {
                        throw new \Exception("Invalid total price for {$item['name']}: {$orderItemData['total_price']}");
                    }

                    if ($orderItemData['quantity'] <= 0) {
                        throw new \Exception("Invalid quantity for {$item['name']}: {$orderItemData['quantity']}");
                    }

                    Log::info('Creating OrderItem with data:', $orderItemData);

                    // Create order item
                    OrderItem::create($orderItemData);

                    // Update stock
                    $product->decrement('stock_quantity', $item['quantity']);

                    Log::info("Stock updated for product {$product->id}: remaining {$product->fresh()->stock_quantity}");

                } catch (\Exception $e) {
                    Log::error('OrderItem creation error: ' . $e->getMessage(), [
                        'item' => $item,
                        'order_id' => $order->id
                    ]);
                    throw $e;
                }
            }

            DB::commit();

            // Clear cart setelah order berhasil
            Session::forget('cart');

            Log::info('Enhanced checkout completed successfully for order: ' . $order->order_number);

            // Redirect to success page
            return redirect()->route('checkout.success', ['orderNumber' => $order->order_number])
                           ->with('success', 'Order placed successfully!' . ($user && $request->create_account ? ' Your account has been created.' : ''));

        } catch (\Exception $e) {
            DB::rollback();
            
            Log::error('Enhanced checkout error: ' . $e->getMessage(), [
                'request_data' => $request->all(),
                'cart_data' => Session::get('cart', [])
            ]);
            
            return back()->withInput()->with('error', 'Failed to process checkout: ' . $e->getMessage());
        }
    }

    public function getCities(Request $request)
    {
        $provinceId = $request->get('province_id');
        
        if (!$provinceId) {
            return response()->json([]);
        }

        try {
            // Try RajaOngkir API first
            $response = Http::withHeaders([
                'key' => env('RAJAONGKIR_API_KEY', 'your-api-key-here')
            ])->get('https://api.rajaongkir.com/starter/city', [
                'province' => $provinceId
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['rajaongkir']['results'])) {
                    return response()->json($data['rajaongkir']['results']);
                }
            }
        } catch (\Exception $e) {
            Log::error('RajaOngkir API error: ' . $e->getMessage());
        }

        // Fallback static data
        $fallbackCities = $this->getFallbackCities($provinceId);
        return response()->json($fallbackCities);
    }

    public function calculateShipping(Request $request)
    {
        $destinationCity = $request->get('destination_city');
        $weight = $request->get('weight', 1000); // Default 1kg

        if (!$destinationCity) {
            return response()->json([]);
        }

        try {
            // Try RajaOngkir API first
            $response = Http::withHeaders([
                'key' => env('RAJAONGKIR_API_KEY', 'your-api-key-here')
            ])->post('https://api.rajaongkir.com/starter/cost', [
                'origin' => '158', // Jakarta Selatan
                'destination' => $destinationCity,
                'weight' => $weight,
                'courier' => 'jne:pos:tiki'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['rajaongkir']['results'])) {
                    $shippingOptions = [];
                    
                    foreach ($data['rajaongkir']['results'] as $courier) {
                        foreach ($courier['costs'] as $cost) {
                            $shippingOptions[] = [
                                'courier' => strtoupper($courier['code']),
                                'service' => $cost['service'],
                                'description' => $cost['description'],
                                'cost' => $cost['cost'][0]['value'],
                                'etd' => $cost['cost'][0]['etd'],
                                'formatted_cost' => 'Rp ' . number_format($cost['cost'][0]['value'], 0, ',', '.'),
                                'formatted_etd' => $cost['cost'][0]['etd'] . ' hari'
                            ];
                        }
                    }

                    return response()->json($shippingOptions);
                }
            }
        } catch (\Exception $e) {
            Log::error('RajaOngkir shipping calculation error: ' . $e->getMessage());
        }

        // Fallback shipping options
        $fallbackOptions = [
            [
                'courier' => 'JNE',
                'service' => 'REG',
                'description' => 'Layanan Reguler',
                'cost' => 15000,
                'etd' => '2-3',
                'formatted_cost' => 'Rp 15.000',
                'formatted_etd' => '2-3 hari'
            ],
            [
                'courier' => 'POS',
                'service' => 'Paket Kilat',
                'description' => 'Pos Kilat Khusus',
                'cost' => 12000,
                'etd' => '3-4',
                'formatted_cost' => 'Rp 12.000',
                'formatted_etd' => '3-4 hari'
            ],
            [
                'courier' => 'TIKI',
                'service' => 'ECO',
                'description' => 'Ekonomi Service',
                'cost' => 10000,
                'etd' => '4-5',
                'formatted_cost' => 'Rp 10.000',
                'formatted_etd' => '4-5 hari'
            ]
        ];

        return response()->json($fallbackOptions);
    }

    public function success($orderNumber)
    {
        $order = Order::with('orderItems.product')
                     ->where('order_number', $orderNumber)
                     ->firstOrFail();
        
        return view('frontend.checkout.success', compact('order'));
    }

    public function finish($orderNumber)
    {
        $order = Order::where('order_number', $orderNumber)->firstOrFail();
        
        // Update payment status if needed
        $order->update(['payment_status' => 'paid']);
        
        return view('frontend.checkout.finish', compact('order'));
    }

    public function unfinish()
    {
        return view('frontend.checkout.unfinish');
    }

    public function error()
    {
        return view('frontend.checkout.error');
    }

    public function paymentNotification(Request $request)
    {
        // Handle payment gateway notification (Midtrans, etc.)
        Log::info('Payment notification received:', $request->all());
        
        // Process notification based on your payment gateway
        
        return response()->json(['status' => 'ok']);
    }

    private function getCartItems($cart)
    {
        $cartItems = collect();
        
        Log::info("Processing cart data:", $cart);
        
        foreach ($cart as $productId => $item) {
            Log::info("Processing product ID: $productId with item:", $item);
            
            // Validate product ID
            if (!is_numeric($productId)) {
                Log::warning("Invalid product ID: $productId");
                continue;
            }

            $product = Product::where('id', $productId)
                             ->where('is_active', true)
                             ->first();
            
            if (!$product) {
                Log::warning("Product not found or inactive for ID: $productId");
                continue;
            }

            // Get quantity from item array
            $quantity = isset($item['quantity']) ? (int) $item['quantity'] : 1;
            if ($quantity <= 0) {
                Log::warning("Invalid quantity for product $productId: $quantity");
                continue;
            }

            // Calculate price
            $price = (float) ($product->sale_price ?? $product->price);
            if ($price <= 0) {
                Log::warning("Invalid price for product $productId: $price");
                continue;
            }

            $subtotal = $price * $quantity;

            $cartItem = [
                'id' => (int) $product->id,
                'name' => $product->name,
                'price' => $price,
                'quantity' => $quantity,
                'subtotal' => $subtotal,
                'image' => $product->featured_image ?? ($product->images[0] ?? null),
                'slug' => $product->slug,
                'sku' => $product->sku ?? ''
            ];
            
            Log::info("Cart item processed successfully:", $cartItem);
            $cartItems->push($cartItem);
        }
        
        Log::info("Total valid cart items: " . $cartItems->count());
        return $cartItems;
    }

    private function getProvinces()
    {
        try {
            // Try RajaOngkir API first
            $response = Http::withHeaders([
                'key' => env('RAJAONGKIR_API_KEY', 'your-api-key-here')
            ])->get('https://api.rajaongkir.com/starter/province');

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['rajaongkir']['results'])) {
                    return $data['rajaongkir']['results'];
                }
            }
        } catch (\Exception $e) {
            Log::error('RajaOngkir provinces API error: ' . $e->getMessage());
        }

        // Fallback static provinces
        return [
            ['province_id' => '6', 'province' => 'DKI Jakarta'],
            ['province_id' => '9', 'province' => 'Jawa Barat'],
            ['province_id' => '10', 'province' => 'Jawa Tengah'],
            ['province_id' => '11', 'province' => 'Jawa Timur'],
            ['province_id' => '1', 'province' => 'Bali'],
        ];
    }

    private function getFallbackCities($provinceId)
    {
        $fallbackData = [
            '6' => [ // DKI Jakarta
                ['city_id' => '155', 'city_name' => 'Jakarta Pusat'],
                ['city_id' => '156', 'city_name' => 'Jakarta Utara'],
                ['city_id' => '157', 'city_name' => 'Jakarta Barat'],
                ['city_id' => '158', 'city_name' => 'Jakarta Selatan'],
                ['city_id' => '159', 'city_name' => 'Jakarta Timur']
            ],
            '9' => [ // Jawa Barat
                ['city_id' => '22', 'city_name' => 'Bandung'],
                ['city_id' => '23', 'city_name' => 'Bogor'],
                ['city_id' => '151', 'city_name' => 'Bekasi'],
                ['city_id' => '107', 'city_name' => 'Depok']
            ],
            '10' => [ // Jawa Tengah
                ['city_id' => '162', 'city_name' => 'Semarang'],
                ['city_id' => '501', 'city_name' => 'Solo']
            ],
            '11' => [ // Jawa Timur
                ['city_id' => '161', 'city_name' => 'Surabaya'],
                ['city_id' => '444', 'city_name' => 'Malang']
            ],
            '1' => [ // Bali
                ['city_id' => '114', 'city_name' => 'Denpasar']
            ]
        ];
        
        return $fallbackData[$provinceId] ?? [];
    }
}