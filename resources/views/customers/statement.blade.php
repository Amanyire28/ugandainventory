@extends('layouts.app')

@section('title', 'Customer Statement - ' . $customer->name)

@section('page-title')
    <a href="{{ route('customers.show', $customer->id) }}" class="text-indigo-600 hover:text-indigo-900 mr-2">
        <i class="fas fa-arrow-left"></i>
    </a>
    <i class="fas fa-file-invoice-dollar text-indigo-600 mr-2"></i>Statement: {{ $customer->name }}
@endsection

@section('content')
<div class="space-y-6">
    <!-- Stat Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Outstanding Balance -->
        <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-red-100 text-sm font-medium uppercase tracking-wider">Outstanding Balance</p>
                    <h3 class="text-3xl font-extrabold mt-1">UGX {{ number_format($currentBalance, 0) }}</h3>
                </div>
                <div class="bg-red-400 bg-opacity-30 rounded-lg p-3">
                    <i class="fas fa-wallet text-2xl"></i>
                </div>
            </div>
            <div class="mt-4 text-red-100 text-xs flex items-center">
                <i class="fas fa-exclamation-circle mr-1.5"></i>
                <span>Total amount customer owes the business</span>
            </div>
        </div>

        <!-- Total Invoiced -->
        <div class="bg-white rounded-xl shadow p-6 border-l-4 border-indigo-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-xs font-semibold uppercase tracking-wider">Total Credit Purchases</p>
                    <h3 class="text-2xl font-bold text-gray-800 mt-1">UGX {{ number_format($transactions->sum('debit'), 0) }}</h3>
                </div>
                <div class="bg-indigo-100 rounded-lg p-3 text-indigo-600">
                    <i class="fas fa-file-invoice text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Total Paid -->
        <div class="bg-white rounded-xl shadow p-6 border-l-4 border-emerald-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-xs font-semibold uppercase tracking-wider">Total Payments Made</p>
                    <h3 class="text-2xl font-bold text-gray-800 mt-1">UGX {{ number_format($transactions->sum('credit'), 0) }}</h3>
                </div>
                <div class="bg-emerald-100 rounded-lg p-3 text-emerald-600">
                    <i class="fas fa-hand-holding-usd text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Statement Ledger -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-xl font-bold text-gray-800">Account Statement</h2>
                <p class="text-gray-600 text-sm mt-0.5">Audit log of all credit sales and payments for {{ $customer->name }}</p>
            </div>
            <button onclick="window.print()" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition flex items-center">
                <i class="fas fa-print mr-2"></i>Print Statement
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Transaction Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Debit (+)</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Credit (-)</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Outstanding Balance</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($transactions as $tx)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $tx->created_at->format('M d, Y h:i A') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2.5 py-1 text-xs font-bold rounded-full 
                                    @if ($tx->transaction_type === 'INVOICE')
                                        bg-red-100 text-red-800
                                    @elseif ($tx->transaction_type === 'PAYMENT')
                                        bg-emerald-100 text-emerald-800
                                    @else
                                        bg-gray-100 text-gray-800
                                    @endif">
                                    {{ $tx->transaction_type }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                {{ $tx->notes }}
                                @if($tx->invoice)
                                    <a href="{{ route('invoices.show', $tx->invoice_id) }}" class="text-indigo-600 hover:text-indigo-900 ml-1 font-mono text-xs">
                                        ({{ $tx->invoice->invoice_number }})
                                    </a>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-semibold text-red-600">
                                {{ $tx->debit > 0 ? 'UGX ' . number_format($tx->debit, 0) : '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-semibold text-emerald-600">
                                {{ $tx->credit > 0 ? 'UGX ' . number_format($tx->credit, 0) : '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-bold text-gray-900">
                                UGX {{ number_format($tx->balance, 0) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-gray-400">
                                <div class="flex flex-col items-center">
                                    <i class="fas fa-file-invoice text-4xl text-gray-300 mb-2"></i>
                                    <p class="text-sm font-medium">No ledger transactions found for this customer.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
