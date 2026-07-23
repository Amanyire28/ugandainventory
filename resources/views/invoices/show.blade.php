@extends('layouts.app')

@section('title', 'Invoice - ' . $invoice->invoice_number)

@section('page-title')
    <i class="fas fa-file-invoice-dollar text-indigo-600 mr-2"></i>Invoice details
@endsection

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Action Header (Hidden during printing) -->
    <div class="no-print mb-6 flex flex-wrap justify-between items-center gap-4">
        <a href="{{ route('invoices.index') }}" class="px-4 py-2 bg-white hover:bg-gray-50 text-gray-700 font-semibold rounded-lg border border-gray-300 shadow-sm transition duration-150 flex items-center">
            <i class="fas fa-arrow-left mr-2 text-gray-500"></i>Back to Invoices
        </a>
        <div class="flex items-center gap-2">
            @if($invoice->status === 'unpaid')
                <a href="{{ route('invoices.edit', $invoice->id) }}" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white font-semibold rounded-lg shadow-sm transition duration-150 flex items-center">
                    <i class="fas fa-edit mr-2"></i>Edit Invoice
                </a>
            @endif
            @if($invoice->status !== 'paid')
                <a href="{{ route('invoices.payForm', $invoice->id) }}" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-sm transition duration-150 flex items-center">
                    <i class="fas fa-coins mr-2"></i>Pay Invoice
                </a>
            @endif
            <button onclick="window.print()" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg shadow-sm transition duration-150 flex items-center">
                <i class="fas fa-print mr-2"></i>Print Invoice
            </button>
        </div>
    </div>

    <!-- Professional Invoice Paper Sheet -->
    <div class="bg-white rounded-2xl shadow-xl p-8 md:p-12 border border-gray-100" id="invoice">
        <!-- Business Header -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 pb-8 border-b-2 border-gray-100">
            <div>
                @if($business->logo)
                    <img src="{{ asset('storage/' . $business->logo) }}" alt="{{ $business->name }}" class="h-16 mb-4 object-contain">
                @else
                    <div class="w-12 h-12 bg-indigo-600 rounded-2xl flex items-center justify-center mb-4 shadow">
                        <span class="text-xl font-bold text-white">
                            {{ substr($business->name, 0, 1) }}
                        </span>
                    </div>
                @endif
                <h1 class="text-2xl font-black text-gray-900 tracking-tight">{{ $business->name }}</h1>
                @if($business->address)
                    <p class="text-gray-500 text-sm mt-1 max-w-sm">{{ $business->address }}</p>
                @endif
            </div>
            <div class="mt-6 md:mt-0 text-left md:text-right">
                <span class="px-3.5 py-1 text-sm font-bold rounded-full 
                    {{ $invoice->status === 'paid' ? 'bg-green-100 text-green-800' : 
                       ($invoice->status === 'partial' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                    {{ strtoupper($invoice->status) }}
                </span>
                <p class="text-3xl font-extrabold text-gray-900 mt-3">{{ $invoice->invoice_number }}</p>
                <p class="text-gray-500 text-sm mt-1">Date: {{ $invoice->created_at->format('M d, Y') }}</p>
            </div>
        </div>

        <!-- Invoice Info Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8 pb-8 border-b border-gray-100 text-sm">
            <div>
                <p class="text-xs text-gray-400 font-bold uppercase tracking-wider mb-2">Billing Information</p>
                @if($invoice->customer)
                    <p class="font-bold text-gray-900 text-base mb-1">{{ $invoice->customer->name }}</p>
                    @if($invoice->customer->phone)
                        <p class="text-gray-600 flex items-center mt-1">
                            <i class="fas fa-phone mr-2 text-gray-400 w-4"></i>{{ $invoice->customer->phone }}
                        </p>
                    @endif
                    @if($invoice->customer->email)
                        <p class="text-gray-600 flex items-center mt-1">
                            <i class="fas fa-envelope mr-2 text-gray-400 w-4"></i>{{ $invoice->customer->email }}
                        </p>
                    @endif
                    @if($invoice->customer->address)
                        <p class="text-gray-600 flex items-center mt-1">
                            <i class="fas fa-map-marker-alt mr-2 text-gray-400 w-4"></i>{{ $invoice->customer->address }}
                        </p>
                    @endif
                @else
                    <p class="text-gray-600">Walk-in Customer</p>
                @endif
            </div>
            <div>
                <p class="text-xs text-gray-400 font-bold uppercase tracking-wider mb-2">Invoice Meta</p>
                <div class="space-y-1 text-gray-600">
                    <p><span class="font-semibold text-gray-800">Time Billed:</span> {{ $invoice->created_at->format('h:i A') }}</p>
                    <p><span class="font-semibold text-gray-800">Billed By:</span> {{ $invoice->user->name }}</p>
                    @if($invoice->due_date)
                        <p class="text-amber-700 font-semibold"><span class="text-gray-800">Due Date:</span> {{ \Carbon\Carbon::parse($invoice->due_date)->format('M d, Y') }}</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <div class="mb-8 overflow-x-auto">
            <p class="text-xs text-gray-400 font-bold uppercase tracking-wider mb-4">Billed Products</p>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">#</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Product Name / Description</th>
                        <th scope="col" class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Qty</th>
                        <th scope="col" class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Unit Price</th>
                        <th scope="col" class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Line Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @foreach($invoice->items as $index => $item)
                        <tr>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-400 font-medium">
                                {{ $index + 1 }}
                            </td>
                            <td class="px-4 py-4">
                                <div class="text-sm font-semibold text-gray-900">{{ $item->description ?? optional($item->product)->name }}</div>
                                @if($item->product && $item->product->sku)
                                    <div class="text-xs text-gray-450 mt-0.5">SKU: {{ $item->product->sku }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-right text-gray-700 font-medium">
                                {{ $item->quantity }} {{ optional($item->product)->unit ?? '' }}
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-right text-gray-700 font-medium">
                                UGX {{ number_format($item->unit_price, 0) }}
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-right text-gray-900 font-bold">
                                UGX {{ number_format($item->total, 0) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Totals Layout -->
        <div class="flex flex-col md:flex-row justify-between items-start gap-6 mb-8">
            <div class="w-full md:w-1/2">
                @if($invoice->notes)
                    <div class="p-4 bg-gray-50 rounded-xl border border-gray-100">
                        <p class="text-xs text-gray-400 font-bold uppercase tracking-wider mb-1">Notes</p>
                        <p class="text-sm text-gray-600 leading-relaxed">{{ $invoice->notes }}</p>
                    </div>
                @endif
            </div>
            <div class="w-full md:w-80 ml-auto bg-gray-50 rounded-2xl p-6 border border-gray-100">
                @php
                    $subtotal = $invoice->subtotal ?? $invoice->items->sum('total');
                    $discountAmount = $invoice->discount_amount ?? 0;
                    $taxAmount = $invoice->tax_amount ?? 0;
                    $total = $invoice->total ?? ($subtotal - $discountAmount + $taxAmount);
                    $paid = $invoice->paid ?? 0;
                    $balance = $total - $paid;
                @endphp
                <div class="space-y-3 text-sm text-gray-600">
                    <div class="flex justify-between">
                        <span>Subtotal:</span>
                        <span class="font-semibold text-gray-800">UGX {{ number_format($subtotal, 0) }}</span>
                    </div>
                    <div class="flex justify-between {{ $discountAmount > 0 ? 'text-red-600 font-medium' : '' }}">
                        <span>Discount:</span>
                        <span>
                            @if($discountAmount > 0)
                                -UGX {{ number_format($discountAmount, 0) }}
                            @else
                                UGX 0
                            @endif
                        </span>
                    </div>
                    @if($taxAmount > 0)
                        <div class="flex justify-between">
                            <span>Tax (18%):</span>
                            <span class="font-semibold text-gray-800">UGX {{ number_format($taxAmount, 0) }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between text-base font-extrabold text-gray-900 pt-3 border-t border-gray-200">
                        <span>TOTAL:</span>
                        <span>UGX {{ number_format($total, 0) }}</span>
                    </div>
                    <div class="flex justify-between text-green-700 pt-2 font-semibold">
                        <span>Amount Paid:</span>
                        <span>UGX {{ number_format($paid, 0) }}</span>
                    </div>
                    <div class="flex justify-between text-amber-700 font-bold pt-1">
                        <span>Balance Due:</span>
                        <span class="px-2.5 py-0.5 text-xs rounded-full bg-amber-100 text-amber-800 font-extrabold">
                            UGX {{ number_format($balance, 0) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Receipt Footer -->
        <div class="text-center pt-8 border-t border-gray-100 mt-10">
            <h3 class="text-lg font-bold text-gray-850 mb-1">
                Thank you{{ $invoice->customer ? ', ' . $invoice->customer->name : '' }}!
            </h3>
            <p class="text-sm text-gray-500">Please clear outstanding balance within the due period.</p>
            @if($business->website)
                <p class="text-sm text-indigo-600 font-medium mt-3 hover:underline">
                    <a href="{{ $business->website }}" target="_blank">{{ $business->website }}</a>
                </p>
            @endif
        </div>
    </div>
</div>

@push('styles')
<style>
    @media print {
        body * { visibility: hidden; }
        #invoice, #invoice * { visibility: visible; }
        #invoice { position: absolute; left: 0; top: 0; width: 100%; border: none !important; box-shadow: none !important; padding: 0 !important; }
        .no-print { display: none !important; }
    }
</style>
@endpush
@endsection