@extends('layouts.cashier-layout')

@section('title', 'Point of Sale (POS)')



@section('content')
<style>
    main {
        display: flex !important;
        flex-direction: column !important;
        height: calc(100vh - 72px) !important;
        overflow: hidden !important;
        padding: 1rem !important;
    }
</style>
<form id="posForm" method="POST" class="h-full">
    @csrf
    <input type="hidden" name="payment_type" id="payment_type" value="cash">

    <!-- JSON Products data for search autocomplete -->
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

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 h-full overflow-hidden">
        <!-- LEFT SIDE: Selected Products Table -->
        <div class="lg:col-span-2 flex flex-col h-full min-h-0 space-y-4">
            <!-- Search Field -->
            <div class="bg-white rounded-xl shadow-lg p-4 flex-shrink-0">
                <div class="relative w-full">
                    <input type="text" id="productSearch" autocomplete="off" placeholder="Type product name, SKU or scan barcode..." class="w-full px-4 py-3 pl-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                    <i class="fas fa-search absolute left-3 top-4 text-gray-400"></i>
                    <!-- Autocomplete Dropdown -->
                    <div id="searchResultsDropdown" class="hidden absolute left-0 right-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-xl z-50 max-h-60 overflow-y-auto animate-fadeIn"></div>
                </div>
            </div>

            <!-- Selected Products List Table -->
            <div class="bg-white rounded-xl shadow-lg p-4 flex-1 flex flex-col min-h-0">
                <div class="flex justify-between items-center mb-3 flex-shrink-0">
                    <h3 class="text-lg font-bold text-gray-800">
                        <i class="fas fa-shopping-basket text-green-600 mr-2"></i>Selected Products
                    </h3>
                    <button type="button" id="clearCartBtn" onclick="clearCart()" class="text-sm text-red-600 hover:text-red-800 font-semibold transition">
                        <i class="fas fa-trash mr-1"></i>Clear All
                    </button>
                </div>
                
                <div class="flex-1 overflow-y-auto rounded-lg border border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 sticky top-0 z-10">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">Product</th>
                                <th scope="col" class="px-4 py-3 text-right font-semibold text-gray-500 uppercase tracking-wider w-32">Price</th>
                                <th scope="col" class="px-4 py-3 text-center font-semibold text-gray-500 uppercase tracking-wider w-40">Quantity</th>
                                <th scope="col" class="px-4 py-3 text-right font-semibold text-gray-500 uppercase tracking-wider w-36">Total</th>
                                <th scope="col" class="px-4 py-3 text-center font-semibold text-gray-500 uppercase tracking-wider w-16">Action</th>
                            </tr>
                        </thead>
                        <tbody id="cartItemsTable" class="bg-white divide-y divide-gray-200 text-gray-700">
                            <tr>
                                <td colspan="5" class="px-4 py-12 text-center text-gray-400">
                                    <i class="fas fa-shopping-basket text-5xl mb-3 block text-gray-300"></i>
                                    No products selected. Search/scan above to add.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- RIGHT SIDE: Payment & Checkout Panel -->
        <div class="lg:col-span-1 bg-white rounded-xl shadow-lg p-4 flex flex-col h-full justify-between min-h-0">
            <!-- Customer Section -->
            <div class="border-b pb-3 flex-shrink-0">
                <h3 class="text-sm font-bold text-gray-800 mb-2 flex items-center">
                    <i class="fas fa-user-circle text-green-600 mr-2"></i>Customer
                </h3>
                <div class="grid grid-cols-3 gap-2">
                    <label class="flex items-center justify-center p-2 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 text-xs">
                        <input type="radio" name="customer_option" value="walk_in" checked onchange="toggleCustomerFields()" class="h-3.5 w-3.5 text-green-600 focus:ring-green-500 mr-1">
                        <span>Walk-in</span>
                    </label>
                    <label class="flex items-center justify-center p-2 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 text-xs">
                        <input type="radio" name="customer_option" value="existing" onchange="toggleCustomerFields()" class="h-3.5 w-3.5 text-green-600 focus:ring-green-500 mr-1">
                        <span>Existing</span>
                    </label>
                    <label class="flex items-center justify-center p-2 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 text-xs">
                        <input type="radio" name="customer_option" value="new" onchange="toggleCustomerFields()" class="h-3.5 w-3.5 text-green-600 focus:ring-green-500 mr-1">
                        <span>New</span>
                    </label>
                </div>
                
                <div id="existingCustomerDiv" class="hidden mt-2">
                    <select id="existingCustomerId" name="customer_id" class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-xs focus:ring-2 focus:ring-green-500">
                        <option value="">Select Customer</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div id="newCustomerDiv" class="hidden mt-2 grid grid-cols-2 gap-2 p-2 bg-green-50 rounded-lg text-xs">
                    <input type="text" name="new_customer_name" id="newCustomerName" class="px-2 py-1 border border-gray-300 rounded-md focus:ring-2 focus:ring-green-500 text-xs" placeholder="Name *">
                    <input type="text" name="new_customer_phone" id="newCustomerPhone" class="px-2 py-1 border border-gray-300 rounded-md focus:ring-2 focus:ring-green-500 text-xs" placeholder="Phone *">
                    <input type="email" name="new_customer_email" id="newCustomerEmail" class="col-span-2 px-2 py-1 border border-gray-300 rounded-md focus:ring-2 focus:ring-green-500 text-xs" placeholder="Email">
                    <input type="text" name="new_customer_address" id="newCustomerAddress" class="col-span-2 px-2 py-1 border border-gray-300 rounded-md focus:ring-2 focus:ring-green-500 text-xs" placeholder="Address">
                </div>
            </div>

            <!-- Payment Option Section -->
            <div class="border-b py-3 flex-shrink-0">
                <h3 class="text-sm font-bold text-gray-800 mb-2 flex items-center">
                    <i class="fas fa-coins text-green-600 mr-2"></i>Payment Option
                </h3>
                <div class="grid grid-cols-2 gap-2">
                    <label class="flex items-center justify-center p-2 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 text-xs">
                        <input type="radio" name="payment_type_radio" value="cash" checked class="h-3.5 w-3.5 text-indigo-600 focus:ring-indigo-500 mr-1.5">
                        <span>Cash Sale</span>
                    </label>
                    <label class="flex items-center justify-center p-2 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 text-xs">
                        <input type="radio" name="payment_type_radio" value="invoice" class="h-3.5 w-3.5 text-indigo-600 focus:ring-indigo-500 mr-1.5">
                        <span>Credit Invoice</span>
                    </label>
                </div>
            </div>

            <!-- Checkout Info Section -->
            <div class="flex-1 flex flex-col justify-between pt-4 pb-2 min-h-0">
                <div class="space-y-4 text-sm bg-gray-50 p-3 rounded-lg border border-gray-100">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-650 font-medium">Subtotal:</span>
                        <span class="font-bold text-gray-950 text-base">UGX <span id="subtotalAmount">0</span></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-650 font-medium">Discount:</span>
                        <input type="number" name="discount" id="discountAmount" value="0" min="0" step="100" class="w-32 px-3 py-1.5 border border-gray-300 rounded-lg text-right text-sm font-bold focus:ring-2 focus:ring-green-500">
                    </div>
                    <div class="flex justify-between items-center py-1 border-t border-dashed">
                        <label class="flex items-center cursor-pointer font-medium text-gray-650">
                            <input type="checkbox" name="add_tax" id="addTaxCheckbox" class="mr-2 h-4 w-4 text-green-600 focus:ring-green-500 rounded" value="1">
                            <span>Add Tax (18%)</span>
                        </label>
                        <span class="font-bold text-gray-950 text-base">UGX <span id="taxAmount">0</span></span>
                    </div>
                    <div class="flex justify-between items-center text-lg font-extrabold text-green-600 pt-2 border-t">
                        <span>TOTAL:</span>
                        <span>UGX <span id="totalAmount">0</span></span>
                    </div>
                </div>

                <!-- Cash Payment Details -->
                <div id="cash-payment-div" class="space-y-4 bg-gray-50 p-3 rounded-lg border border-gray-100 flex-shrink-0">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-xs font-bold text-gray-700 whitespace-nowrap">Amount Paid:</span>
                        <input type="number" name="amount_paid" id="amountPaid" value="0" min="0" step="100" class="flex-1 min-w-0 px-3 py-2 border border-gray-300 rounded-lg text-right text-base font-extrabold text-gray-900 focus:ring-2 focus:ring-green-500">
                        <button type="button" id="exactAmountBtn" class="px-3 py-2 bg-blue-50 hover:bg-blue-100 border border-blue-200 text-blue-700 rounded-lg text-sm font-bold transition">Exact</button>
                    </div>
                    <div class="p-3 rounded-lg flex justify-between items-center text-sm" id="changeBox">
                         <span class="font-semibold text-gray-600">Change:</span>
                         <span class="text-base font-extrabold text-green-600">UGX <span id="changeAmount">0</span></span>
                    </div>
                    <div>
                        <textarea name="notes" id="saleNotes" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500" placeholder="Notes (Optional)..."></textarea>
                    </div>
                </div>
                
                <div id="invoiceNotice" class="hidden p-3 bg-indigo-50 border border-indigo-100 rounded-lg text-sm text-indigo-700 text-center flex-shrink-0">
                    <i class="fas fa-info-circle mr-1.5"></i> Credit Sale. Items added to invoice.
                </div>

                <!-- Checkout Action Button -->
                <button type="submit"
                        id="checkoutBtn"
                        class="w-full py-3.5 bg-green-600 text-white rounded-lg hover:bg-green-700 font-extrabold text-base shadow-lg hover:shadow-xl transition disabled:bg-gray-300 disabled:cursor-not-allowed flex items-center justify-center gap-2 flex-shrink-0">
                    <i class="fas fa-check-circle text-lg"></i>
                    <span id="checkoutBtnText">Complete Sale</span>
                </button>
            </div>
        </div>
    </div>
