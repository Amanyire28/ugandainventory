@extends('layouts.app')

@section('title', "Customer: " . $customer->name)

@section('page-title')
    <i class="fas fa-user-tag text-indigo-600 mr-2"></i>Customer Financial Summary
@endsection

@section('content')
@php
    $totalOutstanding = $outstandingInvoices->sum(fn($inv) => $inv->total - $inv->paid);
@endphp
<div class="max-w-4xl mx-auto mb-8">
    
    <!-- Action Header -->
    <div class="mb-6 flex justify-between items-center">
        <a href="{{ route('invoices.customersWithInvoices') }}" class="px-4 py-2 bg-white hover:bg-gray-50 text-gray-700 font-semibold rounded-lg border border-gray-300 shadow-sm transition flex items-center">
            <i class="fas fa-arrow-left mr-2 text-gray-500"></i>Back to Customer List
        </a>
    </div>

    <!-- Customer Header Profile -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-6 border border-gray-100 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
        <div>
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-indigo-600 rounded-2xl flex items-center justify-center text-white font-bold text-lg shadow-sm">
                    {{ substr($customer->name, 0, 1) }}
                </div>
                <div>
                    <h2 class="text-2xl font-black text-gray-900 leading-tight">{{ $customer->name }}</h2>
                    <p class="text-sm text-gray-500 mt-0.5">Credit limit: UGX {{ number_format($customer->credit_limit ?? 0) }}</p>
                </div>
            </div>
            <div class="mt-4 flex flex-wrap gap-4 text-xs text-gray-600">
                @if($customer->phone)
                    <p><span class="font-bold text-gray-800"><i class="fas fa-phone mr-1.5 text-gray-400"></i></span>{{ $customer->phone }}</p>
                @endif
                @if($customer->email)
                    <p><span class="font-bold text-gray-800"><i class="fas fa-envelope mr-1.5 text-gray-400"></i></span>{{ $customer->email }}</p>
                @endif
                @if($customer->address)
                    <p><span class="font-bold text-gray-800"><i class="fas fa-map-marker-alt mr-1.5 text-gray-400"></i></span>{{ $customer->address }}</p>
                @endif
            </div>
        </div>
        <div class="w-full md:w-auto text-left md:text-right">
            <span class="inline-block px-4 py-2 bg-amber-50 text-amber-800 border border-amber-200 font-extrabold rounded-xl text-lg shadow-sm">
                Outstanding Balance: UGX {{ number_format($totalOutstanding) }}
            </span>
        </div>
    </div>

    <!-- Tab Buttons -->
    <div class="flex space-x-2 mb-6 overflow-x-auto border-b pb-4">
        <button id="btn-outstanding" onclick="showTab('outstanding')" class="tab-btn px-4 py-2 rounded-lg font-semibold whitespace-nowrap bg-indigo-600 text-white shadow">
            <i class="fas fa-hourglass-half mr-2"></i>Outstanding Invoices ({{ $outstandingInvoices->count() }})
        </button>
        <button id="btn-cleared" onclick="showTab('cleared')" class="tab-btn px-4 py-2 rounded-lg font-semibold whitespace-nowrap bg-gray-100 text-gray-700 hover:bg-gray-200">
            <i class="fas fa-check-circle mr-2"></i>Cleared Invoices ({{ $paidInvoices->count() }})
        </button>
        <button id="btn-history" onclick="showTab('history')" class="tab-btn px-4 py-2 rounded-lg font-semibold whitespace-nowrap bg-gray-100 text-gray-700 hover:bg-gray-200">
            <i class="fas fa-history mr-2"></i>Payment History ({{ $payments->count() }})
        </button>
    </div>

    <!-- Outstanding Invoices Tab Content -->
    <div id="tab-outstanding" class="tab-content bg-white rounded-xl shadow-lg p-6 border border-gray-100">
        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center"><i class="fas fa-hourglass-half text-amber-500 mr-2"></i>Outstanding Invoices</h3>
        @if($outstandingInvoices->count())
            <div class="overflow-x-auto rounded-lg border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-500">Invoice #</th>
                            <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-500">Issued Date</th>
                            <th scope="col" class="px-4 py-3 text-right font-semibold text-gray-500">Total</th>
                            <th scope="col" class="px-4 py-3 text-right font-semibold text-gray-500">Paid</th>
                            <th scope="col" class="px-4 py-3 text-right font-semibold text-gray-500">Balance</th>
                            <th scope="col" class="px-4 py-3 class=text-center font-semibold text-gray-500 text-center">Status</th>
                            @if(Auth::user()->can('viewAny', \App\Models\Invoice::class))
                                <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-500">Billed By</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200 text-gray-700">
                        @foreach($outstandingInvoices as $inv)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3.5 font-bold text-indigo-650">
                                    <a href="{{ route('invoices.show', $inv->id) }}" class="hover:underline">{{ $inv->invoice_number }}</a>
                                </td>
                                <td class="px-4 py-3.5">{{ $inv->created_at->format('M d, Y') }}</td>
                                <td class="px-4 py-3.5 text-right">UGX {{ number_format($inv->total) }}</td>
                                <td class="px-4 py-3.5 text-right text-green-700 font-semibold">UGX {{ number_format($inv->paid) }}</td>
                                <td class="px-4 py-3.5 text-right text-red-600 font-bold">UGX {{ number_format($inv->total - $inv->paid) }}</td>
                                <td class="px-4 py-3.5 text-center">
                                    <span class="px-2 py-0.5 text-xs font-bold rounded-full bg-yellow-100 text-yellow-800">{{ strtoupper($inv->status) }}</span>
                                </td>
                                @if(Auth::user()->can('viewAny', \App\Models\Invoice::class))
                                    <td class="px-4 py-3.5 text-gray-550">{{ $inv->user->name ?? '-' }}</td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-8 text-gray-400 font-medium">No outstanding invoices found for this customer.</div>
        @endif
    </div>

    <!-- Cleared Invoices Tab Content -->
    <div id="tab-cleared" class="tab-content bg-white rounded-xl shadow-lg p-6 border border-gray-100 hidden">
        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center"><i class="fas fa-check-circle text-green-600 mr-2"></i>Cleared Invoices</h3>
        @if($paidInvoices->count())
            <div class="overflow-x-auto rounded-lg border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-500">Invoice #</th>
                            <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-500">Date Paid</th>
                            <th scope="col" class="px-4 py-3 text-right font-semibold text-gray-500">Total Billed</th>
                            <th scope="col" class="px-4 py-3 text-right font-semibold text-gray-500">Amount Paid</th>
                            <th scope="col" class="px-4 py-3 text-center font-semibold text-gray-500">Status</th>
                            @if(Auth::user()->can('viewAny', \App\Models\Invoice::class))
                                <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-500">Billed By</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200 text-gray-700">
                        @foreach($paidInvoices as $inv)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3.5 font-bold text-indigo-650">
                                    <a href="{{ route('invoices.show', $inv->id) }}" class="hover:underline">{{ $inv->invoice_number }}</a>
                                </td>
                                <td class="px-4 py-3.5">{{ $inv->updated_at->format('M d, Y') }}</td>
                                <td class="px-4 py-3.5 text-right">UGX {{ number_format($inv->total) }}</td>
                                <td class="px-4 py-3.5 text-right text-green-700 font-bold">UGX {{ number_format($inv->paid) }}</td>
                                <td class="px-4 py-3.5 text-center">
                                    <span class="px-2 py-0.5 text-xs font-bold rounded-full bg-green-100 text-green-800">PAID</span>
                                </td>
                                @if(Auth::user()->can('viewAny', \App\Models\Invoice::class))
                                    <td class="px-4 py-3.5 text-gray-550">{{ $inv->user->name ?? '-' }}</td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-8 text-gray-400 font-medium">No cleared invoices found for this customer.</div>
        @endif
    </div>

    <!-- Payment History Tab Content -->
    <div id="tab-history" class="tab-content bg-white rounded-xl shadow-lg p-6 border border-gray-100 hidden">
        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center"><i class="fas fa-history text-indigo-600 mr-2"></i>Payment History</h3>
        @if($payments->count())
            <div class="overflow-x-auto rounded-lg border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-500">Payment Date</th>
                            <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-500">Invoice Reference</th>
                            <th scope="col" class="px-4 py-3 text-right font-semibold text-gray-500">Amount Paid</th>
                            <th scope="col" class="px-4 py-3 text-center font-semibold text-gray-500">Invoice Status</th>
                            @if(Auth::user()->can('viewAny', \App\Models\Invoice::class))
                                <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-500">Received By</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200 text-gray-700">
                        @foreach($payments as $p)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3.5 font-medium">{{ \Carbon\Carbon::parse($p->paid_at)->format('M d, Y H:i A') }}</td>
                                <td class="px-4 py-3.5 font-bold text-indigo-650">
                                    <a href="{{ route('invoices.show', optional($p->invoice)->id) }}" class="hover:underline">{{ optional($p->invoice)->invoice_number }}</a>
                                </td>
                                <td class="px-4 py-3.5 text-right font-bold text-green-700">UGX {{ number_format($p->amount_paid) }}</td>
                                <td class="px-4 py-3.5 text-center">
                                    <span class="px-2 py-0.5 text-xs font-bold rounded-full 
                                        @if(optional($p->invoice)->status==='paid') bg-green-100 text-green-800
                                        @elseif(optional($p->invoice)->status==='partial') bg-yellow-100 text-yellow-805
                                        @else bg-red-100 text-red-800 @endif">
                                        {{ strtoupper(optional($p->invoice)->status) }}
                                    </span>
                                </td>
                                @if(Auth::user()->can('viewAny', \App\Models\Invoice::class))
                                    <td class="px-4 py-3.5 text-gray-550">{{ $p->user->name ?? '-' }}</td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-8 text-gray-400 font-medium">No payment history found for this customer.</div>
        @endif
    </div>
</div>

<script>
function showTab(tab) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    document.getElementById('tab-' + tab).classList.remove('hidden');
    
    document.querySelectorAll('.tab-btn').forEach(el => {
        el.classList.remove('bg-indigo-600', 'text-white', 'shadow');
        el.classList.add('bg-gray-100', 'text-gray-700', 'hover:bg-gray-200');
    });
    
    const activeBtn = document.getElementById('btn-' + tab);
    activeBtn.classList.remove('bg-gray-100', 'text-gray-700', 'hover:bg-gray-200');
    activeBtn.classList.add('bg-indigo-600', 'text-white', 'shadow');
}
</script>
@endsection