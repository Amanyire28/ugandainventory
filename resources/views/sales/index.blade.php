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

    {{-- Audit Traceability Note --}}
    <div class="mb-4 flex items-start gap-2 p-3 bg-amber-50 border border-amber-200 rounded-lg text-xs text-amber-800">
        <i class="fas fa-info-circle mt-0.5 text-amber-500 flex-shrink-0"></i>
        <span>
            <strong>Audit Note:</strong> Voided sales are displayed below for traceability and accountability purposes but are
            <strong>excluded from all revenue, COGS, gross profit, net profit, and VAT reports</strong>.
            Their stock has been returned to inventory and any customer balance has been corrected.
        </span>
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
                    <td class="px-4 py-3 flex items-center gap-2">
                        <a href="{{ route('sales.show', $sale) }}" 
                           class="px-2.5 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold rounded text-xs transition flex items-center gap-1" title="View Receipt Details">
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