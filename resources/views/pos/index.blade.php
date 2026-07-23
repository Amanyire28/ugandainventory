@extends('layouts.app')

@section('title', 'Point of Sale (POS)')

@section('page-title')
    <i class="fas fa-cash-register text-green-600 mr-2"></i>Point of Sale
@endsection

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- LEFT SIDE: Products -->
    <div class="lg:col-span-2 space-y-4">
        <!-- Search & Filter -->
        <div class="bg-white rounded-xl shadow-lg p-4">
            <div class="relative w-full">
                <input type="text" id="productSearch" placeholder="Search products (Name, SKU, Barcode)... [Press F11 for Fullscreen]" class="w-full px-4 py-3 pl-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                <i class="fas fa-search absolute left-3 top-4 text-gray-400"></i>
            </div>
        </div>
        <!-- Products Table -->
        <div class="bg-white rounded-xl shadow-lg p-4">
            <h3 class="text-lg font-bold text-gray-800 mb-4">
                <i class="fas fa-table text-green-600 mr-2"></i>Products List
            </h3>
            <div class="max-h-[calc(100vh-300px)] overflow-y-auto rounded-lg border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-4 py-2.5 text-left font-semibold text-gray-500 uppercase tracking-wider">Product Name</th>
                            <th scope="col" class="px-4 py-2.5 text-left font-semibold text-gray-500 uppercase tracking-wider">SKU</th>
                            <th scope="col" class="px-4 py-2.5 text-right font-semibold text-gray-500 uppercase tracking-wider">Price</th>
                            <th scope="col" class="px-4 py-2.5 text-right font-semibold text-gray-500 uppercase tracking-wider">Stock</th>
                            <th scope="col" class="px-4 py-2.5 text-center font-semibold text-gray-500 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody id="productsGrid" class="bg-white divide-y divide-gray-200 text-gray-700">
                        @forelse($products as $product)
                            <tr class="product-card hover:bg-gray-50 transition cursor-pointer"
                                 data-id="{{ $product->id }}"
                                 data-name="{{ $product->name }}"
                                 data-price="{{ $product->selling_price }}"
                                 data-stock="{{ $product->quantity }}"
                                 data-unit="{{ $product->unit }}"
                                 data-category="{{ $product->category_id ?? '' }}"
                                 onclick="addToCart({{ $product->id }}, '{{ addslashes($product->name) }}', {{ $product->selling_price }}, '{{ $product->unit }}', {{ $product->quantity }})">
                                <td class="px-4 py-3 font-semibold text-gray-900">
                                    {{ $product->name }}
                                </td>
                                <td class="px-4 py-3 text-gray-500 font-mono">
                                    {{ $product->sku ?? 'N/A' }}
                                </td>
                                <td class="px-4 py-3 text-right font-bold text-gray-950">
                                    UGX {{ number_format($product->selling_price, 0) }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <span class="px-2 py-0.5 text-xs font-bold rounded-full {{ $product->quantity < 10 ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
                                        {{ number_format($product->quantity, 0) }} {{ $product->unit }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <button type="button" 
                                            class="px-2.5 py-1 bg-green-50 text-green-700 hover:bg-green-100 border border-green-200 rounded-lg text-xs font-bold transition">
                                        <i class="fas fa-plus"></i> Add
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-gray-550">No products available</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- RIGHT SIDE -->
    <div class="lg:col-span-1 space-y-4">
        <!-- Payment Type Selection (FIRST) -->
        <div class="bg-white rounded-xl shadow-lg p-4">
            <h3 class="text-lg font-bold text-gray-800 mb-4">
                <i class="fas fa-coins text-green-600 mr-2"></i>Payment Option
            </h3>
            <div class="flex items-center space-x-6 mb-2">
                <label class="flex items-center">
                    <input type="radio" name="payment_type" value="cash" checked onchange="togglePaymentType();" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500">
                    <span class="ml-2 font-semibold text-gray-700">Cash Sale</span>
                </label>
                <label class="flex items-center">
                    <input type="radio" name="payment_type" value="invoice" onchange="togglePaymentType();" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500">
                    <span class="ml-2 font-semibold text-indigo-700">Invoice (Credit)</span>
                </label>
            </div>
            <p id="invoiceNotice" class="text-sm text-indigo-600 hidden">
                Credit: Items will be added to the customer’s open invoice.
            </p>
        </div>
        <!-- Customer Selection -->
        <div class="bg-white rounded-xl shadow-lg p-4">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">
                <i class="fas fa-user text-green-600 mr-1"></i>Customer (Optional)
            </h3>
            <div class="space-y-3">
                <label class="flex items-center cursor-pointer p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
                    <input type="radio" name="customer_option" value="walk_in" checked onchange="toggleCustomerFields()" class="h-4 w-4 text-green-600 focus:ring-green-500">
                    <span class="ml-3 text-sm font-medium text-gray-700">Walk-in Customer</span>
                </label>
                <label class="flex items-center cursor-pointer p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
                    <input type="radio" name="customer_option" value="existing" onchange="toggleCustomerFields()" class="h-4 w-4 text-green-600 focus:ring-green-500">
                    <span class="ml-3 text-sm font-medium text-gray-700">Existing Customer</span>
                </label>
                <label class="flex items-center cursor-pointer p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
                    <input type="radio" name="customer_option" value="new" onchange="toggleCustomerFields()" class="h-4 w-4 text-green-600 focus:ring-green-500">
                    <span class="ml-3 text-sm font-medium text-gray-700">New Customer</span>
                </label>
            </div>
            <div id="existingCustomerDiv" class="hidden mt-3">
                <select id="existingCustomerId" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                    <option value="">Select Customer</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->name }} - {{ $customer->phone }}</option>
                    @endforeach
                </select>
            </div>
            <div id="newCustomerDiv" class="hidden mt-3 space-y-2 p-3 bg-green-50 rounded-lg">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Name <span class="text-red-500">*</span></label>
                    <input type="text" id="newCustomerName" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 text-sm" placeholder="Customer name">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Phone <span class="text-red-500">*</span></label>
                    <input type="text" id="newCustomerPhone" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 text-sm" placeholder="0700123456">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" id="newCustomerEmail" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 text-sm" placeholder="customer@email.com">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Address</label>
                    <input type="text" id="newCustomerAddress" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 text-sm" placeholder="Kampala, Uganda">
                </div>
            </div>
        </div>
        <!-- Cart -->
        <div class="bg-white rounded-xl shadow-lg p-4">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex justify-between items-center">
                <span><i class="fas fa-shopping-cart text-green-600 mr-2"></i>Cart</span>
                <button onclick="clearCart()" class="text-xs text-red-600 hover:text-red-800">
                    <i class="fas fa-trash mr-1"></i>Clear
                </button>
            </h3>
            <div id="cartItems" class="space-y-2 max-h-64 overflow-y-auto mb-4">
                <div class="text-center text-gray-400 py-8">
                    <i class="fas fa-shopping-cart text-4xl mb-2"></i>
                    <p>Cart is empty</p>
                </div>
            </div>
            <div class="border-t pt-4 space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Subtotal:</span>
                    <span class="font-semibold">UGX <span id="subtotalAmount">0</span></span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Discount:</span>
                    <input type="number" id="discountAmount" value="0" min="0" step="100" onchange="updateTotals()" class="w-24 px-2 py-1 border border-gray-300 rounded text-right">
                </div>
                <div class="flex justify-between items-center text-sm py-2 border-t">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" id="addTaxCheckbox" onchange="updateTotals()" class="mr-2 h-4 w-4 text-green-600 focus:ring-green-500 rounded">
                        <span class="text-gray-600">Add Tax (18%)</span>
                    </label>
                    <span class="font-semibold">UGX <span id="taxAmount">0</span></span>
                </div>
                <div class="flex justify-between text-lg font-bold text-green-600 pt-2 border-t">
                    <span>TOTAL:</span>
                    <span>UGX <span id="totalAmount">0</span></span>
                </div>
            </div>
        </div>
        <!-- Payment (only for cash) -->
        <div id="cash-payment-div" class="bg-white rounded-xl shadow-lg p-4">
            <h3 class="text-lg font-bold text-gray-800 mb-4">
                <i class="fas fa-money-bill-wave text-green-600 mr-2"></i>Payment (Cash)
            </h3>
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Amount Paid</label>
                <input type="number" id="amountPaid" value="0" min="0" step="100" onchange="calculateChange()" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 text-lg font-bold text-right">
            </div>
            <div class="mb-4">
                <button type="button" onclick="exactAmount()" class="w-full px-4 py-2 bg-blue-100 hover:bg-blue-200 rounded-lg text-sm font-semibold text-blue-700">
                    <i class="fas fa-equals mr-1"></i> Exact Amount
                </button>
            </div>
            <div class="mb-4 p-3 bg-green-50 rounded-lg" id="changeBox">
                <div class="flex justify-between items-center">
                    <span class="text-sm font-semibold text-gray-700">Change:</span>
                    <span class="text-xl font-bold text-green-600">UGX <span id="changeAmount">0</span></span>
                </div>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Notes (Optional)</label>
                <textarea id="saleNotes" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500" placeholder="Add any notes..."></textarea>
            </div>
        </div>

        <!-- Always visible action button -->
        <button onclick="processSale()"
                id="checkoutBtn"
                class="w-full py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 font-bold text-lg disabled:bg-gray-300 disabled:cursor-not-allowed">
            <i class="fas fa-check-circle mr-2"></i>
            <span id="checkoutBtnText">Complete Sale</span>
        </button>
    </div>
