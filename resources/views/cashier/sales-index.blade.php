@extends('layouts.cashier-layout')

@section('title', 'My Sales')

@section('page-title')
    <i class="fas fa-receipt text-green-600 mr-2"></i>My Sales
@endsection

@section('content')
<div class="space-y-6">
    
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm">Total Sales</p>
                    <p class="text-3xl font-bold mt-2">{{ $totalSales }}</p>
                </div>
                <div class="bg-white bg-opacity-20 rounded-full p-4">
                    <i class="fas fa-shopping-cart text-3xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-500 to-green-600 text-white rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100 text-sm">Total Revenue</p>
                    <p class="text-2xl font-bold mt-2">UGX {{ number_format($totalRevenue, 0) }}</p>
                </div>
                <div class="bg-white bg-opacity-20 rounded-full p-4">
                    <i class="fas fa-money-bill-wave text-3xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-purple-500 to-purple-600 text-white rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 text-sm">Today's Sales</p>
                    <p class="text-3xl font-bold mt-2">{{ $todaySales }}</p>
                </div>
                <div class="bg-white bg-opacity-20 rounded-full p-4">
                    <i class="fas fa-calendar-day text-3xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 text-white rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-yellow-100 text-sm">Today's Revenue</p>
                    <p class="text-xl font-bold mt-2">UGX {{ number_format($todayRevenue, 0) }}</p>
                </div>
                <div class="bg-white bg-opacity-20 rounded-full p-4">
                    <i class="fas fa-coins text-3xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Sales Table -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-bold text-gray-800">
                <i class="fas fa-list text-green-600 mr-2"></i>All My Sales
            </h3>
            <a href="{{ route('pos.index') }}" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                <i class="fas fa-plus mr-2"></i>New Sale
            </a>
        </div>

        @if($sales->count() > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sale #</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Items</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($sales as $sale)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="font-semibold text-indigo-600">{{ $sale->sale_number }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $sale->sale_date->format('M d, Y') }}<br>
                            <span class="text-xs text-gray-500">{{ $sale->sale_date->format('h:i A') }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $sale->customer->name ?? 'Walk-in Customer' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $sale->items->count() }} items
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-lg font-bold {{ $sale->isVoided() ? 'line-through text-red-500' : 'text-green-600' }}">UGX {{ number_format($sale->total, 0) }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($sale->isVoided())
                                <span class="px-2.5 py-1 text-xs font-black rounded-full bg-red-100 text-red-800 border border-red-300">
                                    <i class="fas fa-ban mr-1"></i> VOIDED
                                </span>
                            @else
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                    {{ ucfirst($sale->payment_status) }}
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium flex items-center gap-2">
                            <a href="{{ route('sales.show', $sale->id) }}" class="px-2.5 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold rounded text-xs transition flex items-center gap-1">
                                <i class="fas fa-eye"></i> View
                            </a>
                            @if(!$sale->isVoided())
                                <button type="button" 
                                        onclick="openVoidModalFor({{ $sale->id }}, '{{ $sale->sale_number }}')" 
                                        class="px-2.5 py-1 bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 font-extrabold rounded text-xs transition flex items-center gap-1" title="Void / Reverse Sale">
                                    <i class="fas fa-undo text-red-600"></i> Void
                                </button>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-6">
            {{ $sales->links() }}
        </div>
        @else
        <div class="text-center py-12">
            <i class="fas fa-inbox text-6xl text-gray-300 mb-4"></i>
            <p class="text-gray-500 text-lg">No sales yet</p>
            <a href="{{ route('pos.index') }}" class="inline-block mt-4 px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700">
                <i class="fas fa-plus mr-2"></i>Make First Sale
            </a>
        </div>
        @endif
    </div>
</div>

<!-- Dynamic Void Sale Modal -->
<div id="dynamicVoidModal" class="fixed inset-0 bg-black/60 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 space-y-4">
        <div class="flex justify-between items-center border-b pb-3">
            <h3 class="font-extrabold text-slate-900 text-base flex items-center gap-2">
                <i class="fas fa-undo text-red-600"></i> Void Sale <span id="voidModalSaleNumber" class="text-indigo-600"></span>
            </h3>
            <button type="button" onclick="closeDynamicVoidModal()" class="text-slate-400 hover:text-slate-600 text-lg font-bold">&times;</button>
        </div>
        <form id="dynamicVoidForm" method="POST" action="">
            @csrf
            <div class="space-y-3">
                <div class="p-3 bg-amber-50 border-l-4 border-amber-500 text-amber-900 text-xs rounded font-medium">
                    <i class="fas fa-info-circle mr-1"></i> Voiding will reverse revenue, adjust VAT reports, and automatically restock items back into inventory.
                </div>
                <div>
                    <label class="block text-xs font-extrabold text-slate-800 uppercase tracking-wide mb-1">Reason for Reversal / Void *</label>
                    <textarea name="void_reason" required rows="3" placeholder="e.g. Scanned wrong item, incorrect price, customer cancellation..." class="w-full p-2.5 text-xs font-semibold border border-slate-300 rounded-lg outline-none focus:ring-2 focus:ring-red-500"></textarea>
                </div>
            </div>
            <div class="flex justify-end gap-2 pt-3 border-t">
                <button type="button" onclick="closeDynamicVoidModal()" class="px-4 py-2 bg-slate-200 text-slate-700 font-bold text-xs rounded-lg hover:bg-slate-300">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-red-600 text-white font-extrabold text-xs rounded-lg hover:bg-red-700 shadow">Confirm Void & Restock</button>
            </div>
        </form>
    </div>
</div>

<script>
function openVoidModalFor(saleId, saleNumber) {
    document.getElementById('voidModalSaleNumber').textContent = '#' + saleNumber;
    document.getElementById('dynamicVoidForm').action = '/sales/' + saleId + '/void';
    document.getElementById('dynamicVoidModal').classList.remove('hidden');
}
function closeDynamicVoidModal() {
    document.getElementById('dynamicVoidModal').classList.add('hidden');
}
</script>
@endsection