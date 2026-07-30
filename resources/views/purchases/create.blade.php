@extends('layouts.app')

@section('title', 'Record Purchase')

@section('content')
<div class="max-w-5xl mx-auto">

    {{-- Page Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Record Purchase</h2>
            <p class="text-gray-500 text-sm mt-1">Add stock from a supplier and update inventory</p>
        </div>
        <a href="{{ route('purchases.index') }}"
           class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm font-medium transition">
            <i class="fas fa-arrow-left mr-2"></i>Back to Purchases
        </a>
    </div>

    @if($errors->any())
        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-r-lg">
            <ul class="list-disc list-inside text-sm space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form id="purchaseForm" method="POST" action="{{ route('purchases.store') }}">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- ─── LEFT COLUMN: Purchase Info ─── --}}
            <div class="lg:col-span-1 space-y-5">

                {{-- Supplier --}}
                <div class="bg-white rounded-xl shadow p-5">
                    <h3 class="text-base font-bold text-gray-700 mb-4 flex items-center">
                        <i class="fas fa-truck text-indigo-500 mr-2"></i>Supplier Details
                    </h3>

                    <div>
                        <label for="supplier_id" class="block text-xs font-semibold text-gray-500 uppercase mb-1">Supplier</label>
                        <select id="supplier_id" name="supplier_id"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition bg-white">
                            <option value="">— No Supplier / Walk-in —</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                    {{ $supplier->name }}
                                    @if($supplier->phone) ({{ $supplier->phone }}) @endif
                                </option>
                            @endforeach
                        </select>
                        <a href="{{ route('suppliers.create') }}" target="_blank"
                           class="inline-block mt-1.5 text-xs text-indigo-600 hover:underline">
                            <i class="fas fa-plus mr-1"></i>Add new supplier
                        </a>
                    </div>

                    {{-- Purchase Date --}}
                    <div class="mt-4">
                        <label for="purchase_date" class="block text-xs font-semibold text-gray-500 uppercase mb-1">Purchase Date <span class="text-red-500">*</span></label>
                        <input type="date" id="purchase_date" name="purchase_date"
                               value="{{ old('purchase_date', date('Y-m-d')) }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                    </div>

                    {{-- Notes --}}
                    <div class="mt-4">
                        <label for="notes" class="block text-xs font-semibold text-gray-500 uppercase mb-1">Notes</label>
                        <textarea id="notes" name="notes" rows="3"
                                  class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition resize-none"
                                  placeholder="Delivery note, reference number…">{{ old('notes') }}</textarea>
                    </div>
                </div>

                {{-- Payment --}}
                <div class="bg-white rounded-xl shadow p-5">
                    <h3 class="text-base font-bold text-gray-700 mb-4 flex items-center">
                        <i class="fas fa-credit-card text-green-500 mr-2"></i>Payment
                    </h3>

                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Payment Status <span class="text-red-500">*</span></label>
                        <div class="space-y-2">
                            @foreach(['paid' => ['Fully Paid', 'green'], 'partial' => ['Partially Paid', 'yellow'], 'unpaid' => ['Unpaid / On Credit', 'red']] as $val => [$label, $color])
                                <label class="flex items-center gap-3 p-3 border-2 rounded-lg cursor-pointer transition payment-option
                                    {{ old('payment_status', 'paid') === $val ? "border-{$color}-500 bg-{$color}-50" : 'border-gray-200 hover:border-gray-300' }}"
                                    data-value="{{ $val }}" data-color="{{ $color }}">
                                    <input type="radio" name="payment_status" value="{{ $val }}"
                                           class="text-indigo-600"
                                           {{ old('payment_status', 'paid') === $val ? 'checked' : '' }}>
                                    <span class="font-medium text-sm text-gray-700">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Amount paid (shown only for partial) --}}
                    <div id="amountPaidWrap" class="{{ old('payment_status', 'paid') === 'partial' ? '' : 'hidden' }} mt-4">
                        <label for="amount_paid" class="block text-xs font-semibold text-gray-500 uppercase mb-1">Amount Paid (UGX)</label>
                        <input type="number" id="amount_paid" name="amount_paid"
                               value="{{ old('amount_paid', 0) }}" min="0" step="1"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                    </div>
                </div>

                {{-- Summary Card --}}
                <div class="bg-gradient-to-br from-indigo-600 to-indigo-800 rounded-xl shadow p-5 text-white">
                    <h3 class="text-base font-bold mb-4 flex items-center">
                        <i class="fas fa-receipt mr-2 text-indigo-200"></i>Order Summary
                    </h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-indigo-200">Items:</span>
                            <span id="summaryItemCount" class="font-semibold">0</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-indigo-200">Subtotal:</span>
                            <span id="summarySubtotal" class="font-semibold">UGX 0</span>
                        </div>
                        <div class="border-t border-indigo-500 pt-2 flex justify-between text-base">
                            <span class="font-bold text-indigo-100">Grand Total:</span>
                            <span id="summaryTotal" class="font-black text-yellow-300">UGX 0</span>
                        </div>
                    </div>

                    <button id="submitBtn" type="submit"
                            class="mt-5 w-full py-3 bg-white text-indigo-700 rounded-lg font-bold text-sm hover:bg-indigo-50 transition flex items-center justify-center gap-2 shadow">
                        <span id="submitLabel"><i class="fas fa-save mr-1"></i>Save Purchase</span>
                        <span id="submitSpinner" class="hidden">
                            <svg class="animate-spin h-4 w-4 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Saving...
                        </span>
                    </button>
                </div>
            </div>

            {{-- ─── RIGHT COLUMN: Product Items ─── --}}
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-base font-bold text-gray-700 flex items-center">
                            <i class="fas fa-box text-indigo-500 mr-2"></i>Purchase Items
                        </h3>
                        <button type="button" id="addRowBtn"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm font-semibold transition shadow-sm">
                            <i class="fas fa-plus mr-2"></i>Add Product
                        </button>
                    </div>

                    {{-- Items Table --}}
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm" id="itemsTable">
                            <thead class="bg-gray-50 rounded-lg">
                                <tr>
                                    <th class="px-3 py-2.5 text-left font-semibold text-gray-500 uppercase text-xs">Product</th>
                                    <th class="px-3 py-2.5 text-center font-semibold text-gray-500 uppercase text-xs w-28">Qty</th>
                                    <th class="px-3 py-2.5 text-center font-semibold text-gray-500 uppercase text-xs w-36">Unit Cost (UGX)</th>
                                    <th class="px-3 py-2.5 text-right font-semibold text-gray-500 uppercase text-xs w-36">Line Total</th>
                                    <th class="px-3 py-2.5 w-10"></th>
                                </tr>
                            </thead>
                            <tbody id="itemsBody" class="divide-y divide-gray-100">
                                {{-- Rows injected by JS --}}
                            </tbody>
                        </table>
                    </div>

                    {{-- Empty state --}}
                    <div id="emptyItems" class="flex flex-col items-center justify-center py-10 text-gray-400">
                        <i class="fas fa-cubes text-4xl mb-3 text-gray-300"></i>
                        <p class="text-sm font-medium">No items added yet.</p>
                        <p class="text-xs mt-1">Click <strong>Add Product</strong> to start building the order.</p>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

