@extends('layouts.app')

@section('title', 'Invoices')

@section('page-title')
    <i class="fas fa-file-invoice-dollar text-indigo-600 mr-2"></i>Invoices
@endsection

@section('content')
<div class="bg-white rounded-xl shadow-lg p-6">
    
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 space-y-4 md:space-y-0">
        <div>
            <h2 id="pageTitle" class="text-2xl font-bold text-gray-800">
                @if(($status ?? '') === 'paid') Paid Invoices
                @elseif(($status ?? '') === 'unpaid') Unpaid Invoices
                @elseif(($status ?? '') === 'customers') Customer Ledgers
                @elseif(($status ?? '') === 'creditors') Creditor List
                @else Invoice List @endif
            </h2>
            <p id="pageSubtitle" class="text-gray-600 text-sm mt-1">
                @if(($status ?? '') === 'paid') Review invoices that are fully cleared
                @elseif(($status ?? '') === 'unpaid') Review invoices that have outstanding balances
                @elseif(($status ?? '') === 'customers') Review financial summaries for customers with invoices
                @elseif(($status ?? '') === 'creditors') Review customers who currently owe outstanding credit balances
                @else Manage and track customer credit invoices @endif
            </p>
        </div>
        <div>
            <a href="{{ route('pos.index') }}" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 flex items-center shadow transition duration-150">
                <i class="fas fa-plus mr-2"></i>New Credit Sale (POS)
            </a>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="flex space-x-2 mb-6 overflow-x-auto border-b pb-4">
        <a id="tab-all" href="{{ route('invoices.index') }}" class="tab-link px-4 py-2 rounded-lg whitespace-nowrap {{ !$status || $status === 'all' ? 'bg-indigo-600 text-white font-semibold shadow' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}" data-tab="all">
            <i class="fas fa-list mr-1"></i>All Invoices
        </a>
        <a id="tab-paid" href="{{ route('invoices.paid') }}" class="tab-link px-4 py-2 rounded-lg whitespace-nowrap {{ $status === 'paid' ? 'bg-green-600 text-white font-semibold shadow' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}" data-tab="paid">
            <i class="fas fa-check-circle mr-1"></i>Paid Invoices
        </a>
        <a id="tab-unpaid" href="{{ route('invoices.unpaid') }}" class="tab-link px-4 py-2 rounded-lg whitespace-nowrap {{ $status === 'unpaid' ? 'bg-yellow-500 text-white font-semibold shadow' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}" data-tab="unpaid">
            <i class="fas fa-hourglass-half mr-1"></i>Unpaid Invoices
        </a>
        <a id="tab-customers" href="{{ route('invoices.customersWithInvoices') }}" class="tab-link px-4 py-2 rounded-lg whitespace-nowrap {{ $status === 'customers' ? 'bg-indigo-600 text-white font-semibold shadow' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}" data-tab="customers">
            <i class="fas fa-users mr-1"></i>Customers with Invoices
        </a>
        <a id="tab-creditors" href="{{ route('invoices.creditors') }}" class="tab-link px-4 py-2 rounded-lg whitespace-nowrap {{ $status === 'creditors' ? 'bg-indigo-600 text-white font-semibold shadow' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}" data-tab="creditors">
            <i class="fas fa-user-clock mr-1"></i>Creditors
        </a>
    </div>

    <!-- Search -->
    <div id="searchContainer" class="mb-6 max-w-md {{ in_array(($status ?? ''), ['customers', 'creditors']) ? 'hidden' : '' }}">
        <label for="liveSearchInput" class="sr-only">Search</label>
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <i class="fas fa-search text-gray-400"></i>
            </div>
            <input
                type="text"
                id="liveSearchInput"
                class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm transition"
                placeholder="Search by invoice number or customer name...">
        </div>
    </div>

    {{-- Notifications --}}
    @if (session('success'))
        <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-r-lg shadow-sm flex items-center">
            <i class="fas fa-check-circle mr-3 text-lg text-green-500"></i>
            <span>{{ session('success') }}</span>
        </div>
    @elseif (session('error'))
        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-r-lg shadow-sm flex items-center">
            <i class="fas fa-exclamation-circle mr-3 text-lg text-red-500"></i>
            <span>{{ session('error') }}</span>
        </div>
    @elseif (session('info'))
        <div class="bg-blue-50 border-l-4 border-blue-500 text-blue-700 p-4 mb-6 rounded-r-lg shadow-sm flex items-center">
            <i class="fas fa-info-circle mr-3 text-lg text-blue-500"></i>
            <span>{{ session('info') }}</span>
        </div>
    @endif

    {{-- Table results --}}
    <div id="invoicesTable" class="overflow-x-auto">
        @if(($status ?? '') === 'customers')
            @include('invoices.partials.customers-table', ['customers' => $customers])
        @elseif(($status ?? '') === 'creditors')
            @include('invoices.partials.creditors-table', ['customers' => $customers])
        @else
            @include('invoices.partials.table', ['invoices' => $invoices])
        @endif
    </div>
</div>

@php
    $currentPath = request()->path();
    $initialTab = 'all';
    if (str_contains($currentPath, 'paid')) $initialTab = 'paid';
    elseif (str_contains($currentPath, 'unpaid')) $initialTab = 'unpaid';
    elseif (str_contains($currentPath, 'customers')) $initialTab = 'customers';
    elseif (str_contains($currentPath, 'creditors')) $initialTab = 'creditors';
@endphp

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const input = document.getElementById('liveSearchInput');
        const invoicesTable = document.getElementById('invoicesTable');
        let timeout = null;
        let currentTabUrl = '{{ request()->fullUrl() }}';
        let initialTab = '{{ $initialTab }}';

        const tabInfo = {
            'all': {
                title: 'Invoice List',
                subtitle: 'Manage and track customer credit invoices',
                search: true,
                activeClass: 'bg-indigo-600 text-white font-semibold shadow'
            },
            'paid': {
                title: 'Paid Invoices',
                subtitle: 'Review invoices that are fully cleared',
                search: true,
                activeClass: 'bg-green-600 text-white font-semibold shadow'
            },
            'unpaid': {
                title: 'Unpaid Invoices',
                subtitle: 'Review invoices that have outstanding balances',
                search: true,
                activeClass: 'bg-yellow-500 text-white font-semibold shadow'
            },
            'customers': {
                title: 'Customer Ledgers',
                subtitle: 'Review financial summaries for customers with invoices',
                search: false,
                activeClass: 'bg-indigo-600 text-white font-semibold shadow'
            },
            'creditors': {
                title: 'Creditor List',
                subtitle: 'Review customers who currently owe outstanding credit balances',
                search: false,
                activeClass: 'bg-indigo-600 text-white font-semibold shadow'
            }
        };

        // Initialize state history
        history.replaceState({ tabId: initialTab, url: currentTabUrl }, '', currentTabUrl);

        function switchTab(tabId, url, pushToHistory = true) {
            currentTabUrl = url;

            // Show spinner inside container
            invoicesTable.innerHTML = `
                <div class="flex flex-col items-center justify-center py-12">
                    <svg class="animate-spin h-10 w-10 text-indigo-600 mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <p class="text-sm text-gray-500 font-semibold">Loading data...</p>
                </div>
            `;

            // Reset search input value
            if (input) input.value = '';

            fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(response => response.json())
            .then(data => {
                invoicesTable.innerHTML = data.html;

                const info = tabInfo[tabId];
                if (info) {
                    document.getElementById('pageTitle').textContent = info.title;
                    document.getElementById('pageSubtitle').textContent = info.subtitle;

                    const searchContainer = document.getElementById('searchContainer');
                    if (searchContainer) {
                        if (info.search) {
                            searchContainer.classList.remove('hidden');
                        } else {
                            searchContainer.classList.add('hidden');
                        }
                    }

                    // Update Tab classes
                    document.querySelectorAll('.tab-link').forEach(link => {
                        const linkTab = link.getAttribute('data-tab');
                        const linkInfo = tabInfo[linkTab];
                        
                        link.className = 'tab-link px-4 py-2 rounded-lg whitespace-nowrap bg-gray-100 text-gray-700 hover:bg-gray-200';
                        if (linkTab === tabId) {
                            link.className = 'tab-link px-4 py-2 rounded-lg whitespace-nowrap ' + linkInfo.activeClass;
                        }
                    });
                }

                if (pushToHistory) {
                    history.pushState({ tabId: tabId, url: url }, '', url);
                }
            })
            .catch(error => {
                console.error('Error switching tab:', error);
                invoicesTable.innerHTML = `
                    <div class="text-center py-10 text-red-500">
                        <i class="fas fa-exclamation-triangle text-3xl mb-2"></i>
                        <p class="font-semibold">Failed to load data. Please refresh the page.</p>
                    </div>
                `;
            });
        }

        // Attach click handlers to tabs
        document.querySelectorAll('.tab-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const tabId = this.getAttribute('data-tab');
                const url = this.getAttribute('href');
                switchTab(tabId, url, true);
            });
        });

        // Search handler
        if (input) {
            input.addEventListener('input', function() {
                clearTimeout(timeout);
                timeout = setTimeout(function() {
                    let search = input.value;
                    const baseUrl = currentTabUrl.split('?')[0];
                    fetch(baseUrl + '?search=' + encodeURIComponent(search), {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(response => response.json())
                    .then(data => { invoicesTable.innerHTML = data.html; });
                }, 350);
            });
        }

        // Popstate handler for back/forward navigation
        window.addEventListener('popstate', function(e) {
            if (e.state && e.state.tabId && e.state.url) {
                switchTab(e.state.tabId, e.state.url, false);
            }
        });
    });
</script>
@endsection