</form>

@endsection

@push('scripts')
<script>
// Define all functions in global scope first
let cart = [];

// ----- CUSTOMER FIELDS (Define early) -----
function toggleCustomerFields() {
    const option = document.querySelector('input[name="customer_option"]:checked').value;
    document.getElementById('existingCustomerDiv').classList.add('hidden');
    document.getElementById('newCustomerDiv').classList.add('hidden');
    if (option === 'existing') {
        document.getElementById('existingCustomerDiv').classList.remove('hidden');
    } else if (option === 'new') {
        document.getElementById('newCustomerDiv').classList.remove('hidden');
    }
}

// ----- PRODUCT CART FUNCTIONS -----
function addToCart(id, name, price, unit, maxStock) {
    if (maxStock <= 0) {
        alert('Cannot add! Product is out of stock.');
        return;
    }
    const existingItem = cart.find(item => item.id === id);
    if (existingItem) {
        if (existingItem.quantity + 1 > maxStock) {
            alert('Cannot add more! Maximum stock available: ' + maxStock);
            return;
        }
        existingItem.quantity++;
    } else {
        cart.push({id, name, price, quantity: 1, unit, maxStock});
    }
    renderCart();
    updateTotals();
}

function removeFromCart(id) {
    cart = cart.filter(item => item.id !== id);
    renderCart();
    updateTotals();
}

