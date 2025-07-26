@extends('layouts.app')

@section('title', $product->name . ' - SneakerFlash')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Breadcrumb -->
    <nav class="text-sm mb-6">
        <ol class="flex space-x-2 text-gray-600">
            <li><a href="/" class="hover:text-blue-600">Home</a></li>
            <li>/</li>
            <li><a href="/products" class="hover:text-blue-600">Products</a></li>
            <li>/</li>
            @if($product->category)
                <li><a href="/categories/{{ $product->category->slug }}" class="hover:text-blue-600">{{ $product->category->name }}</a></li>
                <li>/</li>
            @endif
            <li class="text-gray-900">{{ $product->name }}</li>
        </ol>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
        <!-- Product Images -->
        <div class="space-y-4">
            <div class="aspect-square bg-gray-100 rounded-lg overflow-hidden">
                @if($product->images && count($product->images) > 0)
                    <img id="mainImage" src="{{ Storage::url($product->images[0]) }}" 
                         alt="{{ $product->name }}"
                         class="w-full h-full object-cover">
                @else
                    <div class="w-full h-full flex items-center justify-center">
                        <i class="fas fa-image text-6xl text-gray-400"></i>
                    </div>
                @endif
            </div>
            
            @if($product->images && count($product->images) > 1)
                <div class="grid grid-cols-4 gap-2">
                    @foreach($product->images as $index => $image)
                        <button onclick="changeMainImage('{{ Storage::url($image) }}')"
                                class="aspect-square bg-gray-100 rounded-lg overflow-hidden hover:ring-2 hover:ring-blue-500">
                            <img src="{{ Storage::url($image) }}" 
                                 alt="{{ $product->name }}"
                                 class="w-full h-full object-cover">
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Product Info -->
        <div class="space-y-6">
            <div>
                @if($product->category)
                    <p class="text-sm text-gray-500 mb-2">{{ $product->category->name }}</p>
                @endif
                <h1 class="text-3xl font-bold text-gray-900 mb-4">{{ $product->name }}</h1>
                
                @if($product->brand)
                    <p class="text-lg text-gray-600 mb-4">Brand: {{ $product->brand }}</p>
                @endif
            </div>

            <!-- Price -->
            <div class="space-y-2">
                @if($product->sale_price)
                    <div class="flex items-center space-x-3">
                        <span class="text-3xl font-bold text-red-600">
                            Rp {{ number_format($product->sale_price, 0, ',', '.') }}
                        </span>
                        <span class="text-xl text-gray-500 line-through">
                            Rp {{ number_format($product->price, 0, ',', '.') }}
                        </span>
                        <span class="bg-red-100 text-red-800 text-sm px-2 py-1 rounded-full">
                            Save {{ round((($product->price - $product->sale_price) / $product->price) * 100) }}%
                        </span>
                    </div>
                @else
                    <span class="text-3xl font-bold text-gray-900">
                        Rp {{ number_format($product->price, 0, ',', '.') }}
                    </span>
                @endif
            </div>

            <!-- Stock Status -->
            <div>
                @if($product->stock_quantity > 10)
                    <p class="text-green-600 font-medium">
                        <i class="fas fa-check-circle mr-2"></i>In Stock
                    </p>
                @elseif($product->stock_quantity > 0)
                    <p class="text-yellow-600 font-medium">
                        <i class="fas fa-exclamation-triangle mr-2"></i>Only {{ $product->stock_quantity }} left!
                    </p>
                @else
                    <p class="text-red-600 font-medium">
                        <i class="fas fa-times-circle mr-2"></i>Out of Stock
                    </p>
                @endif
            </div>

            <!-- Short Description -->
            @if($product->short_description)
                <div class="prose prose-gray">
                    <p class="text-gray-600">{{ $product->short_description }}</p>
                </div>
            @endif

            <!-- Add to Cart -->
            @if($product->stock_quantity > 0)
                <form action="{{ route('cart.add') }}" method="POST" class="space-y-4">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    
                    <div class="flex items-center space-x-4">
                        <label for="quantity" class="text-sm font-medium text-gray-700">Quantity:</label>
                        <input type="number" name="quantity" id="quantity" 
                               min="1" max="{{ $product->stock_quantity }}" value="1"
                               class="w-20 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div class="flex space-x-4">
                        <button type="submit" 
                                class="flex-1 bg-blue-600 text-white py-3 px-6 rounded-lg hover:bg-blue-700 transition-colors font-medium">
                            <i class="fas fa-shopping-cart mr-2"></i>
                            Add to Cart
                        </button>
                        <button type="button" 
                                class="bg-gray-200 text-gray-800 py-3 px-6 rounded-lg hover:bg-gray-300 transition-colors">
                            <i class="fas fa-heart"></i>
                        </button>
                    </div>
                </form>
            @else
                <div class="space-y-4">
                    <button disabled 
                            class="w-full bg-gray-400 text-white py-3 px-6 rounded-lg cursor-not-allowed">
                        Out of Stock
                    </button>
                </div>
            @endif

            <!-- Features -->
            @if($product->features)
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-3">Features</h3>
                    <ul class="space-y-2">
                        @foreach($product->features as $feature)
                            <li class="flex items-center text-gray-600">
                                <i class="fas fa-check text-green-500 mr-2"></i>
                                {{ $feature }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>

    <!-- Description -->
    @if($product->description)
        <div class="mt-16">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Product Description</h2>
            <div class="prose prose-gray max-w-none">
                {!! nl2br(e($product->description)) !!}
            </div>
        </div>
    @endif

    <!-- Related Products -->
    @if(isset($relatedProducts) && $relatedProducts->count() > 0)
        <div class="mt-16">
            <h2 class="text-2xl font-bold text-gray-900 mb-8">Related Products</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($relatedProducts as $relatedProduct)
                    <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                        <div class="relative">
                            <a href="/products/{{ $relatedProduct->slug }}">
                                @if($relatedProduct->images && count($relatedProduct->images) > 0)
                                    <img src="{{ Storage::url($relatedProduct->images[0]) }}" 
                                         alt="{{ $relatedProduct->name }}"
                                         class="w-full h-48 object-cover">
                                @else
                                    <div class="w-full h-48 bg-gray-200 flex items-center justify-center">
                                        <i class="fas fa-image text-3xl text-gray-400"></i>
                                    </div>
                                @endif
                            </a>
                        </div>
                        
                        <div class="p-4">
                            <h3 class="font-semibold text-gray-900 mb-2">{{ $relatedProduct->name }}</h3>
                            <div class="flex items-center justify-between">
                                <div>
                                    @if($relatedProduct->sale_price)
                                        <span class="text-lg font-bold text-red-600">
                                            Rp {{ number_format($relatedProduct->sale_price, 0, ',', '.') }}
                                        </span>
                                        <span class="text-sm text-gray-500 line-through ml-2">
                                            Rp {{ number_format($relatedProduct->price, 0, ',', '.') }}
                                        </span>
                                    @else
                                        <span class="text-lg font-bold text-gray-900">
                                            Rp {{ number_format($relatedProduct->price, 0, ',', '.') }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
function changeMainImage(imageSrc) {
    document.getElementById('mainImage').src = imageSrc;
}
</script>
@endpush