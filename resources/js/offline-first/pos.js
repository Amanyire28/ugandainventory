import { getStoreItem, getStoreData, putStoreData } from './db';
import { addToSyncQueue } from './sync';

// Generate UUID for offline sales
function generateUUID() {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
        var r = Math.random() * 16 | 0, v = c == 'x' ? r : (r & 0x3 | 0x8);
        return v.toString(16);
    });
}

// Hook into page load to sync HTML products list with IndexedDB products cache
window.addEventListener('DOMContentLoaded', async () => {
    // Let the original scripts run first, then update
    setTimeout(async () => {
        try {
            const dbProducts = await getStoreData('products');
            if (dbProducts && dbProducts.length > 0) {
                window.allProducts = dbProducts.map(p => ({
                    id: p.id,
                    name: p.name,
                    sku: p.sku,
                    barcode: p.barcode,
                    price: parseFloat(p.selling_price),
                    stock: parseFloat(p.quantity),
                    unit: p.unit,
                    requiresVat: !!(p.requires_vat ?? false)
                }));
                console.log('POS allProducts list updated from IndexedDB cache');
                
                // If there's an existing search input listener, refresh search match
                const searchInput = document.getElementById('productSearch');
                if (searchInput && searchInput.value) {
                    searchInput.dispatchEvent(new Event('input'));
                }
            }
            
            // Populate customers dropdown from IndexedDB if offline
            if (!window.navigator.onLine) {
                const customers = await getStoreData('customers');
                const select = document.getElementById('existingCustomerId');
                if (select && customers && customers.length > 0) {
                    // Keep first default option
                    select.innerHTML = '<option value="">Select Customer</option>';
                    customers.forEach(c => {
                        const opt = document.createElement('option');
                        opt.value = c.id;
                        opt.textContent = c.name;
                        select.appendChild(opt);
                    });
                }
            }
        } catch (e) {
            console.error('Failed to sync UI with IndexedDB:', e);
        }
    }, 500);
});

// Override window.processSale for offline-first handling
const originalProcessSale = window.processSale;

