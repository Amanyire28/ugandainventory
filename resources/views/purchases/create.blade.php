@extends('layouts.app')

@section('title', 'Record Purchase')

@push('styles')
<style>
    .po-page { background: #f0f4ff; min-height: 100%; }

    /* Hero gradient header */
    .po-hero {
        background: linear-gradient(135deg, #4f46e5 0%, #6d28d9 100%);
        padding: 1.75rem 2rem;
        border-radius: 1.25rem 1.25rem 0 0;
        position: relative;
        overflow: hidden;
    }
    .po-hero::before {
        content: '';
        position: absolute;
        top: -60px; right: -60px;
        width: 200px; height: 200px;
        border-radius: 50%;
        background: rgba(255,255,255,0.07);
    }
    .po-hero::after {
        content: '';
        position: absolute;
        bottom: -40px; left: 40px;
        width: 140px; height: 140px;
        border-radius: 50%;
        background: rgba(255,255,255,0.05);
    }

    /* Section cards */
    .po-card {
        background: #fff;
        border-radius: 1rem;
        box-shadow: 0 1px 3px rgba(0,0,0,.06), 0 4px 16px rgba(79,70,229,.07);
        overflow: hidden;
    }
    .po-card-header {
        display: flex;
        align-items: center;
        gap: 0.625rem;
        padding: 1.1rem 1.5rem;
        border-bottom: 1px solid #f3f4f6;
        background: #fafbff;
    }
    .po-card-header .icon-wrap {
        width: 32px; height: 32px;
        border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        font-size: 0.875rem;
    }
    .po-card-body { padding: 1.25rem 1.5rem; }

    /* Float-label inputs */
    .field-wrap { position: relative; margin-bottom: 1.1rem; }
    .field-wrap:last-child { margin-bottom: 0; }
    .field-wrap label {
        display: block;
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: .07em;
        text-transform: uppercase;
        color: #6b7280;
        margin-bottom: 0.35rem;
    }
    .field-wrap .required { color: #ef4444; margin-left: 2px; }
    .field-control {
        width: 100%;
        border: 1.5px solid #e5e7eb;
        border-radius: 0.625rem;
        padding: 0.65rem 0.875rem;
        font-size: 0.875rem;
        color: #111827;
        background: #fff;
        transition: border-color .2s, box-shadow .2s;
        outline: none;
    }
    .field-control:focus {
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99,102,241,.15);
    }
    select.field-control { appearance: auto; }
    textarea.field-control { resize: none; }

    /* Payment option pills */
    .pay-option {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem 1rem;
        border: 2px solid #e5e7eb;
        border-radius: 0.75rem;
        cursor: pointer;
        transition: all .2s;
        background: #fff;
    }
    .pay-option:hover { border-color: #c7d2fe; background: #f5f3ff; }
    .pay-option.active-paid    { border-color: #22c55e; background: #f0fdf4; }
    .pay-option.active-partial { border-color: #f59e0b; background: #fffbeb; }
    .pay-option.active-unpaid  { border-color: #ef4444; background: #fef2f2; }
    .pay-option .pay-dot {
        width: 20px; height: 20px;
        border-radius: 50%;
        border: 2px solid #d1d5db;
        flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        transition: all .2s;
    }
    .pay-option input[type=radio] { display: none; }
    .pay-option.active-paid    .pay-dot { border-color: #22c55e; background: #22c55e; }
    .pay-option.active-partial .pay-dot { border-color: #f59e0b; background: #f59e0b; }
    .pay-option.active-unpaid  .pay-dot { border-color: #ef4444; background: #ef4444; }
    .pay-dot::after {
        content: '';
        width: 8px; height: 8px;
        border-radius: 50%;
        background: white;
        display: none;
    }
    .pay-option.active-paid .pay-dot::after,
    .pay-option.active-partial .pay-dot::after,
    .pay-option.active-unpaid .pay-dot::after { display: block; }

    /* Item card on mobile */
    .item-card {
        background: #fff;
        border: 1.5px solid #e5e7eb;
        border-radius: 0.875rem;
        padding: 1rem;
        position: relative;
        transition: border-color .2s, box-shadow .2s;
    }
    .item-card:hover { border-color: #a5b4fc; box-shadow: 0 2px 8px rgba(99,102,241,.1); }
    .item-card .item-num {
        width: 28px; height: 28px;
        border-radius: 8px;
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        color: white;
        font-size: 0.7rem;
        font-weight: 800;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .item-card .line-total-badge {
        background: linear-gradient(135deg, #f0fdf4, #dcfce7);
        color: #166534;
        border: 1px solid #bbf7d0;
        border-radius: 0.5rem;
        padding: 0.35rem 0.75rem;
        font-weight: 800;
        font-size: 0.8rem;
    }

    /* Sticky summary on desktop */
    .summary-panel {
        position: sticky;
        top: 1rem;
    }
    .summary-gradient {
        background: linear-gradient(160deg, #4f46e5 0%, #7c3aed 60%, #6d28d9 100%);
        border-radius: 1.25rem;
        padding: 1.5rem;
        color: white;
        box-shadow: 0 8px 32px rgba(79,70,229,.35);
    }
    .summary-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.5rem 0;
        font-size: 0.875rem;
        color: rgba(255,255,255,.75);
        border-bottom: 1px solid rgba(255,255,255,.1);
    }
    .summary-row:last-of-type { border-bottom: none; }
    .summary-row .val { color: white; font-weight: 600; }
    .summary-total-val {
        font-size: 1.5rem;
        font-weight: 900;
        color: #fde68a;
    }

    /* Save button */
    .save-btn {
        width: 100%;
        padding: 0.875rem;
        background: white;
        color: #4f46e5;
        border: none;
        border-radius: 0.875rem;
        font-weight: 800;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all .2s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        box-shadow: 0 2px 8px rgba(0,0,0,.12);
        margin-top: 1.25rem;
    }
    .save-btn:hover { background: #eef2ff; transform: translateY(-1px); box-shadow: 0 4px 16px rgba(0,0,0,.15); }
    .save-btn:active { transform: translateY(0); }

    /* Mobile sticky bar */
    .mobile-total-bar {
        display: none;
        position: fixed;
        bottom: 0; left: 0; right: 0;
        background: linear-gradient(135deg, #4f46e5, #6d28d9);
        padding: 0.875rem 1.25rem;
        z-index: 50;
        box-shadow: 0 -4px 20px rgba(79,70,229,.3);
    }
    @media (max-width: 1023px) {
        .mobile-total-bar { display: flex; align-items: center; justify-content: space-between; }
        .po-page { padding-bottom: 5rem; }
    }

    /* Add item button */
    .add-item-btn {
        width: 100%;
        padding: 0.75rem;
        border: 2px dashed #c7d2fe;
        border-radius: 0.875rem;
        background: #f5f3ff;
        color: #6366f1;
        font-weight: 700;
        font-size: 0.875rem;
        cursor: pointer;
        transition: all .2s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }
    .add-item-btn:hover { background: #ede9fe; border-color: #8b5cf6; }

    /* Empty state */
    .empty-items {
        text-align: center;
        padding: 2.5rem 1rem;
        color: #9ca3af;
    }

    /* Input group for qty+cost */
    .input-pair { display: grid; grid-template-columns: 1fr 1fr; gap: 0.625rem; }

    /* Animated entry */
    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-10px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    .item-card { animation: slideDown .2s ease; }

    /* Add new supplier inline link */
    .inline-action-link {
        display: inline-flex; align-items: center; gap: 4px;
        font-size: 0.75rem; font-weight: 600;
        color: #6366f1;
        text-decoration: none;
        padding: 0.25rem 0.5rem;
        border-radius: 0.375rem;
        transition: background .15s;
        margin-top: 0.375rem;
    }
    .inline-action-link:hover { background: #eef2ff; }
</style>
@endpush

@section('content')
<div class="po-page">
    <form id="purchaseForm" method="POST" action="{{ route('purchases.store') }}">
        @csrf

        {{-- ═══ HERO HEADER ═══ --}}
        <div class="po-hero mb-6 relative z-10">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 relative z-10">
                <div>
                    <div class="flex items-center gap-3 mb-1">
                        <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center">
                            <i class="fas fa-shopping-bag text-white text-lg"></i>
                        </div>
                        <h1 class="text-xl font-black text-white tracking-tight">Record Purchase</h1>
                    </div>
                    <p class="text-indigo-200 text-sm ml-13">Add stock from a supplier — inventory updates instantly</p>
                </div>
                <a href="{{ route('purchases.index') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-white/20 hover:bg-white/30 text-white rounded-xl text-sm font-semibold transition backdrop-blur-sm self-start sm:self-auto">
                    <i class="fas fa-arrow-left text-xs"></i>Back
                </a>
            </div>
        </div>

        {{-- ═══ ERRORS ═══ --}}
        @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-5 flex gap-3">
                <i class="fas fa-exclamation-circle text-red-400 mt-0.5 flex-shrink-0"></i>
                <ul class="text-sm space-y-0.5 list-disc list-inside">
                    @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
                </ul>
            </div>
        @endif

        {{-- ═══ MAIN GRID ═══ --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

            {{-- ──────────────────────────────── --}}
            {{-- LEFT: INFO + PAYMENT + SUMMARY   --}}
            {{-- ──────────────────────────────── --}}
            <div class="lg:col-span-1 space-y-4">

                {{-- Supplier & Date --}}
                <div class="po-card">
                    <div class="po-card-header">
                        <div class="icon-wrap bg-blue-100"><i class="fas fa-truck text-blue-600"></i></div>
                        <span class="font-bold text-gray-700 text-sm">Supplier & Date</span>
                    </div>
                    <div class="po-card-body space-y-4">
                        <div class="field-wrap">
                            <label for="supplier_id">Supplier</label>
                            <select id="supplier_id" name="supplier_id" class="field-control">
                                <option value="">— No Supplier / Walk-in —</option>
                                @foreach($suppliers as $s)
                                    <option value="{{ $s->id }}" {{ old('supplier_id') == $s->id ? 'selected' : '' }}>
                                        {{ $s->name }}@if($s->phone) · {{ $s->phone }}@endif
                                    </option>
                                @endforeach
                            </select>
                            <a href="{{ route('suppliers.create') }}" target="_blank" class="inline-action-link">
                                <i class="fas fa-plus-circle"></i>Add new supplier
                            </a>
                        </div>

                        <div class="field-wrap">
                            <label for="purchase_date">Purchase Date<span class="required">*</span></label>
                            <input type="date" id="purchase_date" name="purchase_date"
                                   value="{{ old('purchase_date', date('Y-m-d')) }}"
                                   class="field-control">
                        </div>

                        <div class="field-wrap">
                            <label for="notes">Notes / Reference</label>
                            <textarea id="notes" name="notes" rows="3"
                                      class="field-control"
                                      placeholder="Delivery note #, LPO reference…">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Payment Status --}}
                <div class="po-card">
                    <div class="po-card-header">
                        <div class="icon-wrap bg-green-100"><i class="fas fa-credit-card text-green-600"></i></div>
                        <span class="font-bold text-gray-700 text-sm">Payment Status</span>
                    </div>
                    <div class="po-card-body space-y-2">
                        <label class="pay-option {{ old('payment_status','paid') === 'paid' ? 'active-paid' : '' }}"
                               data-status="paid">
                            <input type="radio" name="payment_status" value="paid"
                                   {{ old('payment_status','paid') === 'paid' ? 'checked' : '' }}>
                            <span class="pay-dot"></span>
                            <div>
                                <div class="font-bold text-sm text-gray-800">Fully Paid</div>
                                <div class="text-xs text-gray-500">Complete payment made</div>
                            </div>
                            <i class="fas fa-check-circle text-green-500 ml-auto text-sm"></i>
                        </label>

                        <label class="pay-option {{ old('payment_status') === 'partial' ? 'active-partial' : '' }}"
                               data-status="partial">
                            <input type="radio" name="payment_status" value="partial"
                                   {{ old('payment_status') === 'partial' ? 'checked' : '' }}>
                            <span class="pay-dot"></span>
                            <div>
                                <div class="font-bold text-sm text-gray-800">Partial Payment</div>
                                <div class="text-xs text-gray-500">Some paid, balance pending</div>
                            </div>
                            <i class="fas fa-adjust text-amber-500 ml-auto text-sm"></i>
                        </label>

                        <label class="pay-option {{ old('payment_status') === 'unpaid' ? 'active-unpaid' : '' }}"
                               data-status="unpaid">
                            <input type="radio" name="payment_status" value="unpaid"
                                   {{ old('payment_status') === 'unpaid' ? 'checked' : '' }}>
                            <span class="pay-dot"></span>
                            <div>
                                <div class="font-bold text-sm text-gray-800">On Credit</div>
                                <div class="text-xs text-gray-500">Pay later / credit terms</div>
                            </div>
                            <i class="fas fa-clock text-red-400 ml-auto text-sm"></i>
                        </label>

                        {{-- Amount paid field (partial only) --}}
                        <div id="amountPaidWrap"
                             class="{{ old('payment_status') === 'partial' ? '' : 'hidden' }} pt-1">
                            <div class="field-wrap" style="margin-bottom:0">
                                <label for="amount_paid">Amount Paid (UGX)<span class="required">*</span></label>
                                <div style="position:relative">
                                    <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#6b7280;font-size:.8rem;font-weight:700">UGX</span>
                                    <input type="number" id="amount_paid" name="amount_paid"
                                           value="{{ old('amount_paid', 0) }}" min="0" step="1"
                                           class="field-control" style="padding-left:3rem">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Summary (desktop sticky) --}}
                <div class="summary-panel hidden lg:block">
                    <div class="summary-gradient">
                        <div class="flex items-center gap-2 mb-4">
                            <i class="fas fa-receipt text-indigo-200"></i>
                            <span class="font-black text-white text-sm uppercase tracking-wider">Order Summary</span>
                        </div>
                        <div class="summary-row">
                            <span>Products</span>
                            <span class="val" id="summaryItemCount">0</span>
                        </div>
                        <div class="summary-row">
                            <span>Total Lines</span>
                            <span class="val" id="summaryLineCount">0 items</span>
                        </div>
                        <div class="summary-row">
                            <span>Subtotal</span>
                            <span class="val" id="summarySubtotal">UGX 0</span>
                        </div>
                        <div class="mt-4 text-center">
                            <div class="text-xs text-indigo-300 uppercase font-bold tracking-widest mb-1">Grand Total</div>
                            <div class="summary-total-val" id="summaryTotal">UGX 0</div>
                        </div>

                        <button id="submitBtn" type="submit" class="save-btn">
                            <span id="submitLabel"><i class="fas fa-check-circle"></i> Save Purchase</span>
                            <span id="submitSpinner" class="hidden" style="display:none">
                                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Saving...
                            </span>
                        </button>
                    </div>
                </div>

            </div><!-- /left col -->

            {{-- ──────────────────────────────── --}}
            {{-- RIGHT: PRODUCTS                  --}}
            {{-- ──────────────────────────────── --}}
            <div class="lg:col-span-2">
                <div class="po-card">
                    <div class="po-card-header">
                        <div class="icon-wrap bg-indigo-100"><i class="fas fa-boxes text-indigo-600"></i></div>
                        <span class="font-bold text-gray-700 text-sm">Purchase Items</span>
                        <span id="itemBadge"
                              class="ml-auto bg-indigo-600 text-white text-xs font-black px-2.5 py-0.5 rounded-full">0</span>
                    </div>
                    <div class="po-card-body">

                        {{-- Items container --}}
                        <div id="itemsContainer" class="space-y-3 mb-4">
                            {{-- Item cards injected by JS --}}
                        </div>

                        {{-- Empty state --}}
                        <div id="emptyItems" class="empty-items">
                            <div class="w-16 h-16 bg-indigo-50 rounded-2xl flex items-center justify-center mx-auto mb-3">
                                <i class="fas fa-cubes text-2xl text-indigo-300"></i>
                            </div>
                            <p class="font-semibold text-gray-500 text-sm">No products added yet</p>
                            <p class="text-xs text-gray-400 mt-1">Click the button below to add products</p>
                        </div>

                        {{-- Add row button --}}
                        <button type="button" id="addRowBtn" class="add-item-btn">
                            <i class="fas fa-plus-circle"></i>
                            Add Product Row
                        </button>
                    </div>
                </div>
            </div>

        </div><!-- /main grid -->

    </form>
</div>

{{-- Mobile sticky total bar --}}
<div class="mobile-total-bar lg:hidden">
    <div>
        <div class="text-white/70 text-xs font-semibold">Grand Total</div>
        <div class="text-yellow-300 font-black text-lg" id="mobileSummaryTotal">UGX 0</div>
    </div>
    <button onclick="document.getElementById('purchaseForm').dispatchEvent(new Event('submit',{cancelable:true,bubbles:true}))"
            id="mobileSaveBtn"
            class="inline-flex items-center gap-2 px-5 py-2.5 bg-white text-indigo-700 rounded-xl font-black text-sm shadow-lg transition active:scale-95">
        <i class="fas fa-check-circle"></i>
        <span id="mobileSaveLabel">Save Purchase</span>
    </button>
</div>

{{-- Product data for JS --}}
@php
    $productData = $products->map(function($p) {
        return [
            'id'         => $p->id,
            'name'       => $p->name,
            'sku'        => $p->sku,
            'unit'       => $p->unit,
            'cost_price' => (float) $p->cost_price,
            'quantity'   => (float) $p->quantity,
        ];
    })->values();
@endphp
<script>
    const PRODUCTS = {!! json_encode($productData) !!};
</script>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    let rowIndex = 0;

    const container   = document.getElementById('itemsContainer');
    const emptyState  = document.getElementById('emptyItems');
    const addRowBtn   = document.getElementById('addRowBtn');
    const itemBadge   = document.getElementById('itemBadge');

    // ── Payment option styling ──────────────────────────
    document.querySelectorAll('.pay-option').forEach(opt => {
        const radio = opt.querySelector('input[type=radio]');
        const status = opt.dataset.status;

        opt.addEventListener('click', () => {
            radio.checked = true;
            applyPaymentStyle(status);
        });
    });

    function applyPaymentStyle(active) {
        document.querySelectorAll('.pay-option').forEach(o => {
            o.classList.remove('active-paid', 'active-partial', 'active-unpaid');
        });
        const target = document.querySelector(`.pay-option[data-status="${active}"]`);
        if (target) target.classList.add(`active-${active}`);
        document.getElementById('amountPaidWrap').classList.toggle('hidden', active !== 'partial');
    }

    // Init payment on load
    const checked = document.querySelector('input[name=payment_status]:checked');
    if (checked) applyPaymentStyle(checked.value);

    // ── Build product <select> options ──────────────────
    function buildOptions(selectedId = '') {
        return '<option value="">— Select product —</option>' +
            PRODUCTS.map(p =>
                `<option value="${p.id}" data-cost="${p.cost_price}" data-unit="${p.unit||''}"${p.id == selectedId ? ' selected' : ''}>
                    ${p.name}${p.sku ? ' ['+p.sku+']' : ''} (Stock: ${p.quantity})
                </option>`
            ).join('');
    }

    // ── Add item card ───────────────────────────────────
    function addRow(productId = '', qty = 1, cost = '') {
        const idx = rowIndex++;
        const card = document.createElement('div');
        card.className = 'item-card item-row';
        card.dataset.lineTotal = 0;

        card.innerHTML = `
            <div style="display:flex;align-items:center;gap:0.625rem;margin-bottom:0.75rem">
                <div class="item-num">${idx + 1}</div>
                <div style="flex:1;font-weight:700;font-size:.8rem;color:#374151;">Product ${idx + 1}</div>
                <button type="button" class="remove-row"
                        style="width:28px;height:28px;border-radius:8px;background:#fef2f2;color:#ef4444;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.8rem;transition:background .2s"
                        onmouseover="this.style.background='#fee2e2'" onmouseout="this.style.background='#fef2f2'">
                    <i class="fas fa-trash"></i>
                </button>
            </div>

            <div class="field-wrap">
                <label>Product <span class="required">*</span></label>
                <select name="items[${idx}][product_id]" required class="field-control product-select">
                    ${buildOptions(productId)}
                </select>
            </div>

            <div class="input-pair">
                <div class="field-wrap" style="margin-bottom:0">
                    <label>Quantity <span class="required">*</span></label>
                    <input type="number" name="items[${idx}][quantity]"
                           min="0.01" step="0.01" value="${qty}" required
                           class="field-control qty-input" placeholder="0">
                </div>
                <div class="field-wrap" style="margin-bottom:0">
                    <label>Unit Cost (UGX) <span class="required">*</span></label>
                    <input type="number" name="items[${idx}][unit_cost]"
                           min="0" step="1" value="${cost}" required
                           class="field-control cost-input" placeholder="0">
                </div>
            </div>

            <div style="display:flex;justify-content:flex-end;margin-top:0.625rem;align-items:center;gap:0.5rem">
                <span style="font-size:.75rem;color:#6b7280;font-weight:600">Line Total:</span>
                <span class="line-total-badge line-total">UGX 0</span>
            </div>`;

        container.appendChild(card);
        emptyState.classList.add('hidden');

        // Event: product change → auto-fill cost
        card.querySelector('.product-select').addEventListener('change', function () {
            const opt = this.options[this.selectedIndex];
            const costInput = card.querySelector('.cost-input');
            if (opt.dataset.cost && parseFloat(opt.dataset.cost) > 0) {
                costInput.value = parseFloat(opt.dataset.cost);
            }
            // Update label
            const label = card.querySelector('.item-num + div');
            if (label) label.textContent = this.options[this.selectedIndex].text.split(' (')[0] || `Product ${idx + 1}`;
            recalcRow(card);
            recalcTotals();
        });

        card.querySelector('.qty-input').addEventListener('input', () => { recalcRow(card); recalcTotals(); });
        card.querySelector('.cost-input').addEventListener('input', () => { recalcRow(card); recalcTotals(); });

        card.querySelector('.remove-row').addEventListener('click', () => {
            card.style.opacity = '0';
            card.style.transform = 'scale(.96)';
            card.style.transition = 'all .15s';
            setTimeout(() => {
                card.remove();
                renumberCards();
                recalcTotals();
                if (container.querySelectorAll('.item-row').length === 0) {
                    emptyState.classList.remove('hidden');
                }
            }, 150);
        });

        if (productId) { recalcRow(card); recalcTotals(); }
        recalcTotals();
    }

    function renumberCards() {
        container.querySelectorAll('.item-row').forEach((card, i) => {
            const numEl = card.querySelector('.item-num');
            if (numEl) numEl.textContent = i + 1;
        });
    }

    function recalcRow(card) {
        const qty  = parseFloat(card.querySelector('.qty-input').value)  || 0;
        const cost = parseFloat(card.querySelector('.cost-input').value) || 0;
        const total = qty * cost;
        card.dataset.lineTotal = total;
        card.querySelector('.line-total').textContent = 'UGX ' + fmt(total);
    }

    function fmt(n) {
        return Math.round(n).toLocaleString('en-UG');
    }

    function recalcTotals() {
        const cards = container.querySelectorAll('.item-row');
        let subtotal = 0;
        cards.forEach(c => { subtotal += parseFloat(c.dataset.lineTotal || 0); });

        const count = cards.length;
        itemBadge.textContent = count;

        if (document.getElementById('summaryItemCount'))
            document.getElementById('summaryItemCount').textContent = count;
        if (document.getElementById('summaryLineCount'))
            document.getElementById('summaryLineCount').textContent = count + (count === 1 ? ' item' : ' items');
        if (document.getElementById('summarySubtotal'))
            document.getElementById('summarySubtotal').textContent = 'UGX ' + fmt(subtotal);
        if (document.getElementById('summaryTotal'))
            document.getElementById('summaryTotal').textContent = 'UGX ' + fmt(subtotal);
        if (document.getElementById('mobileSummaryTotal'))
            document.getElementById('mobileSummaryTotal').textContent = 'UGX ' + fmt(subtotal);
    }

    addRowBtn.addEventListener('click', () => addRow());

    // Auto-add first row
    addRow();

    // ── Submit spinner ──────────────────────────────────
    document.getElementById('purchaseForm').addEventListener('submit', function (e) {
        const rows = container.querySelectorAll('.item-row');
        if (rows.length === 0) {
            e.preventDefault();
            alert('Please add at least one product before saving.');
            return;
        }

        const submitLabel   = document.getElementById('submitLabel');
        const submitSpinner = document.getElementById('submitSpinner');
        const submitBtn     = document.getElementById('submitBtn');
        const mobileLabel   = document.getElementById('mobileSaveLabel');

        if (submitLabel)   { submitLabel.style.display = 'none'; }
        if (submitSpinner) { submitSpinner.style.display = 'flex'; submitSpinner.style.alignItems = 'center'; submitSpinner.style.gap = '0.375rem'; }
        if (submitBtn)     { submitBtn.style.pointerEvents = 'none'; }
        if (mobileLabel)   { mobileLabel.textContent = 'Saving…'; }
    });
});
</script>
@endpush
