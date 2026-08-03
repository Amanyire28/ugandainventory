@extends('layouts.cashier-layout')

@section('title', 'Point of Sale (POS)')

@section('page-title')
    <i class="fas fa-cash-register text-green-600 mr-2"></i>Point of Sale
@endsection

@section('content')
<style>
    main {
        display: flex !important;
        flex-direction: column !important;
        height: calc(100vh - 72px) !important;
        overflow: hidden !important;
        padding: 1rem !important;
    }
    @media (max-width: 1023px) {
        main {
            padding: 0.5rem !important;
            height: calc(100vh - 56px) !important;
        }
        #checkoutPanel {
            position: fixed !important;
            left: 0 !important; right: 0 !important; bottom: 0 !important;
            z-index: 50 !important;
            border-top-left-radius: 1rem !important;
            border-top-right-radius: 1rem !important;
            box-shadow: 0 -10px 25px -5px rgba(0,0,0,.1) !important;
            border-top: 1px solid #e5e7eb !important;
            transition: transform .3s ease-in-out !important;
            transform: translateY(100%) !important;
            max-height: 85vh !important;
            overflow-y: auto !important;
            display: flex !important;
        }
        #checkoutPanel.mobile-panel-open {
            transform: translateY(0) !important;
        }
    }
    #mobileCartBar { display: none !important; }
    @media (max-width: 1023px) { #mobileCartBar { display: flex !important; } }
</style>

<!-- Product data for JS -->
<script>
const allProducts = [
    @foreach($products as $product)
    {
        id: {{ $product->id }},
        name: '{{ addslashes($product->name) }}',
        sku: '{{ addslashes($product->sku ?? "") }}',
        price: {{ $product->selling_price }},
        stock: {{ $product->quantity }},
        unit: '{{ addslashes($product->unit) }}'
    },
    @endforeach
];
</script>

<form id="posForm" method="POST" class="h-full">
    @csrf
    <input type="hidden" name="payment_type" id="payment_type" value="cash">

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 h-full overflow-hidden relative">
    <!-- Mobile backdrop -->
    <div id="checkoutPanelBackdrop" onclick="closeMobileCheckout()"
         class="hidden fixed inset-0 bg-black/50 z-40 lg:hidden"></div>

    <!-- LEFT: Products & Search -->
    <div class="lg:col-span-2 flex flex-col h-full min-h-0 space-y-4 pb-16 lg:pb-0">

        <!-- Search bar -->
        <div class="bg-white rounded-xl shadow-lg p-4 flex-shrink-0">
            <div class="relative w-full">
                <input type="text" id="productSearch" autocomplete="off"
                       placeholder="Type product name, SKU or scan barcode..."
                       class="w-full px-4 py-3 pl-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 font-medium">
                <i class="fas fa-search absolute left-3 top-4 text-gray-400"></i>
                <div id="searchResultsDropdown"
                     class="hidden absolute left-0 right-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-xl z-50 max-h-60 overflow-y-auto"></div>
            </div>
        </div>

        <!-- Cart table -->
        <div class="bg-white rounded-xl shadow-lg p-4 flex-1 flex flex-col min-h-0">
            <div class="flex justify-between items-center mb-3 flex-shrink-0">
                <h3 class="text-lg font-bold text-gray-800">
                    <i class="fas fa-shopping-basket text-green-600 mr-2"></i>Selected Products
                </h3>
                <button type="button" id="clearCartBtn"
                        class="text-sm text-red-600 hover:text-red-800 font-semibold transition">
                    <i class="fas fa-trash mr-1"></i>Clear All
                </button>
            </div>
            <div class="flex-1 overflow-y-auto rounded-lg border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 sticky top-0 z-10">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">Product</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-500 uppercase tracking-wider w-28">Price</th>
                            <th class="px-4 py-3 text-center font-semibold text-gray-500 uppercase tracking-wider w-36">Quantity</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-500 uppercase tracking-wider w-32">Total</th>
                            <th class="px-4 py-3 text-center font-semibold text-gray-500 uppercase tracking-wider w-14"></th>
                        </tr>
                    </thead>
                    <tbody id="cartItemsTable" class="bg-white divide-y divide-gray-200 text-gray-700">
                        <tr>
                            <td colspan="5" class="px-4 py-12 text-center text-gray-400">
                                <i class="fas fa-shopping-basket text-5xl mb-3 block text-gray-300"></i>
                                No products selected. Search above to add.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- RIGHT: Checkout panel (drawer on mobile) -->
    <div id="checkoutPanel"
         class="lg:col-span-1 bg-white rounded-xl shadow-lg p-4 flex flex-col justify-between min-h-0
                lg:flex lg:h-full
                max-lg:fixed max-lg:inset-x-0 max-lg:bottom-0 max-lg:z-50
                max-lg:rounded-t-2xl max-lg:shadow-2xl max-lg:border-t max-lg:border-gray-200
                max-lg:transition-transform max-lg:duration-300 max-lg:translate-y-full
                max-lg:max-h-[85vh] max-lg:overflow-y-auto">

        <!-- Mobile drawer handle -->
        <div class="lg:hidden flex justify-between items-center pb-3 border-b mb-3">
            <h3 class="font-extrabold text-gray-800 text-sm flex items-center">
                <i class="fas fa-shopping-cart text-green-600 mr-2"></i>Checkout Details
            </h3>
            <button type="button" onclick="closeMobileCheckout()"
                    class="text-gray-400 hover:text-gray-700 text-xl font-bold p-1">&times;</button>
        </div>

        <!-- Customer section -->
        <div class="border-b pb-3 flex-shrink-0">
            <h3 class="text-sm font-bold text-gray-800 mb-2 flex items-center">
                <i class="fas fa-user-circle text-green-600 mr-2"></i>Customer
            </h3>
            <div class="grid grid-cols-3 gap-2">
                <label class="flex items-center justify-center p-2 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 text-xs">
                    <input type="radio" name="customer_option" value="walk_in" checked class="h-3.5 w-3.5 text-green-600 mr-1">
                    <span>Walk-in</span>
                </label>
                <label class="flex items-center justify-center p-2 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 text-xs">
                    <input type="radio" name="customer_option" value="existing" class="h-3.5 w-3.5 text-green-600 mr-1">
                    <span>Existing</span>
                </label>
                <label class="flex items-center justify-center p-2 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 text-xs">
                    <input type="radio" name="customer_option" value="new" class="h-3.5 w-3.5 text-green-600 mr-1">
                    <span>New</span>
                </label>
            </div>
            <div id="existingCustomerDiv" class="hidden mt-2">
                <select id="existingCustomerId" name="customer_id"
                        class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-xs focus:ring-2 focus:ring-green-500">
                    <option value="">Select Customer</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                    @endforeach
                </select>
            </div>
            <div id="newCustomerDiv" class="hidden mt-2 grid grid-cols-2 gap-2 p-2 bg-green-50 rounded-lg text-xs">
                <input type="text" name="new_customer_name" id="newCustomerName" placeholder="Name *"
                       class="px-2 py-1 border border-gray-300 rounded-md focus:ring-2 focus:ring-green-500 text-xs">
                <input type="text" name="new_customer_phone" id="newCustomerPhone" placeholder="Phone *"
                       class="px-2 py-1 border border-gray-300 rounded-md focus:ring-2 focus:ring-green-500 text-xs">
                <input type="email" name="new_customer_email" id="newCustomerEmail" placeholder="Email"
                       class="col-span-2 px-2 py-1 border border-gray-300 rounded-md focus:ring-2 focus:ring-green-500 text-xs">
                <input type="text" name="new_customer_address" id="newCustomerAddress" placeholder="Address"
                       class="col-span-2 px-2 py-1 border border-gray-300 rounded-md focus:ring-2 focus:ring-green-500 text-xs">
            </div>
        </div>

        <!-- Payment type -->
        <div class="border-b py-3 flex-shrink-0">
            <h3 class="text-sm font-bold text-gray-800 mb-2 flex items-center">
                <i class="fas fa-coins text-green-600 mr-2"></i>Payment Option
            </h3>
            <div class="grid grid-cols-2 gap-2">
                <label class="flex items-center justify-center p-2 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 text-xs">
                    <input type="radio" name="payment_type_radio" value="cash" checked class="h-3.5 w-3.5 text-indigo-600 mr-1.5">
                    <span>Cash Sale</span>
                </label>
                <label class="flex items-center justify-center p-2 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 text-xs">
                    <input type="radio" name="payment_type_radio" value="invoice" class="h-3.5 w-3.5 text-indigo-600 mr-1.5">
                    <span>Credit Invoice</span>
                </label>
            </div>
        </div>

        <!-- Totals + payment details + submit -->
        <div class="flex-1 flex flex-col justify-between pt-4 pb-2 min-h-0">
            <div class="space-y-4 text-sm bg-gray-50 p-3 rounded-lg border border-gray-100">
                <div class="flex justify-between items-center">
                    <span class="font-medium text-gray-650">Subtotal:</span>
                    <span class="font-bold text-gray-950 text-base">UGX <span id="subtotalAmount">0</span></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="font-medium text-gray-650">Discount:</span>
                    <input type="number" name="discount" id="discountAmount" value="0" min="0" step="100"
                           class="w-32 px-3 py-1.5 border border-gray-300 rounded-lg text-right text-sm font-bold focus:ring-2 focus:ring-green-500">
                </div>
                <div class="flex justify-between items-center py-1 border-t border-dashed">
                    <label class="flex items-center cursor-pointer font-medium text-gray-650">
                        <input type="checkbox" name="add_tax" id="addTaxCheckbox" value="1"
                               class="mr-2 h-4 w-4 text-green-600 focus:ring-green-500 rounded">
                        <span>Add Tax (18%)</span>
                    </label>
                    <span class="font-bold text-gray-950 text-base">UGX <span id="taxAmount">0</span></span>
                </div>
                <div class="flex justify-between items-center text-lg font-extrabold text-green-600 pt-2 border-t">
                    <span>TOTAL:</span>
                    <span>UGX <span id="totalAmount">0</span></span>
                </div>
            </div>

            <div id="cash-payment-div" class="space-y-3 bg-gray-50 p-3 rounded-lg border border-gray-100 flex-shrink-0 mt-3">
                <div class="flex items-center justify-between gap-3">
                    <span class="text-xs font-bold text-gray-700 whitespace-nowrap">Amount Paid:</span>
                    <input type="number" name="amount_paid" id="amountPaid" value="0" min="0" step="100"
                           class="flex-1 min-w-0 px-3 py-2 border border-gray-300 rounded-lg text-right text-base font-extrabold text-gray-900 focus:ring-2 focus:ring-green-500">
                    <button type="button" id="exactAmountBtn"
                            class="px-3 py-2 bg-blue-50 hover:bg-blue-100 border border-blue-200 text-blue-700 rounded-lg text-sm font-bold transition">
                        Exact
                    </button>
                </div>
                <div class="p-3 rounded-lg flex justify-between items-center text-sm" id="changeBox">
                    <span class="font-semibold text-gray-600">Change:</span>
                    <span class="text-base font-extrabold text-green-600">UGX <span id="changeAmount">0</span></span>
                </div>
                <textarea name="notes" id="saleNotes" rows="2"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500"
                          placeholder="Notes (Optional)..."></textarea>
            </div>

            <div id="invoiceNotice"
                 class="hidden mt-3 p-3 bg-indigo-50 border border-indigo-100 rounded-lg text-sm text-indigo-700 text-center flex-shrink-0">
                <i class="fas fa-info-circle mr-1.5"></i> Credit Sale — items will be added to invoice.
            </div>

            <button type="submit" id="checkoutBtn"
                    class="mt-3 w-full py-3.5 bg-green-600 text-white rounded-lg hover:bg-green-700
                           font-extrabold text-base shadow-lg transition
                           disabled:bg-gray-300 disabled:cursor-not-allowed
                           flex items-center justify-center gap-2 flex-shrink-0">
                <i class="fas fa-check-circle text-lg"></i>
                <span id="checkoutBtnText">Complete Sale</span>
            </button>
        </div>
    </div>

    <!-- Floating mobile checkout bar -->
    <div id="mobileCartBar"
         class="lg:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200
                px-4 py-3 z-30 flex justify-between items-center shadow-lg
                transition-transform duration-300 translate-y-full">
        <div>
            <span class="text-xs text-gray-500 block font-semibold" id="mobileCartCount">0 items</span>
            <span class="text-base font-extrabold text-green-600">Total: UGX <span id="mobileCartTotal">0</span></span>
        </div>
        <button type="button" onclick="openMobileCheckout()"
                class="px-6 py-2.5 bg-green-600 hover:bg-green-700 text-white font-extrabold rounded-lg shadow-md flex items-center gap-1.5 transition">
            <span>Checkout</span> <i class="fas fa-arrow-right text-xs"></i>
        </button>
    </div>
</div>
</form>
@endsection

@push('scripts')
<script>
let cart = [];

// ── Mobile drawer ─────────────────────────────────────────────────────────────
function openMobileCheckout() {
    document.getElementById('checkoutPanel').classList.add('mobile-panel-open');
    document.getElementById('checkoutPanelBackdrop').classList.remove('hidden');
}
function closeMobileCheckout() {
    document.getElementById('checkoutPanel').classList.remove('mobile-panel-open');
    document.getElementById('checkoutPanelBackdrop').classList.add('hidden');
}

// ── Cart operations ───────────────────────────────────────────────────────────
function addToCart(id, name, price, unit, maxStock) {
    if (maxStock <= 0) { alert('Cannot add! Product is out of stock.'); return; }
    const existing = cart.find(i => i.id === id);
    if (existing) {
        if (existing.quantity + 1 > maxStock) {
            alert('Cannot add more! Maximum stock available: ' + maxStock); return;
        }
        existing.quantity++;
    } else {
        cart.push({ id, name, price, quantity: 1, unit, maxStock });
    }
    renderCart(); updateTotals();
}

function removeFromCart(id) {
    cart = cart.filter(i => i.id !== id);
    renderCart(); updateTotals();
}

function updateQuantity(id, newQty) {
    const item = cart.find(i => i.id === id);
    if (!item) return;
    if (newQty > item.maxStock) { alert('Cannot exceed available stock: ' + item.maxStock); renderCart(); return; }
    if (newQty <= 0) { removeFromCart(id); return; }
    item.quantity = parseFloat(newQty);
    renderCart(); updateTotals();
}

function clearCart() {
    if (!cart.length) return;
    if (confirm('Clear all items from cart?')) { cart = []; renderCart(); updateTotals(); }
}

function renderCart() {
    const tbody  = document.getElementById('cartItemsTable');
    const btn    = document.getElementById('checkoutBtn');
    const bar    = document.getElementById('mobileCartBar');
    const count  = document.getElementById('mobileCartCount');
    const mTotal = document.getElementById('mobileCartTotal');

    if (!cart.length) {
        tbody.innerHTML = `<tr><td colspan="5" class="px-4 py-12 text-center text-gray-400">
            <i class="fas fa-shopping-basket text-5xl mb-3 block text-gray-300"></i>
            No products selected. Search above to add.</td></tr>`;
        if (btn) btn.disabled = true;
        if (bar) bar.classList.add('translate-y-full');
        return;
    }

    if (btn) btn.disabled = false;

    // Show mobile bar
    if (bar) bar.classList.remove('translate-y-full');
    const totalItems = cart.reduce((s, i) => s + i.quantity, 0);
    const totalVal   = cart.reduce((s, i) => s + i.price * i.quantity, 0);
    if (count)  count.textContent  = totalItems + ' item' + (totalItems !== 1 ? 's' : '');
    if (mTotal) mTotal.textContent = totalVal.toLocaleString();

    tbody.innerHTML = cart.map(item => `
        <tr class="hover:bg-gray-50 transition">
            <td class="px-4 py-3 font-semibold text-gray-900">
                ${item.name}
                <span class="text-xs text-gray-500 block font-mono">Stock: ${item.maxStock} ${item.unit}</span>
            </td>
            <td class="px-4 py-3 text-right text-gray-800 font-medium">UGX ${item.price.toLocaleString()}</td>
            <td class="px-4 py-3">
                <div class="flex items-center justify-center space-x-2">
                    <button type="button" onclick="updateQuantity(${item.id}, ${item.quantity - 1})"
                            class="w-8 h-8 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-lg flex items-center justify-center border border-gray-200">
                        <i class="fas fa-minus text-xs"></i>
                    </button>
                    <input type="number" value="${item.quantity}" min="1" max="${item.maxStock}"
                           onchange="updateQuantity(${item.id}, this.value)"
                           class="w-16 px-2 py-1.5 border border-gray-300 rounded-lg text-center font-bold text-sm focus:ring-2 focus:ring-green-500">
                    <button type="button" onclick="updateQuantity(${item.id}, ${item.quantity + 1})"
                            class="w-8 h-8 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-lg flex items-center justify-center border border-gray-200">
                        <i class="fas fa-plus text-xs"></i>
                    </button>
                </div>
            </td>
            <td class="px-4 py-3 text-right font-bold text-green-600">
                UGX ${(item.price * item.quantity).toLocaleString()}
            </td>
            <td class="px-4 py-3 text-center">
                <button type="button" onclick="removeFromCart(${item.id})"
                        class="p-2 bg-red-50 hover:bg-red-100 text-red-600 rounded-lg border border-red-200">
                    <i class="fas fa-trash-alt text-sm"></i>
                </button>
            </td>
        </tr>`).join('');
}

// ── Totals ────────────────────────────────────────────────────────────────────
function updateTotals() {
    const subtotal = cart.reduce((s, i) => s + i.price * i.quantity, 0);
    const discount = parseFloat(document.getElementById('discountAmount').value) || 0;
    const addTax   = document.getElementById('addTaxCheckbox').checked;
    const tax      = addTax ? Math.max(0, subtotal - discount) * 0.18 : 0;
    const total    = subtotal - discount + tax;
    document.getElementById('subtotalAmount').textContent = subtotal.toLocaleString();
    document.getElementById('taxAmount').textContent      = tax.toLocaleString();
    document.getElementById('totalAmount').textContent    = total.toLocaleString();
    calculateChange();
}

function calculateChange() {
    const total    = parseFloat(document.getElementById('totalAmount').textContent.replace(/,/g, '')) || 0;
    const paid     = parseFloat(document.getElementById('amountPaid').value) || 0;
    const change   = paid - total;
    document.getElementById('changeAmount').textContent = Math.max(0, change).toLocaleString();
    const box  = document.getElementById('changeBox');
    const span = document.getElementById('changeAmount');
    if (paid < total && paid > 0) {
        box.classList.replace('bg-green-50', 'bg-red-50');
        span.classList.replace('text-green-600', 'text-red-600');
    } else {
        box.classList.replace('bg-red-50', 'bg-green-50');
        span.classList.replace('text-red-600', 'text-green-600');
    }
}

function exactAmount() {
    const total = parseFloat(document.getElementById('totalAmount').textContent.replace(/,/g, '')) || 0;
    document.getElementById('amountPaid').value = total;
    calculateChange();
}

// ── Customer fields ───────────────────────────────────────────────────────────
function toggleCustomerFields() {
    const val = document.querySelector('input[name="customer_option"]:checked').value;
    document.getElementById('existingCustomerDiv').classList.toggle('hidden', val !== 'existing');
    document.getElementById('newCustomerDiv').classList.toggle('hidden', val !== 'new');
}

// ── Payment type ──────────────────────────────────────────────────────────────
function setPaymentType() {
    const val  = document.querySelector('input[name="payment_type_radio"]:checked').value;
    document.getElementById('payment_type').value = val;
    const cash = document.getElementById('cash-payment-div');
    const inv  = document.getElementById('invoiceNotice');
    const btn  = document.getElementById('checkoutBtnText');
    if (val === 'invoice') {
        cash.classList.add('hidden'); inv.classList.remove('hidden');
        btn.textContent = 'Make Invoice';
        document.getElementById('posForm').action = "{{ route('cashier.posInvoice') }}";
    } else {
        cash.classList.remove('hidden'); inv.classList.add('hidden');
        btn.textContent = 'Complete Sale';
        document.getElementById('posForm').action = "{{ route('pos.process') }}";
    }
    calculateChange();
}

// ── Autocomplete search ───────────────────────────────────────────────────────
function initSearch() {
    const input    = document.getElementById('productSearch');
    const dropdown = document.getElementById('searchResultsDropdown');
    let activeIdx  = 0;

    input.addEventListener('input', function () {
        const q = this.value.toLowerCase().trim();
        if (!q) { dropdown.classList.add('hidden'); dropdown.innerHTML = ''; return; }
        const matches = allProducts.filter(p =>
            p.name.toLowerCase().includes(q) || (p.sku && p.sku.toLowerCase().includes(q)));
        if (!matches.length) {
            dropdown.innerHTML = '<div class="p-3 text-gray-500 text-sm text-center">No products found</div>';
            dropdown.classList.remove('hidden'); return;
        }
        dropdown.innerHTML = matches.map((p, idx) => `
            <div class="search-result-item p-3 border-b border-gray-100 hover:bg-green-50 cursor-pointer flex justify-between items-center ${idx === 0 ? 'bg-green-50 ring-1 ring-green-400 font-semibold' : ''}"
                 data-id="${p.id}" data-index="${idx}">
                <div>
                    <span class="font-semibold text-gray-900">${p.name}</span>
                    <span class="text-xs text-gray-500 font-mono ml-2">SKU: ${p.sku || 'N/A'}</span>
                </div>
                <div class="text-right">
                    <span class="font-bold text-gray-900">UGX ${p.price.toLocaleString()}</span>
                    <span class="text-xs text-gray-600 block">Stock: ${p.stock} ${p.unit}</span>
                </div>
            </div>`).join('');
        dropdown.classList.remove('hidden');
        activeIdx = 0;
    });

    dropdown.addEventListener('click', function (e) {
        const item = e.target.closest('.search-result-item');
        if (!item) return;
        const p = allProducts.find(p => p.id === parseInt(item.dataset.id));
        if (p) { addToCart(p.id, p.name, p.price, p.unit, p.stock); input.value = ''; dropdown.classList.add('hidden'); input.focus(); }
    });

    document.addEventListener('click', e => {
        if (!input.contains(e.target) && !dropdown.contains(e.target)) dropdown.classList.add('hidden');
    });

    document.addEventListener('keydown', function (e) {
        const items = dropdown.querySelectorAll('.search-result-item');
        if (!dropdown.classList.contains('hidden') && items.length) {
            if (e.key === 'ArrowDown') { e.preventDefault(); activeIdx = (activeIdx + 1) % items.length; highlightItem(items, activeIdx); return; }
            if (e.key === 'ArrowUp')   { e.preventDefault(); activeIdx = (activeIdx - 1 + items.length) % items.length; highlightItem(items, activeIdx); return; }
            if (e.key === 'Enter') {
                e.preventDefault();
                const active = items[activeIdx];
                if (active) { const p = allProducts.find(p => p.id === parseInt(active.dataset.id)); if (p) { addToCart(p.id, p.name, p.price, p.unit, p.stock); input.value = ''; dropdown.classList.add('hidden'); input.focus(); } }
                return;
            }
            if (e.key === 'Escape') { input.value = ''; dropdown.classList.add('hidden'); input.focus(); return; }
        }
        // Global shortcuts
        if (e.key === 'Escape') { input.value = ''; input.focus(); }
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') { e.preventDefault(); input.focus(); }
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
            e.preventDefault();
            const btn = document.getElementById('checkoutBtn');
            if (btn && !btn.disabled) document.getElementById('posForm').requestSubmit();
        }
    });
}

