@extends('layouts.app')

@section('title', 'Add Product')

@section('page-title')
    <i class="fas fa-plus-circle text-indigo-600 mr-2"></i>Add New Product
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
        
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6 pb-4 border-b border-gray-200">
            <div>
                <h2 class="text-xl font-extrabold text-gray-900 flex items-center gap-2">
                    <i class="fas fa-box text-indigo-600"></i> Add Single Product
                </h2>
                <p class="text-xs text-gray-500 mt-0.5">Fill in details to register a single item in inventory.</p>
            </div>
            <a href="{{ route('products.bulk-create') }}" class="px-4 py-2.5 shadow-md transition flex items-center gap-2 text-xs" style="background-color: #4f46e5 !important; color: #ffffff !important; font-weight: 800 !important; border-radius: 10px !important; text-decoration: none !important;">
                <i class="fas fa-layer-group text-yellow-300 text-sm"></i> Switch to Bulk Multiple Addition →
            </a>
        </div>
        
        {{-- ── Toast Notification ─────────────────────────────────────────── --}}
        <div id="createToast" style="display:none;position:fixed;top:80px;left:50%;transform:translateX(-50%);z-index:99999;min-width:340px;max-width:520px;">
            <div id="createToastInner" style="display:flex;align-items:flex-start;gap:16px;background:#fff;border-radius:14px;padding:20px 24px;box-shadow:0 12px 40px rgba(0,0,0,0.22);border-left:6px solid #16a34a;">
                <div id="createToastIcon" style="font-size:28px;line-height:1;margin-top:2px;">✅</div>
                <div style="flex:1;">
                    <p id="createToastTitle" style="font-size:15px;font-weight:800;color:#14532d;margin:0 0 6px;"></p>
                    <ul id="createToastList" style="font-size:13px;color:#166534;font-weight:600;margin:0;padding-left:16px;line-height:1.7;"></ul>
                </div>
                <button onclick="document.getElementById('createToast').classList.remove('ct-active')" style="background:none;border:none;cursor:pointer;font-size:20px;color:#94a3b8;line-height:1;padding:0;margin-top:-2px;">×</button>
            </div>
        </div>
        <style>
        #createToast { display:none; }
        #createToast.ct-active { display:block !important; animation:ctSlideIn 0.4s cubic-bezier(.22,1,.36,1); }
        @keyframes ctSlideIn { from{opacity:0;transform:translateX(-50%) translateY(-20px);} to{opacity:1;transform:translateX(-50%) translateY(0);} }
        .field-error { border-color: #ef4444 !important; background-color: #fff5f5 !important; }
        </style>
        
        <form action="{{ route('products.store') }}" id="productCreateForm" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                
                <!-- Product Name -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-box text-indigo-600 mr-1"></i>
                        Product Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 @error('name') border-red-500 @enderror"
                           placeholder="e.g., iPhone 15 Pro Max">
                    @error('name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- SKU -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-barcode text-indigo-600 mr-1"></i>
                        SKU (Stock Keeping Unit)
                    </label>
                    <input type="text" name="sku" value="{{ old('sku') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 @error('sku') border-red-500 @enderror"
                           placeholder="Auto-generated if left blank">
                    @error('sku')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Barcode -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-qrcode text-indigo-600 mr-1"></i>
                        Barcode / ISBN
                    </label>
                    <input type="text" name="barcode" value="{{ old('barcode') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 font-mono"
                           placeholder="Scan or type barcode">
                </div>

                <!-- Category Option Radios -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-folder text-indigo-600 mr-1"></i>
                        Category <span class="text-red-500">*</span>
                    </label>
                    
                    <div class="flex items-center space-x-4 mb-3">
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="radio" name="category_option" value="existing" onclick="toggleCategoryInput('existing')" 
                                   {{ old('category_option', 'existing') == 'existing' ? 'checked' : '' }}
                                   class="form-radio text-indigo-600 focus:ring-indigo-500">
                            <span class="ml-2 text-sm text-gray-700 font-medium">Select Existing</span>
                        </label>
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="radio" name="category_option" value="new" onclick="toggleCategoryInput('new')"
                                   {{ old('category_option') == 'new' ? 'checked' : '' }}
                                   class="form-radio text-green-600 focus:ring-green-500">
                            <span class="ml-2 text-sm text-green-700 font-bold">+ Create New</span>
                        </label>
                    </div>

                    <!-- Existing Category Select -->
                    <div id="existingCategoryDiv" class="space-y-1">
                        <select name="category_id" id="category_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 @error('category_id') border-red-500 @enderror">
                            <option value="">-- Select Category --</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- New Category Inputs -->
                    <div id="newCategoryDiv" class="hidden space-y-3" style="display: none;">
                        <div>
                            <input type="text" name="new_category_name" id="new_category_name" value="{{ old('new_category_name') }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                   placeholder="New category name">
                        </div>
                    </div>
                </div>

                <!-- Unit -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-balance-scale text-indigo-600 mr-1"></i>
                        Unit <span class="text-red-500">*</span>
                    </label>
                    <select name="unit" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 @error('unit') border-red-500 @enderror">
                        <option value="">-- Select Unit --</option>
                        <option value="pcs" {{ old('unit', 'pcs') == 'pcs' ? 'selected' : '' }}>Pieces (pcs)</option>
                        <option value="kg" {{ old('unit') == 'kg' ? 'selected' : '' }}>Kilograms (kg)</option>
                        <option value="grams" {{ old('unit') == 'grams' ? 'selected' : '' }}>Grams (g)</option>
                        <option value="liters" {{ old('unit') == 'liters' ? 'selected' : '' }}>Liters (L)</option>
                        <option value="ml" {{ old('unit') == 'ml' ? 'selected' : '' }}>Milliliters (ml)</option>
                        <option value="boxes" {{ old('unit') == 'boxes' ? 'selected' : '' }}>Boxes</option>
                        <option value="cartons" {{ old('unit') == 'cartons' ? 'selected' : '' }}>Cartons</option>
                        <option value="dozen" {{ old('unit') == 'dozen' ? 'selected' : '' }}>Dozen</option>
                        <option value="pairs" {{ old('unit') == 'pairs' ? 'selected' : '' }}>Pairs</option>
                        <option value="meters" {{ old('unit') == 'meters' ? 'selected' : '' }}>Meters (m)</option>
                    </select>
                    @error('unit')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Quantity -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-boxes text-green-600 mr-1"></i>
                        Quantity
                    </label>
                    <input type="number" name="quantity" value="{{ old('quantity', 0) }}" min="0" step="0.01"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                           placeholder="0">
                </div>

                <!-- Cost Price -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-money-bill text-indigo-600 mr-1"></i>
                        Cost Price (UGX) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="cost_price" value="{{ old('cost_price') }}" required min="0" step="0.01"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 @error('cost_price') border-red-500 @enderror"
                           placeholder="0">
                    @error('cost_price')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Selling Price -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-tag text-green-600 mr-1"></i>
                        Selling Price (UGX) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="selling_price" id="sellingPriceInput" value="{{ old('selling_price') }}" required min="0" step="0.01" oninput="updateVatCalculation()"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 @error('selling_price') border-red-500 @enderror"
                           placeholder="0">
                    @error('selling_price')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
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
                            <input type="checkbox" name="requires_vat" id="requiresVatToggle" value="1" {{ old('requires_vat', '1') ? 'checked' : '' }} onchange="updateVatCalculation()" class="sr-only peer">
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
                    <input type="number" name="reorder_level" value="{{ old('reorder_level', 10) }}" min="0"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                           placeholder="10">
                    <p class="text-xs text-gray-500 mt-1">Alert when stock falls below this level</p>
                </div>

                <!-- Description -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-align-left text-indigo-600 mr-1"></i>
                        Description
                    </label>
                    <textarea name="description" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                              placeholder="Product description...">{{ old('description') }}</textarea>
                </div>

                <!-- Expiry Tracking Section -->
                <div class="md:col-span-2 bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
                    <div class="flex items-start">
                        <input type="checkbox" id="track_expiry" name="track_expiry" value="1" {{ old('track_expiry') ? 'checked' : '' }}
                               class="mt-1 h-4 w-4 text-indigo-600" onchange="toggleExpiryFields(this)">
                        <div class="ml-3">
                            <label for="track_expiry" class="font-medium text-blue-800 cursor-pointer">
                                <i class="fas fa-calendar-times mr-1"></i>
                                Track Expiry Date for this Product
                            </label>
                            <p class="text-xs text-blue-600 mt-1">
                                Enable for perishable items, medicines, or products with expiration dates
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Expiry Fields -->
                <div id="expiryFields" class="md:col-span-2 hidden space-y-4 pl-4 border-l-2 border-blue-300">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-industry text-blue-600 mr-1"></i>
                                Manufacture Date
                            </label>
                            <input type="date" name="manufacture_date" value="{{ old('manufacture_date') }}" 
                                   max="{{ date('Y-m-d') }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-calendar-times text-red-600 mr-1"></i>
                                Expiry Date
                            </label>
                            <input type="date" name="expiry_date" value="{{ old('expiry_date') }}" 
                                   min="{{ date('Y-m-d', strtotime('+1 day')) }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-bell text-yellow-600 mr-1"></i>
                                Alert Days Before
                            </label>
                            <input type="number" name="expiry_alert_days" value="{{ old('expiry_alert_days', 30) }}" 
                                   min="1" max="365"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>
                </div>

            </div>

            <!-- Action Buttons -->
            <div class="flex justify-end space-x-4 mt-6 pt-6 border-t">
                <a href="{{ route('products.index') }}" 
                   class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                    <i class="fas fa-times mr-2"></i>Cancel
                </a>
                <button type="submit" id="submitBtn"
                        class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition duration-200 flex items-center space-x-2">
                    <i class="fas fa-save mr-2" id="submitIcon"></i>
                    <span id="submitText">Save Product</span>
                    <span id="loadingSpinner" class="hidden">
                        <i class="fas fa-spinner fa-spin"></i>
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>


@push('scripts')
<script>
(function () {
    const form      = document.getElementById('productCreateForm');
    const csrfMeta  = document.querySelector('meta[name="csrf-token"]');

    // ── Toast helpers ────────────────────────────────────────────────────
    function showCreateToast(title, messages, isError) {
        const toast  = document.getElementById('createToast');
        const inner  = document.getElementById('createToastInner');
        const icon   = document.getElementById('createToastIcon');
        const titleEl= document.getElementById('createToastTitle');
        const list   = document.getElementById('createToastList');

        if (isError) {
            inner.style.borderLeftColor = '#dc2626';
            icon.textContent  = '❌';
            titleEl.style.color = '#7f1d1d';
            titleEl.textContent = title;
            list.style.color  = '#991b1b';
        } else {
            inner.style.borderLeftColor = '#16a34a';
            icon.textContent  = '✅';
            titleEl.style.color = '#14532d';
            titleEl.textContent = title;
            list.style.color  = '#166534';
        }

        list.innerHTML = '';
        (messages || []).forEach(msg => {
            const li = document.createElement('li');
            li.textContent = msg;
            list.appendChild(li);
        });

        toast.classList.remove('ct-active');
        void toast.offsetWidth;
        toast.classList.add('ct-active');
        if (isError) {
            clearTimeout(toast._timer);
            toast._timer = setTimeout(() => toast.classList.remove('ct-active'), 10000);
        } else {
            clearTimeout(toast._timer);
            toast._timer = setTimeout(() => toast.classList.remove('ct-active'), 4000);
        }
    }

    // ── Clear all field highlights ────────────────────────────────────────
    function clearFieldErrors() {
        document.querySelectorAll('.field-error').forEach(el => el.classList.remove('field-error'));
    }

    // ── Highlight specific fields returned by validation ─────────────────
    function highlightFields(errors) {
        clearFieldErrors();
        Object.keys(errors).forEach(field => {
            const el = document.querySelector(`[name="${field}"]`);
            if (el) el.classList.add('field-error');
        });
    }

    // ── AJAX form submit ─────────────────────────────────────────────────
    form.addEventListener('submit', function (e) {
        e.preventDefault();

        const submitBtn = document.getElementById('submitBtn');
        const submitText = document.getElementById('submitText');
        const spinner    = document.getElementById('loadingSpinner');

        submitBtn.disabled   = true;
        submitText.textContent = 'Saving…';
        spinner.classList.remove('hidden');
        clearFieldErrors();

        const formData = new FormData(form);

        fetch(form.action, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfMeta ? csrfMeta.content : '',
                'Accept': 'application/json',
            },
            body: formData,
        })
        .then(async res => {
            const text = await res.text();
            let data = {};
            try { data = JSON.parse(text); } catch(e) {
                data = { success: false, message: `Server error (HTTP ${res.status}).`, errors: {} };
            }

            submitBtn.disabled = false;
            submitText.textContent = 'Save Product';
            spinner.classList.add('hidden');

            if (res.ok && data.success) {
                showCreateToast('Product saved successfully!', [`"${data.name}" has been added to your inventory.`], false);
                setTimeout(() => { window.location.href = data.redirect; }, 1500);
            } else if (res.status === 422 && data.errors) {
                // Laravel validation errors
                const messages = Object.values(data.errors).flat();
                highlightFields(data.errors);
                showCreateToast('Please fix the following errors:', messages, true);
                // Scroll to first errored field
                const firstError = document.querySelector('.field-error');
                if (firstError) firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            } else {
                showCreateToast('Failed to save product', [data.message || 'An unexpected error occurred.'], true);
            }
        })
        .catch(err => {
            submitBtn.disabled = false;
            submitText.textContent = 'Save Product';
            spinner.classList.add('hidden');
            showCreateToast('Connection error', ['Could not reach the server. Please check your connection and try again.'], true);
            console.error('Product save error:', err);
        });
    });
})();
</script>
@endpush
@endsection