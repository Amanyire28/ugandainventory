@extends('layouts.app')

@section('title', 'Top Selling Products')

@section('page-title')
    <i class="fas fa-medal text-yellow-600 mr-2"></i>Top Selling Products
@endsection

@section('content')
<div class="space-y-6">
    
    <!-- Filter Section -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">
            <i class="fas fa-filter text-indigo-600 mr-2"></i>Filter Report
        </h3>

        <form method="GET" action="{{ route('reports.top-selling') }}">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Period -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Period</label>
                    <select name="period" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                        <option value="today" {{ request('period') == 'today' ? 'selected' : '' }}>Today</option>
                        <option value="week" {{ request('period') == 'week' ? 'selected' : '' }}>This Week</option>
                        <option value="month" {{ request('period', 'month') == 'month' ? 'selected' : '' }}>This Month</option>
                        <option value="year" {{ request('period') == 'year' ? 'selected' : '' }}>This Year</option>
                        <option value="custom" {{ request('period') == 'custom' ? 'selected' : '' }}>Custom Range</option>
                    </select>
                </div>

                <!-- Start Date (for custom) -->
                <div id="startDateDiv" class="{{ request('period') == 'custom' ? '' : 'hidden' }}">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
                    <input type="date" 
                           name="start_date" 
                           value="{{ request('start_date') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                </div>

                <!-- End Date (for custom) -->
                <div id="endDateDiv" class="{{ request('period') == 'custom' ? '' : 'hidden' }}">
                    <label class="block text-sm font-medium text-gray-700 mb-2">End Date</label>
                    <input type="date" 
                           name="end_date" 
                           value="{{ request('end_date') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
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

    <!-- Top 10 Products -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($topProducts as $index => $product)
        <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition">
            <!-- Rank Badge -->
            <div class="flex items-start justify-between mb-4">
                <div class="flex items-center">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center mr-3
                        {{ $index == 0 ? 'bg-yellow-400' : ($index == 1 ? 'bg-gray-300' : ($index == 2 ? 'bg-orange-400' : 'bg-indigo-100')) }}">
                        <span class="text-2xl font-bold {{ $index < 3 ? 'text-white' : 'text-indigo-600' }}">
                            {{ $index + 1 }}
                        </span>
                    </div>
                    @if($index < 3)
                        <i class="fas fa-trophy text-3xl 
                            {{ $index == 0 ? 'text-yellow-500' : ($index == 1 ? 'text-gray-400' : 'text-orange-500') }}"></i>
                    @endif
                </div>
            </div>


            <!-- Product Info -->
            <h4 class="font-bold text-lg text-gray-900 mb-2">{{ $product->name }}</h4>
            <p class="text-sm text-gray-500 mb-4">SKU: {{ $product->sku }}</p>

            <!-- Stats -->
            <div class="space-y-2">
                <div class="flex justify-between items-center p-2 bg-gray-50 rounded">
                    <span class="text-sm text-gray-600">Units Sold:</span>
                    <span class="font-bold text-indigo-600">{{ number_format($product->units_sold, 0) }}</span>
                </div>
                <div class="flex justify-between items-center p-2 bg-gray-50 rounded">
                    <span class="text-sm text-gray-600">Revenue:</span>
                    <span class="font-bold text-green-600">UGX {{ number_format($product->revenue, 0) }}</span>
                </div>
                <div class="flex justify-between items-center p-2 bg-gray-50 rounded">
                    <span class="text-sm text-gray-600">Avg Price:</span>
                    <span class="font-semibold text-gray-900">UGX {{ number_format($product->avg_price, 0) }}</span>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Detailed Table -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Detailed Breakdown</h3>

        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rank</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Units Sold</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Revenue</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">% of Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($topProducts as $index => $product)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <span class="px-3 py-1 rounded-full font-bold
                                {{ $index == 0 ? 'bg-yellow-100 text-yellow-800' : 
                                   ($index == 1 ? 'bg-gray-100 text-gray-800' : 
                                   ($index == 2 ? 'bg-orange-100 text-orange-800' : 'bg-indigo-100 text-indigo-800')) }}">
                                #{{ $index + 1 }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div>
                                <p class="font-semibold text-gray-900">{{ $product->name }}</p>
                                <p class="text-xs text-gray-500">{{ $product->sku }}</p>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $product->category->name ?? 'N/A' }}</td>
                        <td class="px-4 py-3 text-sm text-right font-semibold">{{ number_format($product->units_sold, 0) }}</td>
                        <td class="px-4 py-3 text-sm text-right font-semibold text-green-600">
                            UGX {{ number_format($product->revenue, 0) }}
                        </td>
                        <td class="px-4 py-3 text-sm text-right font-semibold text-indigo-600">
                            {{ number_format(($product->revenue / $totalRevenue) * 100, 1) }}%
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.querySelector('select[name="period"]').addEventListener('change', function() {
        const startDiv = document.getElementById('startDateDiv');
        const endDiv = document.getElementById('endDateDiv');
        
        if (this.value === 'custom') {
            startDiv.classList.remove('hidden');
            endDiv.classList.remove('hidden');
        } else {
            startDiv.classList.add('hidden');
            endDiv.classList.add('hidden');
        }
    });
</script>
@endpush
@endsection