</div>

<!-- Receipt Modal -->
<div id="receiptModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full" id="receiptContent"></div>
</div>


<script>
let cart = [];

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

function togglePaymentType() {
    let paymentType = document.querySelector('input[name="payment_type"]:checked').value;
    let cashDiv = document.getElementById('cash-payment-div');
    let invoiceNotice = document.getElementById('invoiceNotice');
    let btnTextSpan = document.getElementById('checkoutBtnText');
    if (paymentType === 'invoice') {
        cashDiv.classList.add('hidden');
        invoiceNotice.classList.remove('hidden');
        if(btnTextSpan) btnTextSpan.textContent = 'Make Invoice';
    } else {
        cashDiv.classList.remove('hidden');
        invoiceNotice.classList.add('hidden');
        if(btnTextSpan) btnTextSpan.textContent = 'Complete Sale';
    }
    calculateChange();
}

function addToCart(id, name, price, unit, maxStock) {
    const existingItem = cart.find(item => item.id === id);
    if (existingItem) {
        if (existingItem.quantity >= maxStock) {
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
    const cartItemsDiv = document.getElementById('cartItems');
    if (cart.length === 0) {
        cartItemsDiv.innerHTML = `
            <div class="text-center text-gray-400 py-8">
                <i class="fas fa-shopping-cart text-4xl mb-2"></i>
                <p>Cart is empty</p>
            </div>
        `;
        document.getElementById('checkoutBtn').disabled = true;
        return;
    }
    document.getElementById('checkoutBtn').disabled = false;
    let html = '';
    cart.forEach(item => {
        html += `
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                <div class="flex-1">
                    <p class="font-semibold text-sm text-gray-900">${item.name}</p>
                    <p class="text-xs text-gray-500">UGX ${item.price.toLocaleString()} × ${item.quantity} ${item.unit}</p>
                </div>
                <div class="flex items-center space-x-2">
                    <button onclick="updateQuantity(${item.id}, ${item.quantity - 1})"
                            class="w-6 h-6 bg-gray-200 hover:bg-gray-300 rounded flex items-center justify-center">
                        <i class="fas fa-minus text-xs"></i>
                    </button>
                    <input type="number"
                           value="${item.quantity}"
                           min="1"
                           max="${item.maxStock}"
                           onchange="updateQuantity(${item.id}, this.value)"
                           class="w-12 px-2 py-1 border border-gray-300 rounded text-center text-sm">
                    <button onclick="updateQuantity(${item.id}, ${item.quantity + 1})"
                            class="w-6 h-6 bg-gray-200 hover:bg-gray-300 rounded flex items-center justify-center">
                        <i class="fas fa-plus text-xs"></i>
                    </button>
                    <button onclick="removeFromCart(${item.id})"
                            class="w-6 h-6 bg-red-100 hover:bg-red-200 text-red-600 rounded flex items-center justify-center ml-2">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>
                <div class="ml-3 text-right">
                    <p class="font-bold text-green-600">UGX ${(item.price * item.quantity).toLocaleString()}</p>
                </div>
            </div>
        `;
    });
    cartItemsDiv.innerHTML = html;
}

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

function calculateChange() {
    const total = parseFloat(document.getElementById('totalAmount').textContent.replace(/,/g, ''));
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
    const total = parseFloat(document.getElementById('totalAmount').textContent.replace(/,/g, ''));
    document.getElementById('amountPaid').value = total;
    calculateChange();
}

function showSuccessToast(message) {
    const toastId = 'toast-' + Date.now();
    const toast = document.createElement('div');
    toast.id = toastId;
    toast.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-4 rounded-lg shadow-lg flex items-center space-x-3 animate-slideIn z-50';
    toast.innerHTML = `
        <i class="fas fa-check-circle text-xl"></i>
        <div>
            <p class="font-semibold">Success!</p>
            <p class="text-sm text-green-100">${message}</p>
        </div>
    `;
    document.body.appendChild(toast);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease-in-out';
        setTimeout(() => toast.remove(), 300);
    }, 5000);
}

