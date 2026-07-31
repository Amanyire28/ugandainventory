@extends('layouts.app')

@section('title', 'Inventory Management')

@section('page-title')
    <i class="fas fa-warehouse text-indigo-600 mr-2"></i>Inventory Management
@endsection

@section('content')
<div class="space-y-6">
    
    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <!-- Total Products -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Total Products</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ $totalProducts }}</p>
                </div>
                <div class="bg-indigo-100 rounded-full p-4">
                    <i class="fas fa-box text-indigo-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Low Stock -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Low Stock</p>
                    <p class="text-3xl font-bold text-yellow-600 mt-2">{{ $lowStockCount }}</p>
                </div>
                <div class="bg-yellow-100 rounded-full p-4">
                    <i class="fas fa-exclamation-triangle text-yellow-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Out of Stock -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Out of Stock</p>
                    <p class="text-3xl font-bold text-red-600 mt-2">{{ $outOfStockCount }}</p>
                </div>
                <div class="bg-red-100 rounded-full p-4">
                    <i class="fas fa-times-circle text-red-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Total Value -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Inventory Value</p>
                    <p class="text-3xl font-bold text-green-600 mt-2">UGX {{ number_format($totalValue, 0) }}</p>
                </div>
                <div class="bg-green-100 rounded-full p-4">
                    <i class="fas fa-coins text-green-600 text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Inventory Table -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Current Inventory</h2>
            <div class="flex space-x-2">
                <a href="{{ route('products.create') }}" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                    <i class="fas fa-plus mr-2"></i>Add Product
                </a>
            </div>
        </div>

        <!-- Search -->
        <div class="mb-4">
            <input type="text" 
                   id="searchInput" 
                   placeholder="Search products..." 
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">SKU</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">In Stock</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Reorder Level</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Unit Value</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total Value</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($products as $product)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <div class="flex items-center">
                                <div>
                                    <p class="font-semibold text-gray-900">{{ $product->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $product->unit }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $product->sku }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $product->category->name ?? 'N/A' }}</td>
                        <td class="px-4 py-3 text-right text-sm font-semibold">
                            {{ number_format($product->quantity, 0) }}
                        </td>
                        <td class="px-4 py-3 text-right text-sm text-gray-600">
                            {{ $product->reorder_level }}
                        </td>
                        <td class="px-4 py-3 text-right text-sm text-gray-600">
                            UGX {{ number_format($product->cost_price, 0) }}
                        </td>
                        <td class="px-4 py-3 text-right text-sm font-semibold text-green-600">
                            UGX {{ number_format($product->quantity * $product->cost_price, 0) }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                @if($product->quantity <= 0)
                                    bg-red-100 text-red-800
                                @elseif($product->quantity <= $product->reorder_level)
                                    bg-yellow-100 text-yellow-800
                                @else
                                    bg-green-100 text-green-800
                                @endif">
                                @if($product->quantity <= 0)
                                    Out of Stock
                                @elseif($product->quantity <= $product->reorder_level)
                                    Low Stock
                                @else
                                    In Stock
                                @endif
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                            No products in inventory
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-6">
            {{ $products->links() }}
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.getElementById('searchInput').addEventListener('input', function(e) {
        let searchValue = e.target.value.toLowerCase();
        let rows = document.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            let text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchValue) ? '' : 'none';
        });
    });
</script>
@endpush
@endsection