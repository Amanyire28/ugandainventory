@extends('layouts.app')

@section('title', 'Product Performance Report')

@section('page-title')
    <i class="fas fa-box-open text-indigo-600 mr-2"></i>Product Performance Report
@endsection

@section('content')
<div class="space-y-6">
    
    <!-- Filter Section -->
    <div class="bg-white rounded-xl shadow-lg p-6 no-print">
        <h3 class="text-lg font-bold text-gray-800 mb-4">
            <i class="fas fa-filter text-indigo-600 mr-2"></i>Filter Report
        </h3>

        <form method="GET" action="{{ route('reports.products') }}">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Start Date -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
                    <input type="date" 
                           name="start_date" 
                           value="{{ request('start_date', now()->startOfMonth()->format('Y-m-d')) }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                </div>

                <!-- End Date -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">End Date</label>
                    <input type="date" 
                           name="end_date" 
                           value="{{ request('end_date', now()->format('Y-m-d')) }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                </div>

                <!-- Category -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                    <select name="category_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Submit Button -->
                <div class="flex items-end">
                    <button type="submit" class="w-full px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                        <i class="fas fa-search mr-2"></i>Generate Report
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Product Performance Table -->
    <div class="bg-white rounded-xl shadow-lg p-6 print-100">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-bold text-gray-800">Product Sales Performance</h3>
            <button onclick="window.print()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                <i class="fas fa-print mr-2"></i>Print Report
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">SKU</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Units Sold</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Revenue</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Avg Price</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Current Stock</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($products as $product)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <div class="flex items-center">
                                <div>
                                    <p class="font-semibold text-gray-900">{{ $product->name }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $product->sku }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $product->category->name ?? 'N/A' }}</td>
                        <td class="px-4 py-3 text-sm text-right font-semibold">
                            {{ number_format($product->units_sold, 0) }}
                        </td>
                        <td class="px-4 py-3 text-sm text-right font-semibold text-green-600">
                            UGX {{ number_format($product->revenue, 0) }}
                        </td>
                        <td class="px-4 py-3 text-sm text-right text-gray-600">
                            UGX {{ number_format($product->avg_price, 0) }}
                        </td>
                        <td class="px-4 py-3 text-sm text-right">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                {{ $product->quantity > $product->reorder_level ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                {{ number_format($product->quantity, 0) }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                            No product sales data available
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-6">
            {{ $products->appends(request()->query())->links() }}
        </div>
    </div>
</div>
@endsection