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
            <h2 class="text-2xl font-bold text-gray-800">Invoice List</h2>
            <p class="text-gray-600 text-sm mt-1">Manage and track customer credit invoices</p>
        </div>
        <div>
            <a href="{{ route('pos.index') }}" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 flex items-center shadow transition duration-150">
                <i class="fas fa-plus mr-2"></i>New Credit Sale (POS)
            </a>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="flex space-x-2 mb-6 overflow-x-auto border-b pb-4">
        <a href="{{ route('invoices.index') }}" class="px-4 py-2 rounded-lg whitespace-nowrap {{ !$status ? 'bg-indigo-600 text-white font-semibold shadow' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
            <i class="fas fa-list mr-1"></i>All Invoices
        </a>
        <a href="{{ route('invoices.paid') }}" class="px-4 py-2 rounded-lg whitespace-nowrap {{ $status === 'paid' ? 'bg-green-600 text-white font-semibold shadow' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
            <i class="fas fa-check-circle mr-1"></i>Paid Invoices
        </a>
        <a href="{{ route('invoices.unpaid') }}" class="px-4 py-2 rounded-lg whitespace-nowrap {{ $status === 'unpaid' ? 'bg-yellow-500 text-white font-semibold shadow' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
            <i class="fas fa-hourglass-half mr-1"></i>Unpaid Invoices
        </a>
        <a href="{{ route('invoices.customersWithInvoices') }}" class="px-4 py-2 rounded-lg whitespace-nowrap bg-gray-100 text-gray-700 hover:bg-gray-200">
            <i class="fas fa-users mr-1"></i>Customers with Invoices
        </a>
        <a href="{{ route('invoices.creditors') }}" class="px-4 py-2 rounded-lg whitespace-nowrap bg-gray-100 text-gray-700 hover:bg-gray-200">
            <i class="fas fa-user-clock mr-1"></i>Creditors
        </a>
    </div>

    <!-- Search -->
    <div class="mb-6 max-w-md">
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
        @include('invoices.partials.table', ['invoices' => $invoices])
    </div>
</div>

{{-- Vanilla JS for AJAX live search --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const input = document.getElementById('liveSearchInput');
        const invoicesTable = document.getElementById('invoicesTable');
        let timeout = null;

        input.addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(function() {
                let search = input.value;
                let url = '{{ $status === "paid" ? route("invoices.paid") : ($status === "unpaid" ? route("invoices.unpaid") : route("invoices.index")) }}';
                fetch(url + '?search=' + encodeURIComponent(search), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(response => response.json())
                .then(data => { invoicesTable.innerHTML = data.html; });
            }, 350);
        });
    });
</script>
@endsection