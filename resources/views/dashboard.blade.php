@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@push('styles')
<style>
  :root{
    --shadow-soft: 0 8px 24px rgba(0,0,0,.08);
    --shadow-card: 0 12px 32px rgba(0,0,0,.12);
  }
  /* Reveal-on-scroll */
  .reveal { opacity: 0; transform: translateY(14px); will-change: opacity, transform; }
  .reveal.visible { opacity: 1; transform: translateY(0); transition: opacity .6s cubic-bezier(.2,.8,.2,1), transform .6s cubic-bezier(.2,.8,.2,1); }
  .reveal[data-delay="100"] { transition-delay: .1s; }
  .reveal[data-delay="200"] { transition-delay: .2s; }
  .reveal[data-delay="300"] { transition-delay: .3s; }
  .reveal[data-delay="400"] { transition-delay: .4s; }

  /* Card base */
  .card { background: #fff; border-radius: 16px; box-shadow: var(--shadow-soft); padding: 18px; }
  .card:hover { box-shadow: var(--shadow-card); transform: translateY(-2px); transition: box-shadow .2s ease, transform .2s ease; }
  .card-title { font-weight: 700; color: #111827; }
  .muted { color: #6B7280; }
  .badge { border-radius: 9999px; padding: 2px 10px; font-size: 11px; font-weight: 600; }

  /* KPI gradient cards with color-consistent accents */
  .kpi { border-radius: 18px; padding: 22px; color: #fff; box-shadow: var(--shadow-soft); }
  .kpi:hover { box-shadow: var(--shadow-card); transform: translateY(-3px); transition: box-shadow .2s ease, transform .2s ease; }
  .kpi-green { background: linear-gradient(135deg,#22c55e,#16a34a); }
  .kpi-blue  { background: linear-gradient(135deg,#3b82f6,#2563eb); }
  .kpi-yellow{ background: linear-gradient(135deg,#f59e0b,#d97706); }
  .kpi-purple{ background: linear-gradient(135deg,#8b5cf6,#7c3aed); }
  .kpi-divider { border-top: 1px solid rgba(255,255,255,.25); margin-top: 10px; padding-top: 8px; }

  /* Summary cards with consistent color semantics */
  .summary-green { border-left: 4px solid #22c55e; }
  .summary-rose  { border-left: 4px solid #f43f5e; }
  .summary-amber { border-left: 4px solid #f59e0b; }
  .summary-indigo{ border-left: 4px solid #6366f1; }
  .summary-blue  { border-left: 4px solid #3b82f6; }

  /* Charts */
  .chart-wrap { padding: 18px; border-radius: 16px; background: #fff; box-shadow: var(--shadow-soft); }
  .chart-wrap:hover { box-shadow: var(--shadow-card); transform: translateY(-2px); transition: box-shadow .2s ease, transform .2s ease; }

  /* Smaller pie container */
  .pie-small { max-width: 380px; margin: 0 auto; }
</style>
@endpush

@section('content')
  @if(session('success'))
    <div class="reveal visible bg-green-50 border border-green-200 text-green-800 p-4 mb-4 rounded-xl shadow-sm">
      <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
    </div>
  @endif
  @if(session('error'))
    <div class="reveal visible bg-red-50 border border-red-200 text-red-800 p-4 mb-4 rounded-xl shadow-sm">
      <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
    </div>
  @endif

  <!-- Filters -->
  <div class="card reveal mb-6" data-delay="100">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div class="flex items-center gap-2">
        <label for="periodSelect" class="text-sm muted font-medium"><i class="fas fa-filter mr-1 text-indigo-600"></i>Period</label>
        <select id="periodSelect" class="px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500" onchange="onChangePeriod(this.value)">
          <option value="day"  {{ ($period ?? 'week') === 'day'  ? 'selected' : '' }}>Daily</option>
          <option value="week" {{ ($period ?? 'week') === 'week' ? 'selected' : '' }}>Weekly</option>
          <option value="month"{{ ($period ?? 'week') === 'month'? 'selected' : '' }}>Monthly</option>
          <option value="year" {{ ($period ?? 'week') === 'year' ? 'selected' : '' }}>Yearly</option>
        </select>
      </div>

      <div class="flex items-center gap-2">
        <label for="yearSelect" class="text-sm muted font-medium"><i class="fas fa-calendar mr-1 text-indigo-600"></i>Year</label>
        <select id="yearSelect" class="px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500" onchange="onChangeYear(this.value)">
          @foreach(($availableYears ?? collect([date('Y')])) as $y)
            <option value="{{ $y }}" {{ ($selectedYear ?? date('Y')) == $y ? 'selected' : '' }}>{{ $y }}</option>
          @endforeach
        </select>
      </div>

      <div class="flex items-center gap-2">
        <span class="badge bg-indigo-100 text-indigo-800"><i class="fas fa-calendar-day mr-1"></i>{{ now()->format('D, M d, Y') }}</span>
        <span class="badge bg-gray-100 text-gray-700"><i class="fas fa-user mr-1"></i>{{ $userRole ?? optional(auth()->user()->role)->name }}</span>
      </div>
    </div>
  </div>

  <!-- KPI Cards -->
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-6">
    <div class="kpi kpi-green reveal" data-delay="100">
      <p class="text-green-100 text-sm font-semibold">Today's Sales</p>
      <p class="text-3xl md:text-4xl font-extrabold mt-2">UGX {{ number_format($todayRevenue ?? 0, 0) }}</p>
      <p class="text-green-100 text-xs mt-1">{{ $todaySales ?? 0 }} transactions</p>
      <div class="kpi-divider text-sm space-y-1">
        <div class="flex justify-between"><span>Gross Profit</span><span class="font-bold">UGX {{ number_format($todayGrossProfit ?? 0, 0) }}</span></div>
        <div class="flex justify-between"><span>Margin</span><span class="font-bold">{{ number_format($todayProfitMargin ?? 0, 1) }}%</span></div>
      </div>
    </div>

    <div class="kpi kpi-blue reveal" data-delay="200">
      <p class="text-blue-100 text-sm font-semibold">This Week</p>
      <p class="text-3xl md:text-4xl font-extrabold mt-2">UGX {{ number_format($weekRevenue ?? 0, 0) }}</p>
      <p class="text-blue-100 text-xs mt-1">{{ $weekSales ?? 0 }} transactions</p>
      <div class="kpi-divider text-sm space-y-1">
        <div class="flex justify-between"><span>Gross Profit</span><span class="font-bold">UGX {{ number_format($weekGrossProfit ?? 0, 0) }}</span></div>
        <div class="flex justify-between"><span>Margin</span><span class="font-bold">{{ number_format($weekProfitMargin ?? 0, 1) }}%</span></div>
      </div>
    </div>

    <div class="kpi kpi-yellow reveal" data-delay="300">
      <p class="text-yellow-100 text-sm font-semibold">This Month</p>
      <p class="text-3xl md:text-4xl font-extrabold mt-2">UGX {{ number_format($monthRevenue ?? 0, 0) }}</p>
      <p class="text-yellow-100 text-xs mt-1">{{ $monthSales ?? 0 }} transactions</p>
      <div class="kpi-divider text-sm space-y-1">
        <div class="flex justify-between"><span>Gross Profit</span><span class="font-bold">UGX {{ number_format($monthGrossProfit ?? 0, 0) }}</span></div>
        <div class="flex justify-between"><span>Margin</span><span class="font-bold">{{ number_format($monthProfitMargin ?? 0, 1) }}%</span></div>
      </div>
    </div>

    <div class="kpi kpi-purple reveal" data-delay="400">
      <p class="text-purple-100 text-sm font-semibold">All Time</p>
      <p class="text-3xl md:text-4xl font-extrabold mt-2">UGX {{ number_format($totalRevenue ?? 0, 0) }}</p>
      <p class="text-purple-100 text-xs mt-1">{{ $totalSales ?? 0 }} transactions</p>
      <div class="kpi-divider text-sm space-y-1">
        <div class="flex justify-between"><span>Gross Profit</span><span class="font-bold">UGX {{ number_format($totalGrossProfit ?? 0, 0) }}</span></div>
        <div class="flex justify-between"><span>Margin</span><span class="font-bold">{{ number_format($totalProfitMargin ?? 0, 1) }}%</span></div>
      </div>
    </div>
  </div>

  <!-- Inventory Summary Cards (NEW) -->
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-6">
    <div class="card summary-indigo reveal" data-delay="100">
      <div class="flex items-center justify-between">
        <h4 class="card-title">Out of Stock</h4>
        <span class="badge bg-red-100 text-red-700"><i class="fas fa-exclamation mr-1"></i>Critical</span>
      </div>
      <p class="text-3xl font-bold mt-2 text-red-600">{{ $outOfStockProducts ?? 0 }}</p>
      <p class="muted text-xs mt-1">Products with zero quantity</p>
      <a href="{{ route('products.index', ['status' => 'out_of_stock']) }}" class="inline-block mt-3 text-indigo-600 hover:text-indigo-800 text-xs font-semibold">View →</a>
    </div>

    <div class="card summary-amber reveal" data-delay="200">
      <div class="flex items-center justify-between">
        <h4 class="card-title">Low Stock</h4>
        <span class="badge bg-yellow-100 text-yellow-700"><i class="fas fa-warning mr-1"></i>Alert</span>
      </div>
      <p class="text-3xl font-bold mt-2 text-yellow-600">{{ $lowStockCount ?? 0 }}</p>
      <p class="muted text-xs mt-1">Below reorder level</p>
      <a href="{{ route('products.index', ['status' => 'low_stock']) }}" class="inline-block mt-3 text-indigo-600 hover:text-indigo-800 text-xs font-semibold">View →</a>
    </div>

    <div class="card summary-blue reveal" data-delay="300">
      <div class="flex items-center justify-between">
        <h4 class="card-title">Expiring Soon</h4>
        <span class="badge bg-blue-100 text-blue-700"><i class="fas fa-calendar mr-1"></i>30 Days</span>
      </div>
      <p class="text-3xl font-bold mt-2 text-blue-600">{{ $expiringSoonProducts ?? 0 }}</p>
      <p class="muted text-xs mt-1">Expiring within 30 days</p>
      <a href="{{ route('products.expiring-soon') }}" class="inline-block mt-3 text-indigo-600 hover:text-indigo-800 text-xs font-semibold">View →</a>
    </div>

    <div class="card summary-rose reveal" data-delay="400">
      <div class="flex items-center justify-between">
        <h4 class="card-title">Expired</h4>
        <span class="badge bg-rose-100 text-rose-700"><i class="fas fa-trash mr-1"></i>Remove</span>
      </div>
      <p class="text-3xl font-bold mt-2 text-rose-600">{{ $expiredProducts ?? 0 }}</p>
      <p class="muted text-xs mt-1">Already expired</p>
      <a href="{{ route('products.expired') }}" class="inline-block mt-3 text-indigo-600 hover:text-indigo-800 text-xs font-semibold">View →</a>
    </div>
  </div>

  <!-- Financial Summary Cards (color-coded) -->
  <div class="grid grid-cols-1 md:grid-cols-4 gap-4 md:gap-6 mb-6">
    <div class="card summary-green reveal" data-delay="100">
      <div class="flex items-center justify-between">
        <h4 class="card-title">Gross Profit (All Time)</h4>
        <span class="badge bg-green-100 text-green-700"><i class="fas fa-arrow-trend-up mr-1"></i>Gross</span>
      </div>
      <p class="text-2xl font-bold mt-2">UGX {{ number_format($totalGrossProfit ?? 0, 0) }}</p>
      <p class="muted text-xs mt-1">Revenue − COGS</p>
    </div>

    <div class="card summary-rose reveal" data-delay="200">
      <div class="flex items-center justify-between">
        <h4 class="card-title">Total Expenses (All Time)</h4>
        <span class="badge bg-rose-100 text-rose-700"><i class="fas fa-wallet mr-1"></i>Expenses</span>
      </div>
      <p class="text-2xl font-bold mt-2">UGX {{ number_format($totalExpenses ?? 0, 0) }}</p>
      <p class="muted text-xs mt-1">Operational expenses</p>
    </div>

    <div class="card summary-amber reveal" data-delay="300">
      <div class="flex items-center justify-between">
        <h4 class="card-title">Net Profit (All Time)</h4>
        <span class="badge bg-amber-100 text-amber-700"><i class="fas fa-coins mr-1"></i>Net</span>
      </div>
      <p class="text-2xl font-bold mt-2">UGX {{ number_format($netProfitAllTime ?? 0, 0) }}</p>
      <p class="muted text-xs mt-1">Gross − Expenses</p>
    </div>

    <div class="card summary-indigo reveal" data-delay="400">
      <div class="flex items-center justify-between">
        <h4 class="card-title">Net Profit (This Month)</h4>
        <span class="badge bg-indigo-100 text-indigo-700"><i class="fas fa-calendar-check mr-1"></i>Monthly</span>
      </div>
      <p class="text-2xl font-bold mt-2">UGX {{ number_format($monthNetProfit ?? (($monthGrossProfit ?? 0) - ($monthExpenses ?? 0)), 0) }}</p>
      <p class="muted text-xs mt-1">Gross: UGX {{ number_format($monthGrossProfit ?? 0, 0) }} − Expenses: UGX {{ number_format($monthExpenses ?? 0, 0) }}</p>
    </div>
  </div>

  <!-- Period Closing Status -->
  <div class="card reveal mb-6" data-delay="500" style="border-left: 4px solid #8b5cf6; background: linear-gradient(to right, #f3f0ff, #fff);">
    <div class="flex items-center justify-between">
      <div>
        <h3 class="text-lg font-bold text-gray-800"><i class="fas fa-boxes text-purple-600 mr-2"></i>Inventory Period Status</h3>
        @if($lastClosedPeriod)
          <p class="text-gray-600 text-sm mt-2">
            <span class="badge bg-green-100 text-green-700 mr-2"><i class="fas fa-check-circle mr-1"></i>Last Closed</span>
            <strong>{{ $lastClosedPeriod->period_end->format('M d, Y') }}</strong>
          </p>
          <p class="text-gray-600 text-xs mt-1">
            {{-- Show period summary --}}
            <i class="fas fa-archive text-purple-500 mr-1"></i>{{ $lastClosedPeriod->period_start->format('M d') }} - {{ $lastClosedPeriod->period_end->format('M d, Y') }}
            | {{ $lastClosedPeriod->closing_stock }} total items locked
          </p>
          @if($lastClosedPeriod->variance != 0)
            <p class="text-sm mt-2">
              @if($lastClosedPeriod->variance > 0)
                <span class="badge bg-amber-100 text-amber-700"><i class="fas fa-arrow-up mr-1"></i>Overstock {{ number_format($lastClosedPeriod->variance, 0) }}</span>
              @else
                <span class="badge bg-red-100 text-red-700"><i class="fas fa-arrow-down mr-1"></i>Shortage {{ number_format(abs($lastClosedPeriod->variance), 0) }}</span>
              @endif
            </p>
          @endif
        @else
          <p class="text-gray-600 text-sm mt-2">
            <span class="badge bg-yellow-100 text-yellow-700"><i class="fas fa-hourglass-half mr-1"></i>Pending</span>
            No periods closed yet
          </p>
        @endif
      </div>
      <div class="text-right">
        <p class="text-gray-600 text-sm">
          <i class="fas fa-calendar mr-1 text-purple-600"></i><strong>Next Auto-Close</strong>
        </p>
        <p class="text-xl font-bold text-purple-600 mt-1">{{ $nextMonthEnd->format('M d, Y') }}</p>
        <p class="text-xs text-gray-500 mt-1">11:59 PM</p>
        <p class="text-xs text-gray-400 mt-2">
          <i class="fas fa-robot mr-1"></i>Automated
        </p>
        <a href="{{ route('inventory.periods') }}" class="inline-block mt-3 px-3 py-2 bg-purple-100 text-purple-700 text-xs font-semibold rounded-lg hover:bg-purple-200 transition">
          View History →
        </a>
      </div>
    </div>
  </div>

  <!-- Sales Trends -->
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="chart-wrap reveal" data-delay="100">
      <h3 class="text-lg font-bold text-gray-800 mb-2"><i class="fas fa-signal text-indigo-600 mr-2"></i>Sales Trend (Monthly: Jan–Dec)</h3>
      <canvas id="salesMonthlyChart" height="110"></canvas>
    </div>
    <div class="chart-wrap reveal" data-delay="200">
      <h3 class="text-lg font-bold text-gray-800 mb-2"><i class="fas fa-calendar-week text-blue-600 mr-2"></i>Sales Trend (Weekly: Mon–Sun)</h3>
      <canvas id="salesWeeklyChart" height="110"></canvas>
    </div>
  </div>

  <!-- Profit & Expenses Trends -->
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="chart-wrap reveal" data-delay="100">
      <h3 class="text-lg font-bold text-gray-800 mb-1"><i class="fas fa-chart-line text-green-600 mr-2"></i>Profit Trend (Monthly)</h3>
      <p class="muted text-xs mb-3">Net Profit = Gross Profit − Expenses</p>
      <canvas id="profitMonthlyChart" height="110"></canvas>
    </div>
    <div class="chart-wrap reveal" data-delay="200">
      <h3 class="text-lg font-bold text-gray-800 mb-2"><i class="fas fa-wallet text-rose-600 mr-2"></i>Expenses Trend (Monthly: Jan–Dec)</h3>
      <canvas id="expensesMonthlyChart" height="110"></canvas>
    </div>
  </div>

  <div class="chart-wrap reveal mb-6" data-delay="300">
    <h3 class="text-lg font-bold text-gray-800 mb-2"><i class="fas fa-wallet text-rose-600 mr-2"></i>Expenses Trend (Weekly: Mon–Sun)</h3>
    <canvas id="expensesWeeklyChart" height="110"></canvas>
  </div>

  <!-- Sales by Category (smaller pie) -->
  <div class="chart-wrap reveal mb-6" data-delay="400">
    <div class="flex items-center justify-between mb-2">
      <h3 class="text-lg font-bold text-gray-800"><i class="fas fa-tags text-orange-600 mr-2"></i>Sales by Category (All Time)</h3>
      <span class="muted text-xs">Proportional revenue share</span>
    </div>
    @if(in_array($userRole ?? optional(auth()->user()->role)->name, ['admin','manager','owner']) && isset($salesByCategory) && $salesByCategory->count())
      <div class="pie-small">
        <canvas id="salesByCategoryChart" height="160"></canvas>
      </div>
    @else
      <div class="p-6 text-center text-gray-500">No category data or insufficient permissions.</div>
    @endif
  </div>

  <!-- Revenue/Cost/Profit: Weekly + Monthly (Mon–Sun enforced) -->
  <div class="grid grid-cols-1 gap-6 md:gap-8 mb-8">
    <!-- Summary cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
      <!-- Weekly totals -->
      <div class="card summary-blue reveal" data-delay="100">
        <div class="flex items-center justify-between">
          <h4 class="card-title">Weekly Overview</h4>
          <span class="badge bg-indigo-100 text-indigo-700"><i class="fas fa-calendar-week mr-1"></i>Mon–Sun</span>
        </div>
        @php
          $weekStart = \Carbon\Carbon::now()->startOfWeek(\Carbon\Carbon::MONDAY);
          $weekDays = collect(range(0,6))->map(function($i) use ($weekStart) {
              $d = $weekStart->copy()->addDays($i);
              return (object)[
                  'key' => $d->format('Y-m-d'),
                  'label' => $d->format('D'),
                  'revenue' => 0.0,
                  'cogs' => 0.0,
                  'profit' => 0.0
              ];
          })->keyBy('key');

          foreach (collect($profitTrend ?? []) as $row) {
              $dateKey = is_array($row) ? ($row['date'] ?? null) : ($row->date ?? null);
              if ($dateKey && $weekDays->has($dateKey)) {
                  $weekDays[$dateKey]->revenue = (float)(is_array($row) ? ($row['revenue'] ?? 0) : ($row->revenue ?? 0));
                  $weekDays[$dateKey]->cogs    = (float)(is_array($row) ? ($row['cogs'] ?? 0) : ($row->cogs ?? 0));
                  $weekDays[$dateKey]->profit  = (float)(is_array($row) ? ($row['profit'] ?? 0) : ($row->profit ?? 0));
              }
          }
          $weeklySeriesMonSun = $weekDays->values();
          $weeklyRevenueTotal = (float) $weeklySeriesMonSun->sum('revenue');
          $weeklyCogsTotal    = (float) $weeklySeriesMonSun->sum('cogs');
          $weeklyProfitTotal  = $weeklyRevenueTotal - $weeklyCogsTotal;
        @endphp
        <div class="grid grid-cols-3 gap-3 mt-3">
          <div class="p-3 rounded-lg bg-indigo-50">
            <p class="text-xs text-indigo-700 font-semibold">Revenue</p>
            <p class="text-lg font-bold text-indigo-900">UGX {{ number_format($weeklyRevenueTotal, 0) }}</p>
          </div>
          <div class="p-3 rounded-lg bg-rose-50">
            <p class="text-xs text-rose-700 font-semibold">COGS</p>
            <p class="text-lg font-bold text-rose-900">UGX {{ number_format($weeklyCogsTotal, 0) }}</p>
          </div>
          <div class="p-3 rounded-lg bg-emerald-50">
            <p class="text-xs text-emerald-700 font-semibold">Profit</p>
            <p class="text-lg font-bold text-emerald-900">UGX {{ number_format($weeklyProfitTotal, 0) }}</p>
          </div>
        </div>
      </div>

      <!-- Monthly totals -->
      <div class="card summary-amber reveal" data-delay="200">
        <div class="flex items-center justify-between">
          <h4 class="card-title">Monthly Overview</h4>
          <span class="badge bg-amber-100 text-amber-700"><i class="fas fa-calendar-alt mr-1"></i>Jan–Dec {{ $selectedYear ?? now()->year }}</span>
        </div>
        @php
          $monthlySeries = collect($monthlyProfitTrend ?? []);
          $monthlyRevenueTotal = (float) $monthlySeries->sum('revenue');
          $monthlyCogsTotal    = (float) $monthlySeries->sum('cogs');
          $monthlyProfitTotal  = $monthlyRevenueTotal - $monthlyCogsTotal;
        @endphp
        <div class="grid grid-cols-3 gap-3 mt-3">
          <div class="p-3 rounded-lg bg-indigo-50">
            <p class="text-xs text-indigo-700 font-semibold">Revenue</p>
            <p class="text-lg font-bold text-indigo-900">UGX {{ number_format($monthlyRevenueTotal, 0) }}</p>
          </div>
          <div class="p-3 rounded-lg bg-rose-50">
            <p class="text-xs text-rose-700 font-semibold">COGS</p>
            <p class="text-lg font-bold text-rose-900">UGX {{ number_format($monthlyCogsTotal, 0) }}</p>
          </div>
          <div class="p-3 rounded-lg bg-emerald-50">
            <p class="text-xs text-emerald-700 font-semibold">Profit</p>
            <p class="text-lg font-bold text-emerald-900">UGX {{ number_format($monthlyProfitTotal, 0) }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- 3-line charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 md:gap-8">
      <div class="chart-wrap reveal" data-delay="150">
        <h3 class="text-lg font-bold text-gray-800 mb-2"><i class="fas fa-chart-line text-indigo-600 mr-2"></i>Weekly Trend (Revenue • COGS • Profit)</h3>
        <canvas id="weeklyRCPLine" height="110"></canvas>
      </div>
      <div class="chart-wrap reveal" data-delay="250">
        <h3 class="text-lg font-bold text-gray-800 mb-2"><i class="fas fa-chart-line text-amber-500 mr-2"></i>Monthly Trend (Revenue • COGS • Profit)</h3>
        <canvas id="monthlyRCPLine" height="110"></canvas>
      </div>
    </div>

    <!-- pies: revenue vs cost vs profit -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 md:gap-8">
      <div class="chart-wrap reveal" data-delay="200">
        <div class="flex items-center justify-between mb-2">
          <h3 class="text-lg font-bold text-gray-800"><i class="fas fa-chart-pie text-indigo-600 mr-2"></i>Weekly Composition</h3>
          <span class="muted text-xs">Revenue vs COGS vs Profit</span>
        </div>
        <div class="pie-small">
          <canvas id="weeklyRCPPie" height="160"></canvas>
        </div>
      </div>
      <div class="chart-wrap reveal" data-delay="300">
        <div class="flex items-center justify-between mb-2">
          <h3 class="text-lg font-bold text-gray-800"><i class="fas fa-chart-pie text-amber-500 mr-2"></i>Monthly Composition</h3>
          <span class="muted text-xs">Revenue vs COGS vs Profit</span>
        </div>
        <div class="pie-small">
          <canvas id="monthlyRCPPie" height="160"></canvas>
        </div>
      </div>
    </div>
  </div>

  <!-- Recent Sales + Search -->
  <div class="card reveal" data-delay="100">
    <div class="flex justify-between items-center mb-4">
      <h3 class="text-lg font-bold text-gray-800"><i class="fas fa-receipt text-indigo-600 mr-2"></i>Recent Sales</h3>
      <a href="{{ route('sales.index') }}" class="text-indigo-600 hover:text-indigo-800 text-sm">View All <i class="fas fa-arrow-right ml-1"></i></a>
    </div>
    <div class="mb-4">
      <div class="relative">
        <input id="salesSearch" type="text" placeholder="Search by sale #, customer, staff, or date..." class="w-full px-4 py-2 pl-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
        <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
      </div>
    </div>
    <div class="overflow-x-auto">
      <table class="min-w-full" id="recentSalesTable">
        <thead class="bg-gray-50">
        <tr>
          <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Sale #</th>
          <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Date</th>
          <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Customer</th>
          @if(in_array($userRole ?? optional(auth()->user()->role)->name, ['admin','manager','owner']))
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Staff</th>
          @endif
          <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Amount</th>
          <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Status</th>
        </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
        @forelse(($recentSales ?? collect()) as $sale)
          <tr class="hover:bg-gray-50 sale-row">
            <td class="px-4 py-3 text-sm font-medium text-indigo-600 sale-number"><a href="{{ route('sales.show', $sale) }}">{{ $sale->sale_number }}</a></td>
            <td class="px-4 py-3 text-sm text-gray-600 sale-date">{{ optional($sale->sale_date)->format('M d, Y h:i A') }}</td>
            <td class="px-4 py-3 text-sm text-gray-600 sale-customer">{{ optional($sale->customer)->name ?? 'Walk-in' }}</td>
            @if(in_array($userRole ?? optional(auth()->user()->role)->name, ['admin','manager','owner']))
              <td class="px-4 py-3 text-sm text-gray-600 sale-staff">{{ optional($sale->user)->name }}</td>
            @endif
            <td class="px-4 py-3 text-sm font-semibold text-green-600 sale-amount">UGX {{ number_format($sale->total ?? 0, 0) }}</td>
            <td class="px-4 py-3 text-sm">
              <span class="px-2 py-1 text-xs font-semibold rounded-full {{ ($sale->payment_status ?? '') === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                {{ ucfirst($sale->payment_status ?? 'pending') }}
              </span>
            </td>
          </tr>
        @empty
          <tr class="no-results"><td colspan="6" class="px-4 py-8 text-center text-gray-500">No sales recorded</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
    <div id="noResults" class="hidden text-center py-8">
      <i class="fas fa-search text-gray-400 text-4xl mb-2"></i>
      <p class="text-gray-500">No sales found matching your search</p>
    </div>
  </div>

  <!-- Inline JS -->
  <script>
    // URL param handlers
    function onChangePeriod(period) {
      const url = new URL(window.location.href);
      url.searchParams.set('period', period);
      if (period !== 'month' && period !== 'year') url.searchParams.delete('year');
      window.location.href = url.toString();
    }
    function onChangeYear(year) {
      const url = new URL(window.location.href);
      url.searchParams.set('year', year);
      window.location.href = url.toString();
    }

    // Reveal-on-scroll via IntersectionObserver
    (function(){
      const io = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) entry.target.classList.add('visible');
        });
      }, { threshold: 0.12, rootMargin: '0px 0px -20px 0px' });
      document.querySelectorAll('.reveal').forEach(el => io.observe(el));
    })();

    // Search
    window.addEventListener('load', function(){
      const salesSearch = document.getElementById('salesSearch');
      function filterSales() {
        const filter = (salesSearch.value || '').toLowerCase();
        const table = document.getElementById('recentSalesTable');
        const rows = table ? table.querySelectorAll('.sale-row') : [];
        const noResults = document.getElementById('noResults');
        let visible = 0;

        rows.forEach(function(row) {
          const saleNumber = (row.querySelector('.sale-number')?.textContent || '').toLowerCase();
          const customer   = (row.querySelector('.sale-customer')?.textContent || '').toLowerCase();
          const staff      = (row.querySelector('.sale-staff')?.textContent || '').toLowerCase();
          const date       = (row.querySelector('.sale-date')?.textContent || '').toLowerCase();
          const text = saleNumber + ' ' + customer + ' ' + staff + ' ' + date;

          if (text.includes(filter)) { row.style.display = ''; visible++; }
          else { row.style.display = 'none'; }
        });

        if (noResults && table) {
          if (visible === 0 && filter !== '') { noResults.classList.remove('hidden'); table.style.display = 'none'; }
          else { noResults.classList.add('hidden'); table.style.display = 'table'; }
        }
      }
      if (salesSearch) salesSearch.addEventListener('input', filterSales);
    });

  if (!window.Chart) {
    console.error('Chart.js not loaded.');
  } else {
    // Defaults
    Chart.defaults.font.family = "'Inter','system-ui','sans-serif'";
    Chart.defaults.color = '#6B7280';
    Chart.defaults.plugins.legend.position = 'bottom';
    Chart.defaults.animation = { duration: 700, easing: 'easeOutQuart' };

    // Helpers
    const fmt = v => (Number(v) || 0).toLocaleString();

    // Colors
    const colorRevenue = 'rgb(99,102,241)';   // indigo
    const colorCogs    = 'rgb(244,63,94)';    // rose
    const colorProfit  = 'rgb(16,185,129)';   // emerald
    const areaRevenue  = 'rgba(99,102,241,0.12)';
    const areaCogs     = 'rgba(244,63,94,0.12)';
    const areaProfit   = 'rgba(16,185,129,0.12)';

    // Data from Blade (must already be defined in your view above this script)
    const salesMonthlyLabels    = @json(collect($monthlySalesTrend ?? [])->pluck('label'));
    const salesMonthlyRevenue   = @json(collect($monthlySalesTrend ?? [])->pluck('revenue'));
    const salesWeeklyLabels     = @json(collect($salesWeeklyTrend ?? [])->pluck('label'));
    const salesWeeklyRevenue    = @json(collect($salesWeeklyTrend ?? [])->pluck('revenue'));

    const profitMonthlyLabels   = @json(collect($monthlyProfitTrend ?? [])->pluck('label'));
    const profitMonthlyGross    = @json(collect($monthlyProfitTrend ?? [])->pluck('profit'));
    const netMonthlyValues      = @json(collect($monthlyNetProfitTrend ?? [])->pluck('net'));

    const expensesMonthlyLabels = @json(collect($expensesMonthlyTrend ?? [])->pluck('label'));
    const expensesMonthlyValues = @json(collect($expensesMonthlyTrend ?? [])->pluck('amount'));
    const expensesWeeklyLabels  = @json(collect($expensesWeeklyTrend ?? [])->pluck('label'));
    const expensesWeeklyValues  = @json(collect($expensesWeeklyTrend ?? [])->pluck('amount'));

    const catLabels             = @json(collect($salesByCategory ?? [])->pluck('name'));
    const catTotals             = @json(collect($salesByCategory ?? [])->pluck('revenue'));

    // New RCP series (weekly Mon–Sun from PHP pre-bucket)
    const weeklyLabels   = @json(($weeklySeriesMonSun ?? collect())->pluck('label'));
    const weeklyRevenue  = @json(($weeklySeriesMonSun ?? collect())->pluck('revenue'));
    const weeklyCogs     = @json(($weeklySeriesMonSun ?? collect())->pluck('cogs'));
    const weeklyProfit   = @json(($weeklySeriesMonSun ?? collect())->pluck('profit'));

    const monthlyLabels  = @json(collect($monthlyProfitTrend ?? [])->pluck('label'));
    const monthlyRevenue = @json(collect($monthlyProfitTrend ?? [])->pluck('revenue'));
    const monthlyCogs    = @json(collect($monthlyProfitTrend ?? [])->pluck('cogs'));
    const monthlyProfit  = @json(collect($monthlyProfitTrend ?? [])->pluck('profit'));

    const weeklyRevenueTotal  = @json($weeklyRevenueTotal ?? 0);
    const weeklyCogsTotal     = @json($weeklyCogsTotal ?? 0);
    const weeklyProfitTotal   = @json($weeklyProfitTotal ?? 0);
    const monthlyRevenueTotal = @json($monthlyRevenueTotal ?? 0);
    const monthlyCogsTotal    = @json($monthlyCogsTotal ?? 0);
    const monthlyProfitTotal  = @json($monthlyProfitTotal ?? 0);

    function initChart(id, type, data, options){
      const el = document.getElementById(id);
      if (!el) return;
      new Chart(el.getContext('2d'), { type, data, options });
    }

    // 1) Sales Monthly (Revenue)
    initChart('salesMonthlyChart', 'line', {
      labels: salesMonthlyLabels,
      datasets: [
        { label: 'Revenue', data: salesMonthlyRevenue, borderColor: colorRevenue, backgroundColor: areaRevenue, fill: true, tension: .35, pointRadius: 3 }
      ]
    }, {
      interaction: { mode: 'index', intersect: false },
      plugins: {
        tooltip: {
          callbacks: {
            label: ctx => `Revenue: UGX ${fmt(ctx.parsed.y)}`
          }
        }
      },
      scales: {
        y: { beginAtZero: true, ticks: { callback: v => 'UGX ' + fmt(v) } },
        x: { grid: { display: false } }
      }
    });

    // 2) Sales Weekly (Revenue)
    initChart('salesWeeklyChart', 'bar', {
      labels: salesWeeklyLabels,
      datasets: [
        { label: 'Revenue', data: salesWeeklyRevenue, backgroundColor: 'rgba(59,130,246,0.85)', borderColor: 'rgb(59,130,246)', borderWidth: 1.5, borderRadius: 6 }
      ]
    }, {
      interaction: { mode: 'index', intersect: false },
      plugins: {
        tooltip: {
          callbacks: {
            label: ctx => `Revenue: UGX ${fmt(ctx.parsed.y)}`
          }
        }
      },
      scales: {
        y: { beginAtZero: true, ticks: { callback: v => 'UGX ' + fmt(v) } },
        x: { grid: { display: false } }
      }
    });

    // 3) Profit Monthly (Gross vs Net)
    initChart('profitMonthlyChart', 'line', {
      labels: profitMonthlyLabels,
      datasets: [
        { label: 'Gross Profit', data: profitMonthlyGross, borderColor: colorProfit, backgroundColor: areaProfit, fill: true, tension: .35, pointRadius: 3 },
        { label: 'Net Profit',   data: netMonthlyValues,  borderColor: 'rgb(234,179,8)', backgroundColor: 'rgba(234,179,8,0.12)', fill: true, tension: .35, pointRadius: 3 }
      ]
    }, {
      interaction: { mode: 'index', intersect: false },
      plugins: {
        tooltip: {
          callbacks: {
            label: ctx => `${ctx.dataset.label}: UGX ${fmt(ctx.parsed.y)}`
          }
        }
      },
      scales: {
        y: { beginAtZero: true, ticks: { callback: v => 'UGX ' + fmt(v) } },
        x: { grid: { display: false } }
      }
    });

    // 4) Expenses Monthly
    initChart('expensesMonthlyChart', 'bar', {
      labels: expensesMonthlyLabels,
      datasets: [
        { label: 'Expenses', data: expensesMonthlyValues, backgroundColor: 'rgba(244,63,94,0.85)', borderColor: 'rgb(244,63,94)', borderWidth: 1.5, borderRadius: 6 }
      ]
    }, {
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label: ctx => `Expenses: UGX ${fmt(ctx.parsed.y)}`
          }
        }
      },
      scales: {
        y: { beginAtZero: true, ticks: { callback: v => 'UGX ' + fmt(v) } },
        x: { grid: { display: false } }
      }
    });

    // 5) Expenses Weekly
    initChart('expensesWeeklyChart', 'line', {
      labels: expensesWeeklyLabels,
      datasets: [
        { label: 'Expenses', data: expensesWeeklyValues, borderColor: 'rgb(244,63,94)', backgroundColor: 'rgba(244,63,94,0.12)', fill: true, tension: .35, pointRadius: 3 }
      ]
    }, {
      interaction: { mode: 'index', intersect: false },
      plugins: {
        tooltip: {
          callbacks: {
            label: ctx => `Expenses: UGX ${fmt(ctx.parsed.y)}`
          }
        }
      },
      scales: {
        y: { beginAtZero: true, ticks: { callback: v => 'UGX ' + fmt(v) } },
        x: { grid: { display: false } }
      }
    });

    // 6) Sales by Category Pie
    initChart('salesByCategoryChart', 'pie', {
      labels: catLabels,
      datasets: [
        { data: catTotals, backgroundColor: ['#ef4444','#f59e0b','#22c55e','#3b82f6','#8b5cf6','#ec4899','#14b8a6','#f97316'], borderColor: '#fff', borderWidth: 2 }
      ]
    }, {
      plugins: {
        legend: { position: 'bottom', labels: { boxWidth: 10 } },
        tooltip: {
          callbacks: {
            label: ctx => `${ctx.label}: UGX ${fmt(ctx.parsed)}`
          }
        }
      }
    });

    // 7) Weekly RCP (Revenue, COGS, Profit) with margin in footer
    initChart('weeklyRCPLine', 'line', {
      labels: weeklyLabels,
      datasets: [
        { label: 'Revenue', data: weeklyRevenue, borderColor: colorRevenue, backgroundColor: areaRevenue, tension: .35, pointRadius: 3, fill: true },
        { label: 'COGS',    data: weeklyCogs,    borderColor: colorCogs,    backgroundColor: areaCogs,    tension: .35, pointRadius: 3, fill: true },
        { label: 'Profit',  data: weeklyProfit,  borderColor: colorProfit,  backgroundColor: areaProfit,  tension: .35, pointRadius: 3, fill: true }
      ]
    }, {
      interaction: { mode: 'index', intersect: false },
      plugins: {
        tooltip: {
          callbacks: {
            label: ctx => `${ctx.dataset.label}: UGX ${fmt(ctx.parsed.y)}`,
            afterBody: items => {
              const i = items?.[0]?.dataIndex ?? 0;
              const rev = Number(weeklyRevenue?.[i] ?? 0);
              const prof = Number(weeklyProfit?.[i] ?? 0);
              const margin = rev > 0 ? (prof / rev) * 100 : 0;
              return `Margin: ${margin.toFixed(1)}%`;
            }
          }
        }
      },
      scales: {
        y: { beginAtZero: true, ticks: { callback: v => 'UGX ' + fmt(v) } },
        x: { grid: { display: false } }
      }
    });

    // 8) Monthly RCP (Revenue, COGS, Profit) with margin in footer
    initChart('monthlyRCPLine', 'line', {
      labels: monthlyLabels,
      datasets: [
        { label: 'Revenue', data: monthlyRevenue, borderColor: colorRevenue, backgroundColor: areaRevenue, tension: .35, pointRadius: 3, fill: true },
        { label: 'COGS',    data: monthlyCogs,    borderColor: colorCogs,    backgroundColor: areaCogs,    tension: .35, pointRadius: 3, fill: true },
        { label: 'Profit',  data: monthlyProfit,  borderColor: colorProfit,  backgroundColor: areaProfit,  tension: .35, pointRadius: 3, fill: true }
      ]
    }, {
      interaction: { mode: 'index', intersect: false },
      plugins: {
        tooltip: {
          callbacks: {
            label: ctx => `${ctx.dataset.label}: UGX ${fmt(ctx.parsed.y)}`,
            afterBody: items => {
              const i = items?.[0]?.dataIndex ?? 0;
              const rev = Number(monthlyRevenue?.[i] ?? 0);
              const prof = Number(monthlyProfit?.[i] ?? 0);
              const margin = rev > 0 ? (prof / rev) * 100 : 0;
              return `Margin: ${margin.toFixed(1)}%`;
            }
          }
        }
      },
      scales: {
        y: { beginAtZero: true, ticks: { callback: v => 'UGX ' + fmt(v) } },
        x: { grid: { display: false } }
      }
    });

    // 9) Weekly RCP Pie (donut)
    (function(){
      const el = document.getElementById('weeklyRCPPie'); if (!el) return;
      const total = Number(weeklyRevenueTotal || 0) + Number(weeklyCogsTotal || 0) + Number(weeklyProfitTotal || 0);
      new Chart(el.getContext('2d'), {
        type: 'doughnut',
        data: {
          labels: ['Revenue','COGS','Profit'],
          datasets: [{ data: [weeklyRevenueTotal, weeklyCogsTotal, weeklyProfitTotal], backgroundColor: [colorRevenue, colorCogs, colorProfit], borderColor: '#fff', borderWidth: 2 }]
        },
        options: {
          plugins: {
            legend: { position: 'bottom', labels: { boxWidth: 10 } },
            tooltip: {
              callbacks: {
                label: ctx => {
                  const val = Number(ctx.parsed || 0);
                  const pct = total ? ((val / total) * 100).toFixed(1) : '0.0';
                  return `${ctx.label}: UGX ${fmt(val)} (${pct}%)`;
                },
                footer: () => `Total: UGX ${fmt(total)}`
              }
            }
          },
          cutout: '60%'
        }
      });
    })();

    // 10) Monthly RCP Pie (donut)
    (function(){
      const el = document.getElementById('monthlyRCPPie'); if (!el) return;
      const total = Number(monthlyRevenueTotal || 0) + Number(monthlyCogsTotal || 0) + Number(monthlyProfitTotal || 0);
      new Chart(el.getContext('2d'), {
        type: 'doughnut',
        data: {
          labels: ['Revenue','COGS','Profit'],
          datasets: [{ data: [monthlyRevenueTotal, monthlyCogsTotal, monthlyProfitTotal], backgroundColor: [colorRevenue, colorCogs, colorProfit], borderColor: '#fff', borderWidth: 2 }]
        },
        options: {
          plugins: {
            legend: { position: 'bottom', labels: { boxWidth: 10 } },
            tooltip: {
              callbacks: {
                label: ctx => {
                  const val = Number(ctx.parsed || 0);
                  const pct = total ? ((val / total) * 100).toFixed(1) : '0.0';
                  return `${ctx.label}: UGX ${fmt(val)} (${pct}%)`;
                },
                footer: () => `Total: UGX ${fmt(total)}`
              }
            }
          },
          cutout: '60%'
        }
      });
    })();
  }
  </script>
@endsection