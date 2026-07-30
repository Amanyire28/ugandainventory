@extends('layouts.app')

@section('title', 'All Products')

@section('page-title')
    <i class="fas fa-box text-indigo-600 mr-2"></i>All Products
@endsection

@section('content')
@php
    $currentStatus = request('status', 'active');
    if (!$currentStatus) {
        $currentStatus = 'active';
    }
@endphp
<div class="bg-white rounded-xl shadow-lg p-6">
    
    <!-- Success Message -->
    @if (session('success'))
        <div id="successAlert" class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg animate-fadeIn">
            <div class="flex items-start">
                <i class="fas fa-check-circle text-green-600 mt-1 mr-3 text-lg"></i>
                <div>
                    <h3 class="font-semibold text-green-800">Success!</h3>
                    <p class="text-green-700 text-sm">{{ session('success') }}</p>
                </div>
                <button onclick="document.getElementById('successAlert').remove()" class="ml-auto text-green-600 hover:text-green-800">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    @endif
    
    <!-- Low Stock Alert Section -->
    @php
        $lowStockProducts = $products->filter(function($product) {
            return $product->isLowStock() && !$product->isOutOfStock();
        });
        $outOfStockProducts = $products->filter(function($product) {
            return $product->isOutOfStock();
        });
    @endphp
    
    @if($lowStockProducts->count() > 0)
        <div class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
            <div class="flex items-start">
                <i class="fas fa-exclamation-triangle text-yellow-600 mt-1 mr-3 text-lg"></i>
                <div class="flex-1">
                    <h3 class="font-semibold text-yellow-800">⚠️ Low Stock Alert</h3>
                    <p class="text-yellow-700 text-sm mt-1">{{ $lowStockProducts->count() }} product(s) have reached reorder level:</p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach($lowStockProducts->take(5) as $product)
                            <span class="inline-flex items-center px-3 py-1 bg-yellow-100 text-yellow-800 text-xs font-semibold rounded-full">
                                {{ $product->name }} ({{ $product->quantity }}/{{ $product->reorder_level }})
                            </span>
                        @endforeach
                        @if($lowStockProducts->count() > 5)
                            <span class="inline-flex items-center px-3 py-1 bg-yellow-100 text-yellow-800 text-xs font-semibold rounded-full">
                                +{{ $lowStockProducts->count() - 5 }} more
                            </span>
                        @endif
                    </div>
                    <a href="{{ route('products.index', ['status' => 'low_stock']) }}" class="text-yellow-700 underline text-sm mt-2 inline-block hover:text-yellow-900">
                        View all low stock products →
                    </a>
                </div>
                <button onclick="this.parentElement.parentElement.remove()" class="text-yellow-600 hover:text-yellow-800">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    @endif
    
    @if($outOfStockProducts->count() > 0)
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
            <div class="flex items-start">
                <i class="fas fa-times-circle text-red-600 mt-1 mr-3 text-lg"></i>
                <div class="flex-1">
                    <h3 class="font-semibold text-red-800">🔴 Out of Stock</h3>
                    <p class="text-red-700 text-sm mt-1">{{ $outOfStockProducts->count() }} product(s) are out of stock:</p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach($outOfStockProducts->take(5) as $product)
                            <span class="inline-flex items-center px-3 py-1 bg-red-100 text-red-800 text-xs font-semibold rounded-full">
                                {{ $product->name }}
                            </span>
                        @endforeach
                        @if($outOfStockProducts->count() > 5)
                            <span class="inline-flex items-center px-3 py-1 bg-red-100 text-red-800 text-xs font-semibold rounded-full">
                                +{{ $outOfStockProducts->count() - 5 }} more
                            </span>
                        @endif
                    </div>
                    <a href="{{ route('products.index', ['status' => 'out_of_stock']) }}" class="text-red-700 underline text-sm mt-2 inline-block hover:text-red-900">
                        View all out of stock products →
                    </a>
                </div>
                <button onclick="this.parentElement.parentElement.remove()" class="text-red-600 hover:text-red-800">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    @endif
    
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 space-y-4 md:space-y-0">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Products List</h2>
            <p class="text-gray-600 text-sm mt-1">Manage your product inventory</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('products.create') }}" class="px-4 py-2 shadow-md transition flex items-center text-sm" style="background-color: #4f46e5 !important; color: #ffffff !important; font-weight: 800 !important; border-radius: 10px !important; text-decoration: none !important;">
                <i class="fas fa-plus mr-2 text-yellow-300"></i>
                Add Product
            </a>
            <a href="{{ route('products.bulk-create') }}" class="px-4 py-2 shadow-md transition flex items-center text-sm" style="background-color: #4f46e5 !important; color: #ffffff !important; font-weight: 800 !important; border-radius: 10px !important; text-decoration: none !important;">
                <i class="fas fa-layer-group text-yellow-300 mr-2"></i>
                Bulk Add Products
            </a>
        </div>
    </div>

    <!-- Tabs Navigation -->
    <div id="tabsNavigation" class="flex border-b border-gray-200 mb-6 overflow-x-auto whitespace-nowrap scrollbar-none">
        <a href="{{ route('products.index', array_merge(request()->query(), ['status' => 'active'])) }}" 
           class="py-3 px-6 font-bold text-sm border-b-2 transition duration-200 flex items-center gap-2 {{ $currentStatus === 'active' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-indigo-600' }}">
            <i class="fas fa-check-circle"></i>
            Active Products
        </a>
        <a href="{{ route('products.index', array_merge(request()->query(), ['status' => 'inactive'])) }}" 
           class="py-3 px-6 font-bold text-sm border-b-2 transition duration-200 flex items-center gap-2 {{ $currentStatus === 'inactive' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-indigo-600' }}">
            <i class="fas fa-ban"></i>
            Inactive Products
        </a>
        <a href="{{ route('products.index', array_merge(request()->query(), ['status' => 'low_stock'])) }}" 
           class="py-3 px-6 font-bold text-sm border-b-2 transition duration-200 flex items-center gap-2 {{ $currentStatus === 'low_stock' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-indigo-600' }}">
            <i class="fas fa-exclamation-triangle"></i>
            Low Stock Alert
        </a>
        <a href="{{ route('products.index', array_merge(request()->query(), ['status' => 'out_of_stock'])) }}" 
           class="py-3 px-6 font-bold text-sm border-b-2 transition duration-200 flex items-center gap-2 {{ $currentStatus === 'out_of_stock' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-indigo-600' }}">
            <i class="fas fa-times-circle"></i>
            Out of Stock
        </a>
        <a href="{{ route('products.index', array_merge(request()->query(), ['status' => 'all'])) }}" 
           class="py-3 px-6 font-bold text-sm border-b-2 transition duration-200 flex items-center gap-2 {{ $currentStatus === 'all' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-indigo-600' }}">
            <i class="fas fa-boxes"></i>
            All Products
        </a>
    </div>

    <!-- Search & Filter Form -->
    <form method="GET" action="{{ route('products.index') }}" id="filterForm" class="mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            
            <!-- ✅ LIVE SEARCH (No button needed) -->
            <div class="relative">
                <input type="text" 
                       name="search" 
                       id="searchInput" 
                       value="{{ request('search') }}"
                       placeholder="Search by name, SKU, or barcode..." 
                       class="w-full px-4 py-2 pl-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                
                <!-- ✅ Loading Spinner (shows when searching) -->
                <div id="searchSpinner" class="hidden absolute right-3 top-3">
                    <i class="fas fa-spinner fa-spin text-indigo-600"></i>
                </div>
            </div>

            <!-- Category Filter (Auto-submit) -->
            <div>
                <select name="category_id" 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                        onchange="triggerFormFilter()">
                    <option value="">All Categories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" 
                                {{ request('category_id') == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Status Filter (Auto-submit) -->
            <div>
                <select name="status" 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                        onchange="triggerFormFilter()">
                    <option value="active" {{ request('status') == 'active' || !request('status') ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    <option value="low_stock" {{ request('status') == 'low_stock' ? 'selected' : '' }}>Low Stock</option>
                    <option value="out_of_stock" {{ request('status') == 'out_of_stock' ? 'selected' : '' }}>Out of Stock</option>
                    <option value="all" {{ request('status') == 'all' ? 'selected' : '' }}>All Statuses</option>
                </select>
            </div>

            <!-- Reset Button Only -->
            <div class="flex space-x-2">
                <a href="{{ route('products.index') }}" onclick="event.preventDefault(); loadProducts(this.href)" class="w-full px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 flex items-center justify-center">
                    <i class="fas fa-redo mr-1"></i> Reset All
                </a>
            </div>
        </div>
    </form>

    <!-- Active Filters Display -->
    <div id="productsTableContainer">
    @if(request()->hasAny(['search', 'category_id', 'status']))
    <div class="mb-4 flex flex-wrap gap-2">
        <span class="text-sm text-gray-600 font-semibold">Active Filters:</span>
        
        @if(request('search'))
        <span class="px-3 py-1 bg-indigo-100 text-indigo-800 rounded-full text-sm flex items-center">
            <i class="fas fa-search mr-1"></i>
            Search: "{{ request('search') }}"
            <a href="{{ route('products.index', array_filter(request()->except('search'))) }}" class="ml-2 text-indigo-600 hover:text-indigo-800">
                <i class="fas fa-times"></i>
            </a>
        </span>
        @endif

        @if(request('category_id'))
        <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm flex items-center">
            <i class="fas fa-folder mr-1"></i>
            Category: {{ $categories->where('id', request('category_id'))->first()->name ?? 'Unknown' }}
            <a href="{{ route('products.index', array_filter(request()->except('category_id'))) }}" class="ml-2 text-green-600 hover:text-green-800">
                <i class="fas fa-times"></i>
            </a>
        </span>
        @endif

        @if(request('status'))
        <span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-sm flex items-center">
            <i class="fas fa-info-circle mr-1"></i>
            Status: {{ ucfirst(str_replace('_', ' ', request('status'))) }}
            <a href="{{ route('products.index', array_filter(request()->except('status'))) }}" class="ml-2 text-yellow-600 hover:text-yellow-800">
                <i class="fas fa-times"></i>
            </a>
        </span>
        @endif
    </div>
    @endif

    <!-- Products Count -->
    <div class="mb-4">
        <p class="text-sm text-gray-600">
            Showing <span class="font-semibold text-gray-900">{{ $products->firstItem() ?? 0 }}</span> 
            to <span class="font-semibold text-gray-900">{{ $products->lastItem() ?? 0 }}</span> 
            of <span class="font-semibold text-gray-900">{{ $products->total() }}</span> products
        </p>
    </div>

    <!-- Products Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SKU</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expiry</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($products as $product)
                <tr data-product-id="{{ $product->id }}" class="hover:bg-gray-50 transition">
                    <!-- Product Name -->
                    <td class="px-4 py-3 whitespace-nowrap text-sm font-semibold text-gray-900">
                        {{ $product->name }}
                        @if($product->unit)
                            <span class="text-xs text-gray-400 block mt-0.5">Unit: {{ $product->unit }}</span>
                        @endif
                    </td>

                    <!-- SKU -->
                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="text-sm text-gray-700 font-mono">{{ $product->sku }}</span>
                    </td>

                    <!-- Category -->
                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="text-sm text-gray-600">
                            {{ $product->category->name ?? 'Uncategorized' }}
                        </span>
                    </td>

                    <!-- Quantity with Color Coding -->
                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full 
                            @if($product->quantity <= 0)
                                bg-red-100 text-red-800
                            @elseif($product->quantity <= $product->reorder_level)
                                bg-yellow-100 text-yellow-800
                            @else
                                bg-green-100 text-green-800
                            @endif">
                            {{ number_format($product->quantity, 0) }} {{ $product->unit }}
                        </span>
                    </td>

                    <!-- Selling Price -->
                    <td class="px-4 py-3 whitespace-nowrap">
                        <div class="text-sm font-semibold text-gray-900">
                            UGX {{ number_format($product->selling_price, 0) }}
                        </div>
                        <div class="text-xs text-gray-500">
                            Cost: UGX {{ number_format($product->cost_price, 0) }}
                        </div>
                    </td>

                    <!-- Expiry Status -->
                    <td class="px-4 py-3 whitespace-nowrap">
                        @if($product->track_expiry && $product->expiry_date)
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                @if($product->getExpiryStatusColor() === 'red')
                                    bg-red-100 text-red-800
                                @elseif($product->getExpiryStatusColor() === 'yellow')
                                    bg-yellow-100 text-yellow-800
                                @else
                                    bg-green-100 text-green-800
                                @endif">
                                {{ $product->getExpiryStatusText() }}
                            </span>
                            <div class="text-xs text-gray-500 mt-1">
                                {{ $product->expiry_date->format('M d, Y') }}
                            </div>
                        @else
                            <span class="text-xs text-gray-400">No tracking</span>
                        @endif
                    </td>

                    <!-- Active Status -->
                    <td class="px-4 py-3 whitespace-nowrap">
                        <span data-status-badge class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                            {{ $product->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                            {{ $product->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>

                    <!-- Actions -->
                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium">
                        <div class="flex space-x-3 items-center" data-action-container>
                            <a href="{{ route('products.edit', $product) }}" 
                               class="text-indigo-600 hover:text-indigo-900" 
                               title="Edit Product">
                                <i class="fas fa-edit"></i>
                            </a>
                            @if($product->is_active)
                                <button type="button"
                                        onclick="confirmDeleteProduct({{ $product->id }}, '{{ addslashes($product->name) }}', this)"
                                        data-delete-url="{{ route('products.destroy', $product) }}"
                                        class="text-red-600 hover:text-red-900 cursor-pointer"
                                        title="Delete Product">
                                    <i class="fas fa-trash"></i>
                                </button>
                            @else
                                <button type="button"
                                        onclick="ajaxActivateProduct({{ $product->id }}, '{{ addslashes($product->name) }}', this)"
                                        data-activate-url="{{ route('products.activate', $product) }}"
                                        class="text-green-600 hover:text-green-900 cursor-pointer"
                                        title="Activate Product">
                                    <i class="fas fa-check-circle text-lg"></i>
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-4 py-12 text-center">
                        <div class="flex flex-col items-center justify-center">
                            <i class="fas fa-box-open text-6xl text-gray-300 mb-4"></i>
                            <p class="text-gray-500 text-lg font-medium mb-2">No products found</p>
                            <p class="text-gray-400 text-sm mb-4">
                                @if(request()->hasAny(['search', 'category_id', 'status']))
                                    Try adjusting your filters or 
                                    <a href="{{ route('products.index') }}" class="text-indigo-600 hover:text-indigo-800 font-semibold">clear all filters</a>
                                @else
                                    Get started by adding your first product
                                @endif
                            </p>
                            <a href="{{ route('products.create') }}" class="px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 inline-flex items-center">
                                <i class="fas fa-plus mr-2"></i>
                                Add Your First Product
                            </a>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

     <div class="mt-6">
        {{ $products->appends(request()->query())->links() }}
    </div>
    </div>
</div>

@push('scripts')

{{-- ── Delete Confirm Modal ─────────────────────────────────────── --}}
<div id="deleteModal" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.6); z-index:9999; align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:16px; padding:32px 36px; max-width:420px; width:90%; box-shadow:0 20px 60px rgba(0,0,0,0.25); text-align:center;">
        <div style="width:56px;height:56px;background:#fee2e2;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
            <i class="fas fa-trash" style="color:#dc2626;font-size:22px;"></i>
        </div>
        <h3 style="font-size:18px;font-weight:800;color:#111827;margin:0 0 8px;">Delete Product?</h3>
        <p style="font-size:14px;color:#6b7280;margin:0 0 6px;">You are about to delete:</p>
        <p id="deleteModalProductName" style="font-size:15px;font-weight:700;color:#1e40af;margin:0 0 20px;"></p>
        <p style="font-size:13px;color:#6b7280;margin:0 0 8px;">Products <strong>with transaction history</strong> will be <strong style="color:#d97706">deactivated</strong> (hidden from active lists, history preserved).</p>
        <p style="font-size:13px;color:#6b7280;margin:0 0 24px;">Products <strong>with no history</strong> will be <strong style="color:#dc2626">permanently deleted</strong>.</p>
        <div style="display:flex;gap:12px;justify-content:center;">
            <button onclick="closeDeleteModal()" style="padding:10px 28px;border-radius:10px;border:1px solid #e5e7eb;background:#f9fafb;color:#374151;font-weight:700;font-size:14px;cursor:pointer;">Cancel</button>
            <button id="deleteModalConfirmBtn" onclick="executeDelete()" style="padding:10px 28px;border-radius:10px;border:none;background:#dc2626;color:#fff;font-weight:700;font-size:14px;cursor:pointer;display:flex;align-items:center;gap:8px;">
                <i class="fas fa-trash"></i> Yes, Delete
            </button>
        </div>
    </div>
</div>

{{-- ── Toast Notification ───────────────────────────────────────── --}}
<div id="deleteToast" style="display:none;position:fixed;top:80px;left:50%;transform:translateX(-50%);z-index:99999;min-width:340px;max-width:480px;">
    <div id="deleteToastInner" style="display:flex;align-items:flex-start;gap:16px;background:#fff;border-radius:14px;padding:20px 24px;box-shadow:0 12px 40px rgba(0,0,0,0.22);border-left:6px solid #16a34a;">
        <div id="deleteToastIcon" style="font-size:28px;line-height:1;margin-top:2px;">✅</div>
        <div style="flex:1;">
            <p id="deleteToastTitle" style="font-size:15px;font-weight:800;color:#14532d;margin:0 0 4px;">Deleted!</p>
            <p id="deleteToastMsg" style="font-size:13px;color:#166534;font-weight:600;margin:0;line-height:1.5;"></p>
        </div>
        <button onclick="document.getElementById('deleteToast').classList.remove('dt-active')" style="background:none;border:none;cursor:pointer;font-size:20px;color:#94a3b8;line-height:1;padding:0;margin-top:-2px;">×</button>
    </div>
</div>

<style>
#deleteModal.dm-active  { display: flex !important; }
#deleteToast { display: none; }
#deleteToast.dt-active  { display: block !important; animation: dtSlideIn 0.4s cubic-bezier(.22,1,.36,1); }
@keyframes dtSlideIn { from { opacity:0; transform:translateX(-50%) translateY(-20px); } to { opacity:1; transform:translateX(-50%) translateY(0); } }
.product-row-removing { opacity:0 !important; transform:translateX(30px) !important; transition:opacity 0.35s ease, transform 0.35s ease !important; }
</style>

<script>
    // ✅ LIVE SEARCH & AJAX FILTERS
    let searchTimer;
    const searchInput = document.getElementById('searchInput');
    const searchSpinner = document.getElementById('searchSpinner');
    const filterForm = document.getElementById('filterForm');

    // Bind triggers for filter inputs
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimer);
        searchSpinner.classList.remove('hidden');
        searchTimer = setTimeout(function() {
            triggerFormFilter();
        }, 500);
    });

    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            clearTimeout(searchTimer);
            triggerFormFilter();
        }
    });

    document.querySelectorAll('#filterForm select').forEach(select => {
        select.addEventListener('change', () => triggerFormFilter());
    });

    // Handle AJAX request for page filter
    function triggerFormFilter() {
        const formData = new FormData(filterForm);
        const params = new URLSearchParams();
        
        for (const [key, val] of formData.entries()) {
            if (val) {
                params.append(key, val);
            }
        }
        
        const url = `${filterForm.action}?${params.toString()}`;
        loadProducts(url);
    }

    let fetchController = null;
    function loadProducts(url, updateUrl = true) {
        if (fetchController) {
            fetchController.abort();
        }
        fetchController = new AbortController();

        const tableContainer = document.getElementById('productsTableContainer');
        if (tableContainer) {
            tableContainer.style.opacity = '0.5';
            tableContainer.style.pointerEvents = 'none';
        }
        if (searchSpinner) {
            searchSpinner.classList.remove('hidden');
        }

        fetch(url, {
            signal: fetchController.signal
        })
        .then(res => res.text())
        .then(html => {
            if (searchSpinner) {
                searchSpinner.classList.add('hidden');
            }

            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newContent = doc.getElementById('productsTableContainer');

            if (newContent && tableContainer) {
                tableContainer.innerHTML = newContent.innerHTML;
                tableContainer.style.opacity = '1';
                tableContainer.style.pointerEvents = 'auto';
            }

            if (updateUrl) {
                history.pushState({ url: url }, '', url);
            }

            updateTabsActiveState(url);
        })
        .catch(err => {
            if (err.name !== 'AbortError') {
                if (tableContainer) {
                    tableContainer.style.opacity = '1';
                    tableContainer.style.pointerEvents = 'auto';
                }
                if (searchSpinner) {
                    searchSpinner.classList.add('hidden');
                }
                console.error('Failed to load products:', err);
            }
        });
    }

    function updateTabsActiveState(url) {
        const urlObj = new URL(url);
        const status = urlObj.searchParams.get('status') || 'active';
        
        document.querySelectorAll('#tabsNavigation a').forEach(tab => {
            const tabUrl = new URL(tab.href);
            const tabStatus = tabUrl.searchParams.get('status') || 'active';
            
            if (tabStatus === status) {
                tab.className = 'py-3 px-6 font-bold text-sm border-b-2 transition duration-200 flex items-center gap-2 border-indigo-600 text-indigo-600';
            } else {
                tab.className = 'py-3 px-6 font-bold text-sm border-b-2 transition duration-200 flex items-center gap-2 border-transparent text-gray-500 hover:text-indigo-600';
            }
        });

        const statusSelect = document.querySelector('select[name="status"]');
        if (statusSelect) {
            statusSelect.value = status;
        }
    }

    // Intercept Tab clicks
    const tabsNav = document.getElementById('tabsNavigation');
    if (tabsNav) {
        tabsNav.addEventListener('click', function(e) {
            const a = e.target.closest('a');
            if (a) {
                e.preventDefault();
                loadProducts(a.href);
            }
        });
    }

    // Intercept Pagination clicks on dynamically loaded content
    document.addEventListener('click', function(e) {
        const a = e.target.closest('#productsTableContainer a[href*="page="]');
        if (a) {
            e.preventDefault();
            loadProducts(a.href);
        }
    });

    // Handle back/forward navigation
    window.addEventListener('popstate', function(e) {
        if (e.state && e.state.url) {
            loadProducts(e.state.url, false);
        } else {
            loadProducts(window.location.href, false);
        }
    });

    // ✅ Auto-dismiss success alert after 5 seconds
    const successAlert = document.getElementById('successAlert');
    if (successAlert) {
        setTimeout(function() {
            successAlert.style.animation = 'fadeOut 0.3s ease-in-out';
            setTimeout(function() { successAlert.remove(); }, 300);
        }, 5000);
    }

    // ─── Delete AJAX Logic ────────────────────────────────────────────────
    let _deleteUrl  = null;
    let _deleteRow  = null;
    let _deleteName = null;
    const csrfMeta  = document.querySelector('meta[name="csrf-token"]');

    function confirmDeleteProduct(id, name, btn) {
        _deleteUrl  = btn.dataset.deleteUrl;
        _deleteRow  = btn.closest('tr');
        _deleteName = name;
        document.getElementById('deleteModalProductName').textContent = name;
        document.getElementById('deleteModal').classList.add('dm-active');
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.remove('dm-active');
        _deleteUrl = _deleteRow = _deleteName = null;
    }

    function executeDelete() {
        if (!_deleteUrl || !_deleteRow) return;

        const confirmBtn  = document.getElementById('deleteModalConfirmBtn');
        const rowToDelete = _deleteRow; // ← capture BEFORE closeDeleteModal() nulls it
        const deleteUrl   = _deleteUrl;

        confirmBtn.disabled = true;
        confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting…';

        fetch(deleteUrl, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfMeta ? csrfMeta.content : '',
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
        })
        .then(async res => {
            // Safely read response — server might return HTML on errors
            const text = await res.text();
            let data = {};
            try { data = JSON.parse(text); } catch(e) {
                data = { success: false, message: `Server error (HTTP ${res.status}). Check permissions and try again.` };
            }

            closeDeleteModal(); // safe to call now — rowToDelete is already saved
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = '<i class="fas fa-trash"></i> Yes, Delete';

            if (res.ok && data.success) {
                if (data.action === 'deactivated') {
                    // Check if current tab is 'all'
                    const currentTab = document.querySelector('#tabsNavigation a.border-indigo-600');
                    const tabUrl = currentTab ? new URL(currentTab.href) : null;
                    const tabStatus = tabUrl ? (tabUrl.searchParams.get('status') || 'active') : 'active';

                    if (tabStatus === 'all') {
                        // Keep row but grey it out
                        rowToDelete.style.transition = 'opacity 0.5s ease';
                        rowToDelete.style.opacity = '0.45';
                        const statusBadge = rowToDelete.querySelector('[data-status-badge]');
                        if (statusBadge) {
                            statusBadge.textContent = 'Inactive';
                            statusBadge.className = 'px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800';
                        }
                        // Swap delete button to activate button
                        const actionContainer = rowToDelete.querySelector('[data-action-container]');
                        if (actionContainer) {
                            const delBtn = actionContainer.querySelector('[onclick*="confirmDeleteProduct"]');
                            if (delBtn) delBtn.remove();

                            const actBtn = document.createElement('button');
                            actBtn.type = 'button';
                            const productId = rowToDelete.dataset.productId || _deleteUrl.split('/').pop();
                            actBtn.onclick = function() {
                                ajaxActivateProduct(productId, _deleteName, actBtn);
                            };
                            actBtn.dataset.activateUrl = `{{ url('products') }}/${productId}/activate`;
                            actBtn.className = 'text-green-600 hover:text-green-900 cursor-pointer';
                            actBtn.title = 'Activate Product';
                            actBtn.innerHTML = '<i class="fas fa-check-circle text-lg"></i>';
                            actionContainer.appendChild(actBtn);
                        }
                    } else {
                        // Active / Low Stock / Out of Stock tabs — remove row completely
                        rowToDelete.style.transition = 'opacity 0.35s ease, transform 0.35s ease';
                        rowToDelete.style.opacity    = '0';
                        rowToDelete.style.transform  = 'translateX(30px)';
                        setTimeout(() => rowToDelete.remove(), 380);
                    }
                    showDeleteToast(data.message, 'deactivated');
                } else {
                    // Pure deleted — always remove row completely
                    rowToDelete.style.transition = 'opacity 0.35s ease, transform 0.35s ease';
                    rowToDelete.style.opacity    = '0';
                    rowToDelete.style.transform  = 'translateX(30px)';
                    setTimeout(() => rowToDelete.remove(), 380);
                    showDeleteToast(data.message, 'deleted');
                }
            } else {
                showDeleteToast(data.message || 'Could not delete product.', 'error');
            }
        })
        .catch(err => {
            closeDeleteModal();
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = '<i class="fas fa-trash"></i> Yes, Delete';
            showDeleteToast('Connection failed. Check your internet and try again.', true);
            console.error('Delete fetch error:', err);
        });
    }

    // action: 'deleted' | 'deactivated' | 'error' | 'activated'
    function showDeleteToast(message, action) {
        const toast = document.getElementById('deleteToast');
        const inner = document.getElementById('deleteToastInner');
        const icon  = document.getElementById('deleteToastIcon');
        const title = document.getElementById('deleteToastTitle');
        const msg   = document.getElementById('deleteToastMsg');
        let timeoutMs = 5000;

        if (action === 'error') {
            inner.style.borderLeftColor = '#dc2626';
            icon.textContent  = '❌';
            title.style.color = '#7f1d1d';
            title.textContent = 'Error!';
            msg.style.color   = '#991b1b';
        } else if (action === 'deactivated') {
            inner.style.borderLeftColor = '#d97706'; // amber
            icon.textContent  = '⚠️';
            title.style.color = '#78350f';
            title.textContent = 'Deactivated (History Preserved)';
            msg.style.color   = '#92400e';
            timeoutMs = 8000; // stay longer — message is longer
        } else if (action === 'activated') {
            inner.style.borderLeftColor = '#16a34a'; // green
            icon.textContent  = '✅';
            title.style.color = '#14532d';
            title.textContent = 'Activated!';
            msg.style.color   = '#166534';
        } else {
            inner.style.borderLeftColor = '#16a34a';
            icon.textContent  = '🗑️';
            title.style.color = '#14532d';
            title.textContent = 'Permanently Deleted';
            msg.style.color   = '#166534';
        }
        msg.textContent = message;

        toast.classList.remove('dt-active');
        void toast.offsetWidth;
        toast.classList.add('dt-active');
        clearTimeout(toast._dtTimer);
        toast._dtTimer = setTimeout(() => toast.classList.remove('dt-active'), timeoutMs);
    }

    function ajaxActivateProduct(id, name, btn) {
        const tr = btn.closest('tr');
        const activateUrl = btn.dataset.activateUrl;
        
        btn.disabled = true;
        const originalIcon = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        fetch(activateUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfMeta ? csrfMeta.content : '',
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
        })
        .then(async res => {
            const text = await res.text();
            let data = {};
            try { data = JSON.parse(text); } catch(e) {
                data = { success: false, message: `Server error (HTTP ${res.status}).` };
            }

            btn.disabled = false;
            btn.innerHTML = originalIcon;

            if (res.ok && data.success) {
                const currentStatus = '{{ $currentStatus }}';
                if (currentStatus === 'inactive') {
                    // Fade out and remove row
                    tr.style.transition = 'opacity 0.35s ease, transform 0.35s ease';
                    tr.style.opacity    = '0';
                    tr.style.transform  = 'translateX(-30px)';
                    setTimeout(() => tr.remove(), 380);
                } else {
                    // Update row dynamically
                    tr.style.opacity = '1';
                    
                    const statusBadge = tr.querySelector('[data-status-badge]');
                    if (statusBadge) {
                        statusBadge.textContent = 'Active';
                        statusBadge.className = 'px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800';
                    }

                    // Swap the activation button back to a delete button
                    const actionContainer = tr.querySelector('[data-action-container]');
                    if (actionContainer) {
                        btn.remove();
                        const deleteBtn = document.createElement('button');
                        deleteBtn.type = 'button';
                        deleteBtn.onclick = function() {
                            confirmDeleteProduct(id, name, deleteBtn);
                        };
                        deleteBtn.dataset.deleteUrl = `{{ url('products') }}/${id}`;
                        deleteBtn.className = 'text-red-600 hover:text-red-900 cursor-pointer';
                        deleteBtn.title = 'Delete Product';
                        deleteBtn.innerHTML = '<i class="fas fa-trash"></i>';
                        actionContainer.appendChild(deleteBtn);
                    }
                }
                showDeleteToast(data.message, 'activated');
            } else {
                showDeleteToast(data.message || 'Could not activate product.', 'error');
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = originalIcon;
            showDeleteToast('Connection failed. Please check your internet.', 'error');
            console.error('Activate fetch error:', err);
        });
    }

    // Close modal on backdrop click
    document.getElementById('deleteModal').addEventListener('click', function(e) {
        if (e.target === this) closeDeleteModal();
    });
</script>
@endpush
@endsection