function showErrorToast(message) {
    const toastId = 'toast-' + Date.now();
    const toast = document.createElement('div');
    toast.id = toastId;
    toast.className = 'fixed top-4 right-4 bg-red-500 text-white px-6 py-4 rounded-lg shadow-lg flex items-center space-x-3 animate-slideIn z-50';
    toast.innerHTML = `
        <i class="fas fa-exclamation-circle text-xl"></i>
        <div>
            <p class="font-semibold">Error!</p>
            <p class="text-sm text-red-100">${message}</p>
        </div>
    `;
    document.body.appendChild(toast);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease-in-out';
        setTimeout(() => toast.remove(), 300);
    }, 5000);
}

async function processSale() {
    if (cart.length === 0) {
        alert('Cart is empty!');
        return;
    }
    const paymentType = document.querySelector('input[name="payment_type"]:checked').value;
    const customerOption = document.querySelector('input[name="customer_option"]:checked').value;
    let saleData = {
        customer_option: customerOption,
        items: cart.map(item => ({
            product_id: item.id,
            quantity: item.quantity,
            price: item.price
        })),
        discount: parseFloat(document.getElementById('discountAmount').value) || 0,
        add_tax: document.getElementById('addTaxCheckbox').checked,
        notes: document.getElementById('saleNotes').value || null,
        _token: '{{ csrf_token() }}',
        payment_type: paymentType
    };
    if (paymentType === 'cash') {
        saleData.amount_paid = parseFloat(document.getElementById('amountPaid').value) || 0;
    }
    if (customerOption === 'existing') {
        const customerId = document.getElementById('existingCustomerId').value;
        if (!customerId) {
            alert('Please select a customer');
            return;
        }
        saleData.customer_id = customerId;
    } else if (customerOption === 'new') {
        const name = document.getElementById('newCustomerName').value.trim();
        const phone = document.getElementById('newCustomerPhone').value.trim();
        if (!name || !phone) {
            alert('Please enter customer name and phone number');
            return;
        }
        saleData.new_customer_name = name;
        saleData.new_customer_phone = phone;
        saleData.new_customer_email = document.getElementById('newCustomerEmail').value.trim();
        saleData.new_customer_address = document.getElementById('newCustomerAddress').value.trim();
    }
    if (paymentType === 'cash') {
        const total = parseFloat(document.getElementById('totalAmount').textContent.replace(/,/g, ''));
        const amountPaid = parseFloat(document.getElementById('amountPaid').value) || 0;
        if (amountPaid < total) {
            alert('Amount paid is less than total amount!');
            return;
        }
    }
    const checkoutBtn = document.getElementById('checkoutBtn');
    checkoutBtn.disabled = true;
    checkoutBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';
    try {
        let endpoint =
            paymentType === "invoice"
            ? "{{ route('invoices.pos') }}"
            : "{{ route('pos.process') }}";
        
        console.log('Posting to:', endpoint);
        console.log('Payload:', saleData);
        
        const response = await fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(saleData)
        });
        
        console.log('Response status:', response.status);
        const text = await response.text();
        console.log('Response text:', text);
        
        let result;
        try {
            result = JSON.parse(text);
        } catch (e) {
            console.error('Failed to parse JSON:', e);
            alert('Error: Server returned invalid response\n' + text);
            return;
        }
        
        console.log('Parsed result:', result);
        
        if (result && result.success) {
            console.log('Sale successful, showing receipt');
            console.log('Data:', result);
            showReceipt(result);
            cart = [];
            renderCart();
            updateTotals();
            document.querySelector('input[name="customer_option"][value="walk_in"]').checked = true;
            toggleCustomerFields();
            document.getElementById('discountAmount').value = 0;
            document.getElementById('addTaxCheckbox').checked = false;
            document.getElementById('amountPaid').value = 0;
            document.getElementById('saleNotes').value = '';
            document.getElementById('existingCustomerId').value = '';
            document.getElementById('newCustomerName').value = '';
            document.getElementById('newCustomerPhone').value = '';
            document.getElementById('newCustomerEmail').value = '';
            document.getElementById('newCustomerAddress').value = '';
            calculateChange();
        } else if (result && result.message) {
            console.error('Sale failed:', result.message);
            showErrorToast(result.message);
        } else {
            console.error('Unknown error:', result);
            showErrorToast('An unknown error occurred. Please try again.');
        }
    } catch (error) {
        console.error('Fetch error:', error);
        showErrorToast('Failed to process sale. Please check your connection and try again.');
    } finally {
        checkoutBtn.disabled = false;
        let paymentType2 = document.querySelector('input[name="payment_type"]:checked').value;
        let btnText2 = paymentType2 === 'invoice' ? 'Make Invoice' : 'Complete Sale';
        checkoutBtn.innerHTML = '<i class="fas fa-check-circle mr-2"></i> <span id="checkoutBtnText">' + btnText2 + '</span>';
    }
}
function showReceipt(data) {
    // Receipt modal itself shows success - no need for extra toast
    const modal = document.getElementById('receiptModal');
    const content = document.getElementById('receiptContent');
    
    if (!modal || !content) {
        console.error('Modal or content element not found!');
        alert('Receipt display error. Sale #' + (data.sale_number || data.invoice_number) + ' completed successfully!');
        return;
    }
    
    let html = `
        <div class="p-6">
            <div class="text-center mb-6">
                <i class="fas fa-check-circle text-6xl text-green-600 mb-4"></i>
                <h2 class="text-2xl font-bold text-gray-900">${data.sale_number ? 'Sale Completed!' : 'Invoice Created!'}</h2>
                <p class="text-gray-600">${data.sale_number ? 'Sale #' + data.sale_number : 'Invoice #' + data.invoice_number}</p>
            </div>
            <div class="space-y-3 mb-6">
                <div class="flex justify-between text-lg">
                    <span class="text-gray-700">Total Amount:</span>
                    <span class="font-bold">UGX ${(data.total || 0).toLocaleString()}</span>
                </div>
                ${data.amount_paid !== undefined ? `
                <div class="flex justify-between">
                    <span class="text-gray-700">Amount Paid:</span>
                    <span class="font-semibold">UGX ${(data.amount_paid || 0).toLocaleString()}</span>
                </div>
                <div class="flex justify-between text-xl border-t pt-3">
                    <span class="text-gray-700 font-bold">Change:</span>
                    <span class="font-bold text-green-600">UGX ${(data.change || 0).toLocaleString()}</span>
                </div>
                ` : `
                <div class="flex justify-between text-xl border-t pt-3">
                    <span class="text-gray-700 font-bold">Customer:</span>
                    <span class="font-bold text-indigo-600">${data.customer || ''}</span>
                </div>
                `}
            </div>
            <div class="flex space-x-2">
                ${data.sale_id ?
                    `<a href="/sales/${data.sale_id}" target="_blank"
                   class="flex-1 px-4 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-center">
                    <i class="fas fa-print mr-2"></i>Print Receipt
                    </a>` : ''
                }
                ${data.invoice_id ?
                    `<a href="/invoices/${data.invoice_id}" target="_blank"
                   class="flex-1 px-4 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-center">
                    <i class="fas fa-print mr-2"></i>Print Invoice
                    </a>` : ''
                }
                <button onclick="closeReceipt()" 
                        class="flex-1 px-4 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                    <i class="fas fa-times mr-2"></i>Close
                </button>
            </div>
        </div>
    `;
    
    try {
        content.innerHTML = html;
        modal.classList.remove('hidden');
        console.log('Receipt modal displayed successfully');
    } catch (error) {
        console.error('Error displaying receipt modal:', error);
        alert('Sale #' + (data.sale_number || data.invoice_number) + ' completed successfully!');
    }
}
function closeReceipt() {
    document.getElementById('receiptModal').classList.add('hidden');
}
document.getElementById('productSearch').addEventListener('input', function() {
    const search = this.value.toLowerCase();
    filterProducts(search);
});
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

