@extends('layouts.app')

@section('title', 'Cash Flow Statement')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-3xl font-bold text-gray-900">
            <i class="fas fa-money-bill-wave text-emerald-600 mr-2"></i>Cash Flow Statement
        </h1>
        <div class="flex space-x-2">
            <a href="{{ route('profit.index') }}" class="px-4 py-2 bg-indigo-50 text-indigo-700 font-semibold rounded-lg border border-indigo-200 hover:bg-indigo-100 transition">
                <i class="fas fa-chart-line mr-1.5"></i>Profit & Loss Report
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow p-6">
        <form method="GET" action="{{ route('profit.cash-flow') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Start Date</label>
                <input type="date" name="start_date" value="{{ $startDate }}"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">End Date</label>
                <input type="date" name="end_date" value="{{ $endDate }}"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500">
            </div>
            <div>
                <button type="submit" class="w-full px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-lg shadow transition flex items-center justify-center">
                    <i class="fas fa-filter mr-2"></i>Filter Cash Flow
                </button>
            </div>
        </form>
    </div>

    <!-- Cash Flow Summary Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Cash In -->
        <div class="bg-white border-t-4 border-emerald-500 rounded-xl shadow p-6">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-xs font-semibold uppercase tracking-wider">Total Cash Inflow</p>
                    <h3 class="text-3xl font-extrabold text-emerald-600 mt-1">UGX {{ number_format($totalCashIn, 0) }}</h3>
                </div>
                <div class="bg-emerald-100 rounded-lg p-3 text-emerald-600">
                    <i class="fas fa-arrow-down text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Cash Out -->
        <div class="bg-white border-t-4 border-red-500 rounded-xl shadow p-6">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-xs font-semibold uppercase tracking-wider">Total Cash Outflow</p>
                    <h3 class="text-3xl font-extrabold text-red-600 mt-1">UGX {{ number_format($totalCashOut, 0) }}</h3>
                </div>
                <div class="bg-red-100 rounded-lg p-3 text-red-600">
                    <i class="fas fa-arrow-up text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Net Cash Flow -->
        <div class="bg-gradient-to-br @if($netCashFlow >= 0) from-emerald-500 to-teal-600 @else from-red-500 to-rose-600 @endif rounded-xl shadow-lg p-6 text-white">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-white text-opacity-80 text-sm font-semibold uppercase tracking-wider">Net Cash Flow</p>
                    <h3 class="text-3xl font-extrabold mt-1">UGX {{ number_format($netCashFlow, 0) }}</h3>
                </div>
                <div class="bg-white bg-opacity-20 rounded-lg p-3">
                    <i class="fas fa-wallet text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Statement Details Layout -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Cash Inflows -->
        <div class="bg-white rounded-xl shadow p-6">
            <h3 class="text-lg font-bold text-gray-800 border-b pb-4 mb-4 flex items-center">
                <i class="fas fa-arrow-alt-circle-down text-emerald-600 mr-2"></i>Cash Inflows (Sources of Cash)
            </h3>
            <ul class="space-y-4">
                <li class="flex justify-between items-center p-3 hover:bg-gray-50 rounded-lg transition">
                    <div>
                        <p class="font-semibold text-gray-800 text-sm">Direct Cash Sales</p>
                        <p class="text-gray-500 text-xs mt-0.5">Sales made at the POS and paid directly</p>
                    </div>
                    <span class="font-bold text-gray-900 text-sm">UGX {{ number_format($salesCashPayments, 0) }}</span>
                </li>
                <li class="flex justify-between items-center p-3 hover:bg-gray-50 rounded-lg transition">
                    <div>
                        <p class="font-semibold text-gray-800 text-sm">Customer Credit Invoice Payments</p>
                        <p class="text-gray-500 text-xs mt-0.5">Cash collected for previously issued invoices</p>
                    </div>
                    <span class="font-bold text-gray-900 text-sm">UGX {{ number_format($invoicePayments, 0) }}</span>
                </li>
                <li class="border-t pt-4 flex justify-between items-center px-3 mt-4">
                    <span class="font-bold text-gray-800">Total Cash Inflow</span>
                    <span class="font-extrabold text-emerald-600">UGX {{ number_format($totalCashIn, 0) }}</span>
                </li>
            </ul>
        </div>

        <!-- Cash Outflows -->
        <div class="bg-white rounded-xl shadow p-6">
            <h3 class="text-lg font-bold text-gray-800 border-b pb-4 mb-4 flex items-center">
                <i class="fas fa-arrow-alt-circle-up text-red-600 mr-2"></i>Cash Outflows (Uses of Cash)
            </h3>
            <ul class="space-y-4">
                <li class="flex justify-between items-center p-3 hover:bg-gray-50 rounded-lg transition">
                    <div>
                        <p class="font-semibold text-gray-800 text-sm">Business Expenses Paid</p>
                        <p class="text-gray-500 text-xs mt-0.5">Rent, utility, staff wages, etc. paid directly</p>
                    </div>
                    <span class="font-bold text-gray-900 text-sm">UGX {{ number_format($expenses, 0) }}</span>
                </li>
                <li class="flex justify-between items-center p-3 hover:bg-gray-50 rounded-lg transition">
                    <div>
                        <p class="font-semibold text-gray-800 text-sm">Supplier Billed Payments</p>
                        <p class="text-gray-500 text-xs mt-0.5">Payments made to suppliers for credit stock purchases</p>
                    </div>
                    <span class="font-bold text-gray-900 text-sm">UGX {{ number_format($supplierPayments, 0) }}</span>
                </li>
                <li class="border-t pt-4 flex justify-between items-center px-3 mt-4">
                    <span class="font-bold text-gray-800">Total Cash Outflow</span>
                    <span class="font-extrabold text-red-600">UGX {{ number_format($totalCashOut, 0) }}</span>
                </li>
            </ul>
        </div>
    </div>
</div>
@endsection
