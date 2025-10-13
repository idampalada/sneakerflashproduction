{{-- File: resources/views/frontend/orders/invoice.blade.php --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - Order #{{ $order->order_number }}</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <style>
        @media print {
            body { -webkit-print-color-adjust: exact; }
            .no-print { display: none !important; }
            .print-break { page-break-after: always; }
        }
        
        .invoice-header {
            background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%);
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="max-w-4xl mx-auto py-8 px-4">
        <!-- Print/Download Buttons -->
        <div class="no-print mb-6 flex gap-3">
            <button onclick="window.print()" 
                    class="px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition-colors">
                🖨️ Print Invoice
            </button>
            <a href="{{ route('orders.show', $order->order_number) }}" 
               class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                ← Back to Order
            </a>
        </div>

        <!-- Invoice Container -->
        <div class="bg-white shadow-lg rounded-lg overflow-hidden">
            <!-- Invoice Header -->
            <div class="invoice-header text-white p-8">
                <div class="flex justify-between items-start">
                    <div>
                        <h1 class="text-3xl font-bold mb-2">SNKRS FLASH</h1>
                        <p class="text-yellow-100">Premium Sneakers Store</p>
                        <div class="mt-4 text-sm text-yellow-100">
                            <p>📧 support@sneakersflash.com</p>
                            <p>📱 +62-XXX-XXXX-XXXX</p>
                            <p>🌐 www.sneakersflash.com</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <h2 class="text-2xl font-bold mb-2">INVOICE</h2>
                        <div class="text-yellow-100 space-y-1">
                            <p><strong>Invoice #:</strong> {{ $order->order_number }}</p>
                            <p><strong>Date:</strong> {{ $order->created_at->format('d M Y') }}</p>
                            <p><strong>Status:</strong> 
                                <span class="px-2 py-1 bg-white text-yellow-600 rounded-full text-xs font-medium">
                                    {{ ucfirst($order->status) }}
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customer & Order Info -->
            <div class="p-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                    <!-- Bill To -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 border-b-2 border-yellow-200 pb-2">
                            Bill To
                        </h3>
                        <div class="space-y-2 text-gray-700">
                            <p class="font-medium text-lg">{{ $order->customer_name }}</p>
                            <p>{{ $order->customer_email }}</p>
                            <p>{{ $order->customer_phone }}</p>
                        </div>
                    </div>

                    <!-- Ship To -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 border-b-2 border-yellow-200 pb-2">
                            Ship To
                        </h3>
                        <div class="space-y-1 text-gray-700">
                            <p class="font-medium">{{ $order->customer_name }}</p>
                            <p>{{ $order->shipping_address }}</p>
                            <p>{{ $order->shipping_city }}, {{ $order->shipping_province }}</p>
                            <p>{{ $order->shipping_postal_code }}</p>
                        </div>
                    </div>
                </div>

                <!-- Order Details -->
                <div class="mb-8">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 border-b-2 border-yellow-200 pb-2">
                        Order Details
                    </h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div class="p-3 bg-gray-50 rounded-lg">
                            <p class="text-gray-600">Payment Method</p>
                            <p class="font-medium">{{ strtoupper($order->payment_method) }}</p>
                        </div>
                        <div class="p-3 bg-gray-50 rounded-lg">
                            <p class="text-gray-600">Order Date</p>
                            <p class="font-medium">{{ $order->created_at->format('d M Y H:i') }}</p>
                        </div>
                        @if($order->tracking_number)
                        <div class="p-3 bg-gray-50 rounded-lg">
                            <p class="text-gray-600">Tracking Number</p>
                            <p class="font-medium">{{ $order->tracking_number }}</p>
                        </div>
                        @endif
                        <div class="p-3 bg-gray-50 rounded-lg">
                            <p class="text-gray-600">Shipping Service</p>
                            <p class="font-medium">{{ $order->shipping_service ?? 'Standard' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Items Table -->
                <div class="mb-8">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 border-b-2 border-yellow-200 pb-2">
                        Items Purchased
                    </h3>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse border border-gray-300">
                            <thead>
                                <tr class="bg-yellow-50 border-yellow-200">
                                    <th class="border border-gray-300 px-4 py-3 text-left font-semibold">Item</th>
                                    <th class="border border-gray-300 px-4 py-3 text-center font-semibold">SKU</th>
                                    <th class="border border-gray-300 px-4 py-3 text-center font-semibold">Qty</th>
                                    <th class="border border-gray-300 px-4 py-3 text-right font-semibold">Unit Price</th>
                                    <th class="border border-gray-300 px-4 py-3 text-right font-semibold">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->orderItems as $item)
                                <tr class="hover:bg-yellow-50">
                                    <td class="border border-gray-300 px-4 py-4 bg-white">
                                        <div class="flex items-center space-x-3">
                                            @if($item->product && $item->product->featured_image)
                                                <img src="{{ $item->product->featured_image }}" 
                                                     alt="{{ $item->product_name }}" 
                                                     class="h-12 w-12 object-cover rounded border">
                                            @else
                                                <div class="h-12 w-12 bg-gray-200 rounded border flex items-center justify-center">
                                                    <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                    </svg>
                                                </div>
                                            @endif
                                            <div>
                                                <p class="font-medium text-gray-900">{{ $item->product_name }}</p>
                                                <p class="text-sm text-gray-600">{{ Str::limit($item->product_name, 50) }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="border border-gray-300 px-4 py-4 text-center text-sm bg-white">
                                        {{ $item->product_sku ?? 'N/A' }}
                                    </td>
                                    <td class="border border-gray-300 px-4 py-4 text-center font-medium bg-white">
                                        {{ $item->quantity }}
                                    </td>
                                    <td class="border border-gray-300 px-4 py-4 text-right bg-white">
                                        Rp {{ number_format($item->product_price, 0, ',', '.') }}
                                    </td>
                                    <td class="border border-gray-300 px-4 py-4 text-right font-medium bg-white">
                                        Rp {{ number_format($item->total_price, 0, ',', '.') }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Summary Section -->
                <div class="flex justify-end">
                    <div class="w-full max-w-sm">
                        <div class="bg-yellow-50 p-6 rounded-lg border border-yellow-200">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Order Summary</h3>
                            
                            <div class="space-y-3">
                                <div class="flex justify-between text-gray-700">
                                    <span>Subtotal:</span>
                                    <span>Rp {{ number_format($order->subtotal, 0, ',', '.') }}</span>
                                </div>
                                
                                @if($order->shipping_cost > 0)
                                <div class="flex justify-between text-gray-700">
                                    <span>Shipping Cost:</span>
                                    <span>Rp {{ number_format($order->shipping_cost, 0, ',', '.') }}</span>
                                </div>
                                @endif
                                
                                @if($order->discount_amount > 0)
                                <div class="flex justify-between text-yellow-600">
                                    <span>Discount:</span>
                                    <span>-Rp {{ number_format($order->discount_amount, 0, ',', '.') }}</span>
                                </div>
                                @endif
                                
                                <hr class="border-gray-300">
                                
                                <div class="flex justify-between text-lg font-bold text-gray-900">
                                    <span>Total:</span>
                                    <span>Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                                </div>
                                
                                @if($order->payment_method !== 'cod' && $order->status !== 'pending')
                                <div class="mt-4 p-3 bg-yellow-100 border border-yellow-300 rounded">
                                    <div class="flex items-center">
                                        <span class="text-yellow-700 font-medium text-sm">✅ Payment Completed</span>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="mt-12 pt-8 border-t border-gray-200">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 text-sm text-gray-600">
                        <div>
                            <h4 class="font-semibold text-gray-900 mb-2">Terms & Conditions</h4>
                            <ul class="space-y-1">
                                <li>• All sales are final unless item is defective</li>
                                <li>• Returns must be initiated within 7 days</li>
                                <li>• Items must be in original condition</li>
                                <li>• Shipping costs are non-refundable</li>
                            </ul>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-900 mb-2">Contact Support</h4>
                            <p>If you have any questions about this invoice, please contact us:</p>
                            <div class="mt-2 space-y-1">
                                <p>📧 support@snkrsflash.com</p>
                                <p>📱 WhatsApp: +62-XXX-XXXX-XXXX</p>
                                <p>⏰ Mon-Fri, 9:00 AM - 6:00 PM WIB</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-6 text-center text-xs text-gray-500">
                        <p>This is a computer-generated invoice. No signature required.</p>
                        <p class="mt-1">Generated on {{ now()->format('d M Y H:i:s') }} WIB</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Print-specific JavaScript -->
    <script>
        // Auto-print functionality (optional)
        // window.addEventListener('load', function() {
        //     setTimeout(function() {
        //         window.print();
        //     }, 500);
        // });
    </script>
</body>
</html>