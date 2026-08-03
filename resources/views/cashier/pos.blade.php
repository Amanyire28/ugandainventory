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
        #checkoutPanel.mobile-panel-open { transform: translateY(0) !important; }
    }
    #mobileCartBar { display: none !important; }
    @media (max-width: 1023px) { #mobileCartBar { display: flex !important; } }
</style>

<!-- Product data -->
<script>
const allProducts = [
    @foreach($products as $product)
    {
        id: {{ $product->id }},
        name: '{{ addslashes($product->name) }}',
        sku: '{{ addslashes($product->sku ?? "") }}',
        price: {{ $product->selling_price }},
        stock: {{ $product->quantity }},
        unit: '{{ addslashes($product->unit) }}',
        requiresVat: {{ ($product->requires_vat ?? false) ? 'true' : 'false' }}
    },
    @endforeach
];
</script>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 h-full overflow-hidden relative">
    <!-- Mobile backdrop -->
    <div id="checkoutPanelBackdrop" onclick="closeMobileCheckout()"
         class="hidden fixed inset-0 bg-black/50 z-40 lg:hidden"></div>

    <!-- LEFT: Search + cart -->
    <div class="lg:col-span-2 flex flex-col h-full min-h-0 space-y-4 pb-16 lg:pb-0">

        <!-- Search -->
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

        <!-- Customer -->
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
                <select id="existingCustomerId"
                        class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-xs focus:ring-2 focus:ring-green-500">
                    <option value="">Select Customer</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                    @endforeach
                </select>
            </div>
            <div id="newCustomerDiv" class="hidden mt-2 grid grid-cols-2 gap-2 p-2 bg-green-50 rounded-lg">
                <input type="text" id="newCustomerName" placeholder="Name *"
                       class="px-2 py-1 border border-gray-300 rounded-md focus:ring-2 focus:ring-green-500 text-xs">
                <input type="text" id="newCustomerPhone" placeholder="Phone *"
                       class="px-2 py-1 border border-gray-300 rounded-md focus:ring-2 focus:ring-green-500 text-xs">
                <input type="email" id="newCustomerEmail" placeholder="Email"
                       class="col-span-2 px-2 py-1 border border-gray-300 rounded-md focus:ring-2 focus:ring-green-500 text-xs">
                <input type="text" id="newCustomerAddress" placeholder="Address"
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
                    <input type="radio" name="payment_type" value="cash" checked class="h-3.5 w-3.5 text-indigo-600 mr-1.5">
                    <span>Cash Sale</span>
                </label>
                <label class="flex items-center justify-center p-2 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 text-xs">
                    <input type="radio" name="payment_type" value="invoice" class="h-3.5 w-3.5 text-indigo-600 mr-1.5">
                    <span>Credit Invoice</span>
                </label>
            </div>
        </div>

        <!-- Totals + amounts -->
        <div class="flex-1 flex flex-col justify-between pt-4 pb-2 min-h-0">
            <div class="space-y-4 text-sm bg-gray-50 p-3 rounded-lg border border-gray-100">
                <div class="flex justify-between items-center">
                    <span class="font-medium text-gray-600">Subtotal:</span>
                    <span class="font-bold text-gray-900 text-base">UGX <span id="subtotalAmount">0</span></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="font-medium text-gray-600">Discount:</span>
                    <input type="number" id="discountAmount" value="0" min="0" step="100"
                           class="w-32 px-3 py-1.5 border border-gray-300 rounded-lg text-right text-sm font-bold focus:ring-2 focus:ring-green-500">
                </div>
                <div class="flex justify-between items-center py-1 border-t border-dashed">
                    <span class="font-medium text-gray-600">VAT (18%):</span>
                    <span class="font-bold text-gray-900 text-base">UGX <span id="taxAmount">0</span></span>
                </div>
                <div class="flex justify-between items-center text-lg font-extrabold text-green-600 pt-2 border-t">
                    <span>TOTAL:</span>
                    <span>UGX <span id="totalAmount">0</span></span>
                </div>
            </div>

            <div id="cash-payment-div" class="space-y-3 bg-gray-50 p-3 rounded-lg border border-gray-100 flex-shrink-0 mt-3">
                <div class="flex items-center justify-between gap-3">
                    <span class="text-xs font-bold text-gray-700 whitespace-nowrap">Amount Paid:</span>
                    <input type="number" id="amountPaid" value="0" min="0" step="100"
                           class="flex-1 min-w-0 px-3 py-2 border border-gray-300 rounded-lg text-right text-base font-extrabold text-gray-900 focus:ring-2 focus:ring-green-500">
                    <button type="button" id="exactAmountBtn"
                            class="px-3 py-2 bg-blue-50 hover:bg-blue-100 border border-blue-200 text-blue-700 rounded-lg text-sm font-bold transition">
                        Exact
                    </button>
                </div>
                <div class="p-3 rounded-lg bg-green-50 flex justify-between items-center text-sm" id="changeBox">
                    <span class="font-semibold text-gray-600">Change:</span>
                    <span class="text-base font-extrabold text-green-600">UGX <span id="changeAmount">0</span></span>
                </div>
                <textarea id="saleNotes" rows="2"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500"
                          placeholder="Notes (Optional)..."></textarea>
            </div>

            <div id="invoiceNotice"
                 class="hidden mt-3 p-3 bg-indigo-50 border border-indigo-100 rounded-lg text-sm text-indigo-700 text-center flex-shrink-0">
                <i class="fas fa-info-circle mr-1.5"></i> Credit Sale — items will be added to invoice.
            </div>

            <button onclick="processSale()" id="checkoutBtn"
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

<!-- Receipt modal -->
<div id="receiptModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full" id="receiptContent"></div>
</div>

@endsection

@push('scripts')
<script>
let cart = [];
let isProcessingSale = false;

// ── Mobile drawer ─────────────────────────────────────────────────────────
function openMobileCheckout()  { document.getElementById('checkoutPanel').classList.add('mobile-panel-open'); document.getElementById('checkoutPanelBackdrop').classList.remove('hidden'); }
function closeMobileCheckout() { document.getElementById('checkoutPanel').classList.remove('mobile-panel-open'); document.getElementById('checkoutPanelBackdrop').classList.add('hidden'); }

// ── Cart ──────────────────────────────────────────────────────────────────
function addToCart(id, name, price, unit, maxStock, requiresVat) {
    if (maxStock <= 0) { alert('Out of stock!'); return; }
    const existing = cart.find(i => i.id === id);
    if (existing) {
        if (existing.quantity + 1 > maxStock) { alert('Maximum stock: ' + maxStock); return; }
        existing.quantity++;
    } else {
        cart.push({ id, name, price, quantity: 1, unit, maxStock, requiresVat: !!requiresVat });
    }
    renderCart(); updateTotals();
}
function removeFromCart(id) { cart = cart.filter(i => i.id !== id); renderCart(); updateTotals(); }
function updateQuantity(id, val) {
    const item = cart.find(i => i.id === id);
    if (!item) return;
    const q = parseFloat(val);
    if (q > item.maxStock) { alert('Cannot exceed stock: ' + item.maxStock); renderCart(); return; }
    if (q <= 0) { removeFromCart(id); return; }
    item.quantity = q; renderCart(); updateTotals();
}
function clearCart() { if (!cart.length) return; if (confirm('Clear cart?')) { cart = []; renderCart(); updateTotals(); } }

function renderCart() {
    const tbody  = document.getElementById('cartItemsTable');
    const btn    = document.getElementById('checkoutBtn');
    const bar    = document.getElementById('mobileCartBar');
    const count  = document.getElementById('mobileCartCount');
    const mTotal = document.getElementById('mobileCartTotal');
    if (!cart.length) {
        tbody.innerHTML = `<tr><td colspan="5" class="px-4 py-12 text-center text-gray-400"><i class="fas fa-shopping-basket text-5xl mb-3 block text-gray-300"></i>No products selected.</td></tr>`;
        if (btn) btn.disabled = true;
        if (bar) bar.classList.add('translate-y-full');
        return;
    }
    if (btn) btn.disabled = false;
    if (bar) bar.classList.remove('translate-y-full');
    const totalItems = cart.reduce((s, i) => s + i.quantity, 0);
    // Mobile bar total must include VAT so it matches the checkout panel
    const discount   = parseFloat(document.getElementById('discountAmount').value) || 0;
    const autoVat    = cart.reduce((s, i) => i.requiresVat ? s + (i.price * i.quantity * 0.18) : s, 0);
    const totalVal   = cart.reduce((s, i) => s + i.price * i.quantity, 0) - discount + autoVat;
    if (count)  count.textContent  = totalItems + ' item' + (totalItems !== 1 ? 's' : '');
    if (mTotal) mTotal.textContent = totalVal.toLocaleString();
    tbody.innerHTML = cart.map(item => `
        <tr class="hover:bg-gray-50">
            <td class="px-4 py-3 font-semibold text-gray-900">
                ${item.name}
                <span class="text-xs text-gray-500 block">Stock: ${item.maxStock} ${item.unit}</span>
                ${item.requiresVat ? '<span class="inline-block text-xs bg-orange-100 text-orange-700 font-bold px-1.5 py-0.5 rounded mt-0.5">VAT 18%</span>' : '<span class="inline-block text-xs bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded mt-0.5">Tax exempt</span>'}
            </td>
            <td class="px-4 py-3 text-right text-gray-800 font-medium">UGX ${item.price.toLocaleString()}</td>
            <td class="px-4 py-3">
                <div class="flex items-center justify-center space-x-2">
                    <button type="button" onclick="updateQuantity(${item.id},${item.quantity-1})" class="w-8 h-8 bg-gray-100 hover:bg-gray-200 rounded-lg flex items-center justify-center border border-gray-200"><i class="fas fa-minus text-xs"></i></button>
                    <input type="number" value="${item.quantity}" min="1" max="${item.maxStock}" onchange="updateQuantity(${item.id},this.value)" class="w-16 px-2 py-1.5 border border-gray-300 rounded-lg text-center font-bold text-sm focus:ring-2 focus:ring-green-500">
                    <button type="button" onclick="updateQuantity(${item.id},${item.quantity+1})" class="w-8 h-8 bg-gray-100 hover:bg-gray-200 rounded-lg flex items-center justify-center border border-gray-200"><i class="fas fa-plus text-xs"></i></button>
                </div>
            </td>
            <td class="px-4 py-3 text-right font-bold text-green-600">
                UGX ${(item.price*item.quantity).toLocaleString()}
                ${item.requiresVat ? `<span class="text-xs text-orange-600 block font-normal">+VAT UGX ${(item.price*item.quantity*0.18).toLocaleString()}</span>` : ''}
            </td>
            <td class="px-4 py-3 text-center"><button type="button" onclick="removeFromCart(${item.id})" class="p-2 bg-red-50 hover:bg-red-100 text-red-600 rounded-lg border border-red-200"><i class="fas fa-trash-alt text-sm"></i></button></td>
        </tr>`).join('');
}

// ── Totals ────────────────────────────────────────────────────────────────
function updateTotals() {
    const subtotal = cart.reduce((s, i) => s + i.price * i.quantity, 0);
    const discount = parseFloat(document.getElementById('discountAmount').value) || 0;
    // Auto-VAT per product — mirrors server logic in POSController::process()
    // Only products with requiresVat === true trigger 18% VAT
    const autoVat = cart.reduce((s, i) => {
        return i.requiresVat ? s + (i.price * i.quantity * 0.18) : s;
    }, 0);
    const total = subtotal - discount + autoVat;
    document.getElementById('subtotalAmount').textContent = subtotal.toLocaleString();
    document.getElementById('taxAmount').textContent      = autoVat.toLocaleString();
    document.getElementById('totalAmount').textContent    = total.toLocaleString();
    calculateChange();
}
function calculateChange() {
    const total = parseFloat(document.getElementById('totalAmount').textContent.replace(/,/g,'')) || 0;
    const paid  = parseFloat(document.getElementById('amountPaid').value) || 0;
    document.getElementById('changeAmount').textContent = Math.max(0, paid - total).toLocaleString();
    const box = document.getElementById('changeBox'); const span = document.getElementById('changeAmount');
    if (paid > 0 && paid < total) { box.classList.replace('bg-green-50','bg-red-50'); span.className = 'text-base font-extrabold text-red-600'; }
    else { box.classList.replace('bg-red-50','bg-green-50'); span.className = 'text-base font-extrabold text-green-600'; }
}
function exactAmount() { const t = parseFloat(document.getElementById('totalAmount').textContent.replace(/,/g,''))||0; document.getElementById('amountPaid').value = t; calculateChange(); }

// ── Customer / payment toggles ─────────────────────────────────────────────
function toggleCustomerFields() {
    const val = document.querySelector('input[name="customer_option"]:checked').value;
    document.getElementById('existingCustomerDiv').classList.toggle('hidden', val !== 'existing');
    document.getElementById('newCustomerDiv').classList.toggle('hidden', val !== 'new');
}
function togglePaymentType() {
    const val = document.querySelector('input[name="payment_type"]:checked').value;
    const cash = document.getElementById('cash-payment-div');
    const inv  = document.getElementById('invoiceNotice');
    const lbl  = document.getElementById('checkoutBtnText');
    if (val === 'invoice') { cash.classList.add('hidden'); inv.classList.remove('hidden'); lbl.textContent = 'Make Invoice'; }
    else { cash.classList.remove('hidden'); inv.classList.add('hidden'); lbl.textContent = 'Complete Sale'; }
    calculateChange();
}

// ── Process sale (AJAX — same as owner POS) ────────────────────────────────
async function processSale() {
    if (isProcessingSale) return;
    if (!cart.length) { alert('Cart is empty!'); return; }

    const paymentType    = document.querySelector('input[name="payment_type"]:checked').value;
    const customerOption = document.querySelector('input[name="customer_option"]:checked').value;
    const amountPaid     = parseFloat(document.getElementById('amountPaid').value) || 0;
    const total          = parseFloat(document.getElementById('totalAmount').textContent.replace(/,/g,'')) || 0;

    if (paymentType === 'cash' && amountPaid < total) { alert('Amount paid is less than total!'); return; }
    if (customerOption === 'existing' && !document.getElementById('existingCustomerId').value) { alert('Please select a customer.'); return; }
    if (customerOption === 'new') {
        if (!document.getElementById('newCustomerName').value.trim()) { alert('Enter customer name.'); return; }
        if (!document.getElementById('newCustomerPhone').value.trim()) { alert('Enter customer phone.'); return; }
    }

    isProcessingSale = true;
    const btn = document.getElementById('checkoutBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';

    const payload = {
        customer_option: customerOption,
        items: cart.map(i => ({ product_id: i.id, quantity: i.quantity, price: i.price })),
        discount: parseFloat(document.getElementById('discountAmount').value) || 0,
        notes: document.getElementById('saleNotes').value || null,
        amount_paid: amountPaid,
        _token: '{{ csrf_token() }}'
    };
    if (customerOption === 'existing') payload.customer_id = document.getElementById('existingCustomerId').value;
    if (customerOption === 'new') {
        payload.new_customer_name    = document.getElementById('newCustomerName').value.trim();
        payload.new_customer_phone   = document.getElementById('newCustomerPhone').value.trim();
        payload.new_customer_email   = document.getElementById('newCustomerEmail').value.trim();
        payload.new_customer_address = document.getElementById('newCustomerAddress').value.trim();
    }

    const endpoint = paymentType === 'invoice'
        ? '{{ route("invoices.pos", [], false) }}'
        : '{{ route("pos.process", [], false) }}';

    try {
        const resp = await fetch(endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify(payload)
        });
        const result = await resp.json();

        if (result && result.success) {
            // Update local stock
            cart.forEach(item => { const p = allProducts.find(p => p.id === item.id); if (p) p.stock -= item.quantity; });
            showReceipt(result);
            // Reset cart & form
            cart = [];
            renderCart(); updateTotals();
            document.querySelector('input[name="customer_option"][value="walk_in"]').checked = true;
            toggleCustomerFields();
            document.getElementById('discountAmount').value = 0;
            document.getElementById('amountPaid').value = 0;
            document.getElementById('saleNotes').value = '';
            document.getElementById('existingCustomerId').value = '';
            document.getElementById('newCustomerName').value = '';
            document.getElementById('newCustomerPhone').value = '';
            closeMobileCheckout();
        } else {
            alert(result.message || 'Sale failed. Please try again.');
        }
    } catch (err) {
        alert('Connection error. Please check your network and try again.');
    } finally {
        isProcessingSale = false;
        btn.disabled = !cart.length;
        const lbl = document.querySelector('input[name="payment_type"]:checked').value === 'invoice' ? 'Make Invoice' : 'Complete Sale';
        btn.innerHTML = '<i class="fas fa-check-circle mr-2"></i><span id="checkoutBtnText">' + lbl + '</span>';
    }
}

// ── Receipt modal ─────────────────────────────────────────────────────────
function showReceipt(data) {
    const modal   = document.getElementById('receiptModal');
    const content = document.getElementById('receiptContent');
    if (!modal || !content) { alert('Sale #' + (data.sale_number || data.invoice_number) + ' completed!'); return; }
    const isSale = !!data.sale_number;
    content.innerHTML = `
        <div class="p-6">
            <div class="text-center mb-6">
                <i class="fas fa-check-circle text-6xl text-green-500 mb-3"></i>
                <h2 class="text-2xl font-bold text-gray-900">${isSale ? 'Sale Completed!' : 'Invoice Created!'}</h2>
                <p class="text-gray-600 font-semibold">${isSale ? 'Sale #' + data.sale_number : 'Invoice #' + data.invoice_number}</p>
            </div>
            ${isSale ? `
            <div class="space-y-3 mb-6 bg-gray-50 p-4 rounded-lg">
                <div class="flex justify-between"><span class="text-gray-600">Total</span><span class="font-bold">UGX ${(data.total||0).toLocaleString()}</span></div>
                <div class="flex justify-between"><span class="text-gray-600">Paid</span><span class="font-bold text-green-600">UGX ${(data.amount_paid||0).toLocaleString()}</span></div>
                <div class="flex justify-between text-lg font-extrabold border-t pt-2"><span>Change</span><span class="text-green-600">UGX ${(data.change||0).toLocaleString()}</span></div>
            </div>` : ''}
            <div class="flex gap-3">
                <a href="${isSale ? '/sales/' + data.sale_id : '/invoices/' + data.invoice_id}" target="_blank"
                   class="flex-1 py-2.5 bg-indigo-600 text-white rounded-lg text-center font-bold hover:bg-indigo-700 transition">
                    <i class="fas fa-print mr-1"></i>Print Receipt
                </a>
                <button onclick="document.getElementById('receiptModal').classList.add('hidden')"
                        class="flex-1 py-2.5 bg-green-600 text-white rounded-lg font-bold hover:bg-green-700 transition">
                    <i class="fas fa-plus-circle mr-1"></i>New Sale
                </button>
            </div>
        </div>`;
    modal.classList.remove('hidden');
}

// ── Search autocomplete ───────────────────────────────────────────────────
function initSearch() {
    const input = document.getElementById('productSearch');
    const dd    = document.getElementById('searchResultsDropdown');
    let idx = 0;

    input.addEventListener('input', function() {
        const q = this.value.toLowerCase().trim();
        if (!q) { dd.classList.add('hidden'); dd.innerHTML = ''; return; }
        const matches = allProducts.filter(p => p.name.toLowerCase().includes(q) || (p.sku && p.sku.toLowerCase().includes(q)));
        if (!matches.length) { dd.innerHTML = '<div class="p-3 text-gray-500 text-sm text-center">No products found</div>'; dd.classList.remove('hidden'); return; }
        dd.innerHTML = matches.map((p, i) => `
            <div class="search-result-item p-3 border-b border-gray-100 hover:bg-green-50 cursor-pointer flex justify-between ${i===0?'bg-green-50 ring-1 ring-green-400 font-semibold':''}"
                 data-id="${p.id}">
                <div><span class="font-semibold text-gray-900">${p.name}</span><span class="text-xs text-gray-500 font-mono ml-2">SKU: ${p.sku||'N/A'}</span></div>
                <div class="text-right"><span class="font-bold text-gray-900">UGX ${p.price.toLocaleString()}</span><span class="text-xs text-gray-600 block">Stock: ${p.stock} ${p.unit}</span></div>
            </div>`).join('');
        dd.classList.remove('hidden'); idx = 0;
    });

    dd.addEventListener('click', e => {
        const item = e.target.closest('.search-result-item');
        if (!item) return;
        const p = allProducts.find(p => p.id === parseInt(item.dataset.id));
        if (p) { addToCart(p.id, p.name, p.price, p.unit, p.stock, p.requiresVat); input.value = ''; dd.classList.add('hidden'); input.focus(); }
    });

    document.addEventListener('click', e => { if (!input.contains(e.target) && !dd.contains(e.target)) dd.classList.add('hidden'); });

    document.addEventListener('keydown', function(e) {
        const items = dd.querySelectorAll('.search-result-item');
        if (!dd.classList.contains('hidden') && items.length) {
            if (e.key==='ArrowDown') { e.preventDefault(); idx=(idx+1)%items.length; highlightItem(items,idx); return; }
            if (e.key==='ArrowUp')   { e.preventDefault(); idx=(idx-1+items.length)%items.length; highlightItem(items,idx); return; }
            if (e.key==='Enter') {
                e.preventDefault();
                const a = items[idx]; if (a) { const p = allProducts.find(p=>p.id===parseInt(a.dataset.id)); if(p){addToCart(p.id,p.name,p.price,p.unit,p.stock,p.requiresVat); input.value=''; dd.classList.add('hidden'); input.focus();} }
                return;
            }
            if (e.key==='Escape') { input.value=''; dd.classList.add('hidden'); input.focus(); return; }
        }
        if (e.key==='Escape') { input.value=''; input.focus(); }
        if ((e.ctrlKey||e.metaKey)&&e.key==='k') { e.preventDefault(); input.focus(); }
        if ((e.ctrlKey||e.metaKey)&&e.key==='Enter') { e.preventDefault(); if(!isProcessingSale && cart.length) processSale(); }
    });
}

function highlightItem(items, i) {
    items.forEach((el,j) => { el.classList.toggle('bg-green-50',i===j); el.classList.toggle('ring-1',i===j); el.classList.toggle('ring-green-400',i===j); el.classList.toggle('font-semibold',i===j); if(i===j) el.scrollIntoView({block:'nearest',behavior:'smooth'}); });
}

// ── Boot ──────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
    const btn = document.getElementById('checkoutBtn');
    if (btn) { btn.disabled = true; }
    toggleCustomerFields();
    togglePaymentType();
    updateTotals();
    initSearch();

    document.querySelectorAll('input[name="customer_option"]').forEach(r => r.addEventListener('change', toggleCustomerFields));
    document.querySelectorAll('input[name="payment_type"]').forEach(r => r.addEventListener('change', togglePaymentType));
    document.getElementById('discountAmount').addEventListener('input', updateTotals);
    document.getElementById('amountPaid').addEventListener('input', calculateChange);
    document.getElementById('exactAmountBtn').addEventListener('click', exactAmount);
    document.getElementById('clearCartBtn').addEventListener('click', clearCart);
});

window.addEventListener('pageshow', function(e) {
    if (e.persisted) { isProcessingSale = false; const btn=document.getElementById('checkoutBtn'); if(btn){btn.disabled=!cart.length; btn.innerHTML='<i class="fas fa-check-circle text-lg"></i> <span id="checkoutBtnText">Complete Sale</span>';} }
});
</script>
@endpush
