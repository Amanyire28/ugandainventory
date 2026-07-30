@extends('layouts.app')

@section('title', 'Supplier Statement - ' . $supplier->name)

@section('page-title')
    <a href="{{ route('suppliers.index') }}" class="text-indigo-600 hover:text-indigo-900 mr-2">
        <i class="fas fa-arrow-left"></i>
    </a>
    <i class="fas fa-file-invoice-dollar text-indigo-600 mr-2"></i>Statement: {{ $supplier->name }}
@endsection

@section('content')
<div class="space-y-6">
    <!-- Stat Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Outstanding Debt -->
        <div class="bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-amber-100 text-sm font-medium uppercase tracking-wider">Outstanding Debt</p>
                    <h3 class="text-3xl font-extrabold mt-1">UGX {{ number_format($currentBalance, 0) }}</h3>
                </div>
                <div class="bg-amber-400 bg-opacity-30 rounded-lg p-3">
                    <i class="fas fa-handshake text-2xl"></i>
                </div>
            </div>
            <div class="mt-4 text-amber-100 text-xs flex items-center">
                <i class="fas fa-exclamation-circle mr-1.5"></i>
                <span>Total amount business owes this supplier</span>
            </div>
        </div>

        <!-- Total Invoices / Bills -->
        <div class="bg-white rounded-xl shadow p-6 border-l-4 border-indigo-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-xs font-semibold uppercase tracking-wider">Total Bills (Purchases)</p>
                    <h3 class="text-2xl font-bold text-gray-800 mt-1">UGX {{ number_format($transactions->sum('credit'), 0) }}</h3>
                </div>
                <div class="bg-indigo-100 rounded-lg p-3 text-indigo-600">
                    <i class="fas fa-file-invoice-dollar text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Total Payments Made to Supplier -->
        <div class="bg-white rounded-xl shadow p-6 border-l-4 border-emerald-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-xs font-semibold uppercase tracking-wider">Total Payments Billed</p>
                    <h3 class="text-2xl font-bold text-gray-800 mt-1">UGX {{ number_format($transactions->sum('debit'), 0) }}</h3>
                </div>
                <div class="bg-emerald-100 rounded-lg p-3 text-emerald-600">
                    <i class="fas fa-receipt text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Statement Ledger -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-xl font-bold text-gray-800">Supplier Ledger Statement</h2>
                <p class="text-gray-600 text-sm mt-0.5">Audit log of all supplier bills, purchase invoices and payments made to {{ $supplier->name }}</p>
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
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Debit (-) (Our Payments)</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Credit (+) (Supplier Bills)</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Outstanding Debt</th>
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
                                    @if ($tx->transaction_type === 'PURCHASE')
                                        bg-amber-100 text-amber-800
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
                                @if($tx->purchase_id)
                                    <span class="text-gray-500 ml-1 font-mono text-xs">
                                        (Purchase ID: #{{ $tx->purchase_id }})
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-semibold text-emerald-600">
                                {{ $tx->debit > 0 ? 'UGX ' . number_format($tx->debit, 0) : '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-semibold text-red-600">
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
                                    <p class="text-sm font-medium">No ledger transactions found for this supplier.</p>
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
