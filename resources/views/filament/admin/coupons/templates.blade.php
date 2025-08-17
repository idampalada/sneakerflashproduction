{{-- File: resources/views/filament/admin/coupons/templates.blade.php --}}

<div class="p-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Coupon Templates</h3>
    <p class="text-sm text-gray-600 mb-6">Choose a template to quickly create common types of coupons.</p>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        
        {{-- Welcome Discount Template --}}
        <div class="bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200 rounded-lg p-4 hover:shadow-md transition-shadow">
            <div class="flex items-center mb-3">
                <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4V2a1 1 0 011-1h8a1 1 0 011 1v2m0 0V3a1 1 0 011 1v1H6V4a1 1 0 011-1V2z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 8h12v10a2 2 0 01-2 2H8a2 2 0 01-2-2V8z"></path>
                    </svg>
                </div>
                <h4 class="ml-3 font-semibold text-gray-900">Welcome Discount</h4>
            </div>
            <p class="text-sm text-gray-600 mb-4">10% off for new customers with minimum purchase</p>
            <div class="text-xs text-gray-500">
                <div>• Type: Percentage (10%)</div>
                <div>• Min Amount: Rp 100,000</div>
                <div>• Usage Limit: 1,000</div>
                <div>• Expires: 3 months</div>
            </div>
        </div>

        {{-- Free Shipping Template --}}
        <div class="bg-gradient-to-br from-green-50 to-green-100 border border-green-200 rounded-lg p-4 hover:shadow-md transition-shadow">
            <div class="flex items-center mb-3">
                <div class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                </div>
                <h4 class="ml-3 font-semibold text-gray-900">Free Shipping</h4>
            </div>
            <p class="text-sm text-gray-600 mb-4">Free shipping for orders above minimum amount</p>
            <div class="text-xs text-gray-500">
                <div>• Type: Free Shipping</div>
                <div>• Min Amount: Rp 250,000</div>
                <div>• No usage limit</div>
                <div>• Expires: 1 month</div>
            </div>
        </div>

        {{-- Flash Sale Template --}}
        <div class="bg-gradient-to-br from-red-50 to-red-100 border border-red-200 rounded-lg p-4 hover:shadow-md transition-shadow">
            <div class="flex items-center mb-3">
                <div class="w-10 h-10 bg-red-500 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <h4 class="ml-3 font-semibold text-gray-900">Flash Sale</h4>
            </div>
            <p class="text-sm text-gray-600 mb-4">Limited time high discount with usage cap</p>
            <div class="text-xs text-gray-500">
                <div>• Type: Percentage (25%)</div>
                <div>• Max Discount: Rp 500,000</div>
                <div>• Usage Limit: 500</div>
                <div>• Expires: 24 hours</div>
            </div>
        </div>

        {{-- Fixed Amount Template --}}
        <div class="bg-gradient-to-br from-purple-50 to-purple-100 border border-purple-200 rounded-lg p-4 hover:shadow-md transition-shadow">
            <div class="flex items-center mb-3">
                <div class="w-10 h-10 bg-purple-500 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                    </svg>
                </div>
                <h4 class="ml-3 font-semibold text-gray-900">Fixed Amount Off</h4>
            </div>
            <p class="text-sm text-gray-600 mb-4">Fixed rupiah discount for medium purchases</p>
            <div class="text-xs text-gray-500">
                <div>• Type: Fixed Amount</div>
                <div>• Discount: Rp 50,000</div>
                <div>• Min Amount: Rp 250,000</div>
                <div>• Usage Limit: 500</div>
            </div>
        </div>

        {{-- Student Discount Template --}}
        <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 border border-yellow-200 rounded-lg p-4 hover:shadow-md transition-shadow">
            <div class="flex items-center mb-3">
                <div class="w-10 h-10 bg-yellow-500 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"></path>
                    </svg>
                </div>
                <h4 class="ml-3 font-semibold text-gray-900">Student Discount</h4>
            </div>
            <p class="text-sm text-gray-600 mb-4">Special discount for students with low minimum</p>
            <div class="text-xs text-gray-500">
                <div>• Type: Percentage (15%)</div>
                <div>• Min Amount: Rp 75,000</div>
                <div>• Max Discount: Rp 150,000</div>
                <div>• Long term validity</div>
            </div>
        </div>

        {{-- VIP Customer Template --}}
        <div class="bg-gradient-to-br from-gray-50 to-gray-100 border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
            <div class="flex items-center mb-3">
                <div class="w-10 h-10 bg-gray-600 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
                    </svg>
                </div>
                <h4 class="ml-3 font-semibold text-gray-900">VIP Customer</h4>
            </div>
            <p class="text-sm text-gray-600 mb-4">Exclusive discount for high-value customers</p>
            <div class="text-xs text-gray-500">
                <div>• Type: Percentage (20%)</div>
                <div>• Min Amount: Rp 1,000,000</div>
                <div>• Max Discount: Rp 1,000,000</div>
                <div>• Limited usage: 50</div>
            </div>
        </div>

    </div>

    <div class="mt-6 pt-4 border-t border-gray-200">
        <p class="text-xs text-gray-500">
            💡 <strong>Tip:</strong> Click on any template button in the modal actions below to auto-fill the form with these preset values. You can always customize the values after applying a template.
        </p>
    </div>
</div>