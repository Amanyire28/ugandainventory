@extends('layouts.app')

@section('title', 'Profit Report')

@section('content')
<div class="space-y-6">
    
    <div class="flex items-center justify-between">
        <h1 class="text-3xl font-bold text-gray-900">
            <i class="fas fa-chart-pie text-indigo-600 mr-2"></i>Profit Report
        </h1>
        <div class="flex items-center space-x-3">
            <a href="{{ route('profit.cash-flow') }}" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-lg shadow transition flex items-center">
                <i class="fas fa-money-bill-wave mr-2"></i>Cash Flow Statement
            </a>
            @if($hasFilters)
            <span class="px-4 py-2 bg-blue-100 text-blue-800 rounded-lg text-sm font-semibold">
                <i class="fas fa-filter mr-1"></i>Filtered Results
            </span>
            @else
            <span class="px-4 py-2 bg-green-100 text-green-800 rounded-lg text-sm font-semibold">
                <i class="fas fa-infinity mr-1"></i>All Time Data
            </span>
            @endif
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <form method="GET" action="{{ route('profit.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Start Date</label>
                <input type="date" name="start_date" value="{{ $startDate }}"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">End Date</label>
                <input type="date" name="end_date" value="{{ $endDate }}"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Filter by Cashier</label>
                <select name="cashier_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                    <option value="">All Cashiers</option>
                    @foreach($cashiers as $cashier)
                        <option value="{{ $cashier->id }}" {{ $selectedCashierId == $cashier->id ?  'selected' : '' }}>
                            {{ $cashier->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end space-x-2">
                <button type="submit" class="flex-1 px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                    <i class="fas fa-filter mr-2"></i>Filter
                </button>
                <a href="{{ route('profit.index') }}" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                    <i class="fas fa-redo"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Stats Cards - 6 Cards (2 rows x 3 cols) -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Revenue -->
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm">Total Revenue</p>
                    <p class="text-2xl font-bold mt-2">UGX {{ number_format($cardRevenue, 0) }}</p>
                </div>
                <div class="bg-white bg-opacity-20 rounded-full p-4">
                    <i class="fas fa-money-bill-wave text-3xl"></i>
                </div>
            </div>
        </div>

        <!-- Cost -->
        <div class="bg-gradient-to-br from-red-500 to-red-600 text-white rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-red-100 text-sm">Total Cost (COGS)</p>
                    <p class="text-2xl font-bold mt-2">UGX {{ number_format($cardCost, 0) }}</p>
                </div>
                <div class="bg-white bg-opacity-20 rounded-full p-4">
                    <i class="fas fa-receipt text-3xl"></i>
                </div>
            </div>
        </div>

        <!-- Gross Profit -->
        <div class="bg-gradient-to-br from-teal-500 to-teal-600 text-white rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-teal-100 text-sm">Gross Profit</p>
                    <p class="text-2xl font-bold mt-2">UGX {{ number_format($cardGrossProfit, 0) }}</p>
                    <p class="text-xs text-teal-100 mt-1">Before expenses</p>
                </div>
                <div class="bg-white bg-opacity-20 rounded-full p-4">
                    <i class="fas fa-chart-bar text-3xl"></i>
                </div>
            </div>
        </div>

        <!-- Expenses -->
        <div class="bg-gradient-to-br from-orange-500 to-orange-600 text-white rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-orange-100 text-sm">Total Expenses</p>
                    <p class="text-2xl font-bold mt-2">UGX {{ number_format($cardExpenses, 0) }}</p>
                </div>
                <div class="bg-white bg-opacity-20 rounded-full p-4">
                    <i class="fas fa-wallet text-3xl"></i>
                </div>
            </div>
        </div>

        <!-- Net Profit -->
        <div class="bg-gradient-to-br from-green-500 to-green-600 text-white rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100 text-sm">Net Profit</p>
                    <p class="text-2xl font-bold mt-2">UGX {{ number_format($cardProfit, 0) }}</p>
                    <p class="text-xs text-green-100 mt-1">After expenses</p>
                </div>
                <div class="bg-white bg-opacity-20 rounded-full p-4">
                    <i class="fas fa-chart-line text-3xl"></i>
                </div>
            </div>
        </div>

        <!-- Profit Margin -->
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 text-white rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 text-sm">Profit Margin</p>
                    <p class="text-3xl font-bold mt-2">{{ number_format($cardMargin, 1) }}%</p>
                    <p class="text-xs text-purple-100 mt-1">Net margin</p>
                </div>
                <div class="bg-white bg-opacity-20 rounded-full p-4">
                    <i class="fas fa-percentage text-3xl"></i>
                </div>
            </div>
        </div>
    </div>



    <!-- Monthly Breakdown Table -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">
            <i class="fas fa-table text-indigo-600 mr-2"></i>Monthly Breakdown {{ $selectedYear }}
        </h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Month</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Revenue</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Cost</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Gross Profit</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Expenses</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Net Profit</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Margin %</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($monthlyTrend as $month)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="font-semibold text-gray-900">{{ $month->full_label }}</span>
                        </td>
                        <td class="px-6 py-4 text-right font-semibold text-blue-600">
                            UGX {{ number_format($month->revenue, 0) }}
                        </td>
                        <td class="px-6 py-4 text-right font-semibold text-red-600">
                            UGX {{ number_format($month->cost, 0) }}
                        </td>
                        <td class="px-6 py-4 text-right font-semibold text-teal-600">
                            UGX {{ number_format($month->gross_profit, 0) }}
                        </td>
                        <td class="px-6 py-4 text-right font-semibold text-orange-600">
                            UGX {{ number_format($month->expenses, 0) }}
                        </td>
                        <td class="px-6 py-4 text-right font-bold text-green-600">
                            UGX {{ number_format($month->net_profit, 0) }}
                        </td>
                        <td class="px-6 py-4 text-right">
                            <span class="px-2 py-1 text-xs font-bold rounded-full 
                                {{ $month->revenue > 0 && ($month->net_profit / $month->revenue * 100) > 10 ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                {{ $month->revenue > 0 ?   number_format(($month->net_profit / $month->revenue) * 100, 1) : '0. 0' }}%
                            </span>
                        </td>
                    </tr>
                    @endforeach
                    <!-- Totals Row -->
                    <tr class="bg-gray-100 font-bold">
                        <td class="px-6 py-4">TOTAL {{ $selectedYear }}</td>
                        <td class="px-6 py-4 text-right text-blue-700">UGX {{ number_format($yearlyRevenueTotal, 0) }}</td>
                        <td class="px-6 py-4 text-right text-red-700">UGX {{ number_format($yearlyCostTotal, 0) }}</td>
                        <td class="px-6 py-4 text-right text-teal-700">UGX {{ number_format($yearlyGrossProfitTotal, 0) }}</td>
                        <td class="px-6 py-4 text-right text-orange-700">UGX {{ number_format($yearlyExpensesTotal, 0) }}</td>
                        <td class="px-6 py-4 text-right text-green-700">UGX {{ number_format($yearlyNetProfitTotal, 0) }}</td>
                        <td class="px-6 py-4 text-right text-purple-700">
                            {{ $yearlyRevenueTotal > 0 ?  number_format(($yearlyNetProfitTotal / $yearlyRevenueTotal) * 100, 1) : '0. 0' }}%
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Expenses Breakdown by Purpose -->
    @if($expensesByPurpose->count() > 0)
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">
            <i class="fas fa-tags text-orange-600 mr-2"></i>Expenses by Purpose
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @foreach($expensesByPurpose as $expense)
            <div class="p-4 bg-orange-50 rounded-lg border border-orange-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-semibold text-gray-900">{{ $expense->purpose }}</p>
                        <p class="text-xs text-gray-500">{{ $expense->count }} transaction(s)</p>
                    </div>
                    <p class="text-lg font-bold text-orange-600">UGX {{ number_format($expense->total, 0) }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Cashier Performance -->
    @if(! $selectedCashierId && $cashierPerformance->count() > 0)
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">
            <i class="fas fa-users text-indigo-600 mr-2"></i>Cashier Performance Comparison
        </h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cashier</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Sales</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Revenue</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Cost</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Expenses</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Net Profit</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($cashierPerformance as $index => $cashier)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm">{{ $index + 1 }}</td>
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center mr-3">
                                    <span class="text-lg font-bold text-indigo-600">{{ substr($cashier->cashier_name, 0, 1) }}</span>
                                </div>
                                <p class="font-semibold text-gray-900">{{ $cashier->cashier_name }}</p>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right font-semibold">{{ $cashier->total_sales }}</td>
                        <td class="px-6 py-4 text-right font-semibold text-blue-600">UGX {{ number_format($cashier->revenue, 0) }}</td>
                        <td class="px-6 py-4 text-right font-semibold text-red-600">UGX {{ number_format($cashier->cost, 0) }}</td>
                        <td class="px-6 py-4 text-right font-semibold text-orange-600">UGX {{ number_format($cashier->expenses, 0) }}</td>
                        <td class="px-6 py-4 text-right font-bold text-green-600">UGX {{ number_format($cashier->profit, 0) }}</td>
                        <td class="px-6 py-4 text-center">
                            <a href="{{ route('profit.index', ['cashier_id' => $cashier->user_id, 'start_date' => $startDate, 'end_date' => $endDate]) }}" 
                               class="text-indigo-600 hover:text-indigo-800">
                                <i class="fas fa-eye mr-1"></i>View
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Daily Chart with Expenses -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">
            <i class="fas fa-calendar-alt text-indigo-600 mr-2"></i>Daily Breakdown (Revenue • Cost • Expenses • Net Profit)
        </h3>
        <canvas id="dailyProfitChart" height="80"></canvas>
    </div>

</div>
@endsection

@push('scripts')
<script>
// Year selector
function changeYear(year) {
    const url = new URL(window.location.href);
    url.searchParams.set('year', year);
    window.location.href = url.toString();
}

// Wait for page to fully load
window.addEventListener('load', function() {
    console.log('Initializing charts...');
    console.log('Chart. js available:', typeof Chart);
    
    // Daily Chart
    const dailyCtx = document.getElementById('dailyProfitChart');
    if (dailyCtx) {
        new Chart(dailyCtx. getContext('2d'), {
            type: 'bar',
            data: {
                labels: @json($dailyProfit->pluck('date')->map(fn($d) => \Carbon\Carbon::parse($d)->format('M d'))),
                datasets: [
                    {
                        label: 'Revenue',
                        data: @json($dailyProfit->pluck('revenue')),
                        backgroundColor: 'rgba(59, 130, 246, 0.7)',
                        borderColor: 'rgb(59, 130, 246)',
                        borderWidth: 2
                    },
                    {
                        label: 'Cost',
                        data: @json($dailyProfit->pluck('cost')),
                        backgroundColor: 'rgba(239, 68, 68, 0.7)',
                        borderColor: 'rgb(239, 68, 68)',
                        borderWidth: 2
                    },
                    {
                        label: 'Expenses',
                        data: @json($dailyProfit->pluck('expenses')),
                        backgroundColor: 'rgba(249, 115, 22, 0.7)',
                        borderColor: 'rgb(249, 115, 22)',
                        borderWidth: 2
                    },
                    {
                        label: 'Net Profit',
                        data: @json($dailyProfit->pluck('profit')),
                        backgroundColor: 'rgba(34, 197, 94, 0.7)',
                        borderColor: 'rgb(34, 197, 94)',
                        borderWidth: 2
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                return ctx. dataset.label + ': UGX ' + ctx.parsed. y.toLocaleString();
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'UGX ' + value. toLocaleString();
                            }
                        }
                    }
                }
            }
        });
        console.log('✅ Daily chart created');
    }
    
    console.log('All charts initialized');
});
</script>
@endpush