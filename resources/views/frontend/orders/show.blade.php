@extends('layouts.app')

@section('title', 'Order Details - SneakerFlash')

@section('content')
<div>
    <h1 class="text-3xl font-bold text-gray-900 mb-8">Order Details</h1>

    <div class="bg-white rounded-lg shadow-md p-6">
        <p class="text-gray-500">Order details will be displayed here</p>
        <a href="/orders" class="mt-4 inline-block text-blue-600 hover:underline">
            ← Back to Orders
        </a>
    </div>
</div>
@endsection