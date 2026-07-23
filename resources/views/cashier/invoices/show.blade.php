@extends('layouts.cashier-layout')

@section('title', 'Invoice - ' . $invoice->invoice_number)

@section('page-title')
    <i class="fas fa-file-invoice-dollar text-indigo-600 mr-2"></i>
    Invoice - {{ $invoice->invoice_number }}
@endsection

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-4 flex gap-2 items-center no-print">
        <a href="{{ route('cashier.invoices.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 flex items-center">
            <i class="fas fa-arrow-left mr-2"></i>Back to Invoices
        </a>
        <a href="{{ route('cashier.invoices.print', $invoice->id) }}" target="_blank" class="px-5 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 flex items-center">
            <i class="fas fa-print mr-2"></i>Print Invoice
        </a>
        @if($invoice->status !== 'paid')
            {{-- Payment Modal Trigger --}}
            <button type="button" onclick="document.getElementById('payModal').classList.remove('hidden')"
                class="px-5 py-2 bg-green-600 text-white rounded hover:bg-green-700 flex items-center font-bold">
                <i class="fas fa-coins mr-2"></i> Record Payment
            </button>
        @endif
        <form action="{{ route('cashier.invoices.show', $invoice->id) }}" method="POST" onsubmit="return confirm('Delete this invoice?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="px-5 py-2 bg-red-100 text-red-700 rounded hover:bg-red-200 flex items-center font-bold">
                <i class="fas fa-trash mr-2"></i>Delete
            </button>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-8 mt-2" id="invoice">
        <!-- Business Header -->
        <div class="text-center mb-6 pb-6 border-b-2 border-gray-300">
            @if($business->logo)
                <img src="{{ asset('storage/' . $business->logo) }}" alt="{{ $business->name }}" class="h-20 mx-auto mb-3">
            @endif
            <h1 class="text-2xl font-bold text-gray-900">{{ $business->name }}</h1>
            @if($business->address)
                <p class="text-gray-600">{{ $business->address }}</p>
            @endif
            <p class="text-gray-600">
                @if($business->phone)
                    Tel: {{ $business->phone }}
                @endif
                @if($business->email)
                    | Email: {{ $business->email }}
                @endif
            </p>
        </div>

        <!-- Invoice Info -->
        <div class="grid grid-cols-2 gap-6 mb-6 pb-6 border-b border-gray-200">
            <div>
                <p class="text-sm text-gray-500 font-semibold mb-2">INVOICE DETAILS</p>
                <p class="font-bold text-lg text-indigo-600">{{ $invoice->invoice_number }}</p>
                <p class="text-sm text-gray-600">
                    <i class="fas fa-calendar mr-1"></i>
                    {{ $invoice->created_at->format('d M Y') }}
                </p>
                <p class="text-sm text-gray-600">
                    <i class="fas fa-clock mr-1"></i>
                    {{ $invoice->created_at->format('h:i A') }}
                </p>
                <p class="text-sm text-gray-600">
                    <i class="fas fa-user mr-1"></i>
                    Handled by: {{ $invoice->user->name }}
                </p>
                <p class="text-sm text-gray-600">
                    Status:
                    @if($invoice->status == 'paid')
                        <span class="px-2 py-1 rounded-full bg-green-100 text-green-800 font-bold">PAID</span>
                    @elseif($invoice->status == 'cancelled')
                        <span class="px-2 py-1 rounded-full bg-gray-200 text-gray-700 font-bold">CANCELLED</span>
                    @else
                        <span class="px-2 py-1 rounded-full bg-yellow-100 text-yellow-700 font-bold">{{ strtoupper($invoice->status) }}</span>
                    @endif
                </p>
                @if($invoice->due_date)
                    <p class="text-sm text-gray-600">
                        <i class="fas fa-hourglass-end mr-1"></i>
                        Due on: {{ \Carbon\Carbon::parse($invoice->due_date)->format('d M Y') }}
                    </p>
                @endif
            </div>
            <div>
                <p class="text-sm text-gray-500 font-semibold mb-2">BILL TO</p>
                @if($invoice->customer)
                    <p class="font-semibold text-gray-900">{{ $invoice->customer->name }}</p>
                    @if($invoice->customer->phone)
                        <p class="text-sm text-gray-600">
                            <i class="fas fa-phone mr-1"></i>
                            {{ $invoice->customer->phone }}
                        </p>
                    @endif
                    @if($invoice->customer->email)
                        <p class="text-sm text-gray-600">
                            <i class="fas fa-envelope mr-1"></i>
                            {{ $invoice->customer->email }}
                        </p>
                    @endif
                    @if($invoice->customer->address)
                        <p class="text-sm text-gray-600">
                            <i class="fas fa-map-marker-alt mr-1"></i>
                            {{ $invoice->customer->address }}
                        </p>
                    @endif
                @else
                    <p class="text-gray-600">Walk-in Customer</p>
                @endif
            </div>
        </div>

        <!-- Items Table -->
        <div class="mb-6">
            <p class="text-sm text-gray-500 font-semibold mb-3">ITEMS BILLED</p>
            <table class="min-w-full">
                <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Qty</th>
                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Unit Price</th>
                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                @foreach($invoice->items as $index => $item)
                    <tr>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $index + 1 }}</td>
                        <td class="px-4 py-3">
                            <p class="text-sm font-semibold text-gray-900">{{ $item->description ?? $item->product->name ?? '-' }}</p>
                            @if($item->product && $item->product->sku)
                                <p class="text-xs text-gray-500">{{ $item->product->sku }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-right text-gray-600">
                            {{ $item->quantity }} {{ $item->product->unit ?? '' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-right text-gray-600">
                            UGX {{ number_format($item->unit_price, 0) }}
                        </td>
                        <td class="px-4 py-3 text-sm text-right font-semibold text-gray-900">
                            UGX {{ number_format($item->total, 0) }}
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <!-- Totals Section -->
        <div class="flex justify-end mb-6">
            <div class="w-80 space-y-2">
                @php
                    $subtotal = $invoice->subtotal ?? $invoice->items->sum('total');
                    $discountAmount = $invoice->discount_amount ?? 0;
                    $taxAmount = $invoice->tax_amount ?? 0;
                    $total = $invoice->total ?? ($subtotal - $discountAmount + $taxAmount);
                    $paid = $invoice->paid ?? 0;
                    $balance = $total - $paid;
                @endphp
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Subtotal:</span>
                    <span class="font-semibold">UGX {{ number_format($subtotal, 0) }}</span>
                </div>
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
                @if($taxAmount > 0)
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Tax (18%):</span>
                    <span class="font-semibold">UGX {{ number_format($taxAmount, 0) }}</span>
                </div>
                @endif
                <div class="flex justify-between text-xl font-bold text-gray-900 pt-3 border-t-2 border-gray-300">
                    <span>TOTAL:</span>
                    <span>UGX {{ number_format($total, 0) }}</span>
                </div>
                <div class="flex justify-between text-sm pt-2 border-t">
                    <span class="text-gray-600">Paid:</span>
                    <span class="font-semibold text-green-700">UGX {{ number_format($paid, 0) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Balance Due:</span>
                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-700">
                        UGX {{ number_format($balance, 0) }}
                    </span>
                </div>
            </div>
        </div>
        @if($invoice->notes)
        <div class="mb-6 p-4 bg-gray-50 rounded-lg">
            <p class="text-sm font-semibold text-gray-700 mb-1">Notes:</p>
            <p class="text-sm text-gray-600">{{ $invoice->notes }}</p>
        </div>
        @endif

        <div class="text-center pt-6 border-t-2 border-gray-300">
            <p class="text-lg font-semibold text-gray-900 mb-2">
                Thank you{{ $invoice->customer ? ', ' . $invoice->customer->name : '' }}!
            </p>
            <p class="text-sm text-gray-600">Please clear your invoice balance before the due date.</p>
            @if($business->website)
                <p class="text-sm text-gray-600 mt-2">{{ $business->website }}</p>
            @endif
        </div>
    </div>
</div>

{{-- Payment Modal --}}
@if($invoice->status !== 'paid')
<div id="payModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 no-print">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 p-8">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-bold text-gray-900"><i class="fas fa-coins text-green-600 mr-2"></i>Record Payment</h3>
            <button onclick="document.getElementById('payModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
        </div>

        @php $balance = $invoice->total - $invoice->paid; @endphp

        <div class="bg-gray-50 rounded-xl p-4 mb-5 text-sm space-y-1">
            <div class="flex justify-between">
                <span class="text-gray-500">Invoice Total:</span>
                <span class="font-semibold">UGX {{ number_format($invoice->total, 0) }}</span>
            </div>
            <div class="flex justify-between text-green-700">
                <span>Already Paid:</span>
                <span class="font-semibold">UGX {{ number_format($invoice->paid, 0) }}</span>
            </div>
            <div class="flex justify-between text-red-600 font-bold border-t pt-2">
                <span>Balance Due:</span>
                <span>UGX {{ number_format($balance, 0) }}</span>
            </div>
        </div>

        <form action="{{ route('cashier.invoices.markPaid', $invoice->id) }}" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Payment Type</label>
                <div class="flex gap-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="payment_type" value="full" checked
                            onchange="document.getElementById('amountField').value = {{ $balance }}; document.getElementById('amountField').readOnly = true;">
                        <span class="text-sm font-medium">Full Balance (UGX {{ number_format($balance, 0) }})</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="payment_type" value="partial"
                            onchange="document.getElementById('amountField').readOnly = false; document.getElementById('amountField').focus();">
                        <span class="text-sm font-medium">Partial</span>
                    </label>
                </div>
            </div>
            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Amount (UGX)</label>
                <input id="amountField" type="number" name="amount" value="{{ $balance }}"
                    min="1" max="{{ $balance }}" step="1" readonly
                    class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-gray-900 focus:ring-2 focus:ring-green-500 focus:border-green-500">
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="document.getElementById('payModal').classList.add('hidden')"
                    class="flex-1 px-4 py-2.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-semibold">Cancel</button>
                <button type="submit"
                    class="flex-1 px-4 py-2.5 bg-green-600 text-white rounded-lg hover:bg-green-700 font-bold">
                    <i class="fas fa-check mr-1"></i> Confirm Payment
                </button>
            </div>
        </form>
    </div>
</div>
@endif

@push('styles')
<style>
    @media print {
        body * { visibility: hidden; }
        #invoice, #invoice * { visibility: visible; }
        #invoice { position: absolute; left: 0; top: 0; width: 100%; }
        .no-print { display: none !important; }
        .bg-white { box-shadow: none !important; }
    }
</style>
@endpush
@endsection