<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\UserAddress;
use App\Services\MidtransService;
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
    private $rajaOngkirApiKey;
    private $rajaOngkirBaseUrl;
    private $midtransService;

    public function __construct(MidtransService $midtransService)
    {
        $this->midtransService = $midtransService;
        
        // RajaOngkir API V2 via Komerce
        $this->rajaOngkirApiKey = config('services.rajaongkir.api_key') ?: env('RAJAONGKIR_API_KEY');
        $this->rajaOngkirBaseUrl = 'https://rajaongkir.komerce.id/api/v1';
        
        Log::info('RajaOngkir V2 Controller initialized with Address Integration + Voucher System', [
            'base_url' => $this->rajaOngkirBaseUrl,
            'api_key_set' => !empty($this->rajaOngkirApiKey),
            'origin_city' => env('STORE_ORIGIN_CITY_NAME', 'Not configured'),
            'origin_city_id' => env('STORE_ORIGIN_CITY_ID', 'Not configured'),
            'midtrans_configured' => !empty(config('services.midtrans.server_key'))
        ]);
    }

    /**
     * Index method with proper cart item handling and voucher info
     */
    public function index()
{
    // Keep existing code...
    $cart = Session::get('cart', []);
    
    if (empty($cart)) {
        return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
    }

    $cartItems = $this->getCartItems($cart);
    $subtotal = $cartItems->sum('subtotal');
    $totalWeight = $this->calculateTotalWeight($cartItems);
    $provinces = $this->getProvinces();
    $majorCities = $this->getMajorCities();

    // User addresses and authentication data (keep existing)...
    $userAddresses = collect();
    $primaryAddress = null;
    $primaryAddressId = null;
    $userHasPrimaryAddress = false;
    $authenticatedUserName = '';
    $authenticatedUserPhone = '';
    $authenticatedUserEmail = '';
    
    if (Auth::check()) {
        $user = Auth::user();
        
        $userAddresses = UserAddress::where('user_id', $user->id)
                            ->where('is_active', true)
                            ->orderBy('is_primary', 'desc')
                            ->orderBy('created_at', 'desc')
                            ->get();
        
        $primaryAddress = UserAddress::where('user_id', $user->id)
                            ->where('is_primary', true)
                            ->where('is_active', true)
                            ->first();
        
        if ($primaryAddress) {
            $primaryAddressId = $primaryAddress->id;
            $userHasPrimaryAddress = true;
        }
        
        $authenticatedUserName = $user->name ?? '';
        $authenticatedUserPhone = $user->phone ?? '';
        $authenticatedUserEmail = $user->email ?? '';
    }

    // VOUCHER SYSTEM (keep existing)
    $appliedVoucher = Session::get('applied_voucher', null);
    $discountAmount = 0;

    if ($appliedVoucher && isset($appliedVoucher['discount_amount'])) {
        $discountAmount = (float) $appliedVoucher['discount_amount'];
    }

    // POINTS SYSTEM - NEW
    $appliedPoints = Session::get('applied_points', null);
    $pointsDiscount = 0;
    $pointsUsed = 0;

    if ($appliedPoints && isset($appliedPoints['discount'])) {
        $pointsDiscount = (float) $appliedPoints['discount'];
        $pointsUsed = (int) $appliedPoints['points_used'];
    }

    Log::info('Checkout initialized with Points Support', [
        'cart_count' => count($cart),
        'subtotal' => $subtotal,
        'discount_amount' => $discountAmount,
        'points_discount' => $pointsDiscount,
        'points_used' => $pointsUsed,
        'total_weight' => $totalWeight,
        'user_authenticated' => Auth::check(),
        'applied_voucher' => $appliedVoucher ? $appliedVoucher['voucher_code'] : null,
        'user_points_balance' => Auth::check() ? (Auth::user()->points_balance ?? 0) : 0
    ]);

    return view('frontend.checkout.index', compact(
        'cartItems', 
        'subtotal', 
        'provinces', 
        'majorCities', 
        'totalWeight',
        'userAddresses',
        'primaryAddress',
        'primaryAddressId',
        'userHasPrimaryAddress',
        'authenticatedUserName',
        'authenticatedUserPhone',
        'authenticatedUserEmail',
        'appliedVoucher',
        'discountAmount',
        'appliedPoints',
        'pointsDiscount',
        'pointsUsed'
    ));
}

/**
 * Validate and apply points - NEW
 */
public function validatePoints(Request $request)
{
    try {
        $request->validate([
            'points_amount' => 'required|integer|min:1',
        ]);

        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

        $pointsAmount = $request->points_amount;
        $userBalance = $user->points_balance ?? 0;

        // Validate points availability
        if ($pointsAmount > $userBalance) {
            return response()->json([
                'success' => false,
                'message' => "Poin tidak mencukupi. Tersedia: " . number_format($userBalance, 0, ',', '.') . " poin"
            ]);
        }

        // Calculate discount (1 point = 1 rupiah)
        $discount = $pointsAmount;

        // Store in session
        Session::put('applied_points', [
            'points_used' => $pointsAmount,
            'discount' => $discount,
            'applied_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'points_used' => $pointsAmount,
            'discount_amount' => $discount,
            'formatted_discount' => 'Rp ' . number_format($discount, 0, ',', '.'),
            'remaining_balance' => $userBalance - $pointsAmount,
            'message' => number_format($pointsAmount, 0, ',', '.') . " poin berhasil diterapkan"
        ]);

    } catch (\Exception $e) {
        Log::error('Error validating points', [
            'user_id' => Auth::id(),
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Terjadi kesalahan sistem'
        ], 500);
    }
}

/**
 * Remove applied points - NEW
 */
public function removePoints(Request $request)
{
    try {
        // Clear points from session
        Session::forget('applied_points');

        return response()->json([
            'success' => true,
            'message' => 'Penggunaan poin dibatalkan'
        ]);

    } catch (\Exception $e) {
        Log::error('Error removing points', [
            'user_id' => Auth::id(),
            'error' => $e->getMessage()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Gagal membatalkan penggunaan poin'
        ], 500);
    }
}

/**
 * Get current applied points - NEW
 */
