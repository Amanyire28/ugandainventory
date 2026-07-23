@extends('layouts.app')

@section('title', 'Edit Invoice - ' . $invoice->invoice_number)

@section('page-title')
    <i class="fas fa-edit text-indigo-600 mr-2"></i>Edit Invoice
@endsection

@section('content')
<div class="max-w-5xl mx-auto mb-8">
    
    <!-- Action Header -->
    <div class="mb-6 flex justify-between items-center">
        <a href="{{ route('invoices.show', $invoice->id) }}" class="px-4 py-2 bg-white hover:bg-gray-50 text-gray-700 font-semibold rounded-lg border border-gray-300 shadow-sm transition duration-150 flex items-center">
            <i class="fas fa-arrow-left mr-2 text-gray-500"></i>Back to Details
        </a>
    </div>

    {{-- Notifications --}}
    @if (session('success'))
        <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-r-lg shadow-sm flex items-center">
            <i class="fas fa-check-circle mr-3 text-lg text-green-500"></i>
            <span>{{ session('success') }}</span>
        </div>
    @elseif (session('error'))
        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-r-lg shadow-sm flex items-center">
            <i class="fas fa-exclamation-circle mr-3 text-lg text-red-500"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-r-lg shadow-sm">
            <div class="flex items-center mb-2">
                <i class="fas fa-exclamation-triangle mr-3 text-lg text-red-500"></i>
                <span class="font-bold">Validation Errors:</span>
            </div>
            <ul class="list-disc ml-8 text-sm">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Invoice Header Info -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
            <div>
                <span class="text-xs text-gray-450 font-bold uppercase tracking-wider">Invoice Info</span>
                <h2 class="text-xl font-bold text-gray-900 mt-1">Invoice {{ $invoice->invoice_number }}</h2>
                <div class="text-sm text-gray-600 mt-2 space-y-1">
                    <p><span class="font-semibold text-gray-800">Customer:</span> {{ $invoice->customer->name ?? 'Unknown' }}</p>
                    <p><span class="font-semibold text-gray-800">Date Issued:</span> {{ $invoice->created_at->format('M d, Y H:i A') }}</p>
                </div>
            </div>
            <div>
                <span class="text-xs text-gray-450 font-bold uppercase tracking-wider">Invoice Notes</span>
                <div class="mt-1 p-3 bg-gray-50 rounded-lg text-sm text-gray-600 border border-gray-100">
                    {{ $invoice->notes ?? 'No notes recorded for this invoice.' }}
                </div>
            </div>
        </div>

        <hr class="my-6 border-gray-100">

        <!-- Items Billed -->
        <span class="text-xs text-gray-450 font-bold uppercase tracking-wider block mb-3">Invoice Items</span>
        <div class="overflow-x-auto rounded-lg border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Product</th>
                        <th scope="col" class="px-4 py-2 text-right text-xs font-semibold text-gray-500 uppercase">Qty</th>
                        <th scope="col" class="px-4 py-2 text-right text-xs font-semibold text-gray-500 uppercase">Unit</th>
                        <th scope="col" class="px-4 py-2 text-right text-xs font-semibold text-gray-500 uppercase">Price</th>
                        <th scope="col" class="px-4 py-2 text-right text-xs font-semibold text-gray-500 uppercase">Disc</th>
                        <th scope="col" class="px-4 py-2 text-right text-xs font-semibold text-gray-500 uppercase">Line Total</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200 text-sm text-gray-700">
                    @forelse($invoice->items as $i)
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $i->description ?? ($i->product->name ?? '-') }}</td>
                            <td class="px-4 py-3 text-right">{{ $i->quantity }}</td>
                            <td class="px-4 py-3 text-right text-gray-500">{{ $i->product->unit ?? '-' }}</td>
                            <td class="px-4 py-3 text-right">UGX {{ number_format($i->unit_price, 0) }}</td>
                            <td class="px-4 py-3 text-right text-red-600">-UGX {{ number_format($i->discount ?? 0, 0) }}</td>
                            <td class="px-4 py-3 text-right font-bold text-gray-900">UGX {{ number_format(($i->unit_price - ($i->discount ?? 0)) * $i->quantity, 0) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-gray-400">No items in this invoice</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-gray-50 font-bold text-gray-900 border-t-2 border-gray-250">
                    <tr>
                        <td colspan="5" class="px-4 py-3 text-right text-sm">TOTAL:</td>
                        <td class="px-4 py-3 text-right text-base text-indigo-600">UGX {{ number_format($invoice->total, 0) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        {{-- SEE HISTORY BUTTON --}}
        @if(isset($history) && $history->count())
            <div class="mt-6 flex justify-end">
                <button type="button" onclick="toggleHistory()" class="px-4 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-600 font-semibold rounded-lg border border-indigo-200 transition-colors flex items-center">
                    <i class="fas fa-history mr-2"></i> See Invoice History
                </button>
            </div>
        @endif
    </div>

    {{-- HIDDEN HISTORY BLOCK --}}
    @if(isset($history) && $history->count())
        <div class="bg-white rounded-xl shadow-lg p-6 mb-10 transition-all duration-300" id="historyBlock" style="display:none;">
            <h2 class="text-lg font-bold mb-4 text-gray-800 flex items-center">
                <i class="fas fa-history mr-2 text-indigo-600"></i> Change Audit Log
            </h2>
            <div class="overflow-x-auto rounded-lg border border-gray-100">
                <table class="min-w-full divide-y divide-gray-200 text-xs">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-4 py-2 text-left font-semibold text-gray-500 uppercase">Edited At</th>
                            <th scope="col" class="px-4 py-2 text-left font-semibold text-gray-500 uppercase">Edited By</th>
                            <th scope="col" class="px-4 py-2 text-left font-semibold text-gray-500 uppercase">Customer</th>
                            <th scope="col" class="px-4 py-2 text-left font-semibold text-gray-500 uppercase">Items</th>
                            <th scope="col" class="px-4 py-2 text-right font-semibold text-gray-500 uppercase">Subtotal</th>
                            <th scope="col" class="px-4 py-2 text-right font-semibold text-gray-500 uppercase">Total</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200 text-gray-700">
                        @foreach($history as $log)
                            @php $snap = json_decode($log->snapshot, true); @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 whitespace-nowrap font-medium">{{ \Carbon\Carbon::parse($log->created_at)->format('M d, Y H:i') }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-indigo-600 font-semibold">{{ $log->edited_by ?? '-' }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">{{ $snap['customer'] ?? '-' }}</td>
                                <td class="px-4 py-3">
                                    <ul class="list-disc ml-4 space-y-0.5">
                                        @foreach(($snap['items'] ?? []) as $row)
                                            <li>{{ $row['product_name'] ?? $row['product_id'] }} ({{ $row['quantity'] }} × {{ number_format($row['price'], 0) }})</li>
                                        @endforeach
                                    </ul>
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">UGX {{ number_format($snap['subtotal'] ?? 0, 0) }}</td>
                                <td class="px-4 py-3 text-right whitespace-nowrap font-bold text-gray-900">UGX {{ number_format($snap['total'] ?? 0, 0) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>

<script>
    function toggleHistory() {
        var hb = document.getElementById('historyBlock');
        if(hb.style.display === 'none') {
            hb.style.display = '';
        } else {
            hb.style.display = 'none';
        }
    }
</script>
@endsection