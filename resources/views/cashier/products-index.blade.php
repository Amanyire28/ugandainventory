@extends('layouts.cashier-layout')

@section('title', 'Search Products')

@section('page-title')
    <i class="fas fa-search text-green-600 mr-2"></i>Search Products
@endsection

@section('content')
<div class="space-y-6">
    
    <!-- Search & Filter -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Search -->
            <div class="md:col-span-2">
                <div class="relative">
                    <input type="text" 
                           id="liveSearchInput"
                           placeholder="Type to search by name, SKU, or barcode..." 
                           class="w-full px-4 py-3 pl-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                    <i class="fas fa-search absolute left-3 top-4 text-gray-400"></i>
                    <div id="searchSpinner" class="hidden absolute right-3 top-4">
                        <i class="fas fa-spinner fa-spin text-green-600"></i>
                    </div>
                </div>
                <p class="text-xs text-gray-500 mt-1">
                    <i class="fas fa-keyboard mr-1"></i>Press Ctrl+K to focus | Esc to clear
                </p>
            </div>

            <!-- Category Filter -->
            <div>
                <select id="liveCategoryFilter"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                    <option value="">All Categories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Results Count -->
        <div class="mt-4 text-sm font-semibold text-gray-700">
            <i class="fas fa-box text-green-600 mr-1"></i>
            <span id="resultsCount">{{ $products->total() }} products found</span>
        </div>
    </div>

    <!-- Products Table (Tabular Format) -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-6">
            <i class="fas fa-table text-green-600 mr-2"></i>Products List
        </h3>

        <div class="overflow-x-auto rounded-lg border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">Product Name</th>
                        <th scope="col" class="px-6 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">SKU</th>
                        <th scope="col" class="px-6 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">Category</th>
                        <th scope="col" class="px-6 py-3 text-right font-semibold text-gray-500 uppercase tracking-wider">Price</th>
                        <th scope="col" class="px-6 py-3 text-right font-semibold text-gray-500 uppercase tracking-wider">Stock Available</th>
                    </tr>
                </thead>
                <tbody id="productsTableBody" class="bg-white divide-y divide-gray-200 text-gray-700">
                    @forelse($products as $product)
                    <tr class="hover:bg-gray-50 transition cursor-pointer" onclick="window.location='{{ route('products.show', $product->id) }}'">
                        <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">
                            {{ $product->name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-500 font-mono">
                            {{ $product->sku ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-500">
                            {{ $product->category->name ?? 'Uncategorized' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right font-bold text-gray-900">
                            UGX {{ number_format($product->selling_price, 0) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <span class="px-2.5 py-1 text-xs font-bold rounded-full {{ $product->quantity < 10 ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
                                {{ number_format($product->quantity, 0) }} {{ $product->unit ?? 'pcs' }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-10 text-center text-gray-400">
                            <div class="flex flex-col items-center">
                                <i class="fas fa-search-minus text-4xl text-gray-300 mb-2"></i>
                                <p class="text-sm font-medium">No products found</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($products->hasPages())
        <div class="mt-6">
            {{ $products->links() }}
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
// Live Search Functionality
let searchTimeout = null;
const searchInput = document.getElementById('liveSearchInput');
const categoryFilter = document.getElementById('liveCategoryFilter');
const productsTableBody = document.getElementById('productsTableBody');
const resultsCount = document.getElementById('resultsCount');
const searchSpinner = document.getElementById('searchSpinner');

// Function to fetch products
async function fetchProducts() {
    const searchTerm = searchInput.value.trim();
    const categoryId = categoryFilter.value;

    // Show loading
    searchSpinner.classList.remove('hidden');
    productsTableBody.style.opacity = '0.5';

    try {
        const params = new URLSearchParams();
        if (searchTerm) params.append('search', searchTerm);
        if (categoryId) params.append('category_id', categoryId);
        params.append('ajax', '1');

        const response = await fetch('{{ route("products.index") }}?' + params.toString(), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await response.json();

        if (data.success) {
            productsTableBody.innerHTML = data.html;
            resultsCount.textContent = data.count + ' products found';
        }
    } catch (error) {
        console.error('Error:', error);
        productsTableBody.innerHTML = '<tr><td colspan="5" class="px-6 py-10 text-center text-red-500"><i class="fas fa-exclamation-triangle text-5xl mb-3"></i><p>Error loading products</p></td></tr>';
    } finally {
        searchSpinner.classList.add('hidden');
        productsTableBody.style.opacity = '1';
    }
}

// Search input - wait 300ms after user stops typing
searchInput.addEventListener('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(fetchProducts, 300);
});

// Category filter - instant update
categoryFilter.addEventListener('change', fetchProducts);

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        searchInput.focus();
    }
    if (e.key === 'Escape') {
        searchInput.value = '';
        categoryFilter.value = '';
        fetchProducts();
    }
});

// Auto-focus on load
window.addEventListener('load', () => searchInput.focus());
</script>
@endpush

@push('styles')
<style>
#productsTableBody {
    transition: opacity 0.3s ease;
}
</style>
@endpush