function highlightItem(items, idx) {
    items.forEach((el, i) => {
        el.classList.toggle('bg-green-50', i === idx);
        el.classList.toggle('ring-1', i === idx);
        el.classList.toggle('ring-green-400', i === idx);
        el.classList.toggle('font-semibold', i === idx);
        if (i === idx) el.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    });
}

// ── DOMContentLoaded ──────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    // Reset button (back-button / fresh load)
    const btn = document.getElementById('checkoutBtn');
    if (btn) { btn.dataset.submitting = 'false'; btn.disabled = true; btn.innerHTML = '<i class="fas fa-check-circle text-lg"></i> <span id="checkoutBtnText">Complete Sale</span>'; }

    setPaymentType();
    toggleCustomerFields();
    updateTotals();
    initSearch();

    document.querySelectorAll('input[name="payment_type_radio"]').forEach(r => r.addEventListener('change', setPaymentType));
    document.querySelectorAll('input[name="customer_option"]').forEach(r => r.addEventListener('change', toggleCustomerFields));
    document.getElementById('discountAmount').addEventListener('change', updateTotals);
    document.getElementById('addTaxCheckbox').addEventListener('change', updateTotals);
    document.getElementById('amountPaid').addEventListener('input', calculateChange);
    document.getElementById('exactAmountBtn').addEventListener('click', exactAmount);
    document.getElementById('clearCartBtn').addEventListener('click', clearCart);

    // Form submit — double-submit guard
    document.getElementById('posForm').addEventListener('submit', function (e) {
        const btn = document.getElementById('checkoutBtn');
        if (btn && btn.dataset.submitting === 'true') { e.preventDefault(); return; }
        if (btn) { btn.dataset.submitting = 'true'; btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...'; }
        // Inject hidden item fields
        this.querySelectorAll('input[name^="items["]').forEach(i => i.remove());
        cart.forEach(function (item, idx) {
            ['product_id:id', 'quantity:quantity', 'price:price'].forEach(pair => {
                const [name, key] = pair.split(':');
                const inp = document.createElement('input');
                inp.type  = 'hidden';
                inp.name  = `items[${idx}][${name}]`;
                inp.value = item[key];
                e.target.appendChild(inp);
            });
        });
    });
});

// Reset button when browser restores page from bfcache (back button)
window.addEventListener('pageshow', function (e) {
    if (e.persisted) {
        const btn = document.getElementById('checkoutBtn');
        if (btn) { btn.dataset.submitting = 'false'; btn.disabled = !cart.length; btn.innerHTML = '<i class="fas fa-check-circle text-lg"></i> <span id="checkoutBtnText">Complete Sale</span>'; }
    }
});
</script>
@endpush