{{-- Product data for JS --}}
<script>
    const PRODUCTS = @json($products->map(fn($p) => [
        'id'         => $p->id,
        'name'       => $p->name,
        'sku'        => $p->sku,
        'unit'       => $p->unit,
        'cost_price' => (float) $p->cost_price,
        'quantity'   => (float) $p->quantity,
    ]));
</script>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    let rowIndex = 0;

    const itemsBody   = document.getElementById('itemsBody');
    const emptyItems  = document.getElementById('emptyItems');
    const addRowBtn   = document.getElementById('addRowBtn');
    const submitBtn   = document.getElementById('submitBtn');
    const submitLabel = document.getElementById('submitLabel');
    const submitSpinner = document.getElementById('submitSpinner');

    // Payment status radio styling
    document.querySelectorAll('.payment-option').forEach(label => {
        label.querySelector('input').addEventListener('change', function () {
            document.querySelectorAll('.payment-option').forEach(l => {
                const c = l.dataset.color;
                l.classList.remove(`border-${c}-500`, `bg-${c}-50`);
                l.classList.add('border-gray-200');
            });
            label.classList.remove('border-gray-200');
            const c = label.dataset.color;
            label.classList.add(`border-${c}-500`, `bg-${c}-50`);
            document.getElementById('amountPaidWrap').classList.toggle('hidden', this.value !== 'partial');
        });
    });

    // Build product option HTML
    function buildProductOptions(selectedId = '') {
        return PRODUCTS.map(p =>
            `<option value="${p.id}" data-cost="${p.cost_price}" data-unit="${p.unit || ''}"
                ${p.id == selectedId ? 'selected' : ''}>
                ${p.name}${p.sku ? ' [' + p.sku + ']' : ''} — Stock: ${p.quantity}
            </option>`
        ).join('');
    }

    function addRow(productId = '', qty = 1, cost = '') {
        const idx = rowIndex++;
        const tr = document.createElement('tr');
        tr.className = 'item-row hover:bg-gray-50 transition-colors';
        tr.innerHTML = `
            <td class="px-3 py-2">
                <select name="items[${idx}][product_id]" required
                        class="product-select w-full border border-gray-300 rounded-lg px-2 py-2 text-sm focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 bg-white">
                    <option value="">— Select product —</option>
                    ${buildProductOptions(productId)}
                </select>
            </td>
            <td class="px-3 py-2">
                <input type="number" name="items[${idx}][quantity]" min="0.01" step="0.01"
                       value="${qty}" required
                       class="qty-input w-full border border-gray-300 rounded-lg px-2 py-2 text-sm text-center focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 transition">
            </td>
            <td class="px-3 py-2">
                <input type="number" name="items[${idx}][unit_cost]" min="0" step="1"
                       value="${cost}" required
                       placeholder="0"
                       class="cost-input w-full border border-gray-300 rounded-lg px-2 py-2 text-sm text-center focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 transition">
            </td>
            <td class="px-3 py-2 text-right">
                <span class="line-total font-bold text-gray-800 text-sm">UGX 0</span>
            </td>
            <td class="px-3 py-2 text-center">
                <button type="button" class="remove-row text-red-400 hover:text-red-600 transition">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </td>`;

        itemsBody.appendChild(tr);
        emptyItems.classList.add('hidden');

        // Auto-fill cost when product is selected
        tr.querySelector('.product-select').addEventListener('change', function () {
            const opt = this.options[this.selectedIndex];
            const costInput = tr.querySelector('.cost-input');
            if (opt.dataset.cost && parseFloat(opt.dataset.cost) > 0) {
                costInput.value = opt.dataset.cost;
            }
            recalcRow(tr);
            recalcTotals();
        });

        tr.querySelector('.qty-input').addEventListener('input', () => { recalcRow(tr); recalcTotals(); });
        tr.querySelector('.cost-input').addEventListener('input', () => { recalcRow(tr); recalcTotals(); });

        tr.querySelector('.remove-row').addEventListener('click', () => {
            tr.remove();
            if (itemsBody.querySelectorAll('.item-row').length === 0) {
                emptyItems.classList.remove('hidden');
            }
            recalcTotals();
        });

        if (productId) { recalcRow(tr); recalcTotals(); }
    }

    function recalcRow(tr) {
        const qty  = parseFloat(tr.querySelector('.qty-input').value) || 0;
        const cost = parseFloat(tr.querySelector('.cost-input').value) || 0;
        const total = qty * cost;
        tr.querySelector('.line-total').textContent = 'UGX ' + total.toLocaleString('en-UG', { maximumFractionDigits: 0 });
        tr.dataset.lineTotal = total;
    }

    function recalcTotals() {
        const rows = itemsBody.querySelectorAll('.item-row');
        let subtotal = 0;
        rows.forEach(r => { subtotal += parseFloat(r.dataset.lineTotal || 0); });

        document.getElementById('summaryItemCount').textContent = rows.length;
        document.getElementById('summarySubtotal').textContent = 'UGX ' + subtotal.toLocaleString('en-UG', { maximumFractionDigits: 0 });
        document.getElementById('summaryTotal').textContent = 'UGX ' + subtotal.toLocaleString('en-UG', { maximumFractionDigits: 0 });
    }

    addRowBtn.addEventListener('click', () => addRow());

    // Add first row automatically
    addRow();

    // Spinner on submit
    document.getElementById('purchaseForm').addEventListener('submit', function (e) {
        const rows = itemsBody.querySelectorAll('.item-row');
        if (rows.length === 0) {
            e.preventDefault();
            alert('Please add at least one product to the purchase.');
            return;
        }
        submitLabel.classList.add('hidden');
        submitSpinner.classList.remove('hidden');
        submitBtn.style.pointerEvents = 'none';
    });
});
</script>
@endpush
