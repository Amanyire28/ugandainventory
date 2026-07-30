@extends('layouts.app')

@section('title', 'Edit Product')

@section('page-title')
    <i class="fas fa-edit text-indigo-600 mr-2"></i>Edit Product
@endsection

@section('content')

<script>
    function toggleCategoryInput(option) {
        const existingDiv = document.getElementById('existingCategoryDiv');
        const newDiv = document.getElementById('newCategoryDiv');
        const categorySelect = document.getElementById('category_id');
        const newCategoryInput = document.getElementById('new_category_name');

        if (!existingDiv || !newDiv) return;

        if (option === 'existing') {
            existingDiv.style.display = 'block';
            newDiv.style.display = 'none';
            if (categorySelect) categorySelect.disabled = false;
            if (newCategoryInput) {
                newCategoryInput.disabled = true;
                newCategoryInput.required = false;
            }
        } else {
            existingDiv.style.display = 'none';
            newDiv.style.display = 'block';
            if (categorySelect) categorySelect.disabled = true;
            if (newCategoryInput) {
                newCategoryInput.disabled = false;
                newCategoryInput.required = true;
                setTimeout(() => newCategoryInput.focus(), 100);
            }
        }
    }

    function toggleExpiryFields(checkbox) {
        const expiryFields = document.getElementById('expiryFields');
        if (!expiryFields) return;
        expiryFields.style.display = checkbox.checked ? 'block' : 'none';
    }

    function updateVatCalculation() {
        const sellingInput = document.getElementById('sellingPriceInput');
        const vatToggle = document.getElementById('requiresVatToggle');
        const vatBox = document.getElementById('vatCalculationBox');
        if (!sellingInput || !vatToggle || !vatBox) return;

        const priceInput = parseFloat(sellingInput.value) || 0;
        const requiresVat = vatToggle.checked;
        
        if (!requiresVat) {
            vatBox.style.opacity = '0.5';
            document.getElementById('previewExclVat').innerText = 'UGX 0';
            document.getElementById('previewVatAmount').innerText = 'UGX 0';
            document.getElementById('previewInclVat').innerText = 'UGX 0';
            return;
        }

        vatBox.style.opacity = '1';
        const vatRate = 0.18;
        const vatAmount = priceInput * vatRate;
        const total = priceInput + vatAmount;

        document.getElementById('previewExclVat').innerText = 'UGX ' + priceInput.toLocaleString();
        document.getElementById('previewVatAmount').innerText = 'UGX ' + vatAmount.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('previewInclVat').innerText = 'UGX ' + total.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    document.addEventListener('DOMContentLoaded', function() {
        updateVatCalculation();
    });
</script>

<div class="max-w-7xl mx-auto w-full">
    <div class="bg-white rounded-xl shadow-lg p-6">
        
        {{-- ── Toast Notification ─────────────────────────────────────────── --}}
        <div id="editToast" style="display:none;position:fixed;top:80px;left:50%;transform:translateX(-50%);z-index:99999;min-width:340px;max-width:520px;">
            <div id="editToastInner" style="display:flex;align-items:flex-start;gap:16px;background:#fff;border-radius:14px;padding:20px 24px;box-shadow:0 12px 40px rgba(0,0,0,0.22);border-left:6px solid #16a34a;">
                <div id="editToastIcon" style="font-size:28px;line-height:1;margin-top:2px;">✅</div>
                <div style="flex:1;">
                    <p id="editToastTitle" style="font-size:15px;font-weight:800;color:#14532d;margin:0 0 6px;"></p>
                    <ul id="editToastList" style="font-size:13px;color:#166534;font-weight:600;margin:0;padding-left:16px;line-height:1.7;"></ul>
                </div>
                <button onclick="document.getElementById('editToast').classList.remove('et-active')" style="background:none;border:none;cursor:pointer;font-size:20px;color:#94a3b8;line-height:1;padding:0;margin-top:-2px;">×</button>
            </div>
        </div>
        <style>
        #editToast { display:none; }
        #editToast.et-active { display:block !important; animation:etSlideIn 0.4s cubic-bezier(.22,1,.36,1); }
        @keyframes etSlideIn { from{opacity:0;transform:translateX(-50%) translateY(-20px);} to{opacity:1;transform:translateX(-50%) translateY(0);} }
        .field-error { border-color: #ef4444 !important; background-color: #fff5f5 !important; }
        </style>

        <form method="POST" id="productEditForm" action="{{ route('products.update', $product) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                
                <!-- Product Name -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-tag text-indigo-600 mr-1"></i>
                        Product Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" value="{{ old('name', $product->name) }}" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                </div>

                <!-- SKU -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-barcode text-indigo-600 mr-1"></i>
                        SKU <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="sku" value="{{ old('sku', $product->sku) }}" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                </div>

                <!-- Barcode -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-qrcode text-indigo-600 mr-1"></i>
                        Barcode / ISBN
                    </label>
                    <input type="text" name="barcode" value="{{ old('barcode', $product->barcode) }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 font-mono"
                           placeholder="Scan or type barcode">
                </div>

                <!-- Category Section -->
                <div class="md:col-span-2 space-y-3">
                    <label class="block text-sm font-medium text-gray-700">
                        <i class="fas fa-folder text-indigo-600 mr-1"></i>
                        Category
                    </label>

                    <div class="flex space-x-6 mb-3">
                        <label class="flex items-center cursor-pointer">
                            <input type="radio" name="category_option" value="existing" checked
                                   onchange="toggleCategoryInput(this.value)"
                                   class="h-4 w-4 text-indigo-600 focus:ring-indigo-500">
                            <span class="ml-2 text-sm text-gray-700">Select Existing</span>
                        </label>
                        <label class="flex items-center cursor-pointer">
                            <input type="radio" name="category_option" value="new"
                                   onchange="toggleCategoryInput(this.value)"
                                   class="h-4 w-4 text-indigo-600 focus:ring-indigo-500">
                            <span class="ml-2 text-sm text-gray-700">Add New Category</span>
                        </label>
                    </div>

                    <div id="existingCategoryDiv">
                        <select name="category_id" id="category_id" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                            <option value="">-- Select Category (Optional) --</option>
                            @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div id="newCategoryDiv" class="hidden space-y-3" style="display: none;">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-plus text-green-600 mr-1"></i>
                                New Category Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="new_category_name" id="new_category_name" value="{{ old('new_category_name') }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                   placeholder="e.g., Electronics">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-align-left text-green-600 mr-1"></i>
                                Category Description (Optional)
                            </label>
                            <textarea name="new_category_description" id="new_category_description" rows="2"
                                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                      placeholder="Brief description">{{ old('new_category_description') }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Unit -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-balance-scale text-indigo-600 mr-1"></i>
                        Unit <span class="text-red-500">*</span>
                    </label>
                    <select name="unit" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                        <option value="pcs" {{ old('unit', $product->unit) == 'pcs' ? 'selected' : '' }}>Pieces (pcs)</option>
                        <option value="kg" {{ old('unit', $product->unit) == 'kg' ? 'selected' : '' }}>Kilograms (kg)</option>
                        <option value="grams" {{ old('unit', $product->unit) == 'grams' ? 'selected' : '' }}>Grams (g)</option>
                        <option value="liters" {{ old('unit', $product->unit) == 'liters' ? 'selected' : '' }}>Liters (L)</option>
                        <option value="ml" {{ old('unit', $product->unit) == 'ml' ? 'selected' : '' }}>Milliliters (ml)</option>
                        <option value="boxes" {{ old('unit', $product->unit) == 'boxes' ? 'selected' : '' }}>Boxes</option>
                        <option value="cartons" {{ old('unit', $product->unit) == 'cartons' ? 'selected' : '' }}>Cartons</option>
                        <option value="dozen" {{ old('unit', $product->unit) == 'dozen' ? 'selected' : '' }}>Dozen</option>
                        <option value="pairs" {{ old('unit', $product->unit) == 'pairs' ? 'selected' : '' }}>Pairs</option>
                        <option value="meters" {{ old('unit', $product->unit) == 'meters' ? 'selected' : '' }}>Meters (m)</option>
                    </select>
                </div>

                <!-- ✅ QUANTITY (Editable) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-boxes text-green-600 mr-1"></i>
                        Quantity
                    </label>
                    <input type="number" name="quantity" value="{{ old('quantity', $product->quantity ?? 0) }}" min="0" step="0.01"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                           placeholder="0">
                    <p class="text-xs text-gray-500 mt-1">Current stock quantity</p>
                </div>

                <!-- Cost Price -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-money-bill text-indigo-600 mr-1"></i>
                        Cost Price (UGX) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="cost_price" value="{{ old('cost_price', $product->cost_price) }}" required min="0" step="0.01"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                </div>

                <!-- Selling Price -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-tag text-green-600 mr-1"></i>
                        Selling Price (UGX) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="selling_price" id="sellingPriceInput" value="{{ old('selling_price', $product->selling_price) }}" required min="0" step="0.01" oninput="updateVatCalculation()"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                </div>

                <!-- ✅ VAT TOGGLE & CALCULATION PREVIEW -->
                <div class="md:col-span-2 bg-indigo-50 border-2 border-indigo-200 rounded-xl p-5">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-indigo-600 text-white rounded-lg flex items-center justify-center font-bold text-lg">
                                <i class="fas fa-percent"></i>
                            </div>
                            <div>
                                <h4 class="font-extrabold text-gray-900 text-base">VAT Configuration</h4>
                                <p class="text-xs text-gray-600 font-medium">Select if this product is subject to Value Added Tax (VAT)</p>
                            </div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="requires_vat" id="requiresVatToggle" value="1" {{ old('requires_vat', $product->requires_vat ?? true) ? 'checked' : '' }} onchange="updateVatCalculation()" class="sr-only peer">
                            <div class="w-14 h-7 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-indigo-600"></div>
                        </label>
                    </div>

                    <div id="vatCalculationBox" class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-3 border-t border-indigo-200 text-sm">
                        <div class="bg-white p-3 rounded-lg border border-indigo-100 shadow-sm">
                            <span class="text-xs text-gray-500 font-semibold uppercase block">Selling Price (Excl. VAT)</span>
                            <span class="text-base font-extrabold text-gray-900" id="previewExclVat">UGX 0</span>
                        </div>
                        <div class="bg-white p-3 rounded-lg border border-indigo-100 shadow-sm">
                            <span class="text-xs text-indigo-600 font-semibold uppercase block">VAT Amount (18%)</span>
                            <span class="text-base font-extrabold text-indigo-700" id="previewVatAmount">UGX 0</span>
                        </div>
                        <div class="bg-white p-3 rounded-lg border border-indigo-100 shadow-sm">
                            <span class="text-xs text-emerald-600 font-semibold uppercase block">Final Total Price (Incl. VAT)</span>
                            <span class="text-base font-extrabold text-emerald-700" id="previewInclVat">UGX 0</span>
                        </div>
                    </div>
                </div>

                <!-- Reorder Level -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-exclamation-triangle text-yellow-600 mr-1"></i>
                        Reorder Level
                    </label>
                    <input type="number" name="reorder_level" value="{{ old('reorder_level', $product->reorder_level) }}" min="0"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                </div>

                <!-- Active Status -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-toggle-on text-indigo-600 mr-1"></i>
                        Status <span class="text-red-500">*</span>
                    </label>
                    <select name="is_active" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                        <option value="1" {{ old('is_active', $product->is_active) == 1 ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ old('is_active', $product->is_active) == 0 ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>



                <!-- Description -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-align-left text-indigo-600 mr-1"></i>
                        Description
                    </label>
                    <textarea name="description" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">{{ old('description', $product->description) }}</textarea>
                </div>

                <!-- Expiry Tracking Section -->
                <div class="md:col-span-2 bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
                    <div class="flex items-start">
                        <input type="checkbox" id="track_expiry" name="track_expiry" value="1" 
                               {{ old('track_expiry', $product->track_expiry) ? 'checked' : '' }}
                               class="mt-1 h-4 w-4 text-indigo-600" onchange="toggleExpiryFields(this)">
                        <div class="ml-3">
                            <label for="track_expiry" class="font-medium text-blue-800 cursor-pointer">
                                <i class="fas fa-calendar-times mr-1"></i>
                                Track Expiry Date for this Product
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Expiry Fields -->
                <div id="expiryFields" class="md:col-span-2 {{ old('track_expiry', $product->track_expiry) ? '' : 'hidden' }} space-y-4 pl-4 border-l-2 border-blue-300">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-industry text-blue-600 mr-1"></i>
                                Manufacture Date
                            </label>
                            <input type="date" name="manufacture_date" 
                                   value="{{ old('manufacture_date', $product->manufacture_date?->format('Y-m-d')) }}" 
                                   max="{{ date('Y-m-d') }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-calendar-times text-red-600 mr-1"></i>
                                Expiry Date
                            </label>
                            <input type="date" name="expiry_date" 
                                   value="{{ old('expiry_date', $product->expiry_date?->format('Y-m-d')) }}" 
                                   min="{{ date('Y-m-d', strtotime('+1 day')) }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-bell text-yellow-600 mr-1"></i>
                                Alert Days Before
                            </label>
                            <input type="number" name="expiry_alert_days" 
                                   value="{{ old('expiry_alert_days', $product->expiry_alert_days ?? 30) }}" 
                                   min="1" max="365"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>
                </div>

            </div>

            <!-- Action Buttons -->
            <div class="flex justify-end space-x-4 mt-6 pt-6 border-t">
                <a href="{{ route('products.index') }}" 
                   class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                    <i class="fas fa-times mr-2"></i>Cancel
                </a>
                <button type="submit" id="editSubmitBtn"
                        class="px-6 py-2 bg-indigo-600 text-white font-bold rounded-lg hover:bg-indigo-700 transition flex items-center gap-2">
                    <i class="fas fa-save" id="editSubmitIcon"></i>
                    <span id="editSubmitText">Update Product</span>
                    <i class="fas fa-spinner fa-spin hidden" id="editSpinner"></i>
                </button>
            </div>
        </form>

    </div>
</div>

<!-- HTML5 QRCode Camera Scanner Library -->
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

<!-- Camera Barcode Scanner Modal -->
<div id="fieldCameraScannerModal" class="hidden fixed inset-0 bg-black bg-opacity-75 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden flex flex-col">
        <div class="bg-indigo-900 text-white px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i class="fas fa-camera text-yellow-400 text-xl"></i>
                <h3 class="font-extrabold text-lg">Scan Product Barcode</h3>
            </div>
            <button type="button" onclick="closeFieldCameraScanner()" class="text-white hover:text-yellow-400 text-xl transition">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="p-6 space-y-4 text-center">
            <div id="fieldCameraViewfinder" class="w-full h-64 bg-slate-900 rounded-xl overflow-hidden relative border-2 border-indigo-500 shadow-inner"></div>
            <p class="text-xs text-gray-600 font-semibold">Point camera at product barcode. Scanned barcode will automatically fill the form input.</p>
        </div>
        <div class="bg-gray-50 px-6 py-4 border-t flex justify-between items-center">
            <span class="text-xs text-gray-600 font-bold" id="fieldCamScanStatus">Initializing camera…</span>
            <button type="button" onclick="closeFieldCameraScanner()" class="px-5 py-2 bg-gray-200 text-gray-800 font-bold rounded-lg hover:bg-gray-300 text-sm">
                Cancel
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const form     = document.getElementById('productEditForm');
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');

    // ── Toast ──────────────────────────────────────────────────────────────
    function showEditToast(title, messages, isError) {
        const toast   = document.getElementById('editToast');
        const inner   = document.getElementById('editToastInner');
        const icon    = document.getElementById('editToastIcon');
        const titleEl = document.getElementById('editToastTitle');
        const list    = document.getElementById('editToastList');

        if (isError) {
            inner.style.borderLeftColor = '#dc2626';
            icon.textContent   = '❌';
            titleEl.style.color = '#7f1d1d';
            titleEl.textContent = title;
            list.style.color   = '#991b1b';
        } else {
            inner.style.borderLeftColor = '#16a34a';
            icon.textContent   = '✅';
            titleEl.style.color = '#14532d';
            titleEl.textContent = title;
            list.style.color   = '#166534';
        }

        list.innerHTML = '';
        (messages || []).forEach(msg => {
            const li = document.createElement('li');
            li.textContent = msg;
            list.appendChild(li);
        });

        toast.classList.remove('et-active');
        void toast.offsetWidth;
        toast.classList.add('et-active');
        clearTimeout(toast._timer);
        toast._timer = setTimeout(() => toast.classList.remove('et-active'), isError ? 10000 : 4000);
    }

    function clearFieldErrors() {
        document.querySelectorAll('.field-error').forEach(el => el.classList.remove('field-error'));
    }

    function highlightFields(errors) {
        clearFieldErrors();
        Object.keys(errors).forEach(field => {
            const el = document.querySelector(`[name="${field}"]`);
            if (el) el.classList.add('field-error');
        });
    }

    // ── AJAX Submit ──────────────────────────────────────────────────────
    form.addEventListener('submit', function (e) {
        e.preventDefault();

        const btn     = document.getElementById('editSubmitBtn');
        const btnText = document.getElementById('editSubmitText');
        const spinner = document.getElementById('editSpinner');
        const icon    = document.getElementById('editSubmitIcon');

        btn.disabled      = true;
        btnText.textContent = 'Updating…';
        icon.classList.add('hidden');
        spinner.classList.remove('hidden');
        clearFieldErrors();

        const formData = new FormData(form);

        fetch(form.action, {
            method: 'POST',          // FormData with _method=PUT is fine
            headers: {
                'X-CSRF-TOKEN': csrfMeta ? csrfMeta.content : '',
                'Accept': 'application/json',
            },
            body: formData,
        })
        .then(async res => {
            const text = await res.text();
            let data = {};
            try { data = JSON.parse(text); } catch(err) {
                data = { success: false, message: `Server error (HTTP ${res.status}). Please try again.`, errors: {} };
            }

            btn.disabled      = false;
            btnText.textContent = 'Update Product';
            icon.classList.remove('hidden');
            spinner.classList.add('hidden');

            if (res.ok && data.success) {
                showEditToast('Product updated!', [`"${data.name}" has been saved successfully.`], false);
                setTimeout(() => { window.location.href = data.redirect; }, 1500);
            } else if (res.status === 422 && data.errors) {
                const messages = Object.values(data.errors).flat();
                highlightFields(data.errors);
                showEditToast('Please fix the following errors:', messages, true);
                const firstError = document.querySelector('.field-error');
                if (firstError) firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            } else {
                showEditToast('Update failed', [data.message || 'An unexpected error occurred. Please try again.'], true);
            }
        })
        .catch(err => {
            btn.disabled      = false;
            btnText.textContent = 'Update Product';
            icon.classList.remove('hidden');
            spinner.classList.add('hidden');
            showEditToast('Connection error', ['Could not reach the server. Check your internet and try again.'], true);
            console.error('Edit save error:', err);
        });
    });
})();
</script>
@endpush
@endsection