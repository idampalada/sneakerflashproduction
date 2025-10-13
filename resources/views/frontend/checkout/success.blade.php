@extends('layouts.app')

@section('title', 'Order Confirmed - SneakerFlash')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Success Icon and Header -->
        <div class="text-center mb-8">
            <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Order Confirmed!</h1>
            <p class="text-gray-600">Thank you for your order. We've received your order and will process it shortly.</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Left Column - Order Details -->
            <div class="lg:col-span-2 space-y-6">
                
                <!-- Order Information -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-xl font-semibold text-gray-900">Order #{{ $order->order_number }}</h2>
                        <span class="px-3 py-1 bg-green-100 text-green-800 text-sm font-medium rounded-full">
                            {{ ucfirst($order->status) }}
                        </span>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <div>
                            <p class="text-sm font-medium text-gray-700">Order Date:</p>
                            <p class="text-gray-900">{{ $order->created_at->format('F d, Y \a\t g:i A') }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-700">Payment Method:</p>
                            <p class="text-gray-900">{{ strtoupper($order->payment_method) }}</p>
                        </div>
                    </div>
                    
                    <!-- Payment Confirmation -->
                    @if($order->status === 'paid')
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <div>
                                <h3 class="text-sm font-medium text-green-800">Payment Confirmed</h3>
                                <p class="text-sm text-green-700">Your payment has been successfully processed. We will start preparing your order for shipment.</p>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Points Earned Section - NEW -->
                @if($order->status === 'paid' && $order->user)
                    @php
                        $user = $order->user;
                        $pointsEarned = 0;
                        $pointsPercentage = 1; // Default 1%
                        
                        // Calculate points based on user tier
                        if (method_exists($user, 'getPointsPercentage')) {
                            $pointsPercentage = $user->getPointsPercentage();
                        }
                        
                        if (method_exists($user, 'calculatePointsFromPurchase')) {
                            $pointsEarned = $user->calculatePointsFromPurchase($order->total_amount);
                        } else {
                            // Fallback calculation
                            $pointsEarned = round(($order->total_amount * $pointsPercentage) / 100, 2);
                        }
                        
                        $userTier = method_exists($user, 'getCustomerTier') ? $user->getCustomerTier() : 'basic';
                        $tierLabel = method_exists($user, 'getCustomerTierLabel') ? $user->getCustomerTierLabel() : 'Basic Member';
                    @endphp
                    
                    <div class="bg-gradient-to-r from-yellow-400 to-orange-500 rounded-lg shadow-lg text-white p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h3 class="text-2xl font-bold">🪙 Points Earned!</h3>
                                <p class="text-white opacity-90">Congratulations! You've earned points from this purchase.</p>
                            </div>
                            <div class="text-right">
                                <div class="text-3xl font-bold">+{{ number_format($pointsEarned, 0, ',', '.') }}</div>
                                <div class="text-sm opacity-90">Points</div>
                            </div>
                        </div>
                        
                        <div class="bg-white bg-opacity-20 rounded-lg p-4">
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <div class="opacity-90">Your Tier:</div>
                                    <div class="font-semibold">
                                        @if($userTier === 'ultimate')
                                            💎 {{ $tierLabel }}
                                        @elseif($userTier === 'advance')
                                            🥇 {{ $tierLabel }}
                                        @else
                                            🥉 {{ $tierLabel }}
                                        @endif
                                    </div>
                                </div>
                                <div>
                                    <div class="opacity-90">Points Rate:</div>
                                    <div class="font-semibold">{{ $pointsPercentage }}% of purchase</div>
                                </div>
                            </div>
                            
                            <div class="mt-3 pt-3 border-t border-white border-opacity-30">
                                <div class="text-xs opacity-90">
                                    Purchase Amount: Rp {{ number_format($order->total_amount, 0, ',', '.') }} × {{ $pointsPercentage }}% = {{ number_format($pointsEarned, 0, ',', '.') }} points
                                </div>
                            </div>
                        </div>
                        
                        @if(method_exists($user, 'points_balance'))
                        <div class="mt-4 text-center">
                            <a href="{{ route('profile.index') }}" class="bg-white text-yellow-600 px-6 py-2 rounded-lg font-semibold hover:bg-gray-100 transition-colors">
                                View Points Balance
                            </a>
                        </div>
                        @endif
                    </div>
                @endif

                <!-- Order Items -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Order Items ({{ $order->orderItems->count() }} item{{ $order->orderItems->count() != 1 ? 's' : '' }})</h3>
                    
                    <div class="space-y-4">
                        @foreach($order->orderItems as $item)
                        <div class="flex items-center space-x-4 p-4 border border-gray-200 rounded-lg">
                            @if($item->product && $item->product->image_url)
                            <img src="{{ $item->product->image_url }}" alt="{{ $item->product_name }}" class="w-16 h-16 object-cover rounded">
                            @else
                            <div class="w-16 h-16 bg-gray-200 rounded flex items-center justify-center">
                                <svg class="w-8 h-8 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            @endif
                            
                            <div class="flex-1">
                                <h4 class="font-medium text-gray-900">{{ $item->product_name }}</h4>
                                @if($item->product && $item->product->brand)
                                <p class="text-sm text-gray-600">{{ $item->product->brand }}</p>
                                @endif
                                <p class="text-sm text-gray-600">Quantity: {{ $item->quantity }}</p>
                            </div>
                            
                            <div class="text-right">
                                <p class="font-medium text-gray-900">Rp {{ number_format($item->total_price, 0, ',', '.') }}</p>
                                @if($item->quantity > 1)
                                <p class="text-sm text-gray-600">Rp {{ number_format($item->product_price, 0, ',', '.') }} each</p>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- Order Total -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Order Summary</h3>
                    
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Subtotal:</span>
                            <span class="text-gray-900">Rp {{ number_format($order->orderItems->sum('total_price'), 0, ',', '.') }}</span>
                        </div>
                        
                        @if($order->shipping_cost > 0)
                        <div class="flex justify-between">
                            <span class="text-gray-600">Shipping:</span>
                            <span class="text-gray-900">Rp {{ number_format($order->shipping_cost, 0, ',', '.') }}</span>
                        </div>
                        @endif
                        
                        @if($order->discount_amount > 0)
                        <div class="flex justify-between text-green-600">
                            <span>Discount:</span>
                            <span>-Rp {{ number_format($order->discount_amount, 0, ',', '.') }}</span>
                        </div>
                        @endif
                        
                        <div class="border-t pt-3">
                            <div class="flex justify-between text-lg font-semibold">
                                <span class="text-gray-900">Total:</span>
                                <span class="text-gray-900">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column - Customer Information -->
            <div class="space-y-6">
                
                <!-- Customer Information -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Customer Information</h3>
                    
                    <div class="space-y-4">
                        <div>
                            <h4 class="text-sm font-medium text-gray-700 mb-2">Contact Details</h4>
                            <div class="text-sm text-gray-900 space-y-1">
                                <p><strong>Name:</strong> {{ $order->customer_name }}</p>
                                <p><strong>Email:</strong> {{ $order->customer_email }}</p>
                                <p><strong>Phone:</strong> {{ $order->customer_phone }}</p>
                            </div>
                        </div>
                        
                        <div>
                            <h4 class="text-sm font-medium text-gray-700 mb-2">Shipping Address</h4>
                            <div class="text-sm text-gray-900">
                                <p>{{ $order->shipping_address }}</p>
                                @if($order->shipping_destination_label)
                                <p class="text-gray-600">{{ $order->shipping_destination_label }}</p>
                                @endif
                                @if($order->shipping_postal_code)
                                <p class="text-gray-600">{{ $order->shipping_postal_code }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Next Steps -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-blue-900 mb-4">What's Next?</h3>
                    
                    <div class="space-y-3 text-sm text-blue-800">
                        <div class="flex items-start">
                            <span class="flex-shrink-0 w-6 h-6 bg-blue-200 rounded-full flex items-center justify-center mr-3 mt-0.5">
                                <span class="text-xs font-semibold">1</span>
                            </span>
                            <p>We'll prepare your order for shipment</p>
                        </div>
                        
                        <div class="flex items-start">
                            <span class="flex-shrink-0 w-6 h-6 bg-blue-200 rounded-full flex items-center justify-center mr-3 mt-0.5">
                                <span class="text-xs font-semibold">2</span>
                            </span>
                            <p>You'll receive tracking information via email</p>
                        </div>
                        
                        <div class="flex items-start">
                            <span class="flex-shrink-0 w-6 h-6 bg-blue-200 rounded-full flex items-center justify-center mr-3 mt-0.5">
                                <span class="text-xs font-semibold">3</span>
                            </span>
                            <p>Your order will be delivered to your address</p>
                        </div>
                        
                        @if($order->status === 'paid' && $pointsEarned > 0)
                        <div class="flex items-start">
                            <span class="flex-shrink-0 w-6 h-6 bg-yellow-200 rounded-full flex items-center justify-center mr-3 mt-0.5">
                                <span class="text-xs">🪙</span>
                            </span>
                            <p class="text-yellow-800">Your {{ number_format($pointsEarned, 0, ',', '.') }} points have been added to your account</p>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="space-y-3">
                    @if(Route::has('orders.index'))
                    <a href="{{ route('orders.index') }}" 
                       class="block w-full text-center bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700 transition-colors font-medium">
                        View All Orders
                    </a>
                    @endif
                    
                    <a href="{{ route('home') }}" 
                       class="block w-full text-center bg-gray-200 text-gray-800 py-3 px-4 rounded-lg hover:bg-gray-300 transition-colors font-medium">
                        Continue Shopping
                    </a>
                    
                    @if($order->status === 'paid' && method_exists($order->user, 'points_balance'))
                    <a href="{{ route('profile.index') }}" 
                       class="block w-full text-center bg-yellow-500 text-white py-3 px-4 rounded-lg hover:bg-yellow-600 transition-colors font-medium">
                        View Points Balance
                    </a>
                    @endif
                </div>

                <!-- Customer Support -->
                <div class="bg-gray-50 rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Need Help?</h3>
                    
                    <div class="space-y-3 text-sm">
                        <p class="text-gray-600">If you have any questions about your order, please contact our customer support:</p>
                        
                        <div class="space-y-2">
                            <p class="text-gray-900">
                                <strong>Email:</strong> support@sneakerflash.com
                            </p>
                            <p class="text-gray-900">
                                <strong>Phone:</strong> +62 21 1234 5678
                            </p>
                            <p class="text-gray-900">
                                <strong>Hours:</strong> Mon-Fri 9AM-6PM WIB
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection