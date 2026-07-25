@extends('layouts.cashier-layout')

@section('title', 'Sale Details - ' . $sale->sale_number)

@section('page-title')
    <i class="fas fa-file-invoice text-green-600 mr-2"></i>Sale Details - {{ $sale->sale_number }}
@endsection

@section('content')
<div class="space-y-6">
    
    <!-- Back Button -->
    </div>

    <!-- Sale Info Card -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <h3 class="text-lg font-bold text-gray-800 mb-4">Sale Information</h3>
                <dl class="space-y-2">
                    <div class="flex justify-between">
                        <dt class="text-gray-600">Sale Number:</dt>
                        <dd class="font-semibold text-indigo-600">{{ $sale->sale_number }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-600">Date:</dt>
                        <dd class="font-semibold">{{ $sale->sale_date->format('M d, Y h:i A') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-600">Cashier:</dt>
                        <dd class="font-semibold">{{ $sale->user->name }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-600">Payment Method:</dt>
                        <dd class="font-semibold">{{ ucfirst(str_replace('_', ' ', $sale->payment_method)) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-600">Status:</dt>
                        <dd>
                            @if($sale->isVoided())
                                <span class="px-2.5 py-1 text-xs font-black rounded-full bg-red-100 text-red-800 border border-red-300">
                                    <i class="fas fa-ban mr-1"></i> VOIDED
                                </span>
                            @else
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                    {{ ucfirst($sale->payment_status) }}
                                </span>
                            @endif
                        </dd>
                    </div>
                </dl>
                @if(!$sale->isVoided())
                    <div class="mt-4 pt-3 border-t">
                        <button type="button" onclick="openVoidModal()" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-bold rounded-lg text-xs transition flex items-center gap-1.5 shadow">
                            <i class="fas fa-undo"></i> Void / Reverse Sale
                        </button>
                    </div>
                @endif
            </div>
            </div>

            <div>
                <h3 class="text-lg font-bold text-gray-800 mb-4">Customer Information</h3>
                @if($sale->customer)
                <dl class="space-y-2">
                    <div class="flex justify-between">
                        <dt class="text-gray-600">Name:</dt>
                        <dd class="font-semibold">{{ $sale->customer->name }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-600">Phone:</dt>
                        <dd class="font-semibold">{{ $sale->customer->phone }}</dd>
                    </div>
                    @if($sale->customer->email)
                    <div class="flex justify-between">
                        <dt class="text-gray-600">Email:</dt>
                        <dd class="font-semibold">{{ $sale->customer->email }}</dd>
                    </div>
                    @endif
                </dl>
                @else
                <p class="text-gray-500">Walk-in Customer</p>
                @endif
            </div>
        </div>

        <!-- Items -->
        <div class="mb-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Items Sold</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Qty</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Unit Price</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($sale->items as $index => $item)
                        <tr>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $index + 1 }}</td>
                            <td class="px-6 py-4">
                                <p class="font-semibold text-gray-900">{{ $item->product->name }}</p>
                                <p class="text-xs text-gray-500">{{ $item->product->sku }}</p>
                            </td>
                            <td class="px-6 py-4 text-right text-sm text-gray-900">{{ $item->quantity }}</td>
                            <td class="px-6 py-4 text-right text-sm text-gray-900">UGX {{ number_format($item->unit_price, 0) }}</td>
                            <td class="px-6 py-4 text-right font-semibold text-gray-900">UGX {{ number_format($item->total, 0) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
<div class="flex justify-end mb-6">
    <div class="w-80 space-y-2">
        @php
            // Use correct column names from database
            $subtotal = $sale->subtotal ?? 0;
            $discountAmount = $sale->discount_amount ?? 0;  // ✅ Using 'discount_amount'
            $taxAmount = $sale->tax_amount ?? 0;            // ✅ Using 'tax_amount'
            $total = $sale->total ?? 0;
        @endphp
        
        <!-- Subtotal -->
        <div class="flex justify-between text-sm">
            <span class="text-gray-600">Subtotal:</span>
            <span class="font-semibold">UGX {{ number_format($subtotal, 0) }}</span>
        </div>
        
        <!-- Discount (Always show, even if 0) -->
        <div class="flex justify-between text-sm {{ $discountAmount > 0 ? 'text-red-600' : 'text-gray-600' }}">
            <span>Discount:</span>
            <span class="font-semibold">
                @if($discountAmount > 0)
                    -UGX {{ number_format($discountAmount, 0) }}
                @else
                    UGX 0
                @endif
            </span>
        </div>

        <!-- Tax (if applicable) -->
        @if($taxAmount > 0)
        <div class="flex justify-between text-sm">
            <span class="text-gray-600">Tax (18%):</span>
            <span class="font-semibold">UGX {{ number_format($taxAmount, 0) }}</span>
        </div>
        @endif

        <!-- Total -->
        <div class="flex justify-between text-xl font-bold text-gray-900 pt-3 border-t-2 border-gray-300">
            <span>TOTAL:</span>
            <span>UGX {{ number_format($total, 0) }}</span>
        </div>

        <!-- Payment Method -->
        <div class="flex justify-between text-sm pt-2 border-t">
            <span class="text-gray-600">Payment Method:</span>
            <span class="font-semibold">{{ ucfirst(str_replace('_', ' ', $sale->payment_method)) }}</span>
        </div>

        <!-- Payment Status -->
        <div class="flex justify-between text-sm">
            <span class="text-gray-600">Payment Status:</span>
            <span class="px-2 py-1 text-xs font-semibold rounded-full 
                {{ $sale->payment_status === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                {{ ucfirst($sale->payment_status) }}
            </span>
        </div>
    </div>
</div>

        <!-- Actions -->
        <div class="flex justify-end space-x-4 mt-6 pt-6 border-t">
            <a href="{{ route('pos.receipt', $sale->id) }}" target="_blank" class="px-6 py-3 bg-green-600 text-white font-bold text-xs rounded-lg hover:bg-green-700">
                <i class="fas fa-print mr-2"></i>Print Receipt
            </a>
        </div>
    </div>
</div>

<!-- Void Sale Modal -->
<div id="voidModal" class="fixed inset-0 bg-black/60 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 space-y-4">
        <div class="flex justify-between items-center border-b pb-3">
            <h3 class="font-extrabold text-slate-900 text-base flex items-center gap-2">
                <i class="fas fa-undo text-red-600"></i> Void Sale #{{ $sale->sale_number }}
            </h3>
            <button type="button" onclick="closeVoidModal()" class="text-slate-400 hover:text-slate-600 text-lg font-bold">&times;</button>
        </div>
        <form action="{{ route('sales.void', $sale->id) }}" method="POST">
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
                <button type="button" onclick="closeVoidModal()" class="px-4 py-2 bg-slate-200 text-slate-700 font-bold text-xs rounded-lg hover:bg-slate-300">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-red-600 text-white font-extrabold text-xs rounded-lg hover:bg-red-700 shadow">Confirm Void & Restock</button>
            </div>
        </form>
    </div>
</div>

<script>
function openVoidModal() {
    document.getElementById('voidModal').classList.remove('hidden');
}
function closeVoidModal() {
    document.getElementById('voidModal').classList.add('hidden');
}
</script>
@endsection