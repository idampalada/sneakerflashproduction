@extends('layouts.app')

@section('title', 'Checkout - SneakerFlash')

@section('content')
<div>
    <h1 class="text-3xl font-bold text-gray-900 mb-8">Checkout</h1>

    <div class="bg-white rounded-lg shadow-md p-6">
        <form method="POST" action="/checkout">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Customer Info -->
                <div>
                    <h2 class="text-xl font-semibold mb-4">Customer Information</h2>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                            <input type="text" name="name" required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                            <input type="email" name="email" required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
                            <input type="tel" name="phone" required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Address</label>
                            <textarea name="address" rows="3" required 
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Order Summary -->
                <div>
                    <h2 class="text-xl font-semibold mb-4">Order Summary</h2>
                    
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span>Subtotal:</span>
                                <span>Rp 0</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Shipping:</span>
                                <span>Rp 15,000</span>
                            </div>
                            <div class="border-t pt-2">
                                <div class="flex justify-between font-bold">
                                    <span>Total:</span>
                                    <span>Rp 15,000</span>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" 
                                class="w-full mt-6 bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700">
                            Place Order
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection