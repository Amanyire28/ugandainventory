@extends('layouts.app')

@section('title', 'Purchases')

@section('content')
<div class="bg-white rounded-xl shadow-lg p-6">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 space-y-4 md:space-y-0">
        <div>
            <h2 id="pageTitle" class="text-2xl font-bold text-gray-800">Purchase Records</h2>
            <p id="pageSubtitle" class="text-gray-500 text-sm mt-1">Track all stock purchases and supplier payments</p>
        </div>
        <a href="{{ route('purchases.create') }}"
           class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 shadow transition font-semibold text-sm">
            <i class="fas fa-plus mr-2"></i>Record Purchase
        </a>
    </div>

    {{-- Filter Tabs --}}
    <div class="flex space-x-2 mb-6 overflow-x-auto border-b pb-4">
        <a id="tab-all" href="{{ route('purchases.index') }}"
           class="tab-link px-4 py-2 rounded-lg whitespace-nowrap text-sm font-medium {{ !$status ? 'bg-indigo-600 text-white shadow' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}"
           data-tab="all">
            <i class="fas fa-list mr-1"></i>All Purchases
        </a>
        <a id="tab-unpaid" href="{{ route('purchases.index', ['status' => 'unpaid']) }}"
           class="tab-link px-4 py-2 rounded-lg whitespace-nowrap text-sm font-medium {{ $status === 'unpaid' ? 'bg-red-500 text-white shadow' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}"
           data-tab="unpaid">
            <i class="fas fa-hourglass-half mr-1"></i>Unpaid
        </a>
        <a id="tab-partial" href="{{ route('purchases.index', ['status' => 'partial']) }}"
           class="tab-link px-4 py-2 rounded-lg whitespace-nowrap text-sm font-medium {{ $status === 'partial' ? 'bg-yellow-500 text-white shadow' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}"
           data-tab="partial">
            <i class="fas fa-adjust mr-1"></i>Partially Paid
        </a>
        <a id="tab-paid" href="{{ route('purchases.index', ['status' => 'paid']) }}"
           class="tab-link px-4 py-2 rounded-lg whitespace-nowrap text-sm font-medium {{ $status === 'paid' ? 'bg-green-600 text-white shadow' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}"
           data-tab="paid">
            <i class="fas fa-check-circle mr-1"></i>Fully Paid
        </a>
    </div>

    {{-- Search --}}
    <div class="mb-5 max-w-md">
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <i class="fas fa-search text-gray-400"></i>
            </div>
            <input type="text" id="liveSearchInput"
                   class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm transition"
                   placeholder="Search by purchase #, supplier or notes..."
                   value="{{ $search ?? '' }}">
        </div>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-5 rounded-r-lg flex items-center">
            <i class="fas fa-check-circle mr-3 text-green-500"></i>{{ session('success') }}
        </div>
    @elseif(session('error'))
        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-5 rounded-r-lg flex items-center">
            <i class="fas fa-exclamation-circle mr-3 text-red-500"></i>{{ session('error') }}
        </div>
    @endif

    {{-- Table Container --}}
    <div id="purchasesTable">
        @include('purchases.partials.table', ['purchases' => $purchases])
    </div>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const input     = document.getElementById('liveSearchInput');
    const container = document.getElementById('purchasesTable');
    const tabs      = document.querySelectorAll('.tab-link');
    let currentUrl  = '{{ request()->fullUrl() }}';
    let debounce;

    const tabInfo = {
        'all':     { label: 'All Purchases',       activeClass: 'bg-indigo-600 text-white shadow' },
        'unpaid':  { label: 'Unpaid Purchases',     activeClass: 'bg-red-500 text-white shadow' },
        'partial': { label: 'Partially Paid',       activeClass: 'bg-yellow-500 text-white shadow' },
        'paid':    { label: 'Fully Paid Purchases', activeClass: 'bg-green-600 text-white shadow' },
    };

    function loadTab(url, tabId, pushState = true) {
        currentUrl = url;
        container.innerHTML = `
            <div class="flex flex-col items-center justify-center py-12">
                <svg class="animate-spin h-10 w-10 text-indigo-500 mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="text-sm text-gray-500 font-medium">Loading...</p>
            </div>`;

        if (input) input.value = '';

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(data => {
                container.innerHTML = data.html;
                // Update active tab style
                tabs.forEach(t => {
                    const tid = t.dataset.tab;
                    const info = tabInfo[tid];
                    t.className = t.className.replace(/(bg-\w+-\d+\s*text-white\s*shadow|bg-gray-100\s*text-gray-700\s*hover:bg-gray-200)/g, '').trim();
                    if (tid === tabId) {
                        t.classList.add(...info.activeClass.split(' '));
                    } else {
                        t.classList.add('bg-gray-100', 'text-gray-700', 'hover:bg-gray-200');
                    }
                });
                if (pushState) history.pushState({ tabId, url }, '', url);
            })
            .catch(() => {
                container.innerHTML = '<p class="text-center text-red-500 py-8">Failed to load. Please refresh.</p>';
            });
    }

    // Tab click handlers
    tabs.forEach(tab => {
        tab.addEventListener('click', function (e) {
            e.preventDefault();
            loadTab(this.href, this.dataset.tab);
        });
    });

    // Live search
    if (input) {
        input.addEventListener('input', function () {
            clearTimeout(debounce);
            debounce = setTimeout(() => {
                const base = currentUrl.split('?')[0];
                const params = new URLSearchParams(new URL(currentUrl, location.origin).search);
                if (this.value.trim()) {
                    params.set('search', this.value.trim());
                } else {
                    params.delete('search');
                }
                const searchUrl = base + (params.toString() ? '?' + params.toString() : '');
                const activeTab = document.querySelector('.tab-link[class*="text-white"]')?.dataset.tab ?? 'all';
                loadTab(searchUrl, activeTab, false);
            }, 350);
        });
    }

    // Browser back/forward
    window.addEventListener('popstate', function (e) {
        if (e.state?.url) {
            loadTab(e.state.url, e.state.tabId, false);
        }
    });
});
</script>
@endpush