public function getCurrentPoints(Request $request)
{
    try {
        $appliedPoints = Session::get('applied_points');

        if (!$appliedPoints) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada poin yang diterapkan'
            ]);
        }

        return response()->json([
            'success' => true,
            'points' => $appliedPoints
        ]);

    } catch (\Exception $e) {
        Log::error('Error getting current points', [
            'user_id' => Auth::id(),
            'error' => $e->getMessage()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Terjadi kesalahan sistem'
        ], 500);
    }
}


    // Keep all existing methods exactly the same until store method
    private function getCartItems($cart)
    {
        $cartItems = collect();
        
        foreach ($cart as $cartKey => $details) {
            $productId = $details['product_id'] ?? $details['id'] ?? null;
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

    private function calculateTotalWeight($cartItems)
    {
        $totalWeight = 0;
        
        foreach ($cartItems as $item) {
            $itemWeight = $item['weight'] ?? 500;
            $totalWeight += $itemWeight * $item['quantity'];
        }
        
        return max($totalWeight, 1000);
    }

    // Keep all location and shipping methods exactly the same...
    public function searchDestinations(Request $request)
    {
        $search = $request->get('search');
        $limit = $request->get('limit', 10);
        
        Log::info('Searching destinations via RajaOngkir V2', ['search' => $search]);
        
        if (!$search || strlen($search) < 2) {
            return response()->json(['error' => 'Search term must be at least 2 characters'], 400);
        }

        try {
            $response = Http::timeout(10)->withHeaders([
                'key' => $this->rajaOngkirApiKey
            ])->get($this->rajaOngkirBaseUrl . '/destination/domestic-destination', [
                'search' => $search,
                'limit' => $limit,
                'offset' => 0
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['data']) && is_array($data['data'])) {
                    $destinations = array_map(function($dest) {
                        return [
                            'location_id' => $dest['id'],
                            'subdistrict_name' => $dest['subdistrict_name'],
                            'district_name' => $dest['district_name'],
                            'city_name' => $dest['city_name'],
                            'province_name' => $dest['province_name'],
                            'zip_code' => $dest['zip_code'],
                            'label' => $dest['label'],
                            'display_name' => $dest['subdistrict_name'] . ', ' . $dest['district_name'] . ', ' . $dest['city_name'],
                            'full_address' => $dest['label']
                        ];
                    }, $data['data']);
                    
                    Log::info('Found ' . count($destinations) . ' destinations for search: ' . $search);
                    
                    return response()->json([
                        'success' => true,
                        'total' => count($destinations),
                        'data' => $destinations
                    ]);
                }
            } else {
                Log::warning('API search failed, returning mock data', [
                    'status' => $response->status(),
                    'search' => $search
                ]);
                
                return $this->getMockDestinations($search);
            }

            return response()->json(['error' => 'No destinations found'], 404);

        } catch (\Exception $e) {
            Log::error('RajaOngkir V2 search error: ' . $e->getMessage());
            return $this->getMockDestinations($search);
        }
    }

    public function calculateShipping(Request $request)
    {
        $destinationId = $request->get('destination_id');
        $destinationLabel = $request->get('destination_label', '');
        $weight = $request->get('weight', 1000);

        Log::info('Calculating JNE shipping via RajaOngkir V2', [
            'destination_id' => $destinationId,
            'destination_label' => $destinationLabel,
            'weight' => $weight,
            'store_origin_city' => env('STORE_ORIGIN_CITY_NAME', 'Not configured'),
            'courier' => 'JNE only'
        ]);

        if (!$destinationId) {
            return response()->json(['error' => 'Destination ID is required'], 400);
        }

        try {
            $originId = $this->getOriginIdFromEnv();
            
            Log::info('Using origin configuration for JNE shipping', [
                'origin_id' => $originId,
                'origin_city_name' => env('STORE_ORIGIN_CITY_NAME'),
                'origin_city_id_fallback' => env('STORE_ORIGIN_CITY_ID'),
                'courier' => 'JNE only'
            ]);

            $shippingOptions = $this->calculateRealShipping($originId, $destinationId, $weight);
            
            if (empty($shippingOptions)) {
                Log::info('No real JNE shipping options found, using mock JNE data');
                $shippingOptions = $this->getMockShippingOptions($weight, $destinationLabel);
            }

            if (!empty($shippingOptions)) {
                $shippingOptions = $this->autoSortShippingOptions($shippingOptions);
                
                Log::info('Successfully calculated ' . count($shippingOptions) . ' JNE shipping options');
                
                return response()->json([
                    'success' => true,
                    'total_options' => count($shippingOptions),
                    'origin_id' => $originId,
                    'origin_city_name' => env('STORE_ORIGIN_CITY_NAME'),
                    'destination_id' => $destinationId,
                    'destination_label' => $destinationLabel,
                    'weight' => $weight,
                    'courier' => 'JNE only',
                    'api_version' => 'v2_jne_only_with_address_integration',
                    'options' => $shippingOptions
                ]);
            } else {
                Log::warning('No JNE shipping options available');
                return response()->json(['error' => 'No JNE shipping options available for this route'], 404);
            }

        } catch (\Exception $e) {
            Log::error('JNE shipping calculation error: ' . $e->getMessage());
            
            $mockOptions = $this->getMockShippingOptions($weight, $destinationLabel);
            
            return response()->json([
                'success' => true,
                'total_options' => count($mockOptions),
                'origin_id' => $this->getOriginIdFromEnv(),
                'origin_city_name' => env('STORE_ORIGIN_CITY_NAME'),
                'destination_id' => $destinationId,
                'destination_label' => $destinationLabel,
                'weight' => $weight,
                'courier' => 'JNE only',
                'api_version' => 'v2_jne_only_emergency_fallback',
                'options' => $mockOptions,
                'note' => 'Using fallback JNE shipping options due to API error'
            ]);
        }
    }

    /**
     * CRITICAL FIX: Store method with VOUCHER integration that works
     */
    public function store(Request $request)
{
    Log::info('Checkout request received with points support', [
        'payment_method' => $request->payment_method,
        'applied_voucher_code' => $request->get('applied_voucher_code'),
        'points_used' => $request->get('points_used', 0),
        'points_discount' => $request->get('points_discount', 0)
    ]);

    // Validation (tambahkan fields points)
    $request->validate([
        'gender' => 'nullable|in:mens,womens,kids',
        'first_name' => 'required|string|max:255',
        'last_name' => 'required|string|max:255',
        'email' => 'required|email|max:255',
        'phone' => 'required|string|max:20',
        'birthdate' => 'nullable|date|before:today',
        
        // Address fields (keep existing)...
        'saved_address_id' => 'nullable|string',
        'address_label' => 'required_without:saved_address_id|nullable|in:Kantor,Rumah',
        'recipient_name' => 'required|string|max:255',
        'phone_recipient' => 'required|string|max:20|regex:/^[0-9+\-\s\(\)]{10,}$/',
        'province_name' => 'required|string|max:100',
        'city_name' => 'required|string|max:100',
        'subdistrict_name' => 'required|string|max:100',
        'postal_code' => 'required|string|size:5|regex:/^[0-9]{5}$/',
        'destination_id' => 'nullable|string|max:50',
        'street_address' => 'required|string|min:10|max:500',
        
        'shipping_method' => 'required|string',
        'shipping_cost' => 'required|numeric|min:0',
        'payment_method' => 'required|in:bank_transfer,credit_card,ewallet',
        
        // Voucher fields (keep existing)
        'applied_voucher_code' => 'nullable|string|max:50',
        'applied_voucher_discount' => 'nullable|numeric|min:0',
        
        // Points fields - NEW
        'points_used' => 'nullable|integer|min:0',
        'points_discount' => 'nullable|numeric|min:0',
        
        'privacy_accepted' => 'required|boolean',
    ]);
    
    try {
        DB::beginTransaction();

        $cart = Session::get('cart', []);
        
        if (empty($cart)) {
            throw new \Exception('Cart is empty');
        }

        $cartItems = $this->getCartItems($cart);
        
        if ($cartItems->isEmpty()) {
            throw new \Exception('No valid items in cart');
        }

        $subtotal = $cartItems->sum('subtotal');
        $shippingCost = (float) $request->shipping_cost;
        
        // VOUCHER HANDLING (keep existing logic)
        $discountAmount = 0;
        $voucherInfo = null;

        if ($request->get('applied_voucher_code') && $request->get('applied_voucher_discount')) {
            $discountAmount = (float) $request->get('applied_voucher_discount');
            $voucherInfo = [
                'voucher_code' => $request->get('applied_voucher_code'),
                'discount_amount' => $discountAmount,
                'source' => 'form_data'
            ];
        } else {
            $sessionVoucher = Session::get('applied_voucher', null);
            if ($sessionVoucher && isset($sessionVoucher['discount_amount'])) {
                $discountAmount = (float) $sessionVoucher['discount_amount'];
                $voucherInfo = [
                    'voucher_code' => $sessionVoucher['voucher_code'] ?? 'unknown',
                    'discount_amount' => $discountAmount,
                    'source' => 'session'
                ];
            }
        }
        
        // POINTS HANDLING - NEW
        $pointsUsed = 0;
        $pointsDiscount = 0;
        $user = Auth::user();

        if ($request->get('points_used') && $request->get('points_discount')) {
            $pointsUsed = (int) $request->get('points_used');
            $pointsDiscount = (float) $request->get('points_discount');
            
            // Validate user has enough points
            if ($user && $pointsUsed > ($user->points_balance ?? 0)) {
                throw new \Exception('Poin tidak mencukupi');
            }
        } else {
            // Fallback to session
            $sessionPoints = Session::get('applied_points', null);
            if ($sessionPoints) {
                $pointsUsed = (int) ($sessionPoints['points_used'] ?? 0);
                $pointsDiscount = (float) ($sessionPoints['discount'] ?? 0);
            }
        }
        
        $tax = 0; // No tax as per existing system
        $totalAmount = $subtotal + $shippingCost - $discountAmount - $pointsDiscount;
        $totalAmount = max(0, $totalAmount);

        Log::info('Order totals calculated with points system', [
            'subtotal' => $subtotal,
            'shipping_cost' => $shippingCost,
            'discount_amount' => $discountAmount,
            'points_discount' => $pointsDiscount,
            'total_amount' => $totalAmount,
            'voucher_applied' => !empty($voucherInfo),
            'points_used' => $pointsUsed
        ]);

        // Keep existing user and address handling...
        $user = $this->handleUserAccountCreationOrUpdate($request);
        $addressData = $this->handleAddressData($request, $user);

        // Generate order number (keep existing)
        do {
            $orderNumber = 'SF-' . date('Ymd') . '-' . strtoupper(Str::random(6));
        } while (Order::where('order_number', $orderNumber)->exists());

        // Order data with points support
        $orderData = [
            'order_number' => $orderNumber,
            'user_id' => $user ? $user->id : null,
            'customer_name' => $request->first_name . ' ' . $request->last_name,
            'customer_email' => $request->email,
            'customer_phone' => $request->phone,
            
            'shipping_address' => $addressData['full_address'],
            'billing_address' => $addressData['full_address'],
            'shipping_destination_id' => $addressData['destination_id'] ?? $request->destination_id,
            'shipping_destination_label' => $addressData['location_string'] ?? $request->destination_label,
            'shipping_postal_code' => $addressData['postal_code'],
            
            'payment_method' => $request->payment_method,
            'subtotal' => $subtotal,
            'shipping_cost' => $shippingCost,
            'tax_amount' => 0,
            'discount_amount' => $discountAmount,
            'total_amount' => $totalAmount,
            'currency' => 'IDR',
            
            'status' => 'pending',
            'store_origin' => env('STORE_ORIGIN_CITY_NAME', 'Jakarta'),
            'notes' => trim(($request->notes ?? '') . "\n" . "Shipping: " . $request->shipping_method),
            
            'meta_data' => json_encode([
                'shipping_method' => $request->shipping_method,
                'destination_info' => [
                    'id' => $addressData['destination_id'] ?? $request->destination_id,
                    'label' => $addressData['location_string'] ?? $request->destination_label,
                    'postal_code' => $addressData['postal_code'],
                    'full_address' => $addressData['full_address']
                ],
                'address_info' => [
                    'address_id' => $addressData['address_id'] ?? null,
                    'label' => $addressData['label'],
                    'recipient_name' => $addressData['recipient_name'],
                    'phone_recipient' => $addressData['phone_recipient'],
                    'province_name' => $addressData['province_name'],
                    'city_name' => $addressData['city_name'],
                    'subdistrict_name' => $addressData['subdistrict_name'],
                    'street_address' => $addressData['street_address'],
                ],
                'customer_info' => [
                    'gender' => $request->gender ?? null,
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'birthdate' => $request->birthdate ?? null,
                    'newsletter_subscribe' => $request->newsletter_subscribe ?? false,
                ],
                // VOUCHER info (keep existing)
                'voucher_info' => $voucherInfo,
                // POINTS info - NEW
                'points_info' => [
                    'points_used' => $pointsUsed,
                    'points_discount' => $pointsDiscount,
                    'user_points_balance_before' => $user ? ($user->points_balance ?? 0) : 0,
                ],
                'checkout_info' => [
                    'created_via' => 'web_checkout_with_points_support',
                    'user_agent' => $request->userAgent(),
                    'ip_address' => $request->ip(),
                    'checkout_timestamp' => now()->toISOString(),
                    'tax_rate' => 0,
                    'cart_items_count' => $cartItems->count(),
                    'total_weight' => $cartItems->sum(function($item) { 
                        return ($item['weight'] ?? 500) * $item['quantity']; 
                    }),
                    'subtotal_breakdown' => [
                        'items_subtotal' => $subtotal,
                        'shipping_cost' => $shippingCost,
                        'tax_amount' => 0,
                        'discount_amount' => $discountAmount,
                        'points_discount' => $pointsDiscount,
                        'total_amount' => $totalAmount
                    ]
                ]
            ]),
            
            'created_at' => now(),
            'updated_at' => now()
        ];

        // Filter existing columns (keep existing logic)
        $existingColumns = [
            'order_number', 'user_id', 'customer_name', 'customer_email', 'customer_phone',
            'status', 'subtotal', 'tax_amount', 'shipping_cost', 'discount_amount', 
            'total_amount', 'currency', 'shipping_address', 'billing_address', 
            'store_origin', 'payment_method', 'payment_token', 
            'payment_url', 'tracking_number', 'shipped_at', 'delivered_at', 
            'notes', 'meta_data', 'created_at', 'updated_at',
            'shipping_destination_id', 'shipping_destination_label', 'shipping_postal_code',
            'snap_token', 'payment_response'
        ];

        $filteredOrderData = array_intersect_key($orderData, array_flip($existingColumns));

        Log::info('Creating order with points support', [
            'order_number' => $orderNumber,
            'customer_email' => $request->email,
            'payment_method' => $request->payment_method,
            'total_amount' => $totalAmount,
            'discount_amount' => $discountAmount,
            'points_discount' => $pointsDiscount,
            'points_used' => $pointsUsed,
            'voucher_applied' => !empty($voucherInfo),
            'initial_status' => 'pending',
            'user_id' => $user ? $user->id : null,
        ]);

        $order = Order::create($filteredOrderData);

        // Create order items (keep existing logic)
        foreach ($cartItems as $item) {
            $product = Product::lockForUpdate()->find($item['id']);
            
            if (!$product || $product->stock_quantity < $item['quantity']) {
                throw new \Exception("Insufficient stock for {$item['name']}");
            }

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item['id'],
                'product_name' => $item['name'],
                'product_sku' => $item['sku'] ?? '',
                'product_price' => (float) $item['price'],
                'quantity' => (int) $item['quantity'],
                'total_price' => (float) $item['subtotal']
            ]);

            $product->decrement('stock_quantity', $item['quantity']);
        }

        // DEDUCT POINTS FROM USER - NEW
        if ($pointsUsed > 0 && $user) {
            $user->decrement('points_balance', $pointsUsed);
            
            // You can add points transaction logging here if you have that table
            Log::info('Points deducted from user', [
                'user_id' => $user->id,
                'points_used' => $pointsUsed,
                'points_discount' => $pointsDiscount,
                'order_number' => $order->order_number,
                'remaining_balance' => $user->fresh()->points_balance
            ]);
        }

        DB::commit();

        // Clear session data
        Session::forget(['cart', 'applied_voucher', 'applied_points']);

        Log::info('Order created successfully with points support', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'payment_method' => $request->payment_method,
            'total_amount' => $totalAmount,
            'discount_amount' => $discountAmount,
            'points_discount' => $pointsDiscount,
            'points_used' => $pointsUsed,
            'status' => $order->status,
            'customer_email' => $request->email,
            'user_id' => $user ? $user->id : null,
        ]);

        // Create Midtrans payment (keep existing logic but update for points)
        $midtrans = $this->createMidtransPayment($order, $cartItems, $request);

        if ($midtrans && isset($midtrans['token'])) {
            $snapToken = $midtrans['token'];
            $redirectUrl = $midtrans['redirect_url'] ?? null;

            $order->update([
                'snap_token' => $snapToken,
                'payment_url' => $redirectUrl,
            ]);

            Log::info('Midtrans token created successfully with points support', [
                'order_number' => $order->order_number,
                'snap_token_length' => strlen($snapToken),
                'final_amount' => $totalAmount,
                'points_discount_applied' => $pointsDiscount
            ]);

            if ($request->ajax() || $request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Order created successfully. Opening payment gateway...',
                    'order_number' => $order->order_number,
                    'customer_name' => $order->customer_name,
                    'snap_token' => $snapToken,
                    'redirect_url' => $redirectUrl ?: route('checkout.payment', ['orderNumber' => $order->order_number]),
                ]);
            }

            return redirect()
                ->route('checkout.payment', ['orderNumber' => $order->order_number])
                ->with('snap_token', $snapToken);
        } else {
            Log::error('Failed to create Midtrans token with points support', [
                'order_number' => $order->order_number,
                'payment_method' => $request->payment_method,
                'total_amount' => $totalAmount
            ]);

            if ($request->ajax() || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to create payment session. Please try again or contact support.',
                    'order_number' => $order->order_number
                ], 500);
            }

            return redirect()->route('checkout.success', ['orderNumber' => $order->order_number])
                           ->with('error', 'Order created but payment session failed. Please contact support.');
        }

    } catch (\Exception $e) {
        DB::rollback();
        
        // REFUND POINTS IF ORDER FAILED - NEW
        if ($pointsUsed > 0 && $user) {
            $user->increment('points_balance', $pointsUsed);
            Log::info('Points refunded due to order failure', [
                'user_id' => $user->id,
                'points_refunded' => $pointsUsed,
                'new_balance' => $user->fresh()->points_balance
            ]);
        }
        
        Log::error('Checkout error with points support: ' . $e->getMessage(), [
            'trace' => $e->getTraceAsString(),
            'payment_method' => $request->payment_method ?? 'unknown',
            'customer_email' => $request->email ?? 'unknown',
            'points_used' => $pointsUsed,
            'order_number' => $orderNumber ?? 'not_generated',
        ]);
        
        $errorMessage = 'Failed to process checkout: ' . $e->getMessage();
        
        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'success' => false,
                'error' => $errorMessage
            ], 500);
        }
        
        return back()->withInput()->with('error', $errorMessage);
    }
}

    /**
     * Handle address data processing - keep same as working version
     */
    private function handleAddressData(Request $request, $user)
    {
        if (!empty($request->saved_address_id) && $request->saved_address_id !== 'new' && $user) {
            $savedAddress = $user->addresses()
                                ->where('id', $request->saved_address_id)
                                ->where('is_active', true)
                                ->first();
                                
            if ($savedAddress) {
                Log::info('Using saved address for checkout', [
                    'user_id' => $user->id,
                    'address_id' => $savedAddress->id,
                    'address_label' => $savedAddress->label,
                    'recipient_name' => $savedAddress->recipient_name
                ]);
                
                return [
                    'address_id' => $savedAddress->id,
                    'label' => $savedAddress->label,
                    'recipient_name' => $savedAddress->recipient_name,
                    'phone_recipient' => $savedAddress->phone_recipient,
                    'province_name' => $savedAddress->province_name,
                    'city_name' => $savedAddress->city_name,
                    'subdistrict_name' => $savedAddress->subdistrict_name,
                    'postal_code' => $savedAddress->postal_code,
                    'destination_id' => $savedAddress->destination_id,
                    'street_address' => $savedAddress->street_address,
                    'full_address' => $savedAddress->full_address,
                    'location_string' => $savedAddress->location_string,
                ];
            }
        }
        
        $addressData = [
            'address_id' => null,
            'label' => $request->address_label ?? 'Rumah',
            'recipient_name' => trim($request->recipient_name),
            'phone_recipient' => preg_replace('/[^0-9+\-\s\(\)]/', '', $request->phone_recipient),
            'province_name' => $request->province_name,
            'city_name' => $request->city_name,
            'subdistrict_name' => $request->subdistrict_name,
            'postal_code' => $request->postal_code,
            'destination_id' => $request->destination_id ?? null,
            'street_address' => trim($request->street_address),
        ];
        
        $addressData['full_address'] = $addressData['street_address'] . ', ' . 
                                      $addressData['subdistrict_name'] . ', ' . 
                                      $addressData['city_name'] . ', ' . 
                                      $addressData['province_name'] . ' ' . 
                                      $addressData['postal_code'];
        
        $addressData['location_string'] = $addressData['province_name'] . ', ' . 
                                         $addressData['city_name'] . ', ' . 
                                         $addressData['subdistrict_name'] . ', ' . 
                                         $addressData['postal_code'];
        
        if ($user && ($request->save_address ?? false)) {
            try {
                $newAddress = $user->addresses()->create([
                    'label' => $addressData['label'],
                    'recipient_name' => $addressData['recipient_name'],
                    'phone_recipient' => $addressData['phone_recipient'],
                    'province_name' => $addressData['province_name'],
                    'city_name' => $addressData['city_name'],
                    'subdistrict_name' => $addressData['subdistrict_name'],
                    'postal_code' => $addressData['postal_code'],
                    'destination_id' => $addressData['destination_id'],
                    'street_address' => $addressData['street_address'],
                    'is_primary' => false,
                    'is_active' => true,
                ]);
                
                if ($request->set_as_primary ?? false) {
                    $newAddress->setPrimary();
                }
                
                $addressData['address_id'] = $newAddress->id;
                
                Log::info('New address saved during checkout', [
                    'user_id' => $user->id,
                    'address_id' => $newAddress->id,
                    'label' => $newAddress->label,
                    'is_primary' => $newAddress->is_primary,
                ]);
                
            } catch (\Exception $e) {
                Log::warning('Failed to save address during checkout', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                    'address_data' => $addressData
                ]);
            }
        }
        
        return $addressData;
    }

    /**
     * Handle user account creation or update - keep same as working version
     */
    private function handleUserAccountCreationOrUpdate(Request $request)
    {
        $user = null;
        
        if ($request->create_account && !Auth::check()) {
            $existingUser = User::where('email', $request->email)->first();
            if ($existingUser) {
                throw new \Exception('Email already exists. Please login or use different email.');
            }

            $user = User::create([
                'name' => $request->first_name . ' ' . $request->last_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'email_verified_at' => now(),
                'password' => Hash::make($request->password),
                'gender' => $request->gender,
                'birthdate' => $request->birthdate,
            ]);

            Auth::login($user);

            Log::info('New user account created during checkout', [
                'user_id' => $user->id,
                'email' => $user->email,
                'gender' => $user->gender,
                'birthdate' => $user->birthdate,
                'phone' => $user->phone
            ]);
            
        } elseif (Auth::check()) {
            $user = Auth::user();
            
            $userModel = User::find($user->id);
            
            if ($userModel) {
                $updateData = [];
                
                if ($request->gender && (!$userModel->gender || $userModel->gender !== $request->gender)) {
                    $updateData['gender'] = $request->gender;
                }
                
                if ($request->birthdate && !$userModel->birthdate) {
                    $updateData['birthdate'] = $request->birthdate;
                }
                
                if ($request->phone && (!$userModel->phone || $userModel->phone !== $request->phone)) {
                    $updateData['phone'] = $request->phone;
                }
                
                if (!empty($updateData)) {
                    try {
                        $updateResult = $userModel->update($updateData);
                        
                        Log::info('Updated existing user data from checkout', [
                            'user_id' => $userModel->id,
                            'email' => $userModel->email,
                            'updated_fields' => array_keys($updateData),
                            'updated_data' => $updateData,
                            'update_result' => $updateResult
                        ]);
                        
                        $user = $userModel;
                        
                    } catch (\Exception $updateError) {
                        Log::error('Failed to update user data', [
                            'user_id' => $userModel->id,
                            'error' => $updateError->getMessage(),
                            'update_data' => $updateData
                        ]);
                    }
                }
            }
        }

        return $user;
    }

    /**
     * CRITICAL FIX: Create Midtrans payment with VOUCHER support that works
     */
    private function createMidtransPayment($order, $cartItems, $request)
{
    try {
        Log::info('Creating Midtrans payment session with points support', [
            'order_number' => $order->order_number,
            'total_amount' => $order->total_amount,
            'discount_amount' => $order->discount_amount ?? 0,
            'payment_method' => $request->payment_method,
        ]);

        // Prepare item details (keep existing logic)
        $itemDetails = [];
        
        foreach ($cartItems as $item) {
            $itemDetails[] = [
                'id' => (string) $item['id'],
                'price' => (int) $item['price'],
                'quantity' => (int) $item['quantity'],
                'name' => substr($item['name'], 0, 50)
            ];
        }
        
        // Add shipping as item
        if ($order->shipping_cost > 0) {
            $shippingMethodName = 'Shipping Cost';
            
            if ($order->meta_data) {
                $metaData = json_decode($order->meta_data, true);
                if (isset($metaData['shipping_method'])) {
                    $shippingMethodName = 'Shipping - ' . substr($metaData['shipping_method'], 0, 30);
                }
            }
            
            $itemDetails[] = [
                'id' => 'shipping',
                'price' => (int) $order->shipping_cost,
                'quantity' => 1,
                'name' => $shippingMethodName
            ];
        }
        
        // Add voucher discount as negative item
        $discountAmount = (float) ($order->discount_amount ?? 0);
        if ($discountAmount > 0) {
            $discountName = 'Voucher Discount';
            
            if ($order->meta_data) {
                $metaData = json_decode($order->meta_data, true);
                if (isset($metaData['voucher_info']['voucher_code'])) {
                    $discountName = 'Voucher (' . $metaData['voucher_info']['voucher_code'] . ')';
                }
            }
            
            $itemDetails[] = [
                'id' => 'voucher_discount',
                'price' => -((int) $discountAmount),
                'quantity' => 1,
                'name' => $discountName
            ];
        }
        
        // Add points discount as negative item - NEW
        if ($order->meta_data) {
            $metaData = json_decode($order->meta_data, true);
            $pointsDiscount = (float) ($metaData['points_info']['points_discount'] ?? 0);
            $pointsUsed = (int) ($metaData['points_info']['points_used'] ?? 0);
            
            if ($pointsDiscount > 0) {
                $itemDetails[] = [
                    'id' => 'points_discount',
                    'price' => -((int) $pointsDiscount),
                    'quantity' => 1,
                    'name' => 'Points Discount (' . number_format($pointsUsed, 0, ',', '.') . ' poin)'
                ];
                
                Log::info('Added points discount item to Midtrans payload', [
                    'points_used' => $pointsUsed,
                    'points_discount' => -((int) $pointsDiscount)
                ]);
            }
        }
        
        // Verification and adjustment (keep existing logic)
        $calculatedSum = 0;
        foreach ($itemDetails as $item) {
            $calculatedSum += $item['price'] * $item['quantity'];
        }
        
        $expectedTotal = (int) $order->total_amount;
        
        if ($calculatedSum !== $expectedTotal) {
            $difference = $expectedTotal - $calculatedSum;
            
            Log::warning('Midtrans amounts mismatch with points, adding adjustment', [
                'difference' => $difference,
                'calculated_sum' => $calculatedSum,
                'expected_total' => $expectedTotal
            ]);
            
            $itemDetails[] = [
                'id' => 'adjustment',
                'price' => $difference,
                'quantity' => 1,
                'name' => 'Price Adjustment'
            ];
        }

        // Customer details (keep existing logic)
        $customerDetails = [
            'first_name' => $request->first_name ?? explode(' ', $order->customer_name)[0],
            'last_name' => $request->last_name ?? (explode(' ', $order->customer_name, 2)[1] ?? ''),
            'email' => $request->email ?? $order->customer_email,
            'phone' => $request->phone ?? $order->customer_phone,
            'billing_address' => [
                'first_name' => $request->first_name ?? explode(' ', $order->customer_name)[0],
                'last_name' => $request->last_name ?? (explode(' ', $order->customer_name, 2)[1] ?? ''),
                'address' => $request->street_address ?? substr($order->shipping_address, 0, 200),
                'city' => substr($request->city_name ?? 'Jakarta', 0, 20),
                'postal_code' => $request->postal_code ?? '10000',
                'phone' => $request->phone ?? $order->customer_phone,
                'country_code' => 'IDN'
            ],
            'shipping_address' => [
                'first_name' => $request->first_name ?? explode(' ', $order->customer_name)[0],
                'last_name' => $request->last_name ?? (explode(' ', $order->customer_name, 2)[1] ?? ''),
                'address' => $request->street_address ?? substr($order->shipping_address, 0, 200),
                'city' => substr($request->city_name ?? 'Jakarta', 0, 20),
                'postal_code' => $request->postal_code ?? '10000',
                'phone' => $request->phone ?? $order->customer_phone,
                'country_code' => 'IDN'
            ]
        ];

        $transactionDetails = [
            'order_id' => $order->order_number,
            'gross_amount' => (int) $order->total_amount
        ];

        // Build payload
        $midtransPayload = [
            'transaction_details' => $transactionDetails,
            'customer_details' => $customerDetails,
            'item_details' => $itemDetails
        ];

        Log::info('Calling MidtransService with points-enabled payload', [
            'order_number' => $order->order_number,
            'gross_amount' => (int) $order->total_amount,
            'item_details_count' => count($itemDetails),
            'has_voucher_discount' => $discountAmount > 0,
            'has_points_discount' => isset($metaData) && ($metaData['points_info']['points_discount'] ?? 0) > 0
        ]);

        // Use MidtransService
        $response = $this->midtransService->createSnapToken($midtransPayload);
        
        if (isset($response['token'])) {
            Log::info('Midtrans Snap token created successfully with points support', [
                'order_number' => $order->order_number,
                'token_length' => strlen($response['token']),
                'total_discounts' => $discountAmount + ($metaData['points_info']['points_discount'] ?? 0)
            ]);

            return [
                'token' => $response['token'],
                'redirect_url' => $response['redirect_url'] ?? null,
            ];
        } else {
            Log::error('MidtransService returned no token', [
                'order_number' => $order->order_number,
                'response' => $response
            ]);
            return null;
        }

    } catch (\Exception $e) {
        Log::error('Exception in Midtrans payment creation with points support', [
            'order_number' => $order->order_number ?? 'unknown',
            'error' => $e->getMessage(),
            'error_line' => $e->getLine(),
            'error_file' => $e->getFile(),
        ]);
        
        return null;
    }
}

    // Keep ALL other methods exactly the same as working version
    private function getMockDestinations($search)
    {
        $mockDestinations = [];
        
        $searchLower = strtolower($search);
        
        if (strpos($searchLower, 'jakarta') !== false) {
            $mockDestinations = [
                [
                    'location_id' => 'mock_jkt_001',
                    'subdistrict_name' => 'Menteng',
                    'district_name' => 'Menteng',
                    'city_name' => 'Jakarta Pusat',
                    'province_name' => 'DKI Jakarta',
                    'zip_code' => '10310',
                    'label' => 'Menteng, Jakarta Pusat, DKI Jakarta 10310',
                    'display_name' => 'Menteng, Jakarta Pusat',
                    'full_address' => 'Menteng, Jakarta Pusat, DKI Jakarta 10310'
                ],
                [
                    'location_id' => 'mock_jkt_002',
                    'subdistrict_name' => 'Kebayoran Lama',
                    'district_name' => 'Kebayoran Lama',
                    'city_name' => 'Jakarta Selatan',
                    'province_name' => 'DKI Jakarta',
                    'zip_code' => '12240',
                    'label' => 'Kebayoran Lama, Jakarta Selatan, DKI Jakarta 12240',
                    'display_name' => 'Kebayoran Lama, Jakarta Selatan',
                    'full_address' => 'Kebayoran Lama, Jakarta Selatan, DKI Jakarta 12240'
                ]
            ];
        } elseif (strpos($searchLower, 'bandung') !== false) {
            $mockDestinations = [
                [
                    'location_id' => 'mock_bdg_001',
                    'subdistrict_name' => 'Sukasari',
                    'district_name' => 'Sukasari',
                    'city_name' => 'Bandung',
                    'province_name' => 'Jawa Barat',
                    'zip_code' => '40164',
                    'label' => 'Sukasari, Bandung, Jawa Barat 40164',
                    'display_name' => 'Sukasari, Bandung',
                    'full_address' => 'Sukasari, Bandung, Jawa Barat 40164'
                ]
            ];
        } elseif (strpos($searchLower, 'surabaya') !== false) {
            $mockDestinations = [
                [
                    'location_id' => 'mock_sby_001',
                    'subdistrict_name' => 'Gubeng',
                    'district_name' => 'Gubeng',
                    'city_name' => 'Surabaya',
                    'province_name' => 'Jawa Timur',
                    'zip_code' => '60281',
                    'label' => 'Gubeng, Surabaya, Jawa Timur 60281',
                    'display_name' => 'Gubeng, Surabaya',
                    'full_address' => 'Gubeng, Surabaya, Jawa Timur 60281'
                ]
            ];
        }
        
        if (empty($mockDestinations)) {
            $mockDestinations = [
                [
                    'location_id' => 'mock_generic_001',
                    'subdistrict_name' => ucfirst($search),
                    'district_name' => ucfirst($search),
                    'city_name' => ucfirst($search),
                    'province_name' => 'Indonesia',
                    'zip_code' => '10000',
                    'label' => ucfirst($search) . ', Indonesia 10000',
                    'display_name' => ucfirst($search),
                    'full_address' => ucfirst($search) . ', Indonesia 10000'
                ]
            ];
        }
        
        Log::info('Returning mock destinations for search: ' . $search, [
            'count' => count($mockDestinations)
        ]);
        
        return response()->json([
            'success' => true,
            'total' => count($mockDestinations),
            'data' => $mockDestinations,
            'note' => 'Mock data - API not available'
        ]);
    }

    private function getOriginIdFromEnv()
    {
        $originCityName = env('STORE_ORIGIN_CITY_NAME', 'jakarta selatan');
        $originCityIdFallback = env('STORE_ORIGIN_CITY_ID', 158);
        
        Log::info('Getting origin from .env', [
            'configured_city_name' => $originCityName,
            'configured_city_id_fallback' => $originCityIdFallback
        ]);

        try {
            $response = Http::timeout(10)->withHeaders([
                'key' => $this->rajaOngkirApiKey
            ])->get($this->rajaOngkirBaseUrl . '/destination/domestic-destination', [
                'search' => strtolower($originCityName),
                'limit' => 1
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['data'][0])) {
                    $foundOrigin = $data['data'][0];
                    Log::info('Found origin via API search', [
                        'origin_id' => $foundOrigin['id'],
                        'origin_label' => $foundOrigin['label'],
                        'search_term' => $originCityName
                    ]);
                    return $foundOrigin['id'];
                }
            }
        } catch (\Exception $e) {
            Log::warning('Error searching origin city via API: ' . $e->getMessage());
        }
        
        Log::info('Using fallback origin ID from .env', [
            'fallback_origin_id' => $originCityIdFallback
        ]);
        
        return $originCityIdFallback;
    }

    private function calculateRealShipping($originId, $destinationId, $weight)
    {
        $couriers = ['jne'];
        $shippingOptions = [];

        $endpoints = ['/cost', '/shipping/cost', '/destination/cost', '/calculate'];
        
        foreach ($endpoints as $endpoint) {
            try {
                $response = Http::timeout(15)->withHeaders([
                    'key' => $this->rajaOngkirApiKey
                ])->post($this->rajaOngkirBaseUrl . $endpoint, [
                    'origin' => $originId,
                    'destination' => $destinationId,
                    'weight' => $weight,
                    'courier' => 'jne'
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    
                    Log::info("Found working cost endpoint: {$endpoint} for JNE courier");
                    
                    $parsed = $this->parseShippingResponse($data, 'jne');
                    $shippingOptions = array_merge($shippingOptions, $parsed);
                }
            } catch (\Exception $e) {
                continue;
            }
            
            if (!empty($shippingOptions)) {
                break;
            }
        }

        return $shippingOptions;
    }

    private function parseShippingResponse($data, $courier)
    {
        return [];
    }

    private function getMockShippingOptions($weight, $destinationLabel = '')
    {
        $basePrice = max(10000, $weight * 5);
        
        $distanceFactor = 1;
        $originCity = env('STORE_ORIGIN_CITY_NAME', 'jakarta');
        
        if (stripos($destinationLabel, strtolower($originCity)) !== false) {
            $distanceFactor = 1;
        } elseif (stripos($destinationLabel, 'jakarta') !== false && stripos($originCity, 'jakarta') !== false) {
            $distanceFactor = 1;
        } elseif (stripos($destinationLabel, 'bandung') !== false || stripos($destinationLabel, 'jawa barat') !== false) {
            $distanceFactor = 1.2;
        } elseif (stripos($destinationLabel, 'surabaya') !== false || stripos($destinationLabel, 'jawa timur') !== false) {
            $distanceFactor = 1.5;
        } elseif (stripos($destinationLabel, 'medan') !== false || stripos($destinationLabel, 'sumatera') !== false) {
            $distanceFactor = 2;
        } else {
            $distanceFactor = 1.8;
        }
        
        $adjustedPrice = $basePrice * $distanceFactor;

        return [
            [
                'courier' => 'JNE',
                'courier_name' => 'Jalur Nugraha Ekakurir (JNE)',
                'service' => 'REG',
                'description' => 'Layanan Reguler',
                'cost' => (int) $adjustedPrice,
                'etd' => '2-3',
                'formatted_cost' => 'Rp ' . number_format($adjustedPrice, 0, ',', '.'),
                'formatted_etd' => '2-3 hari',
                'is_mock' => true,
                'type' => 'mock',
                'origin_info' => env('STORE_ORIGIN_CITY_NAME', 'jakarta'),
                'recommended' => true
            ],
            [
                'courier' => 'JNE',
                'courier_name' => 'Jalur Nugraha Ekakurir (JNE)',
                'service' => 'YES',
                'description' => 'Yakin Esok Sampai',
                'cost' => (int) ($adjustedPrice * 1.8),
                'etd' => '1',
                'formatted_cost' => 'Rp ' . number_format($adjustedPrice * 1.8, 0, ',', '.'),
                'formatted_etd' => '1 hari',
                'is_mock' => true,
                'type' => 'mock',
                'origin_info' => env('STORE_ORIGIN_CITY_NAME', 'jakarta'),
                'recommended' => false
            ],
            [
                'courier' => 'JNE',
                'courier_name' => 'Jalur Nugraha Ekakurir (JNE)',
                'service' => 'OKE',
                'description' => 'Ongkos Kirim Ekonomis',
                'cost' => (int) ($adjustedPrice * 0.8),
                'etd' => '3-4',
                'formatted_cost' => 'Rp ' . number_format($adjustedPrice * 0.8, 0, ',', '.'),
                'formatted_etd' => '3-4 hari',
                'is_mock' => true,
                'type' => 'mock',
                'origin_info' => env('STORE_ORIGIN_CITY_NAME', 'jakarta'),
                'recommended' => false
            ]
        ];
    }

    private function getProvinces()
    {
        try {
            $response = Http::timeout(10)->withHeaders([
                'key' => $this->rajaOngkirApiKey
            ])->get($this->rajaOngkirBaseUrl . '/destination/province');

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['data']) && is_array($data['data'])) {
                    return array_map(function($province) {
                        return [
                            'province_id' => $province['id'],
                            'province' => $province['name']
                        ];
                    }, $data['data']);
                }
            }
        } catch (\Exception $e) {
            Log::error('RajaOngkir V2 provinces API error: ' . $e->getMessage());
        }

        return [];
    }

    private function getMajorCities()
    {
        $majorCityNames = ['jakarta', 'bandung', 'surabaya', 'medan', 'semarang', 'makassar'];
        $cities = [];
        
        foreach ($majorCityNames as $cityName) {
            try {
                $response = Http::timeout(10)->withHeaders([
                    'key' => $this->rajaOngkirApiKey
                ])->get($this->rajaOngkirBaseUrl . '/destination/domestic-destination', [
                    'search' => $cityName,
                    'limit' => 1
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    
                    if (isset($data['data'][0])) {
                        $location = $data['data'][0];
                        $cities[] = [
                            'name' => ucfirst($cityName),
                            'location_id' => $location['id'],
                            'label' => $location['label'],
                            'city_name' => $location['city_name'],
                            'province_name' => $location['province_name']
                        ];
                    }
                }
            } catch (\Exception $e) {
                Log::error("Error getting major city {$cityName}: " . $e->getMessage());
            }
        }

        return $cities;
    }

    private function autoSortShippingOptions($options)
    {
        usort($options, function($a, $b) {
            if ($a['recommended'] && !$b['recommended']) return -1;
            if (!$a['recommended'] && $b['recommended']) return 1;
            
            $etdA = $this->parseEtd($a['etd']);
            $etdB = $this->parseEtd($b['etd']);
            
            if ($etdA !== $etdB) {
                return $etdA <=> $etdB;
            }
            
            return $a['cost'] <=> $b['cost'];
        });
        
        return $options;
    }

    private function parseEtd($etd)
    {
        if (strpos($etd, '-') !== false) {
            $parts = explode('-', $etd);
            return (intval($parts[0]) + intval($parts[1])) / 2;
        }
        
        return intval($etd);
    }

    // Keep ALL payment methods exactly the same as working version
    public function payment($orderNumber)
    {
        Log::info('Payment page accessed', ['order_number' => $orderNumber]);
        
        $order = Order::with('orderItems.product')
                     ->where('order_number', $orderNumber)
                     ->firstOrFail();
        
        if ($order->status === 'paid') {
            return redirect()->route('checkout.success', ['orderNumber' => $orderNumber]);
        }
        
        if ($order->payment_method === 'cod') {
            return redirect()->route('checkout.success', ['orderNumber' => $orderNumber]);
        }
        
        $snapToken = session('snap_token') ?: $order->snap_token;
        
        if (!$snapToken && $order->status === 'pending') {
            $cartItems = collect();
            foreach ($order->orderItems as $item) {
                $cartItems->push([
                    'id' => $item->product_id,
                    'name' => $item->product_name,
                    'price' => $item->product_price,
                    'quantity' => $item->quantity,
                    'subtotal' => $item->total_price
                ]);
            }
            
            $simulatedRequest = (object) [
                'first_name' => explode(' ', $order->customer_name)[0],
                'last_name' => explode(' ', $order->customer_name, 2)[1] ?? '',
                'email' => $order->customer_email,
                'phone' => $order->customer_phone,
                'address' => $order->shipping_address,
                'destination_label' => $order->shipping_destination_label,
                'postal_code' => $order->shipping_postal_code,
                'payment_method' => $order->payment_method
            ];
            
            $midtrans = $this->createMidtransPayment($order, $cartItems, $simulatedRequest);
            
            if ($midtrans && isset($midtrans['token'])) {
                $order->update(['snap_token' => $midtrans['token']]);
                $snapToken = $midtrans['token'];
            }
        }
        
        if (!$snapToken) {
            return redirect()->route('checkout.success', ['orderNumber' => $orderNumber])
                           ->with('error', 'Payment session expired. Please contact support.');
        }

        return view('frontend.checkout.payment', compact('order', 'snapToken'));
    }

    public function paymentSuccess(Request $request)
{
    $orderNumber = $request->get('order_id');
    
    if ($orderNumber) {
        $order = Order::where('order_number', $orderNumber)->first();
        
        if ($order && $order->status === 'pending') {
            // Update order status to paid
            $order->update(['status' => 'paid']);
            
            // Log successful payment
            Log::info('Payment successful via callback', [
                'order_number' => $orderNumber,
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'amount' => $order->total_amount
            ]);
        }
        
        return redirect()->route('checkout.success', ['orderNumber' => $orderNumber])
                       ->with('success', 'Payment completed! We are processing your order.');
    }
    
    return redirect()->route('home')->with('success', 'Payment completed successfully!');
}

    public function paymentPending(Request $request)
{
    $orderNumber = $request->get('order_id');
    
    if ($orderNumber) {
        return redirect()->route('checkout.success', ['orderNumber' => $orderNumber])
                       ->with('warning', 'Payment is being processed. You will receive confirmation shortly.');
    }
    
    return redirect()->route('home')->with('warning', 'Payment is being processed.');
}

    public function paymentError(Request $request)
{
    $orderNumber = $request->get('order_id');
    
    if ($orderNumber) {
        $order = Order::where('order_number', $orderNumber)->first();
        
        if ($order && $order->status === 'pending') {
            // Optionally update status to failed
            // $order->update(['status' => 'failed']);
            
            Log::warning('Payment failed via callback', [
                'order_number' => $orderNumber,
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'amount' => $order->total_amount
            ]);
        }
        
        return redirect()->route('checkout.index')
                       ->with('error', 'Payment failed. Please try again.');
    }
    
    return redirect()->route('home')->with('error', 'Payment failed.');
}

    public function paymentFinish(Request $request)
    {
        $orderNumber = $request->get('order_id');
        
        if ($orderNumber) {
            return redirect()->route('checkout.payment-success', ['order_id' => $orderNumber]);
        }
        
        return redirect()->route('home')->with('success', 'Payment completed successfully!');
    }

    public function paymentUnfinish(Request $request)
    {
        $orderNumber = $request->get('order_id');
        
        if ($orderNumber) {
            return redirect()->route('checkout.success', ['orderNumber' => $orderNumber])
                           ->with('warning', 'Payment was not completed. You can retry payment anytime.');
        }
        
        return redirect()->route('home')->with('warning', 'Payment pending.');
    }

    public function getPaymentStatus($orderNumber)
    {
        try {
            $order = Order::where('order_number', $orderNumber)->first();
            
            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'total_amount' => $order->total_amount,
                'created_at' => $order->created_at,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error getting payment status: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get payment status'
            ], 500);
        }
    }

    public function retryPayment($orderNumber)
    {
        try {
            $order = Order::where('order_number', $orderNumber)->first();
            
            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found'
                ], 404);
            }
            
            if ($order->status === 'paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'Order already paid'
                ], 400);
            }
            
            $cartItems = collect();
            foreach ($order->orderItems as $item) {
                $cartItems->push([
                    'id' => $item->product_id,
                    'name' => $item->product_name,
                    'price' => $item->product_price,
                    'quantity' => $item->quantity,
                    'subtotal' => $item->total_price
                ]);
            }
            
            $simulatedRequest = (object) [
                'first_name' => explode(' ', $order->customer_name)[0],
                'last_name' => explode(' ', $order->customer_name, 2)[1] ?? '',
                'email' => $order->customer_email,
                'phone' => $order->customer_phone,
                'address' => $order->shipping_address,
                'destination_label' => $order->shipping_destination_label,
                'postal_code' => $order->shipping_postal_code,
                'payment_method' => $order->payment_method
            ];
            
            $midtrans = $this->createMidtransPayment($order, $cartItems, $simulatedRequest);
            
            if ($midtrans && isset($midtrans['token'])) {
                $order->update(['snap_token' => $midtrans['token']]);
                
                return response()->json([
                    'success' => true,
                    'snap_token' => $midtrans['token'],
                    'order_number' => $order->order_number,
                    'message' => 'Payment session created successfully'
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create payment session'
            ], 500);
            
        } catch (\Exception $e) {
            Log::error('Error retrying payment: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retry payment'
            ], 500);
        }
    }

    public function paymentNotification(Request $request)
    {
        try {
            Log::info('=== MIDTRANS WEBHOOK RECEIVED ===', [
                'timestamp' => now()->toISOString(),
                'payload' => $request->all(),
                'order_id' => $request->get('order_id'),
                'transaction_status' => $request->get('transaction_status')
            ]);
            
            $notification = $this->midtransService->handleNotification($request->all());
            
            if (!$notification) {
                return response()->json([
                    'status' => 'failed', 
                    'message' => 'Invalid notification'
                ], 400);
            }

            $order = Order::where('order_number', $notification['order_id'])->first();
            
            if (!$order) {
                return response()->json([
                    'status' => 'success', 
                    'message' => 'Order not found but notification received'
                ]);
            }

            $oldStatus = $order->status;
            $newStatus = $this->mapMidtransToOrderStatus(
                $notification['payment_status'] ?? 'unknown',
                $notification['transaction_status'] ?? 'unknown',
                $notification['fraud_status'] ?? 'accept'
            );
            
            $order->update([
                'status' => $newStatus,
                'payment_response' => json_encode($notification['raw_notification'] ?? $request->all())
            ]);
            
            Log::info('Order status updated', [
                'order_number' => $notification['order_id'],
                'old_status' => $oldStatus,
                'new_status' => $newStatus
            ]);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Notification processed successfully'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Webhook processing error: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error', 
                'message' => 'Processing failed'
            ], 200);
        }
    }

    private function mapMidtransToOrderStatus($paymentStatus, $transactionStatus, $fraudStatus = 'accept')
    {
        if ($fraudStatus === 'challenge') {
            return 'pending';
        }
        
        if ($fraudStatus === 'deny') {
            return 'cancelled';
        }

        switch ($paymentStatus) {
            case 'paid':
                return 'paid';
            case 'pending':
                return 'pending';
            case 'failed':
            case 'cancelled':
                return 'cancelled';
            case 'refunded':
                return 'refund';
            case 'challenge':
                return 'pending';
            default:
                return 'pending';
        }
        
    }
    public function success($orderNumber)
{
    try {
        // Find order by order number
        $order = Order::where('order_number', $orderNumber)
                      ->with(['orderItems.product', 'user'])
                      ->first();
        
        if (!$order) {
            return redirect()->route('home')
                           ->with('error', 'Order not found.');
        }
        
        // Check if user owns this order (if logged in)
        if (Auth::check() && $order->user_id !== Auth::id()) {
            return redirect()->route('home')
                           ->with('error', 'Unauthorized access to order.');
        }
        
        // Calculate points if order is paid and user exists
        $pointsData = null;
        if ($order->status === 'paid' && $order->user) {
            $pointsData = $this->calculateOrderPoints($order);
        }
        
        return view('frontend.checkout.success', compact('order', 'pointsData'));
        
    } catch (\Exception $e) {
        Log::error('Error showing order success page', [
            'order_number' => $orderNumber,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return redirect()->route('home')
                       ->with('error', 'Unable to load order details.');
    }
}
private function calculateOrderPoints(Order $order)
{
    try {
        $user = $order->user;
        if (!$user) {
            return null;
        }
        
        $pointsEarned = 0;
        $pointsPercentage = 1; // Default 1%
        $userTier = 'basic';
        $tierLabel = 'Basic Member';
        
        // Get user tier and points percentage
        if (method_exists($user, 'getCustomerTier')) {
            $userTier = $user->getCustomerTier();
        }
        
        if (method_exists($user, 'getCustomerTierLabel')) {
            $tierLabel = $user->getCustomerTierLabel();
        }
        
        if (method_exists($user, 'getPointsPercentage')) {
            $pointsPercentage = $user->getPointsPercentage();
        }
        
        // Calculate points earned
        if (method_exists($user, 'calculatePointsFromPurchase')) {
            $pointsEarned = $user->calculatePointsFromPurchase($order->total_amount);
        } else {
            // Fallback calculation
            $pointsEarned = round(($order->total_amount * $pointsPercentage) / 100, 2);
        }
        
        return [
            'points_earned' => $pointsEarned,
            'points_percentage' => $pointsPercentage,
            'user_tier' => $userTier,
            'tier_label' => $tierLabel,
            'order_amount' => $order->total_amount,
            'calculation_text' => "Rp " . number_format($order->total_amount, 0, ',', '.') . " × {$pointsPercentage}% = " . number_format($pointsEarned, 0, ',', '.') . " points"
        ];
        
    } catch (\Exception $e) {
        Log::error('Error calculating order points', [
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'error' => $e->getMessage()
        ]);
        
        return null;
    }
}

}