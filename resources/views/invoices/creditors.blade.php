@extends('layouts.app')

@section('title', 'Creditors')

@section('page-title')
    <i class="fas fa-user-clock text-indigo-600 mr-2"></i>Creditors
@endsection

@section('content')
<div class="bg-white rounded-xl shadow-lg p-6">
    
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 space-y-4 md:space-y-0">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Creditor List</h2>
            <p class="text-gray-600 text-sm mt-1">Review customers who currently owe outstanding credit balances</p>
        </div>
    </div>

    <!-- Filter Tabs (Uniform) -->
    <div class="flex space-x-2 mb-6 overflow-x-auto border-b pb-4">
        <a href="{{ route('invoices.index') }}" class="px-4 py-2 rounded-lg whitespace-nowrap bg-gray-100 text-gray-700 hover:bg-gray-200">
            <i class="fas fa-list mr-1"></i>All Invoices
        </a>
        <a href="{{ route('invoices.paid') }}" class="px-4 py-2 rounded-lg whitespace-nowrap bg-gray-100 text-gray-700 hover:bg-gray-200">
            <i class="fas fa-check-circle mr-1"></i>Paid Invoices
        </a>
        <a href="{{ route('invoices.unpaid') }}" class="px-4 py-2 rounded-lg whitespace-nowrap bg-gray-100 text-gray-700 hover:bg-gray-200">
            <i class="fas fa-hourglass-half mr-1"></i>Unpaid Invoices
        </a>
        <a href="{{ route('invoices.customersWithInvoices') }}" class="px-4 py-2 rounded-lg whitespace-nowrap bg-gray-100 text-gray-700 hover:bg-gray-200">
            <i class="fas fa-users mr-1"></i>Customers with Invoices
        </a>
        <a href="{{ route('invoices.creditors') }}" class="px-4 py-2 rounded-lg whitespace-nowrap bg-indigo-600 text-white font-semibold shadow">
            <i class="fas fa-user-clock mr-1"></i>Creditors
        </a>
    </div>

    <div id="creditorsTable">
        @include('invoices.partials.creditors-table', ['customers' => $customers])
    </div>
</div>
@endsection