window.processSale = async function() {
    if (window.navigator.onLine) {
        // Online: use standard controller action
        return originalProcessSale();
    }

    // Offline Mode: handle locally
    if (window.isProcessingSale) return;

    if (cart.length === 0) {
        alert('Cart is empty!');
        return;
    }

    window.isProcessingSale = true;
    const checkoutBtn = document.getElementById('checkoutBtn');
    if (checkoutBtn) {
        checkoutBtn.disabled = true;
        checkoutBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing Offline...';
    }

    try {
        const paymentType = document.querySelector('input[name="payment_type"]:checked').value;
        const customerOption = document.querySelector('input[name="customer_option"]:checked').value;
        const discount = parseFloat(document.getElementById('discountAmount').value) || 0;
        const notes = document.getElementById('saleNotes').value || null;
        
        let customerId = null;
        let customerName = 'Walk-in Customer';
        let customerOfflineUuid = null;

        if (customerOption === 'existing') {
            customerId = document.getElementById('existingCustomerId').value;
            if (!customerId) {
                alert('Please select a customer');
                return;
            }
            const selectEl = document.getElementById('existingCustomerId');
            customerName = selectEl.options[selectEl.selectedIndex].text;
        } else if (customerOption === 'new') {
            const name = document.getElementById('newCustomerName').value.trim();
            const phone = document.getElementById('newCustomerPhone').value.trim();
            if (!name || !phone) {
                alert('Please enter customer name and phone number');
                return;
            }
            
            // Queue offline customer creation
            customerOfflineUuid = generateUUID();
            const customerPayload = {
                offline_uuid: customerOfflineUuid,
                name: name,
                phone: phone,
                email: document.getElementById('newCustomerEmail').value.trim() || null,
                address: document.getElementById('newCustomerAddress').value.trim() || null,
            };
            
            await addToSyncQueue('customer', customerPayload, customerOfflineUuid);
            customerName = name;
        }

        // Validate stock locally against IndexedDB counts
        for (const item of cart) {
            const dbProduct = await getStoreItem('products', item.id);
            const currentStock = dbProduct ? parseFloat(dbProduct.quantity) : item.stock;
            if (currentStock < item.quantity) {
                alert(`Insufficient stock for ${item.name}! Available: ${currentStock}, Sold: ${item.quantity}`);
                return;
            }
        }

        // Calculate Totals locally
        let subtotal = 0;
        let taxAmount = 0;
        cart.forEach(item => {
            const itemTotal = item.quantity * item.price;
            subtotal += itemTotal;
            if (item.requiresVat) {
                taxAmount += itemTotal * 0.18;
            }
        });

        const total = subtotal - discount + taxAmount;
        let amountPaid = total;

        if (paymentType === 'cash') {
            amountPaid = parseFloat(document.getElementById('amountPaid').value) || 0;
            if (amountPaid < total) {
                alert('Amount paid is less than total amount!');
                return;
            }
        }

        const offlineUuid = generateUUID();
        const deviceUuid = localStorage.getItem('pwa_device_uuid') || 'unknown';
        const saleNumber = 'SAL-OFF-' + deviceUuid.substring(0, 5).toUpperCase() + '-' + Date.now();

        // Build offline sale structure
        const salePayload = {
            offline_uuid: offlineUuid,
            sale_number: saleNumber,
            sale_date: new Date().toISOString(),
            subtotal: subtotal,
            tax_amount: taxAmount,
            discount_amount: discount,
            total: total,
            payment_status: paymentType === 'cash' ? 'paid' : 'unpaid',
            payment_method: paymentType === 'cash' ? 'cash' : 'credit',
            notes: notes,
            customer_id: customerId,
            customer_offline_uuid: customerOfflineUuid,
            items: cart.map(item => ({
                product_id: item.id,
                quantity: item.quantity,
                price: item.price
            }))
        };

        // Save sale to sync queue
        await addToSyncQueue('sale', salePayload, offlineUuid);

        // Deduct quantities locally in IndexedDB to preserve stock accuracy
        for (const item of cart) {
            const dbProduct = await getStoreItem('products', item.id);
            if (dbProduct) {
                dbProduct.quantity = parseFloat(dbProduct.quantity) - item.quantity;
                await putStoreData('products', dbProduct);
            }
            
            // Also deduct in the loaded HTML UI memory list
            let uiProduct = allProducts.find(p => p.id === item.id);
            if (uiProduct) {
                uiProduct.stock -= item.quantity;
            }
        }

        // Render receipt offline
        const receiptData = {
            success: true,
            sale_number: saleNumber,
            total: total,
            amount_paid: amountPaid,
            change: amountPaid - total,
            customer: customerName,
            is_offline: true
        };

        showReceipt(receiptData);

        // Reset cart
        cart = [];
        renderCart();
        updateTotals();

        // Reset customer options to default walk-in
        document.querySelector('input[name="customer_option"][value="walk_in"]').checked = true;
        toggleCustomerFields();
        document.getElementById('discountAmount').value = 0;
        const addTaxCheckbox = document.getElementById('addTaxCheckbox');
        if (addTaxCheckbox) addTaxCheckbox.checked = false;
        document.getElementById('amountPaid').value = 0;
        document.getElementById('saleNotes').value = '';
        document.getElementById('existingCustomerId').value = '';
        document.getElementById('newCustomerName').value = '';
        document.getElementById('newCustomerPhone').value = '';
        document.getElementById('newCustomerEmail').value = '';
        document.getElementById('newCustomerAddress').value = '';
        calculateChange();

        // Show standard success message
        if (typeof window.showSuccessToast === 'function') {
            window.showSuccessToast('Sale completed successfully!');
        } else if (typeof showSuccessToast === 'function') {
            showSuccessToast('Sale completed successfully!');
        }

    } catch (e) {
        console.error('Offline sale processing failed:', e);
        alert('Failed to process sale offline: ' + e.message);
    } finally {
        window.isProcessingSale = false;
        if (checkoutBtn) {
            checkoutBtn.disabled = false;
            let paymentType2 = document.querySelector('input[name="payment_type"]:checked').value;
            let btnText2 = paymentType2 === 'invoice' ? 'Make Invoice' : 'Complete Sale';
            checkoutBtn.innerHTML = '<i class="fas fa-check-circle mr-2"></i> <span id="checkoutBtnText">' + btnText2 + '</span>';
        }
    }
}