// Initialize highlight on load
updateRowHighlight();

document.addEventListener('keydown', function(e) {
    // Escape key
    if (e.key === 'Escape') {
        document.getElementById('productSearch').value = '';
        filterProducts('', '');
        document.getElementById('productSearch').focus();
        return;
    }

    // Control/Meta shortcuts
    if (e.ctrlKey || e.metaKey) {
        if (e.key === 'k') {
            e.preventDefault();
            document.getElementById('productSearch').focus();
        }
        if (e.key === 'Enter') {
            e.preventDefault();
            if (!document.getElementById('checkoutBtn').disabled) {
                processSale();
            }
        }
        return;
    }

    // Only allow navigation when the search field is focused or body has focus
    const isSearchFocused = document.activeElement === document.getElementById('productSearch');
    
    if (isSearchFocused || document.activeElement === document.body) {
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            const visibleRows = Array.from(document.querySelectorAll('.product-card')).filter(row => row.style.display !== 'none');
            if (visibleRows.length > 0) {
                activeRowIndex = (activeRowIndex + 1) % visibleRows.length;
                updateRowHighlight();
            }
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            const visibleRows = Array.from(document.querySelectorAll('.product-card')).filter(row => row.style.display !== 'none');
            if (visibleRows.length > 0) {
                activeRowIndex = (activeRowIndex - 1 + visibleRows.length) % visibleRows.length;
                updateRowHighlight();
            }
        } else if (e.key === 'Enter') {
            const visibleRows = Array.from(document.querySelectorAll('.product-card')).filter(row => row.style.display !== 'none');
            if (activeRowIndex >= 0 && activeRowIndex < visibleRows.length) {
                e.preventDefault();
                const activeRow = visibleRows[activeRowIndex];
                
                // Trigger click to add to cart
                activeRow.click();
                
                // Visual feedback flash
                activeRow.classList.add('bg-green-200');
                setTimeout(() => activeRow.classList.remove('bg-green-200'), 150);
            }
        }
    }
});

function checkFullscreenState() {
    // Detect HTML5 Fullscreen OR native F11 Fullscreen
    const isHTML5 = !!document.fullscreenElement;
    const isNative = window.innerHeight === screen.height || (window.outerHeight === screen.height && window.innerHeight >= screen.height - 10);
    const isFullscreen = isHTML5 || isNative;

    const sidebar = document.getElementById('sidebar');
    const header = document.querySelector('header');
    const main = document.querySelector('main');

    if (isFullscreen) {
        if (sidebar) sidebar.classList.add('hidden');
        if (header) header.classList.add('hidden');
        if (main) {
            main.classList.remove('p-4', 'md:p-6');
            main.classList.add('p-2');
        }
    } else {
        if (sidebar) sidebar.classList.remove('hidden');
        if (header) header.classList.remove('hidden');
        if (main) {
            main.classList.add('p-4', 'md:p-6');
            main.classList.remove('p-2');
        }
    }
}

window.addEventListener('resize', checkFullscreenState);
document.addEventListener('fullscreenchange', checkFullscreenState);
// Run once on load to initialize state
checkFullscreenState();
</script>
@endsection