@extends('layouts.app')

@section('title', 'All Sales')

@section('page-title')
    <i class="fas fa-shopping-cart text-indigo-600 mr-2"></i>All Sales
@endsection

@section('content')
<div class="bg-white rounded-xl shadow-lg p-6">
    
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 space-y-4 md:space-y-0">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Sales History</h2>
            <p class="text-gray-600 text-sm mt-1">View all sales transactions</p>
        </div>
        <div class="flex space-x-2">
            <a href="{{ route('pos.index') }}" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 flex items-center">
                <i class="fas fa-plus mr-2"></i>
                New Sale
            </a>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="flex space-x-2 mb-6 overflow-x-auto">
        <a href="{{ route('sales.index') }}" class="px-4 py-2 bg-indigo-600 text-white rounded-lg whitespace-nowrap">
            <i class="fas fa-list mr-1"></i>All Sales
        </a>
        <a href="{{ route('sales.today') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 whitespace-nowrap">
            <i class="fas fa-calendar-day mr-1"></i>Today
        </a>
        <a href="{{ route('sales.weekly') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 whitespace-nowrap">
            <i class="fas fa-calendar-week mr-1"></i>This Week
        </a>
        <a href="{{ route('sales.monthly') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 whitespace-nowrap">
            <i class="fas fa-calendar-alt mr-1"></i>This Month
        </a>
    </div>

    <!-- Sales Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sale #</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Staff</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Items</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Payment</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($sales as $sale)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-sm font-medium text-indigo-600">
                        <a href="{{ route('sales.show', $sale) }}" class="hover:underline">
                            {{ $sale->sale_number }}
                        </a>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600">
                        {{ $sale->sale_date->format('M d, Y') }}<br>
                        <span class="text-xs text-gray-400">{{ $sale->sale_date->format('h:i A') }}</span>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600">
                        {{ $sale->customer->name ?? 'Walk-in Customer' }}
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600">
                        {{ $sale->user->name }}
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600">
                        {{ $sale->items->count() }} items
                    </td>
                    <td class="px-4 py-3 text-sm font-semibold {{ $sale->isVoided() ? 'line-through text-red-500' : 'text-green-600' }}">
                        UGX {{ number_format($sale->total, 0) }}
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600">
                        {{ ucfirst(str_replace('_', ' ', $sale->payment_method)) }}
                    </td>
                    <td class="px-4 py-3">
                        @if($sale->isVoided())
                            <span class="px-2.5 py-1 text-xs font-black rounded-full bg-red-100 text-red-800 border border-red-300">
                                <i class="fas fa-ban mr-1"></i> VOIDED
                            </span>
                        @else
                            <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                {{ $sale->payment_status === 'paid' ? 'bg-green-100 text-green-800' : 
                                   ($sale->payment_status === 'partial' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                {{ ucfirst($sale->payment_status) }}
                            </span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <a href="{{ route('sales.show', $sale) }}" 
                           class="text-indigo-600 hover:text-indigo-800" title="View Details">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="px-4 py-8 text-center text-gray-500">
                        <i class="fas fa-shopping-cart text-4xl text-gray-300 mb-2"></i>
                        <p>No sales recorded yet. <a href="{{ route('pos.index') }}" class="text-indigo-600">Make your first sale</a></p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $sales->links() }}
    </div>
</div>
@endsection