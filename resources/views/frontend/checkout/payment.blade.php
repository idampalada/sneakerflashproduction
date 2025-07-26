{{-- File: resources/views/frontend/checkout/payment.blade.php --}}
@extends('layouts.app')

@section('title', 'Payment - SneakerFlash')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-lg shadow-md p-8 text-center">
            <div class="mb-6">
                <i class="fas fa-credit-card text-4xl text-blue-600 mb-4"></i>
                <h1 class="text-2xl font-bold text-gray-900">Complete Your Payment</h1>
                <p class="text-gray-600 mt-2">Order #{{ $order->order_number }}</p>
            </div>

            <div class="bg-gray-50 rounded-lg p-6 mb-6">
                <div class="flex justify-between items-center text-lg">
                    <span class="font-medium">Total Amount:</span>
                    <span class="font-bold text-blue-600">
                        Rp {{ number_format($order->total_amount, 0, ',', '.') }}
                    </span>
                </div>
            </div>

            <button id="pay-button" 
                    class="w-full bg-blue-600 text-white py-3 px-6 rounded-lg font-semibold hover:bg-blue-700 transition-colors mb-4">
                <i class="fas fa-lock mr-2"></i>
                Pay Now
            </button>

            <div class="text-center">
                <p class="text-sm text-gray-500 mb-4">
                    Secure payment powered by Midtrans
                </p>
                <div class="flex justify-center space-x-4">
                    <img src="https://docs.midtrans.com/asset/image/footer-logo/visa.png" alt="Visa" class="h-6">
                    <img src="https://docs.midtrans.com/asset/image/footer-logo/mastercard.png" alt="Mastercard" class="h-6">
                    <img src="https://docs.midtrans.com/asset/image/footer-logo/bca.png" alt="BCA" class="h-6">
                    <img src="https://docs.midtrans.com/asset/image/footer-logo/mandiri.png" alt="Mandiri" class="h-6">
                </div>
            </div>

            <div class="mt-6 text-center">
                <a href="{{ route('orders.show', $order->order_number) }}" 
                   class="text-blue-600 hover:text-blue-800 text-sm">
                    View order details
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="{{ $clientKey }}"></script>
<script>
document.getElementById('pay-button').onclick = function(){
    snap.pay('{{ $snapToken }}', {
        onSuccess: function(result){
            console.log('Payment success:', result);
            window.location.href = '{{ route("checkout.finish") }}?order_id={{ $order->order_number }}';
        },
        onPending: function(result){
            console.log('Payment pending:', result);
            window.location.href = '{{ route("checkout.finish") }}?order_id={{ $order->order_number }}';
        },
        onError: function(result){
            console.log('Payment error:', result);
            alert('Payment failed! Please try again.');
        },
        onClose: function(){
            console.log('Payment popup closed');
            alert('You closed the popup without finishing the payment');
        }
    });
};
</script>
@endpush