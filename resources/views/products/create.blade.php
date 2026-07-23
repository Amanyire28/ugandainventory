@extends('layouts.app')

@section('title', 'Add Product')

@section('page-title')
    <i class="fas fa-plus-circle text-indigo-600 mr-2"></i>Add New Product
@endsection

@section('content')

<script>
    // Define functions immediately so they're available for inline handlers
    function toggleCategoryInput(option) {
        const existingDiv = document.getElementById('existingCategoryDiv');
        const newDiv = document.getElementById('newCategoryDiv');
        const categorySelect = document.getElementById('category_id');
        const newCategoryInput = document.getElementById('new_category_name');

        if (option === 'existing') {
            existingDiv.style.display = 'block';
            newDiv.style.display = 'none';
            categorySelect.disabled = false;
            newCategoryInput.disabled = true;
            newCategoryInput.required = false;
            newCategoryInput.value = '';
        } else {
            existingDiv.style.display = 'none';
            newDiv.style.display = 'block';
            categorySelect.disabled = true;
            categorySelect.value = '';
            newCategoryInput.disabled = false;
            newCategoryInput.required = true;
            setTimeout(() => newCategoryInput.focus(), 100);
        }
    }

    function toggleExpiryFields(checkbox) {
        const expiryFields = document.getElementById('expiryFields');
        if (checkbox.checked) {
            expiryFields.style.display = 'block';
        } else {
            expiryFields.style.display = 'none';
        }
    }
</script>
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-xl shadow-lg p-6">
        
        <!-- Display Validation Errors -->
        @if ($errors->any())
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                <div class="flex items-start">
                    <i class="fas fa-exclamation-circle text-red-600 mt-1 mr-3"></i>
                    <div>
                        <h3 class="font-semibold text-red-800 mb-2">Validation Errors</h3>
                        <ul class="list-disc list-inside space-y-1 text-red-700 text-sm">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif
        
        <form method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                
                <!-- Product Name -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-tag text-indigo-600 mr-1"></i>
                        Product Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 @error('name') border-red-500 @enderror"
                           placeholder="e.g., Samsung Galaxy A54">
                    @error('name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- SKU -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-barcode text-indigo-600 mr-1"></i>
                        SKU <span class="text-gray-500 text-sm">(Optional)</span>
                    </label>
                    <input type="text" name="sku" value="{{ old('sku') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 @error('sku') border-red-500 @enderror"
                           placeholder="e.g., PROD-001">
                    @error('sku')
                        <p class="text-red-500 text-xs mt-1">
                            @if(str_contains($message, 'unique'))
                                This SKU is already used for another product. Please use a different SKU.
                            @else
                                {{ $message }}
                            @endif
                        </p>
                    @enderror
                </div>

                <!-- Barcode -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-qrcode text-indigo-600 mr-1"></i>
                        Barcode (Optional)
                    </label>
                    <input type="text" name="barcode" value="{{ old('barcode') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                           placeholder="Scan or enter barcode">
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
                            <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
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
                    <select name="unit" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 @error('unit') border-red-500 @enderror">
                        <option value="">-- Select Unit --</option>
                        <option value="pcs" {{ old('unit') == 'pcs' ? 'selected' : '' }}>Pieces (pcs)</option>
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

                <!-- ✅ QUANTITY (Simple - No Location) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-boxes text-green-600 mr-1"></i>
                        Quantity
                    </label>
                    <input type="number" name="quantity" value="{{ old('quantity', 0) }}" min="0" step="0.01"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                           placeholder="0">
                    <p class="text-xs text-gray-500 mt-1">Opening stock quantity</p>
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
                    <input type="number" name="selling_price" value="{{ old('selling_price') }}" required min="0" step="0.01"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 @error('selling_price') border-red-500 @enderror"
                           placeholder="0">
                    @error('selling_price')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
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
                    <i class="fas fa-save mr-2"></i>
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
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize category display on page load
        const checkedRadio = document.querySelector('input[name="category_option"]:checked');
        if (checkedRadio) {
            toggleCategoryInput(checkedRadio.value);
        }

        // Initialize expiry fields
        const trackExpiryCheckbox = document.getElementById('track_expiry');
        if (trackExpiryCheckbox && trackExpiryCheckbox.checked) {
            toggleExpiryFields(trackExpiryCheckbox);
        }

        // Handle form submission with loading feedback
        const form = document.querySelector('form');
        const submitBtn = document.getElementById('submitBtn');
        const submitText = document.getElementById('submitText');
        const loadingSpinner = document.getElementById('loadingSpinner');
        
        if (form && submitBtn) {
            form.addEventListener('submit', function(e) {
                if (!form.checkValidity()) {
                    e.preventDefault();
                    alert('Please fill in all required fields');
                    return;
                }

                // Show loading state
                submitBtn.disabled = true;
                submitText.classList.add('hidden');
                loadingSpinner.classList.remove('hidden');
                submitBtn.classList.add('opacity-75', 'cursor-not-allowed');
            });
        }
    });
</script>
@endpush
@endsection