function updateQuantity(id, newQuantity) {
    const item = cart.find(item => item.id === id);
    if (item) {
        if (newQuantity > item.maxStock) {
            alert('Cannot exceed available stock: ' + item.maxStock);
            renderCart();
            return;
        }
        if (newQuantity <= 0) {
            removeFromCart(id);
        } else {
            item.quantity = parseFloat(newQuantity);
            renderCart();
            updateTotals();
        }
    }
}

function clearCart() {
    if (cart.length === 0) return;
    if (confirm('Clear all items from cart?')) {
        cart = [];
        renderCart();
        updateTotals();
    }
}

function renderCart() {
    const tableBody = document.getElementById('cartItemsTable');
    const checkoutBtn = document.getElementById('checkoutBtn');
    
    if (cart.length === 0) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="5" class="px-4 py-12 text-center text-gray-400">
                    <i class="fas fa-shopping-basket text-5xl mb-3 block text-gray-300"></i>
                    No products selected. Search/scan above to add.
                </td>
            </tr>
        `;
        if (checkoutBtn) checkoutBtn.disabled = true;
        return;
    }
    
    if (checkoutBtn) checkoutBtn.disabled = false;
    let html = '';
    cart.forEach(item => {
        html += `
            <tr class="hover:bg-gray-50 transition">
                <td class="px-4 py-3 font-semibold text-gray-900">
                    ${item.name}
                    <span class="text-xs text-gray-500 block font-mono">Max Stock: ${item.maxStock} ${item.unit}</span>
                </td>
                <td class="px-4 py-3 text-right text-gray-800 font-medium">
                    UGX ${item.price.toLocaleString()}
                </td>
                <td class="px-4 py-3">
                    <div class="flex items-center justify-center space-x-2">
                        <button type="button" onclick="updateQuantity(${item.id}, ${item.quantity - 1})"
                                class="w-8 h-8 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-lg flex items-center justify-center transition border border-gray-200">
                            <i class="fas fa-minus text-xs"></i>
                        </button>
                        <input type="number"
                               value="${item.quantity}"
                               min="1"
                               max="${item.maxStock}"
                               onchange="updateQuantity(${item.id}, this.value)"
                               class="w-16 px-2 py-1.5 border border-gray-300 rounded-lg text-center font-bold text-sm focus:ring-2 focus:ring-green-500">
                        <button type="button" onclick="updateQuantity(${item.id}, ${item.quantity + 1})"
                                class="w-8 h-8 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-lg flex items-center justify-center transition border border-gray-200">
                            <i class="fas fa-plus text-xs"></i>
                        </button>
                    </div>
                </td>
                <td class="px-4 py-3 text-right font-bold text-green-600">
                    UGX ${(item.price * item.quantity).toLocaleString()}
                </td>
                <td class="px-4 py-3 text-center">
                    <button type="button" onclick="removeFromCart(${item.id})"
                            class="p-2 bg-red-50 hover:bg-red-100 text-red-600 rounded-lg transition border border-red-200">
                        <i class="fas fa-trash-alt text-sm"></i>
                    </button>
                </td>
            </tr>
        `;
    });
    tableBody.innerHTML = html;
}

// ----- CART TOTALS -----
function updateTotals() {
    const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const discount = parseFloat(document.getElementById('discountAmount').value) || 0;
    let tax = 0;
    const addTax = document.getElementById('addTaxCheckbox').checked;
    if (addTax) {
        const taxableAmount = subtotal - discount;
        tax = taxableAmount * 0.18;
    }
    const total = subtotal - discount + tax;
    document.getElementById('subtotalAmount').textContent = subtotal.toLocaleString();
    document.getElementById('taxAmount').textContent = tax.toLocaleString();
    document.getElementById('totalAmount').textContent = total.toLocaleString();
    calculateChange();
}

// ----- CHANGE/AMOUNT LOGIC -----
function calculateChange() {
    const total = parseFloat(document.getElementById('totalAmount').textContent.replace(/,/g, '')) || 0;
    const amountPaid = parseFloat(document.getElementById('amountPaid').value) || 0;
    const change = amountPaid - total;
    document.getElementById('changeAmount').textContent = Math.max(0, change).toLocaleString();
    const changeBox = document.getElementById('changeBox');
    const changeAmountSpan = document.getElementById('changeAmount');
    if (amountPaid < total && amountPaid > 0) {
        changeBox.classList.remove('bg-green-50');
        changeBox.classList.add('bg-red-50');
        changeAmountSpan.classList.remove('text-green-600');
        changeAmountSpan.classList.add('text-red-600');
    } else {
        changeBox.classList.add('bg-green-50');
        changeBox.classList.remove('bg-red-50');
        changeAmountSpan.classList.add('text-green-600');
        changeAmountSpan.classList.remove('text-red-600');
    }
}

function exactAmount() {
    const total = parseFloat(document.getElementById('totalAmount').textContent.replace(/,/g, '')) || 0;
    document.getElementById('amountPaid').value = total;
    calculateChange();
}

// ----- PAYMENT TYPE + FORM ACTION -----
function setPaymentType() {
    let value = document.querySelector('input[name="payment_type_radio"]:checked').value;
    document.getElementById('payment_type').value = value;
    let cashDiv = document.getElementById('cash-payment-div');
    let invoiceNotice = document.getElementById('invoiceNotice');
    let btnTextSpan = document.getElementById('checkoutBtnText');
    if (value === 'invoice') {
        cashDiv.classList.add('hidden');
        invoiceNotice.classList.remove('hidden');
        btnTextSpan.textContent = 'Make Invoice';
        document.getElementById('posForm').action = "{{ route('cashier.posInvoice') }}";
    } else {
        cashDiv.classList.remove('hidden');
        invoiceNotice.classList.add('hidden');
        btnTextSpan.textContent = 'Complete Sale';
        document.getElementById('posForm').action = "{{ route('pos.process') }}";
    }
    calculateChange();
}

let activeRowIndex = 0;

function updateRowHighlight() {
    const visibleRows = Array.from(document.querySelectorAll('.product-card')).filter(row => row.style.display !== 'none');
    
    // Clear all highlights
    document.querySelectorAll('.product-card').forEach(row => {
        row.classList.remove('bg-green-50', 'ring-2', 'ring-green-500');
    });

    if (visibleRows.length === 0) {
        activeRowIndex = -1;
        return;
    }

    // Keep index in range
    if (activeRowIndex < 0) {
        activeRowIndex = 0;
    } else if (activeRowIndex >= visibleRows.length) {
        activeRowIndex = visibleRows.length - 1;
    }

    const activeRow = visibleRows[activeRowIndex];
    if (activeRow) {
        activeRow.classList.add('bg-green-50', 'ring-2', 'ring-green-500');
        activeRow.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    }
}

function filterProducts(search) {
    const products = document.querySelectorAll('.product-card');
    products.forEach(product => {
        const name = product.dataset.name.toLowerCase();
        const matchesSearch = name.includes(search) || search === '';
        product.style.display = matchesSearch ? '' : 'none';
    });
    
    activeRowIndex = 0;
    updateRowHighlight();
}

// ----- INITIAL SETUP -----
document.addEventListener('DOMContentLoaded', function() {
    setPaymentType();
    toggleCustomerFields();
    updateTotals();

    // Event listeners using addEventListener instead of inline handlers
    document.querySelectorAll('input[name="payment_type_radio"]').forEach(radio => {
        radio.addEventListener('change', setPaymentType);
    });

    document.querySelectorAll('input[name="customer_option"]').forEach(radio => {
        radio.addEventListener('change', toggleCustomerFields);
    });

    document.getElementById('discountAmount').addEventListener('change', updateTotals);
    document.getElementById('addTaxCheckbox').addEventListener('change', updateTotals);
    document.getElementById('amountPaid').addEventListener('input', calculateChange);
    document.getElementById('exactAmountBtn').addEventListener('click', exactAmount);
    document.getElementById('clearCartBtn').addEventListener('click', clearCart);

    // Build item fields on form submit
    document.getElementById('posForm').addEventListener('submit', function(e) {
        // Remove any old item inputs
        this.querySelectorAll('input[name^="items["]').forEach(i => i.remove());
        cart.forEach(function(item, idx) {
            let pid = document.createElement('input');
            pid.type = 'hidden';
            pid.name = `items[${idx}][product_id]`;
            pid.value = item.id;
            let qty = document.createElement('input');
            qty.type = 'hidden';
            qty.name = `items[${idx}][quantity]`;
            qty.value = item.quantity;
            let price = document.createElement('input');
            price.type = 'hidden';
            price.name = `items[${idx}][price]`;
            price.value = item.price;
            e.target.appendChild(pid);
            e.target.appendChild(qty);
            e.target.appendChild(price);
        });
    });

    // Autocomplete Dropdown Search
    const searchInput = document.getElementById('productSearch');
    const dropdown = document.getElementById('searchResultsDropdown');
    let activeSearchIndex = 0;

    searchInput.addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        if (!query) {
            dropdown.classList.add('hidden');
            dropdown.innerHTML = '';
            return;
        }

        const matches = allProducts.filter(p => 
            p.name.toLowerCase().includes(query) || 
            p.sku.toLowerCase().includes(query)
        );

        if (matches.length === 0) {
            dropdown.innerHTML = '<div class="p-3 text-gray-500 text-sm text-center">No products found</div>';
            dropdown.classList.remove('hidden');
            return;
        }

        let html = '';
        matches.forEach((p, index) => {
            html += `
                <div class="search-result-item p-3 border-b border-gray-100 hover:bg-green-50 cursor-pointer flex justify-between items-center ${index === 0 ? 'bg-green-50 ring-1 ring-green-400 font-semibold' : ''}" 
                     data-id="${p.id}" 
                     data-index="${index}">
                    <div>
                        <span class="font-semibold text-gray-900">${p.name}</span>
                        <span class="text-xs text-gray-500 font-mono ml-2">SKU: ${p.sku || 'N/A'}</span>
                    </div>
                    <div class="text-right">
                        <span class="font-bold text-gray-950">UGX ${p.price.toLocaleString()}</span>
                        <span class="text-xs text-gray-600 block">Stock: ${p.stock} ${p.unit}</span>
                    </div>
                </div>
            `;
        });
        dropdown.innerHTML = html;
        dropdown.classList.remove('hidden');
        activeSearchIndex = 0;
    });

    dropdown.addEventListener('click', function(e) {
        const item = e.target.closest('.search-result-item');
        if (item) {
            const id = parseInt(item.dataset.id);
            const product = allProducts.find(p => p.id === id);
            if (product) {
                addToCart(product.id, product.name, product.price, product.unit, product.stock);
                searchInput.value = '';
                dropdown.classList.add('hidden');
                dropdown.innerHTML = '';
                searchInput.focus();
            }
        }
    });

    // Hide dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.classList.add('hidden');
        }
    });

    document.addEventListener('keydown', function(e) {
        // If dropdown is open, handle navigation there
        if (!dropdown.classList.contains('hidden')) {
            const items = dropdown.querySelectorAll('.search-result-item');
            if (items.length > 0) {
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    activeSearchIndex = (activeSearchIndex + 1) % items.length;
                    highlightSearchItem(items);
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    activeSearchIndex = (activeSearchIndex - 1 + items.length) % items.length;
                    highlightSearchItem(items);
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    const activeItem = items[activeSearchIndex];
                    if (activeItem) {
                        const id = parseInt(activeItem.dataset.id);
                        const product = allProducts.find(p => p.id === id);
                        if (product) {
                            addToCart(product.id, product.name, product.price, product.unit, product.stock);
                            searchInput.value = '';
                            dropdown.classList.add('hidden');
                            dropdown.innerHTML = '';
                            searchInput.focus();
                        }
                    }
                } else if (e.key === 'Escape') {
                    e.preventDefault();
                    searchInput.value = '';
                    dropdown.classList.add('hidden');
                    dropdown.innerHTML = '';
                    searchInput.focus();
                }
                return;
            }
        }

        // Standard keyboard shortcuts when dropdown is not active
        if (e.key === 'Escape') {
            searchInput.value = '';
            searchInput.focus();
            return;
        }

        if (e.ctrlKey || e.metaKey) {
            if (e.key === 'k') {
                e.preventDefault();
                searchInput.focus();
            }
            if (e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('posForm').requestSubmit();
            }
            return;
        }
    });

    function highlightSearchItem(items) {
        items.forEach((item, index) => {
            if (index === activeSearchIndex) {
                item.classList.add('bg-green-50', 'ring-1', 'ring-green-400', 'font-semibold');
                item.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            } else {
                item.classList.remove('bg-green-50', 'ring-1', 'ring-green-400', 'font-semibold');
            }
        });
    }
});
</script>
@endpush