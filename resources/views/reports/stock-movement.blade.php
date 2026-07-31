@extends('layouts.app')

@section('title', 'Stock Movement Report')

@section('page-title')
    <i class="fas fa-boxes text-indigo-600 mr-2"></i>Stock Movement Report
@endsection

@section('content')
<div class="space-y-6">

    {{-- ─── Filter Panel ──────────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl shadow-lg p-6">
        <h3 class="text-base font-bold text-gray-700 mb-4 flex items-center gap-2">
            <i class="fas fa-sliders-h text-indigo-500"></i> Filter Report
        </h3>

        <form method="GET" action="{{ route('reports.stock-movement') }}" id="filterForm">

            {{-- Mode toggle --}}
            <div class="flex gap-2 mb-5">
                <button type="button" id="btnMonth"
                    class="mode-btn px-5 py-2 rounded-full text-sm font-semibold border transition-all
                           {{ $mode !== 'custom' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-600 border-gray-300 hover:border-indigo-400' }}"
                    onclick="setMode('month')">
                    <i class="fas fa-calendar-alt mr-1"></i> Monthly
                </button>
                <button type="button" id="btnCustom"
                    class="mode-btn px-5 py-2 rounded-full text-sm font-semibold border transition-all
                           {{ $mode === 'custom' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-600 border-gray-300 hover:border-indigo-400' }}"
                    onclick="setMode('custom')">
                    <i class="fas fa-calendar-week mr-1"></i> Custom Range
                </button>
                <input type="hidden" name="mode" id="modeInput" value="{{ $mode }}">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">

                {{-- Month picker --}}
                <div id="monthGroup" class="{{ $mode === 'custom' ? 'hidden' : '' }}">
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Month</label>
                    <input type="month"
                           name="month"
                           value="{{ request('month', now()->format('Y-m')) }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-400 text-sm">
                </div>

                {{-- Custom start --}}
                <div id="startGroup" class="{{ $mode !== 'custom' ? 'hidden' : '' }}">
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Start Date</label>
                    <input type="date"
                           name="start_date"
                           value="{{ request('start_date', $startDate->format('Y-m-d')) }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-400 text-sm">
                </div>

                {{-- Custom end --}}
                <div id="endGroup" class="{{ $mode !== 'custom' ? 'hidden' : '' }}">
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">End Date</label>
                    <input type="date"
                           name="end_date"
                           value="{{ request('end_date', $endDate->format('Y-m-d')) }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-400 text-sm">
                </div>

                {{-- Category --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Category</label>
                    <select name="category_id"
                            class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-400 text-sm">
                        <option value="">All Categories</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Product --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Product</label>
                    <select name="product_id"
                            class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-400 text-sm">
                        <option value="">All Products</option>
                        @foreach($allProducts as $p)
                            <option value="{{ $p->id }}" {{ request('product_id') == $p->id ? 'selected' : '' }}>
                                {{ $p->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Generate --}}
                <div>
                    <button type="submit"
                            class="w-full px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-semibold text-sm transition-all shadow">
                        <i class="fas fa-chart-bar mr-2"></i>Generate
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- ─── Validation Warnings ──────────────────────────────────── --}}
    @if(count($warnings) > 0)
    <div class="bg-amber-50 border border-amber-300 rounded-2xl p-5">
        <div class="flex items-start gap-3">
            <i class="fas fa-exclamation-triangle text-amber-500 text-lg mt-0.5"></i>
            <div>
                <p class="font-bold text-amber-800 text-sm">Internal Validation Warning</p>
                <p class="text-amber-700 text-xs mt-1 mb-3">
                    The following products have a closing stock discrepancy between the ledger and calculated figures.
                    This may indicate a data integrity issue that should be reviewed.
                </p>
                <ul class="space-y-1">
                    @foreach($warnings as $w)
                    <li class="text-xs text-amber-700 flex items-center gap-2">
                        <span class="w-2 h-2 bg-amber-500 rounded-full flex-shrink-0"></span>
                        <strong>{{ $w['product'] }}</strong>:
                        Expected closing {{ number_format($w['expected'], 0) }},
                        recorded {{ number_format($w['actual'], 0) }}
                        (Δ {{ $w['delta'] > 0 ? '+' : '' }}{{ number_format($w['delta'], 2) }})
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
    @endif

    {{-- ─── Summary Cards ─────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        {{-- Opening --}}
        <div class="bg-white rounded-2xl shadow p-5 flex items-center gap-4">
            <div class="bg-blue-100 rounded-xl p-3 flex-shrink-0">
                <i class="fas fa-box-open text-blue-600 text-xl"></i>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-semibold uppercase">Opening Stock</p>
                <p class="text-2xl font-bold text-blue-700 mt-0.5">{{ number_format($totals['opening'], 0) }}</p>
                <p class="text-xs text-gray-400">units</p>
            </div>
        </div>

        {{-- Purchases --}}
        <div class="bg-white rounded-2xl shadow p-5 flex items-center gap-4">
            <div class="bg-green-100 rounded-xl p-3 flex-shrink-0">
                <i class="fas fa-truck-loading text-green-600 text-xl"></i>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-semibold uppercase">Purchases In</p>
                <p class="text-2xl font-bold text-green-700 mt-0.5">+{{ number_format($totals['purchases'], 0) }}</p>
                <p class="text-xs text-gray-400">units received</p>
            </div>
        </div>

        {{-- Sales --}}
        <div class="bg-white rounded-2xl shadow p-5 flex items-center gap-4">
            <div class="bg-red-100 rounded-xl p-3 flex-shrink-0">
                <i class="fas fa-shopping-cart text-red-600 text-xl"></i>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-semibold uppercase">Sales Out</p>
                <p class="text-2xl font-bold text-red-700 mt-0.5">−{{ number_format($totals['sales'], 0) }}</p>
                <p class="text-xs text-gray-400">units sold</p>
            </div>
        </div>

        {{-- Closing --}}
        <div class="bg-white rounded-2xl shadow p-5 flex items-center gap-4">
            <div class="bg-indigo-100 rounded-xl p-3 flex-shrink-0">
                <i class="fas fa-warehouse text-indigo-600 text-xl"></i>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-semibold uppercase">Closing Stock</p>
                <p class="text-2xl font-bold text-indigo-700 mt-0.5">{{ number_format($totals['closing'], 0) }}</p>
                <p class="text-xs text-gray-400">units on hand</p>
            </div>
        </div>
    </div>

    {{-- ─── Main Report Table ──────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">

        {{-- Header --}}
        <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 px-6 py-4 flex items-center justify-between">
            <div>
                <h2 class="text-white font-bold text-lg">
                    <i class="fas fa-boxes mr-2 opacity-80"></i>
                    Monthly Stock Movement Report
                </h2>
                <p class="text-indigo-200 text-sm mt-0.5">Period: {{ $periodLabel }}</p>
            </div>
            <div class="flex gap-2">
                <button onclick="window.print()"
                        class="px-4 py-2 bg-white bg-opacity-20 hover:bg-opacity-30 text-white text-sm rounded-lg transition-all border border-white border-opacity-30">
                    <i class="fas fa-print mr-1"></i> Print
                </button>
                <a href="{{ route('reports.stock-movement', array_merge(request()->all(), ['export' => 'csv'])) }}"
                   class="px-4 py-2 bg-white bg-opacity-20 hover:bg-opacity-30 text-white text-sm rounded-lg transition-all border border-white border-opacity-30">
                    <i class="fas fa-file-csv mr-1"></i> Export
                </a>
            </div>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="min-w-full" id="movementTable">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Product</th>
                        <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Category</th>
                        <th class="px-5 py-3 text-right text-xs font-bold text-blue-600 uppercase tracking-wider">
                            <i class="fas fa-box-open mr-1"></i>Opening
                        </th>
                        <th class="px-5 py-3 text-right text-xs font-bold text-green-600 uppercase tracking-wider">
                            <i class="fas fa-plus-circle mr-1"></i>Purchases
                        </th>
                        <th class="px-5 py-3 text-right text-xs font-bold text-red-600 uppercase tracking-wider">
                            <i class="fas fa-minus-circle mr-1"></i>Sales
                        </th>
                        <th class="px-5 py-3 text-right text-xs font-bold text-indigo-700 uppercase tracking-wider">
                            <i class="fas fa-warehouse mr-1"></i>Closing
                        </th>
                        <th class="px-5 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($movements as $row)
                    @php
                        $net = $row['purchases'] - $row['sales'];
                        $hasActivity = $row['opening'] > 0 || $row['purchases'] > 0 || $row['sales'] > 0;
                    @endphp
                    <tr class="hover:bg-gray-50 transition-colors {{ $row['mismatch'] ? 'bg-amber-50' : '' }}">
                        {{-- Product --}}
                        <td class="px-5 py-3.5">
                            <div>
                                <p class="font-semibold text-gray-900 text-sm">{{ $row['product']->name }}</p>
                                <p class="text-xs text-gray-400 mt-0.5">{{ $row['product']->sku }} &bull; {{ $row['product']->unit }}</p>
                            </div>
                        </td>

                        {{-- Category --}}
                        <td class="px-5 py-3.5">
                            <span class="inline-block px-2 py-0.5 bg-gray-100 text-gray-600 rounded text-xs font-medium">
                                {{ $row['product']->category->name ?? '—' }}
                            </span>
                        </td>

                        {{-- Opening --}}
                        <td class="px-5 py-3.5 text-right">
                            <span class="font-semibold text-blue-700 text-sm">
                                {{ number_format($row['opening'], 0) }}
                            </span>
                        </td>

                        {{-- Purchases --}}
                        <td class="px-5 py-3.5 text-right">
                            @if($row['purchases'] > 0)
                                <span class="font-semibold text-green-700 text-sm">+{{ number_format($row['purchases'], 0) }}</span>
                            @else
                                <span class="text-gray-300 text-sm">—</span>
                            @endif
                        </td>

                        {{-- Sales --}}
                        <td class="px-5 py-3.5 text-right">
                            @if($row['sales'] > 0)
                                <span class="font-semibold text-red-600 text-sm">−{{ number_format($row['sales'], 0) }}</span>
                            @else
                                <span class="text-gray-300 text-sm">—</span>
                            @endif
                        </td>

                        {{-- Closing --}}
                        <td class="px-5 py-3.5 text-right">
                            @php
                                $closingColor = $row['closing'] <= 0
                                    ? 'text-red-700 font-bold'
                                    : ($row['closing'] <= $row['product']->reorder_level
                                        ? 'text-amber-700 font-bold'
                                        : 'text-indigo-700 font-bold');
                            @endphp
                            <span class="{{ $closingColor }} text-sm">
                                {{ number_format($row['closing'], 0) }}
                            </span>
                        </td>

                        {{-- Notes --}}
                        <td class="px-5 py-3.5 text-center">
                            <div class="flex items-center justify-center gap-1.5">
                                @if($row['physical_count'] !== null)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-teal-100 text-teal-700"
                                          title="Physical stock count was conducted this period">
                                        <i class="fas fa-clipboard-check mr-1"></i>Stock Take
                                    </span>
                                @endif
                                @if($row['closing'] <= 0)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">
                                        <i class="fas fa-times-circle mr-1"></i>Out of Stock
                                    </span>
                                @elseif($row['closing'] <= $row['product']->reorder_level && $row['product']->reorder_level > 0)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">
                                        <i class="fas fa-exclamation-triangle mr-1"></i>Low Stock
                                    </span>
                                @endif
                                @if($row['mismatch'])
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800"
                                          title="Internal validation mismatch — please review">
                                        <i class="fas fa-exclamation-circle mr-1"></i>Review
                                    </span>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-5 py-16 text-center">
                            <div class="flex flex-col items-center gap-3 text-gray-400">
                                <i class="fas fa-boxes text-5xl opacity-30"></i>
                                <p class="font-semibold text-gray-500">No products found for the selected period.</p>
                                <p class="text-sm">Adjust your filters and try again.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>

                {{-- Totals footer --}}
                @if(count($movements) > 0)
                <tfoot>
                    <tr class="bg-indigo-50 border-t-2 border-indigo-200">
                        <td colspan="2" class="px-5 py-4 font-bold text-gray-700 text-sm">
                            TOTALS &mdash; {{ count($movements) }} {{ Str::plural('product', count($movements)) }}
                        </td>
                        <td class="px-5 py-4 text-right font-bold text-blue-700 text-sm">
                            {{ number_format($totals['opening'], 0) }}
                        </td>
                        <td class="px-5 py-4 text-right font-bold text-green-700 text-sm">
                            +{{ number_format($totals['purchases'], 0) }}
                        </td>
                        <td class="px-5 py-4 text-right font-bold text-red-700 text-sm">
                            −{{ number_format($totals['sales'], 0) }}
                        </td>
                        <td class="px-5 py-4 text-right font-bold text-indigo-800 text-sm">
                            {{ number_format($totals['closing'], 0) }}
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>

        {{-- Legend --}}
        @if(count($movements) > 0)
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex flex-wrap gap-4 text-xs text-gray-500">
            <span><i class="fas fa-clipboard-check text-teal-600 mr-1"></i> Stock Take = Physical count was performed this period</span>
            <span><i class="fas fa-exclamation-triangle text-amber-500 mr-1"></i> Low Stock = Below reorder level</span>
            <span><i class="fas fa-times-circle text-red-500 mr-1"></i> Out of Stock = Zero or negative closing stock</span>
            <span class="ml-auto italic">Generated {{ now()->format('d M Y, H:i') }}</span>
        </div>
        @endif
    </div>

</div>
@endsection

@push('styles')
<style>
    @media print {
        nav, aside, header, .no-print, form { display: none !important; }
        body { background: white !important; }
        .bg-white { box-shadow: none !important; border: 1px solid #e5e7eb !important; }
        .bg-gradient-to-r { background: #4f46e5 !important; print-color-adjust: exact; -webkit-print-color-adjust: exact; }
        tr.hover\:bg-gray-50:hover { background: white !important; }
    }
</style>
@endpush

@push('scripts')
<script>
function setMode(mode) {
    document.getElementById('modeInput').value = mode;

    const isCustom = mode === 'custom';
    document.getElementById('monthGroup').classList.toggle('hidden', isCustom);
    document.getElementById('startGroup').classList.toggle('hidden', !isCustom);
    document.getElementById('endGroup').classList.toggle('hidden', !isCustom);

    document.getElementById('btnMonth').className = `mode-btn px-5 py-2 rounded-full text-sm font-semibold border transition-all ${!isCustom ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-600 border-gray-300 hover:border-indigo-400'}`;
    document.getElementById('btnCustom').className = `mode-btn px-5 py-2 rounded-full text-sm font-semibold border transition-all ${isCustom ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-600 border-gray-300 hover:border-indigo-400'}`;
}

// Quick search
document.addEventListener('DOMContentLoaded', function () {
    const searchEl = document.createElement('input');
    searchEl.type = 'text';
    searchEl.placeholder = 'Search products in table…';
    searchEl.className = 'w-full md:w-64 px-4 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-400 no-print';
    const legend = document.querySelector('tfoot');
    if (legend) {
        const wrap = document.createElement('div');
        wrap.className = 'px-6 pt-4 no-print';
        wrap.appendChild(searchEl);
        legend.parentElement.parentElement.insertBefore(wrap, legend.parentElement.nextSibling);
    }

    searchEl.addEventListener('input', function () {
        const val = this.value.toLowerCase();
        document.querySelectorAll('#movementTable tbody tr').forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(val) ? '' : 'none';
        });
    });
});
</script>
@endpush
