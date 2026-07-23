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

    <!-- Creditors Table -->
    <div class="overflow-x-auto rounded-xl border border-gray-200">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">Customer Name</th>
                    <th scope="col" class="px-6 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">Phone Number</th>
                    <th scope="col" class="px-6 py-3 text-center font-semibold text-gray-500 uppercase tracking-wider">Pending Invoices</th>
                    <th scope="col" class="px-6 py-3 text-right font-semibold text-gray-500 uppercase tracking-wider">Amount Owed</th>
                    <th scope="col" class="px-6 py-3 text-center font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200 text-gray-700">
                @forelse($customers as $customer)
                    @php
                        $pendingCount = $customer->invoices->count();
                        $outstanding = $customer->invoices->sum(fn($inv) => $inv->total - $inv->paid);
                    @endphp
                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                        <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-amber-50 rounded-full flex items-center justify-center mr-3 text-amber-600 font-bold text-xs">
                                    {{ substr($customer->name, 0, 1) }}
                                </div>
                                <span class="font-semibold">{{ $customer->name }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-500">
                            {{ $customer->phone ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center font-semibold text-gray-800">
                            {{ $pendingCount }} {{ Str::plural('invoice', $pendingCount) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right font-black text-red-600">
                            UGX {{ number_format($outstanding) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <a href="{{ route('invoices.customerSummary', $customer->id) }}" 
                               class="inline-flex items-center px-4 py-1.5 bg-indigo-50 text-indigo-600 hover:bg-indigo-100 rounded-lg text-xs font-bold transition duration-150">
                                <i class="fas fa-eye mr-1.5"></i> View Ledger
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-10 text-center text-gray-500">
                            <div class="flex flex-col items-center">
                                <i class="fas fa-smile text-4xl text-gray-300 mb-2"></i>
                                <p class="text-sm font-medium">Excellent! No creditors outstanding.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection