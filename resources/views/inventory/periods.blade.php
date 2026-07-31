@extends('layouts.app')

@section('title', 'Period Closing History')
@section('page-title')
    <i class="fas fa-history text-purple-600 mr-2"></i>Period Closing & Inventory Reconciliation
@endsection

@section('content')
<div class="space-y-6">

    @if(session('success'))
    <div class="p-4 bg-green-50 border-l-4 border-green-500 rounded-lg text-green-800 flex items-center space-x-2">
        <i class="fas fa-check-circle text-green-500"></i>
        <span>{{ session('success') }}</span>
    </div>
    @endif

    @if(session('error'))
    <div class="p-4 bg-red-50 border-l-4 border-red-500 rounded-lg text-red-800 flex items-center space-x-2">
        <i class="fas fa-exclamation-circle text-red-500"></i>
        <span>{{ session('error') }}</span>
    </div>
    @endif

    <!-- Close Month Control Panel -->
    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
        <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
            <i class="fas fa-calendar-check text-purple-600 mr-2"></i>Close Accounting Period
        </h3>
        
        <form method="POST" action="{{ route('stock-taking.close-month') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            @csrf
            
            <div>
                <label class="block text-xs font-semibold text-gray-600 uppercase mb-2">Year</label>
                <select name="year" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                    @php $currentYear = now()->year; @endphp
                    @for($y = $currentYear; $y >= $currentYear - 3; $y--)
                        <option value="{{ $y }}" {{ old('year', $currentYear) == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-600 uppercase mb-2">Month</label>
                <select name="month" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                    @php $currentMonth = now()->month; @endphp
                    @for($m = 1; $m <= 12; $m++)
                        @php $date = Carbon\Carbon::create(2020, $m, 1); @endphp
                        <option value="{{ $m }}" {{ old('month', $currentMonth) == $m ? 'selected' : '' }}>
                            {{ $date->format('F') }}
                        </option>
                    @endfor
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-600 uppercase mb-2">Reconciliation Session (Optional)</label>
                <select name="session_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                    <option value="">-- No Stock Take (Use System Quantities) --</option>
                    @foreach($closedSessions as $sess)
                        <option value="{{ $sess->id }}">
                            Session #{{ $sess->id }} ({{ $sess->session_date->format('M d, Y') }}) - {{ $sess->adjustments->count() }} items
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <button type="submit" class="w-full flex items-center justify-center space-x-2 px-6 py-2.5 bg-purple-600 hover:bg-purple-700 text-white font-semibold rounded-lg shadow transition">
                    <i class="fas fa-lock"></i>
                    <span>Execute Month Close</span>
                </button>
            </div>
        </form>
        <p class="text-xs text-gray-500 mt-2">
            <i class="fas fa-info-circle mr-1"></i>Closing a period calculates official closing stock balances and locks the records. Voided sales are automatically excluded from calculations.
        </p>
    </div>

    <!-- Period Closing History Table -->
    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
        <h3 class="text-2xl font-bold text-gray-800 mb-6">
            <i class="fas fa-history text-purple-600 mr-2"></i>Accounting Period History
        </h3>

        @if($periods->isEmpty())
        <div class="text-center py-12">
            <i class="fas fa-inbox text-gray-300 text-5xl mb-4"></i>
            <p class="text-gray-500">No accounting periods have been closed yet.</p>
            <p class="text-xs text-gray-400 mt-2">Select a month above to perform your first monthly close.</p>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-3 py-3 text-left font-semibold text-gray-700 w-8"></th>
                        <th class="px-3 py-3 text-left font-semibold text-gray-700">Product</th>
                        <th class="px-3 py-3 text-left font-semibold text-gray-700">Period</th>
                        <th class="px-3 py-3 text-right font-semibold text-gray-700">Opening</th>
                        <th class="px-3 py-3 text-right font-semibold text-gray-700">Purchases</th>
                        <th class="px-3 py-3 text-right font-semibold text-gray-700">Sales</th>
                        <th class="px-3 py-3 text-right font-semibold text-gray-700">System Calculated</th>
                        <th class="px-3 py-3 text-right font-semibold text-gray-700">Physical</th>
                        <th class="px-3 py-3 text-right font-semibold text-gray-700 text-red-600">Stock Loss</th>
                        <th class="px-3 py-3 text-right font-semibold text-gray-700 text-green-600">Stock Gain</th>
                        <th class="px-3 py-3 text-right font-semibold text-gray-700">Closing</th>
                        <th class="px-3 py-3 text-right font-semibold text-gray-700">Adj Value</th>
                        <th class="px-3 py-3 text-center font-semibold text-gray-700">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-150">
                    @foreach($periods as $period)
                    <tr class="hover:bg-gray-50 cursor-pointer transition" onclick="toggleRow({{ $period->id }})">
                        <td class="px-3 py-4 text-center">
                            <i class="fas fa-chevron-down text-gray-400 transition-transform duration-200" id="chevron-{{ $period->id }}"></i>
                        </td>
                        <td class="px-3 py-4 text-gray-800 font-semibold">
                            <a href="{{ route('inventory.show', $period->product_id) }}" class="text-indigo-600 hover:underline" onclick="event.stopPropagation()">
                                {{ $period->product->name }}
                            </a>
                            <div class="text-xs text-gray-400 font-normal">SKU: {{ $period->product->sku }}</div>
                        </td>
                        <td class="px-3 py-4 text-gray-600">
                            {{ $period->period_start->format('M Y') }}
                        </td>
                        <td class="px-3 py-4 text-right text-gray-700">{{ number_format($period->opening_stock, 1) }}</td>
                        <td class="px-3 py-4 text-right text-green-600 font-semibold">+{{ number_format($period->purchases, 1) }}</td>
                        <td class="px-3 py-4 text-right text-red-600 font-semibold">-{{ number_format($period->sales, 1) }}</td>
                        <td class="px-3 py-4 text-right text-gray-700 font-semibold">{{ number_format($period->calculated_stock, 1) }}</td>
                        <td class="px-3 py-4 text-right text-gray-700 font-semibold">
                            {{ $period->physical_count !== null ? number_format($period->physical_count, 1) : '-' }}
                        </td>
                        <td class="px-3 py-4 text-right">
                            @if($period->variance < -0.001)
                                <span class="text-red-600 font-bold">{{ number_format(abs($period->variance), 1) }}</span>
                            @else
                                <span class="text-gray-300">-</span>
                            @endif
                        </td>
                        <td class="px-3 py-4 text-right">
                            @if($period->variance > 0.001)
                                <span class="text-green-600 font-bold">+{{ number_format($period->variance, 1) }}</span>
                            @else
                                <span class="text-gray-300">-</span>
                            @endif
                        </td>
                        <td class="px-3 py-4 text-right text-gray-900 font-bold bg-gray-50">{{ number_format($period->closing_stock, 1) }}</td>
                        <td class="px-3 py-4 text-right">
                            @if($period->adjustment_value < -0.01)
                                <span class="text-red-700 font-semibold">UGX -{{ number_format(abs($period->adjustment_value), 0) }}</span>
                            @elseif($period->adjustment_value > 0.01)
                                <span class="text-green-700 font-semibold">UGX +{{ number_format($period->adjustment_value, 0) }}</span>
                            @else
                                <span class="text-gray-400">UGX 0</span>
                            @endif
                        </td>
                        <td class="px-3 py-4 text-center">
                            @if($period->status === 'locked')
                                <span class="px-2.5 py-1 bg-purple-100 text-purple-800 rounded-full text-xs font-bold uppercase tracking-wider">
                                    <i class="fas fa-lock mr-1"></i>Locked
                                </span>
                            @elseif($period->status === 'reconciled')
                                <span class="px-2.5 py-1 bg-green-100 text-green-800 rounded-full text-xs font-bold uppercase tracking-wider">
                                    <i class="fas fa-check-double mr-1"></i>Reconciled
                                </span>
                            @elseif($period->status === 'pending_reconciliation')
                                <span class="px-2.5 py-1 bg-amber-100 text-amber-800 rounded-full text-xs font-bold uppercase tracking-wider animate-pulse">
                                    <i class="fas fa-hourglass-half mr-1"></i>Pending
                                </span>
                            @else
                                <span class="px-2.5 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-bold uppercase tracking-wider">
                                    <i class="fas fa-folder-open mr-1"></i>Open
                                </span>
                            @endif
                        </td>
                    </tr>
                    
                    <!-- Expandable Detail Row -->
                    <tr id="detail-{{ $period->id }}" class="hidden bg-gray-50 border-l-4 border-purple-500">
                        <td colspan="13" class="px-6 py-6">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <!-- Stock Calculations Detail -->
                                <div class="bg-white p-4 rounded-xl shadow-sm border">
                                    <h4 class="font-bold text-gray-800 mb-3 border-b pb-2 flex items-center">
                                        <i class="fas fa-calculator text-blue-500 mr-2"></i>Stock Equation (Qty)
                                    </h4>
                                    <div class="space-y-2 text-sm">
                                        <div class="flex justify-between">
                                            <span class="text-gray-500">Opening Quantity</span>
                                            <span class="font-semibold">{{ number_format($period->opening_stock, 2) }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-500">+ Purchases Quantity</span>
                                            <span class="font-semibold text-green-600">+{{ number_format($period->purchases, 2) }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-500">- Sales Quantity</span>
                                            <span class="font-semibold text-red-600">-{{ number_format($period->sales, 2) }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-500">± prior Adjustments</span>
                                            <span class="font-semibold">{{ $period->adjustments >= 0 ? '+' : '' }}{{ number_format($period->adjustments, 2) }}</span>
                                        </div>
                                        <div class="flex justify-between border-t pt-2 font-bold text-indigo-600">
                                            <span>Expected System Stock</span>
                                            <span>{{ number_format($period->calculated_stock, 2) }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Financial Value Calculations -->
                                <div class="bg-white p-4 rounded-xl shadow-sm border">
                                    <h4 class="font-bold text-gray-800 mb-3 border-b pb-2 flex items-center">
                                        <i class="fas fa-coins text-yellow-500 mr-2"></i>Financial Reconciliation
                                    </h4>
                                    <div class="space-y-2 text-sm">
                                        <div class="flex justify-between">
                                            <span class="text-gray-500">Opening Stock Value</span>
                                            <span class="font-semibold">UGX {{ number_format($period->opening_stock_value, 0) }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-500">+ Purchases Value</span>
                                            <span class="font-semibold">UGX {{ number_format($period->purchases_value, 0) }}</span>
                                        </div>
                                        <div class="flex justify-between text-red-500">
                                            <span>- Sales Cost Value</span>
                                            <span class="font-semibold">UGX -{{ number_format($period->sales_cost_value, 0) }}</span>
                                        </div>
                                        <div class="flex justify-between border-t pt-2 font-bold text-purple-600">
                                            <span>Closing Stock Value</span>
                                            <span>UGX {{ number_format($period->closing_stock_value, 0) }}</span>
                                        </div>
                                        <div class="flex justify-between border-t pt-1 font-bold {{ $period->adjustment_value < 0 ? 'text-red-600' : 'text-green-600' }}">
                                            <span>Adjustment Value</span>
                                            <span>UGX {{ $period->adjustment_value >= 0 ? '+' : '' }}{{ number_format($period->adjustment_value, 0) }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Audit Metadata -->
                                <div class="bg-white p-4 rounded-xl shadow-sm border">
                                    <h4 class="font-bold text-gray-800 mb-3 border-b pb-2 flex items-center">
                                        <i class="fas fa-file-invoice text-purple-500 mr-2"></i>Audit Information
                                    </h4>
                                    <div class="space-y-2 text-sm">
                                        <div class="flex justify-between">
                                            <span class="text-gray-500">Reconciliation Source</span>
                                            @if($period->session)
                                                <a href="{{ route('stock-taking.session', $period->stock_taking_session_id) }}" class="text-indigo-600 hover:underline font-bold">
                                                    Stock Take #{{ $period->stock_taking_session_id }}
                                                </a>
                                            @else
                                                <span class="text-gray-600 italic">System Auto-Close</span>
                                            @endif
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-500">Closed By</span>
                                            <span class="font-semibold">{{ $period->closer->name ?? 'System' }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-500">Closed Timestamp</span>
                                            <span class="font-semibold">{{ $period->closed_at ? $period->closed_at->format('M d, Y H:i') : '-' }}</span>
                                        </div>
                                        <div class="text-xs text-gray-500 border-t pt-2 leading-relaxed">
                                            <strong>Note:</strong> Approved closing records are read-only and immutable. Corrective entries can only be posted via new stock-taking or count sheets.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-6">
            {{ $periods->links() }}
        </div>
        @endif
    </div>

    <!-- Historical Monthly Aggregates Summary -->
    @if(!empty($monthlySummary))
    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
        <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
            <i class="fas fa-file-contract text-indigo-600 mr-2"></i>Monthly Financial Summary Reports
        </h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @foreach($monthlySummary as $month)
            <div class="bg-gray-50 rounded-xl p-5 border border-gray-200">
                <div class="flex justify-between items-center mb-3">
                    <h4 class="font-bold text-gray-800 text-lg">{{ $month['month_label'] }}</h4>
                    <span class="px-2.5 py-1 bg-purple-100 text-purple-800 rounded-full text-xs font-bold">
                        {{ $month['products'] }} Products
                    </span>
                </div>
                
                <div class="grid grid-cols-2 gap-4 text-sm mt-3">
                    <div>
                        <p class="text-xs text-gray-400 uppercase font-semibold">Total Stock Loss</p>
                        <p class="text-lg font-bold text-red-600">-{{ number_format($month['total_loss'], 1) }} units</p>
                        <p class="text-xs font-semibold text-red-500">UGX -{{ number_format($month['total_loss_value'], 0) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase font-semibold">Total Stock Gain</p>
                        <p class="text-lg font-bold text-green-600">+{{ number_format($month['total_gain'], 1) }} units</p>
                        <p class="text-xs font-semibold text-green-500">UGX +{{ number_format($month['total_gain_value'], 0) }}</p>
                    </div>
                </div>
                
                @if($month['has_physical_count'])
                <div class="mt-4 pt-3 border-t border-gray-200 text-xs text-gray-500 flex items-center">
                    <i class="fas fa-clipboard-check text-green-500 mr-1.5"></i> Reconciled using physical stock count sessions
                </div>
                @else
                <div class="mt-4 pt-3 border-t border-gray-200 text-xs text-gray-400 flex items-center italic">
                    <i class="fas fa-server text-gray-400 mr-1.5"></i> Reconciled using system stock totals (no physical sheet)
                </div>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif

</div>

<script>
    function toggleRow(periodId) {
        const detailRow = document.getElementById('detail-' + periodId);
        const chevron = document.getElementById('chevron-' + periodId);
        
        detailRow.classList.toggle('hidden');
        chevron.classList.toggle('rotate-180');
    }
</script>
@endsection
