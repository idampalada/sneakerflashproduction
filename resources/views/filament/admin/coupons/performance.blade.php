{{-- File: resources/views/filament/admin/coupons/performance.blade.php --}}

<div class="p-6">
    <div class="mb-6">
        <h3 class="text-xl font-semibold text-gray-900">{{ $coupon->name }} Performance</h3>
        <p class="text-sm text-gray-600">Code: <span class="font-mono bg-gray-100 px-2 py-1 rounded">{{ $coupon->code }}</span></p>
    </div>

    {{-- Key Metrics --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="text-2xl font-bold text-blue-600">{{ $metrics['redemption_rate'] ? number_format($metrics['redemption_rate'], 1) . '%' : 'Unlimited' }}</div>
            <div class="text-sm text-blue-700">Redemption Rate</div>
            <div class="text-xs text-gray-600 mt-1">{{ $coupon->used_count }} / {{ $coupon->usage_limit ?: '∞' }}</div>
        </div>

        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <div class="text-2xl font-bold text-green-600">Rp {{ number_format($metrics['total_savings_provided'], 0, ',', '.') }}</div>
            <div class="text-sm text-green-700">Total Savings Given</div>
            <div class="text-xs text-gray-600 mt-1">Lifetime discount amount</div>
        </div>

        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
            <div class="text-2xl font-bold text-purple-600">Rp {{ number_format($metrics['average_order_value'], 0, ',', '.') }}</div>
            <div class="text-sm text-purple-700">Avg Order Value</div>
            <div class="text-xs text-gray-600 mt-1">With this coupon</div>
        </div>

        <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
            <div class="text-2xl font-bold text-orange-600">Rp {{ number_format($metrics['total_revenue_generated'], 0, ',', '.') }}</div>
            <div class="text-sm text-orange-700">Total Revenue</div>
            <div class="text-xs text-gray-600 mt-1">From coupon orders</div>
        </div>
    </div>

    {{-- Additional Metrics --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        
        {{-- Usage Statistics --}}
        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <h4 class="font-semibold text-gray-900 mb-3">Usage Statistics</h4>
            <div class="space-y-2">
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Conversion Rate:</span>
                    <span class="text-sm font-medium">{{ number_format($metrics['conversion_rate'], 1) }}%</span>
                </div>
                @if($metrics['most_popular_day'])
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Most Popular Day:</span>
                    <span class="text-sm font-medium">{{ $metrics['most_popular_day'] }}</span>
                </div>
                @endif
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">First Used:</span>
                    <span class="text-sm font-medium">{{ $coupon->created_at->format('M j, Y') }}</span>
                </div>
                @if($coupon->expires_at)
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Expires:</span>
                    <span class="text-sm font-medium">{{ $coupon->expires_at->format('M j, Y') }}</span>
                </div>
                @endif
            </div>
        </div>

        {{-- Coupon Details --}}
        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <h4 class="font-semibold text-gray-900 mb-3">Coupon Details</h4>
            <div class="space-y-2">
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Type:</span>
                    <span class="text-sm font-medium">{{ ucfirst(str_replace('_', ' ', $coupon->type)) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Value:</span>
                    <span class="text-sm font-medium">{{ $coupon->formatted_value }}</span>
                </div>
                @if($coupon->minimum_amount)
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Min Amount:</span>
                    <span class="text-sm font-medium">{{ $coupon->formatted_minimum_amount }}</span>
                </div>
                @endif
                @if($coupon->maximum_discount)
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Max Discount:</span>
                    <span class="text-sm font-medium">Rp {{ number_format($coupon->maximum_discount, 0, ',', '.') }}</span>
                </div>
                @endif
            </div>
        </div>

        {{-- Status Info --}}
        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <h4 class="font-semibold text-gray-900 mb-3">Current Status</h4>
            <div class="space-y-2">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Status:</span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                        @if($coupon->status === 'active') bg-green-100 text-green-800
                        @elseif($coupon->status === 'expired') bg-red-100 text-red-800
                        @elseif($coupon->status === 'scheduled') bg-yellow-100 text-yellow-800
                        @else bg-gray-100 text-gray-800
                        @endif">
                        {{ $coupon->status_label }}
                    </span>
                </div>
                
                @if($coupon->is_expiring_soon)
                <div class="text-sm text-orange-600">
                    ⚠️ Expires in {{ $coupon->days_until_expiry }} days
                </div>
                @endif
                
                @if($coupon->usage_percentage)
                <div class="text-sm text-gray-600">
                    Usage: {{ number_format($coupon->usage_percentage, 1) }}% of limit
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Top Customers --}}
    @if(count($topCustomers) > 0)
    <div class="bg-white border border-gray-200 rounded-lg p-4">
        <h4 class="font-semibold text-gray-900 mb-4">Top Customers Using This Coupon</h4>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Usage Count</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Total Spent</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Avg Order</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($topCustomers as $customer)
                    <tr>
                        <td class="px-4 py-2 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ $customer['user']->name ?? 'Unknown' }}</div>
                            <div class="text-sm text-gray-500">{{ $customer['user']->email ?? 'No email' }}</div>
                        </td>
                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900">
                            {{ $customer['usage_count'] }}x
                        </td>
                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900">
                            Rp {{ number_format($customer['total_spent'], 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900">
                            Rp {{ number_format($customer['average_order'], 0, ',', '.') }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Usage Trend (if available) --}}
    @if(isset($metrics['usage_trend']) && count($metrics['usage_trend']) > 0)
    <div class="mt-6 bg-white border border-gray-200 rounded-lg p-4">
        <h4 class="font-semibold text-gray-900 mb-4">Usage Trend (Last 30 Days)</h4>
        <div class="text-sm text-gray-600">
            @foreach($metrics['usage_trend']->take(7) as $trend)
                <div class="flex justify-between py-1">
                    <span>{{ \Carbon\Carbon::parse($trend->date)->format('M j') }}</span>
                    <span>{{ $trend->usage }} uses</span>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    <div class="mt-6 text-xs text-gray-500">
        <p><strong>Note:</strong> Performance data is calculated in real-time based on completed orders. Metrics may take a few minutes to update after recent coupon usage.</p>
    </div>
</div>