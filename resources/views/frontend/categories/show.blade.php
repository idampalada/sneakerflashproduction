@extends('layouts.app')

@section('title', (isset($category) ? $category->name : 'Category') . ' - SneakerFlash')

@section('content')
<div>
    @if(isset($category))
        <h1 class="text-3xl font-bold text-gray-900 mb-8">{{ $category->name }}</h1>
        
        @if($category->description)
            <p class="text-gray-600 mb-8">{{ $category->description }}</p>
        @endif

        @if(isset($products) && $products->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6">
                @foreach($products as $product)
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="font-semibold text-gray-900 mb-2">{{ $product->name }}</h3>
                        <p class="text-lg font-bold text-blue-600 mb-4">
                            Rp {{ number_format($product->price, 0, ',', '.') }}
                        </p>
                        <a href="/products/{{ $product->slug }}" 
                           class="block text-center bg-blue-600 text-white py-2 rounded hover:bg-blue-700">
                            View Details
                        </a>
                    </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            @if(method_exists($products, 'links'))
                <div class="mt-8">
                    {{ $products->links() }}
                </div>
            @endif
        @else
            <div class="text-center py-12">
                <p class="text-gray-500">No products found in this category</p>
            </div>
        @endif
    @else
        <div class="text-center py-12">
            <p class="text-gray-500">Category not found</p>
            <a href="/" class="mt-4 inline-block bg-blue-600 text-white px-6 py-2 rounded">
                Back to Home
            </a>
        </div>
    @endif
</div>
@endsection