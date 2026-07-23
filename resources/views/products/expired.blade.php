@extends('layouts.app')

@section('title', 'Expired Products')

@section('page-title')
    <i class="fas fa-exclamation-triangle text-red-600 mr-2"></i>Expired Products
@endsection

@section('content')
<div class="bg-white rounded-xl shadow-lg p-6">
    
    <!-- Alert Banner -->
    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded">
        <div class="flex items-center">
            <i class="fas fa-exclamation-circle text-red-500 text-2xl mr-3"></i>
            <div class="flex-1">
                <h3 class="font-bold text-red-800">{{ $products->total() }} Expired Products Found</h3>
                <p class="text-sm text-red-600">These products have passed their expiration date and should be removed from inventory immediately.</p>
            </div>
        </div>
    </div>

    <!-- Products Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">SKU</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Expiry Date</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Days Expired</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stock</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Value Lost</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($products as $product)
                <tr class="hover:bg-red-50">
                    <td class="px-4 py-3 text-sm font-semibold text-gray-900">
                        {{ $product->name }}
                        @if($product->unit)
                            <span class="text-xs text-gray-400 block mt-0.5">Unit: {{ $product->unit }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $product->sku }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $product->category->name ?? 'N/A' }}</td>
                    <td class="px-4 py-3 text-sm text-red-600 font-semibold">
                        {{ $product->expiry_date->format('M d, Y') }}
                    </td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 text-xs font-semibold bg-red-100 text-red-800 rounded-full">
                            {{ abs($product->daysUntilExpiry()) }} days ago
                        </span>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600">
                        {{ $product->getTotalStock() }} {{ $product->unit }}
                    </td>
                    <td class="px-4 py-3 text-sm text-red-600 font-semibold">
                        UGX {{ number_format($product->getTotalInventoryValue(), 0) }}
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex space-x-2">
                            <a href="{{ route('products.edit', $product) }}" 
                               class="text-indigo-600 hover:text-indigo-800" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button onclick="markAsRemoved('{{ $product->id }}')" 
                                    class="text-red-600 hover:text-red-800" title="Mark as Removed">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                        <i class="fas fa-check-circle text-4xl text-green-300 mb-2"></i>
                        <p>No expired products! All products are within their expiration dates.</p>
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

@push('scripts')
<script>
    function markAsRemoved(productId) {
        if (confirm('Mark this expired product as removed from inventory?')) {
            // Handle removal logic
            console.log('Remove product:', productId);
        }
    }
</script>
@endpush
@endsection