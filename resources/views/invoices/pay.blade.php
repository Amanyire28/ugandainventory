@extends('layouts.app')

@section('title', 'Pay Invoice - ' . $invoice->invoice_number)

@section('page-title')
    <i class="fas fa-coins text-green-600 mr-2"></i>Pay Invoice
@endsection

@section('content')
<div class="max-w-3xl mx-auto my-6">
    
    <!-- Action Header -->
    <div class="mb-6 flex justify-between items-center">
        <a href="{{ route('invoices.show', $invoice->id) }}" class="px-4 py-2 bg-white hover:bg-gray-50 text-gray-700 font-semibold rounded-lg border border-gray-300 shadow-sm transition duration-150 flex items-center">
            <i class="fas fa-arrow-left mr-2 text-gray-500"></i>Back to Invoice Details
        </a>
    </div>

    <!-- Invoice Overview Panel -->
    <div class="bg-white rounded-2xl shadow-lg p-6 md:p-8 mb-6 border border-gray-100">
        <div class="flex flex-wrap justify-between items-center gap-4 mb-4 pb-4 border-b border-gray-100">
            <div>
                <span class="text-xs text-gray-400 font-bold uppercase tracking-wider">Payments Portal</span>
                <h2 class="text-xl font-extrabold text-gray-900 mt-1 flex items-center gap-3">
                    <span>Invoice {{ $invoice->invoice_number }}</span>
                    <span class="px-2.5 py-0.5 text-xs font-bold rounded-full 
                        @if($invoice->status==='paid') bg-green-100 text-green-800
                        @elseif($invoice->status==='partial') bg-yellow-100 text-yellow-800
                        @else bg-red-100 text-red-805 @endif">
                        {{ strtoupper($invoice->status) }}
                    </span>
                </h2>
                <p class="text-gray-500 text-xs mt-1">Issued: {{ $invoice->created_at->format('M d, Y h:i A') }}</p>
            </div>
            <div class="text-left md:text-right text-sm text-gray-650">
                <p><span class="font-semibold text-gray-800">Customer:</span> {{ $invoice->customer->name ?? 'Walk-in Customer' }}</p>
                @if($invoice->customer && $invoice->customer->phone)
                    <p class="mt-0.5"><i class="fas fa-phone text-gray-400 mr-1.5"></i>{{ $invoice->customer->phone }}</p>
                @endif
                <p class="mt-0.5"><span class="font-semibold text-gray-800">Created By:</span> {{ $invoice->user->name ?? '---' }}</p>
            </div>
        </div>

        <!-- Short Items Table -->
        <span class="text-xs text-gray-400 font-bold uppercase tracking-wider block mb-3">Items Summary</span>
        <div class="overflow-x-auto rounded-xl border border-gray-250 mb-6">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-4 py-2 text-left font-semibold text-gray-500">Description</th>
                        <th scope="col" class="px-4 py-2 text-right font-semibold text-gray-500">Qty</th>
                        <th scope="col" class="px-4 py-2 text-right font-semibold text-gray-500">Price</th>
                        <th scope="col" class="px-4 py-2 text-right font-semibold text-gray-500">Total</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200 text-gray-700">
                    @foreach($invoice->items as $item)
                        <tr>
                            <td class="px-4 py-2.5">
                                <div class="font-semibold text-gray-900">{{ $item->description ?? optional($item->product)->name }}</div>
                            </td>
                            <td class="px-4 py-2.5 text-right font-medium">{{ $item->quantity }}</td>
                            <td class="px-4 py-2.5 text-right">UGX {{ number_format($item->unit_price, 0) }}</td>
                            <td class="px-4 py-2.5 text-right font-bold text-gray-900">UGX {{ number_format(($item->unit_price - ($item->discount ?? 0)) * $item->quantity, 0) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Checkout Breakdown -->
        <div class="flex justify-end">
            <div class="w-full md:w-80 bg-gray-50 border border-gray-100 rounded-xl p-5 text-sm text-gray-600 space-y-2">
                <div class="flex justify-between">
                    <span>Invoice Total:</span>
                    <span class="font-semibold text-gray-800">UGX {{ number_format($invoice->total, 0) }}</span>
                </div>
                <div class="flex justify-between text-green-700 font-semibold">
                    <span>Already Paid:</span>
                    <span>UGX {{ number_format($invoice->paid, 0) }}</span>
                </div>
                <div class="flex justify-between text-red-650 font-extrabold pt-2 border-t border-gray-200 text-base">
                    <span>Balance Due:</span>
                    <span>UGX {{ number_format($invoice->total - $invoice->paid, 0) }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Processing Form -->
    @if($invoice->status !== 'paid')
        <div class="bg-white rounded-2xl shadow-lg p-6 md:p-8 border border-gray-100">
            <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                <i class="fas fa-coins text-green-600 mr-2"></i>Record New Payment
            </h3>
            <form method="POST" action="{{ route('invoices.pay', $invoice->id) }}" class="space-y-6">
                @csrf
                
                <!-- Payment Mode Selector -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Payment Option</label>
                    <div class="grid grid-cols-2 gap-4">
                        <label class="flex items-center justify-between p-4 bg-gray-50 hover:bg-gray-100 rounded-xl border border-gray-200 cursor-pointer transition">
                            <span class="flex items-center gap-3">
                                <input type="radio" name="payment_type" value="full" 
                                    {{ old('payment_type', 'full') === 'full' ? 'checked' : '' }} 
                                    class="text-indigo-600 focus:ring-indigo-500 h-4 w-4">
                                <span class="text-sm font-bold text-gray-800">Pay In Full</span>
                            </span>
                            <span class="text-xs text-green-700 font-bold bg-green-50 px-2.5 py-0.5 rounded-full">UGX {{ number_format($invoice->total - $invoice->paid, 0) }}</span>
                        </label>
                        <label class="flex items-center p-4 bg-gray-50 hover:bg-gray-100 rounded-xl border border-gray-200 cursor-pointer transition">
                            <input type="radio" name="payment_type" value="partial" 
                                {{ old('payment_type') === 'partial' ? 'checked' : '' }} 
                                class="text-indigo-600 focus:ring-indigo-500 h-4 w-4 mr-3">
                            <span class="text-sm font-bold text-gray-800">Partial Payment</span>
                        </label>
                    </div>
                </div>

                <input type="hidden" name="amount" id="fullPaymentAmountInput" value="{{ $invoice->total - $invoice->paid }}">

                <!-- Partial Payment Field (Shown conditionally via script) -->
                <div id="partialAmountDiv" class="mb-4 hidden p-4 bg-gray-55 rounded-xl border border-gray-200 space-y-3">
                    <label for="partialPaymentInput" class="block text-sm font-bold text-gray-700">Enter Payment Amount (UGX)</label>
                    <div class="relative max-w-sm rounded-lg shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 text-sm font-bold">UGX</span>
                        </div>
                        <input type="number" min="1" max="{{ $invoice->total - $invoice->paid }}" name="amount"
                            id="partialPaymentInput" class="w-full pl-12 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 font-semibold"
                            value="{{ old('amount') }}" placeholder="0">
                    </div>
                    @error('amount')
                        <div class="text-xs text-red-500 mt-1 font-semibold">{{ $message }}</div>
                    @enderror
                    <div class="flex flex-col gap-1 text-xs text-gray-500 pt-1">
                        <p>Total Outstanding: <span class="font-bold text-gray-750">UGX {{ number_format($invoice->total - $invoice->paid) }}</span></p>
                        <p id="liveBalance" class="text-indigo-600 font-bold hidden">
                            Calculated Remaining Balance: <span id="calculatedBalance"></span>
                        </p>
                    </div>
                </div>

                <!-- Action buttons -->
                <div class="flex gap-3 justify-end pt-4 border-t border-gray-100">
                    <a href="{{ route('invoices.show', $invoice->id) }}" class="px-5 py-2.5 bg-white hover:bg-gray-50 text-gray-700 font-semibold rounded-xl border border-gray-300 transition shadow-sm">
                        Cancel
                    </a>
                    <button type="submit" class="px-6 py-2.5 bg-green-600 hover:bg-green-700 text-white font-bold rounded-xl transition shadow-sm">
                        Submit Payment
                    </button>
                </div>
            </form>
        </div>
    @else
        <div class="bg-green-50 border border-green-200 rounded-2xl p-8 text-center shadow-sm">
            <i class="fas fa-check-circle text-4xl text-green-500 mb-3"></i>
            <h3 class="text-lg font-bold text-green-950">Invoice Fully Paid</h3>
            <p class="text-green-700 text-sm mt-1">This invoice has already been cleared. No further payments are outstanding.</p>
        </div>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const radios = document.getElementsByName('payment_type');
    const partialDiv = document.getElementById('partialAmountDiv');
    const partialInput = document.getElementById('partialPaymentInput');
    const fullAmountInput = document.getElementById('fullPaymentAmountInput');
    const liveBalanceDiv = document.getElementById('liveBalance');
    const calculatedBalance = document.getElementById('calculatedBalance');
    const outstanding = {{ $invoice->total - $invoice->paid }};

    function checkInitial() {
        const checkedRadio = document.querySelector('input[name="payment_type"]:checked');
        if (checkedRadio && checkedRadio.value === 'partial') {
            partialDiv.classList.remove('hidden');
            if (partialInput) {
                fullAmountInput.disabled = true;
                partialInput.disabled = false;
                partialInput.focus();
                updateLiveBalance();
            }
        } else {
            partialDiv.classList.add('hidden');
            liveBalanceDiv.classList.add('hidden');
            if (fullAmountInput) {
                fullAmountInput.disabled = false;
                if (partialInput) partialInput.disabled = true;
            }
        }
    }

    function updateLiveBalance() {
        if (!partialInput) return;
        const value = parseFloat(partialInput.value || 0);
        if (value > 0 && value <= outstanding) {
            calculatedBalance.innerText = 'UGX ' + (outstanding - value).toLocaleString('en-US', {minimumFractionDigits: 0, maximumFractionDigits: 0});
            liveBalanceDiv.classList.remove('hidden');
        } else {
            liveBalanceDiv.classList.add('hidden');
        }
    }

    radios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'partial') {
                partialDiv.classList.remove('hidden');
                if (fullAmountInput) fullAmountInput.disabled = true;
                if (partialInput) {
                    partialInput.disabled = false;
                    partialInput.focus();
                    updateLiveBalance();
                }
            } else {
                partialDiv.classList.add('hidden');
                liveBalanceDiv.classList.add('hidden');
                if (fullAmountInput) fullAmountInput.disabled = false;
                if (partialInput) partialInput.disabled = true;
            }
        });
    });

    document.querySelector('form').addEventListener('submit', function(e) {
        const checkedRadio = document.querySelector('input[name="payment_type"]:checked');
        if (checkedRadio.value === 'full') {
            if (fullAmountInput) fullAmountInput.value = outstanding;
        } else if (partialInput) {
            if (fullAmountInput) fullAmountInput.value = '';
        }
    });

    if (partialInput) {
        partialInput.addEventListener('input', updateLiveBalance);
        partialInput.addEventListener('change', updateLiveBalance);
    }

    checkInitial();
});
</script>
@endsection