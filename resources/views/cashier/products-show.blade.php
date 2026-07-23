@extends('layouts.cashier-layout')

@section('title', 'Product Details - ' . $product->name)

@section('page-title')
    <i class="fas fa-box text-green-600 mr-2"></i>Product Details
@endsection

@section('content')
<div class="space-y-6">
    
    <!-- Back Button -->
    <div>
        <a href="{{ route('products.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
            <i class="fas fa-arrow-left mr-2"></i>Back to Products
        </a>
    </div>

    <!-- Product Details Card -->
    <div class="bg-white rounded-xl shadow-lg p-6 max-w-2xl mx-auto">
        <!-- Product Info -->
        <div class="space-y-6">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ $product->name }}</h1>
                    @if($product->category)
                    <span class="inline-block px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm">
                        <i class="fas fa-tag mr-1"></i>{{ $product->category->name }}
                    </span>
                    @endif
                </div>

                <!-- Price -->
                <div class="bg-green-50 rounded-lg p-4">
                    <p class="text-sm text-gray-600 mb-1">Selling Price</p>
                    <p class="text-4xl font-bold text-green-600">UGX {{ number_format($product->selling_price, 0) }}</p>
                </div>

                <!-- Stock Info -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="border border-gray-200 rounded-lg p-4">
                        <p class="text-sm text-gray-600 mb-1">Stock Available</p>
                        <p class="text-2xl font-bold {{ $product->quantity < 10 ? 'text-red-600' : 'text-gray-900' }}">
                            {{ $product->quantity }} {{ $product->unit ?? 'pcs' }}
                        </p>
                    </div>
                    
                    @if($product->reorder_level)
                    <div class="border border-gray-200 rounded-lg p-4">
                        <p class="text-sm text-gray-600 mb-1">Reorder Level</p>
                        <p class="text-2xl font-bold text-gray-900">
                            {{ $product->reorder_level }} {{ $product->unit ?? 'pcs' }}
                        </p>
                    </div>
                    @endif
                </div>

                <!-- Product Details -->
                <div class="space-y-3">
                    @if($product->sku)
                    <div class="flex justify-between">
                        <span class="text-gray-600">SKU:</span>
                        <span class="font-semibold">{{ $product->sku }}</span>
                    </div>
                    @endif

                    @if($product->barcode)
                    <div class="flex justify-between">
                        <span class="text-gray-600">Barcode:</span>
                        <span class="font-semibold">{{ $product->barcode }}</span>
                    </div>
                    @endif

                    @if($product->expiry_date)
                    <div class="flex justify-between">
                        <span class="text-gray-600">Expiry Date:</span>
                        <span class="font-semibold {{ $product->expiry_date->isPast() ? 'text-red-600' : ($product->expiry_date->diffInDays(now()) < 30 ? 'text-yellow-600' : 'text-gray-900') }}">
                            {{ $product->expiry_date->format('M d, Y') }}
                        </span>
                    </div>
                    @endif
                </div>

                @if($product->description)
                <div>
                    <h3 class="font-semibold text-gray-900 mb-2">Description</h3>
                    <p class="text-gray-600">{{ $product->description }}</p>
                </div>
                @endif

                <!-- Status Badges -->
                <div class="flex flex-wrap gap-2">
                    @if($product->quantity > 0)
                    <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-semibold">
                        <i class="fas fa-check-circle mr-1"></i>In Stock
                    </span>
                    @else
                    <span class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm font-semibold">
                        <i class="fas fa-times-circle mr-1"></i>Out of Stock
                    </span>
                    @endif

                    @if($product->quantity < $product->reorder_level)
                    <span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-sm font-semibold">
                        <i class="fas fa-exclamation-triangle mr-1"></i>Low Stock
                    </span>
                    @endif
                </div>
            </div>
        </div>

    <!-- Recent Sales -->
    @if($recentSales && $recentSales->count() > 0)
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">
            <i class="fas fa-history text-green-600 mr-2"></i>Recent Sales
        </h3>
        <div class="space-y-3">
            @foreach($recentSales as $saleItem)
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                <div>
                    <p class="font-semibold text-gray-900">{{ $saleItem->sale->sale_number }}</p>
                    <p class="text-sm text-gray-600">
                        {{ $saleItem->sale->sale_date->format('M d, Y h:i A') }} • 
                        {{ $saleItem->sale->customer->name ?? 'Walk-in' }}
                    </p>
                </div>
                <div class="text-right">
                    <p class="font-semibold text-gray-900">{{ $saleItem->quantity }} {{ $product->unit ?? 'pcs' }}</p>
                    <p class="text-sm text-green-600">UGX {{ number_format($saleItem->total, 0) }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection