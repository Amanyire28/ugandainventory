@extends('layouts.app')

@section('title', 'Products Expiring Soon')

@section('page-title')
    <i class="fas fa-clock text-yellow-600 mr-2"></i>Products Expiring Soon
@endsection

@section('content')
<div class="bg-white rounded-xl shadow-lg p-6">
    
    <!-- Alert Banner -->
    <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 mb-6 rounded">
        <div class="flex items-center">
            <i class="fas fa-bell text-yellow-500 text-2xl mr-3"></i>
            <div class="flex-1">
                <h3 class="font-bold text-yellow-800">{{ $products->total() }} Products Expiring Soon</h3>
                <p class="text-sm text-yellow-600">These products will expire within the next 30 days. Consider promotions or discounts to sell them quickly.</p>
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
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Days Left</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stock</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Value</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($products as $product)
                <tr class="hover:bg-yellow-50">
                    <td class="px-4 py-3 text-sm font-semibold text-gray-900">
                        {{ $product->name }}
                        @if($product->unit)
                            <span class="text-xs text-gray-400 block mt-0.5">Unit: {{ $product->unit }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $product->sku }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $product->category->name ?? 'N/A' }}</td>
                    <td class="px-4 py-3 text-sm text-yellow-600 font-semibold">
                        {{ $product->expiry_date->format('M d, Y') }}
                    </td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 text-xs font-semibold 
                            {{ $product->daysUntilExpiry() <= 7 ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800' }} rounded-full">
                            {{ $product->daysUntilExpiry() }} days
                        </span>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600">
                        {{ $product->getTotalStock() }} {{ $product->unit }}
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-900 font-semibold">
                        UGX {{ number_format($product->getTotalInventoryValue(), 0) }}
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex space-x-2">
                            <a href="{{ route('products.edit', $product) }}" 
                               class="text-indigo-600 hover:text-indigo-800" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button onclick="createPromotion('{{ $product->id }}')" 
                                    class="text-green-600 hover:text-green-800" title="Create Promotion">
                                <i class="fas fa-percentage"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                        <i class="fas fa-check-circle text-4xl text-green-300 mb-2"></i>
                        <p>No products expiring soon! All products have plenty of time before expiration.</p>
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
    function createPromotion(productId) {
        alert('Create promotional discount for product ' + productId);
        // Implement promotion creation logic
    }
</script>
@endpush
@endsection