<?php

namespace App\Http\Controllers;

use App\Models\{Sale, Product, Customer, User, Category, Expense};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB};
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Display the dashboard with role-based data + analytics
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $businessId = $user->business_id;
        $userRole = $user->role->name;

        // Filters
        $period = $request->get('period', 'week'); // day, week, month, year
        $selectedYear = $request->get('year', now()->year);
        
        
        // Base query (role-aware)
        $salesQuery = Sale::where('business_id', $businessId);
        if (in_array($userRole, ['cashier', 'staff'])) {
            $salesQuery->where('user_id', $user->id);
        }

        // TODAY
        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();

        $todayStats = (clone $salesQuery)
            ->whereBetween('sale_date', [$todayStart, $todayEnd])
            ->select(
                DB::raw('COUNT(*) as sales_count'),
                DB::raw('COALESCE(SUM(total), 0) as revenue'),
                DB::raw('COALESCE(AVG(total), 0) as avg_sale')
            )->first();

        $todaySales = $todayStats->sales_count ?? 0;
        $todayRevenue = $todayStats->revenue ?? 0;
        $todayAvgSale = $todayStats->avg_sale ?? 0;

        $todayCOGS = $this->calculateCOGS($businessId, $todayStart, $todayEnd);
        $todayGrossProfit = $todayRevenue - $todayCOGS;
        $todayProfitMargin = $todayRevenue > 0 ? ($todayGrossProfit / $todayRevenue) * 100 : 0;
        $todayExpenses = $this->sumExpenses($businessId, $todayStart, $todayEnd);

        // WEEK (Mon–Sun)
        $weekStart = now()->copy()->startOfWeek(Carbon::MONDAY);
        $weekEnd = now()->copy()->endOfWeek(Carbon::SUNDAY);

        $weekStats = (clone $salesQuery)
            ->whereBetween('sale_date', [$weekStart, $weekEnd])
            ->select(
                DB::raw('COUNT(*) as sales_count'),
                DB::raw('COALESCE(SUM(total), 0) as revenue')
            )->first();

        $weekSales = $weekStats->sales_count ?? 0;
        $weekRevenue = $weekStats->revenue ?? 0;

        $weekCOGS = $this->calculateCOGS($businessId, $weekStart, $weekEnd);
        $weekGrossProfit = $weekRevenue - $weekCOGS;
        $weekProfitMargin = $weekRevenue > 0 ? ($weekGrossProfit / $weekRevenue) * 100 : 0;
        $weekExpenses = $this->sumExpenses($businessId, $weekStart, $weekEnd);

        // MONTH
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $monthStats = (clone $salesQuery)
            ->whereBetween('sale_date', [$monthStart, $monthEnd])
            ->select(
                DB::raw('COUNT(*) as sales_count'),
                DB::raw('COALESCE(SUM(total), 0) as revenue')
            )->first();

        $monthSales = $monthStats->sales_count ?? 0;
        $monthRevenue = $monthStats->revenue ?? 0;

        $monthCOGS = $this->calculateCOGS($businessId, $monthStart, $monthEnd);
        $monthGrossProfit = $monthRevenue - $monthCOGS;
        $monthProfitMargin = $monthRevenue > 0 ? ($monthGrossProfit / $monthRevenue) * 100 : 0;
        $monthExpenses = $this->sumExpenses($businessId, $monthStart, $monthEnd);

        // ALL TIME
        $allTimeStats = (clone $salesQuery)
            ->select(
                DB::raw('COUNT(*) as sales_count'),
                DB::raw('COALESCE(SUM(total), 0) as revenue')
            )->first();

        $totalSales = $allTimeStats->sales_count ?? 0;
        $totalRevenue = $allTimeStats->revenue ?? 0;

        $totalCOGS = $this->calculateCOGS($businessId, null, null);
        $totalGrossProfit = $totalRevenue - $totalCOGS;
        $totalProfitMargin = $totalRevenue > 0 ? ($totalGrossProfit / $totalRevenue) * 100 : 0;
        $totalExpenses = $this->sumExpenses($businessId, null, null);
        $netProfitAllTime = $totalGrossProfit - $totalExpenses;

        // RECENT SALES
        $recentSales = (clone $salesQuery)
            ->with(['customer', 'user'])
            ->latest('sale_date')
            ->limit(50)
            ->get();

        // Trends (existing)
        $profitTrend = $this->getDailyProfitTrend($businessId);             // last 7 days
        $weeklyProfitTrend = $this->getWeeklyProfitTrend($businessId);      // last 12 weeks
        $monthlyProfitTrend = $this->getMonthlyProfitTrend($businessId, $selectedYear); // 12 months (Gross)
        $salesTrendData = $this->getSalesTrend($businessId, $period, $selectedYear);

        // NEW: Sales weekly (Mon–Sun current week) and monthly Jan–Dec
        $salesWeeklyTrend = $this->getSalesWeeklyMonSun($businessId, $weekStart, $weekEnd);
        $monthlySalesTrend = $this->getMonthlySalesTrend($businessId, $selectedYear);

        // NEW: Expenses weekly/monthly + Net Profit monthly (Gross − Expenses)
        $expensesWeeklyTrend = $this->getExpensesWeeklyMonSun($businessId, $weekStart);
        $expensesMonthlyTrend = $this->getExpensesMonthlyTrend($businessId, $selectedYear);
        $monthlyNetProfitTrend = $this->mergeNetProfitMonthly($monthlyProfitTrend, $expensesMonthlyTrend);

        // Hourly sales (today)
        $hourlyData = (clone $salesQuery)
            ->whereDate('sale_date', today())
            ->select(
                DB::raw('HOUR(sale_date) as hour'),
                DB::raw('COUNT(*) as count'),
                DB::raw('COALESCE(SUM(total), 0) as revenue')
            )->groupBy('hour')->orderBy('hour')->get();

        $hourlyDataFilled = [];
        for ($i = 0; $i < 24; $i++) {
            $found = $hourlyData->firstWhere('hour', $i);
            $hourlyDataFilled[] = [
                'hour' => $i,
                'count' => $found ? $found->count : 0,
                'revenue' => $found ? $found->revenue : 0,
            ];
        }

        // Notifications
        $expiredProducts = Product::where('business_id', $businessId)
            ->whereNotNull('expiry_date')->where('expiry_date', '<', now())->count();

        $expiringSoonProducts = Product::where('business_id', $businessId)
            ->whereNotNull('expiry_date')->whereBetween('expiry_date', [now(), now()->addDays(30)])->count();

        $outOfStockProducts = Product::where('business_id', $businessId)->where('quantity', 0)->count();

        $lowStockCount = Product::where('business_id', $businessId)
            ->whereColumn('quantity', '<=', 'reorder_level')->where('quantity', '>', 0)->count();

        // Admin-only analytics
        $lowStockProducts = $topSellingProducts = $topProfitableProducts = $lossMakingProducts = $profitByCategory = $salesByCategory = $totalCustomers = $activeStaff = $topStaffPerformance = null;

        if (in_array($userRole, ['admin', 'manager', 'owner'])) {
            $lowStockProducts = Product::where('business_id', $businessId)
                ->whereColumn('quantity', '<=', 'reorder_level')
                ->where('quantity', '>', 0)
                ->orderBy('quantity', 'asc')
                ->limit(10)->get();

            $topSellingProducts = Product::where('products.business_id', $businessId)
                ->join('sale_items', 'products.id', '=', 'sale_items.product_id')
                ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                ->select(
                    'products.id',
                    'products.name',
                    'products.image',
                    DB::raw('SUM(sale_items.quantity) as units_sold'),
                    DB::raw('SUM(sale_items.total) as revenue')
                )
                ->groupBy('products.id', 'products.name', 'products.image')
                ->orderByDesc('revenue')
                ->limit(10)->get();

            $topProfitableProducts = $this->getTopProfitableProducts($businessId, null, null);
            $lossMakingProducts = $this->getLossMakingProducts($businessId, null, null);
            $profitByCategory = $this->getProfitByCategory($businessId, null, null);

            $salesByCategory = DB::table('sale_items')
                ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                ->join('products', 'sale_items.product_id', '=', 'products.id')
                ->join('categories', 'products.category_id', '=', 'categories.id')
                ->where('sales.business_id', $businessId)
                ->select('categories.name', DB::raw('COALESCE(SUM(sale_items.total), 0) as revenue'))
                ->groupBy('categories.name')
                ->orderByDesc('revenue')->get();

            $totalCustomers = Customer::where('business_id', $businessId)->where('is_active', true)->count();
            $activeStaff = User::where('business_id', $businessId)->where('is_active', true)->count();

            $topStaffPerformance = User::where('business_id', $businessId)
                ->withCount('sales')->withSum('sales', 'total')
                ->having('sales_count', '>', 0)
                ->orderByDesc('sales_sum_total')
                ->limit(10)->get();
        }

        // Years list
        $availableYears = Sale::where('business_id', $businessId)
            ->selectRaw('DISTINCT YEAR(sale_date) as year')
            ->orderByDesc('year')->pluck('year');
        if ($availableYears->isEmpty() || !$availableYears->contains(now()->year)) {
            $availableYears->prepend(now()->year);
        }

        // Period closing information
        $lastClosedPeriod = \App\Models\InventoryPeriod::where('business_id', $businessId)
            ->where('status', 'closed')
            ->latest('period_end')
            ->first();
        
        $lastMonthEnd = now()->subMonth()->endOfMonth();
        $nextMonthEnd = now()->endOfMonth();
        if (now()->day >= 28) { // If we're near or past month end
            $nextMonthEnd = now()->addMonth()->endOfMonth();
        }

        return view('dashboard', [
            // Today's data
            'todaySales' => $todaySales,
            'todayRevenue' => $todayRevenue,
            'todayAvgSale' => $todayAvgSale,
            'todayCOGS' => $todayCOGS,
            'todayGrossProfit' => $todayGrossProfit,
            'todayProfitMargin' => $todayProfitMargin,
            'todayExpenses' => $todayExpenses,

            // This week
            'weekSales' => $weekSales,
            'weekRevenue' => $weekRevenue,
            'weekCOGS' => $weekCOGS,
            'weekGrossProfit' => $weekGrossProfit,
            'weekProfitMargin' => $weekProfitMargin,
            'weekExpenses' => $weekExpenses,

            // This month
            'monthSales' => $monthSales,
            'monthRevenue' => $monthRevenue,
            'monthCOGS' => $monthCOGS,
            'monthGrossProfit' => $monthGrossProfit,
            'monthProfitMargin' => $monthProfitMargin,
            'monthExpenses' => $monthExpenses,

            // All time
            'totalSales' => $totalSales,
            'totalRevenue' => $totalRevenue,
            'totalCOGS' => $totalCOGS,
            'totalGrossProfit' => $totalGrossProfit,
            'totalProfitMargin' => $totalProfitMargin,
            'totalExpenses' => $totalExpenses,
            'netProfitAllTime' => $netProfitAllTime,

            // Lists and charts
            'recentSales' => $recentSales,
            'salesTrend' => $salesTrendData, // dynamic based on period

            // Profit trends
            'profitTrend' => $profitTrend,                 // daily 7
            'weeklyProfitTrend' => $weeklyProfitTrend,     // weekly 12
            'monthlyProfitTrend' => $monthlyProfitTrend,   // monthly 12 (Gross)
            'monthlyNetProfitTrend' => $monthlyNetProfitTrend, // monthly 12 (Net = Gross - Expenses)

            // Sales trends
            'monthlySalesTrend' => $monthlySalesTrend,     // monthly revenue
            'salesWeeklyTrend' => $salesWeeklyTrend,       // current week Mon–Sun

            // Expenses trends
            'expensesWeeklyTrend' => $expensesWeeklyTrend, // current week Mon–Sun
            'expensesMonthlyTrend' => $expensesMonthlyTrend, // monthly 12

            // Other charts
            'paymentMethodData' => (clone $salesQuery)
                ->select('payment_method', DB::raw('COUNT(*) as count'), DB::raw('COALESCE(SUM(total), 0) as total'))
                ->groupBy('payment_method')->get(),
            'hourlyData' => $hourlyDataFilled,

            // Admin/Manager/Owner only
            'lowStockProducts' => $lowStockProducts,
            'topSellingProducts' => $topSellingProducts,
            'topProfitableProducts' => $topProfitableProducts,
            'lossMakingProducts' => $lossMakingProducts,
            'profitByCategory' => $profitByCategory,
            'salesByCategory' => $salesByCategory,
            'totalCustomers' => $totalCustomers,
            'activeStaff' => $activeStaff,
            'topStaffPerformance' => $topStaffPerformance,

            // Notifications
            'expiredProducts' => $expiredProducts,
            'expiringSoonProducts' => $expiringSoonProducts,
            'outOfStockProducts' => $outOfStockProducts,
            'lowStockCount' => $lowStockCount,

            // Filters
            'period' => $period,
            'selectedYear' => $selectedYear,
            'availableYears' => $availableYears,

            // Packages
            'packages' => \App\Models\Package::where('is_active', true)->orderBy('price')->get(),

            // Role
            'userRole' => $userRole,

            // Period closing
            'lastClosedPeriod' => $lastClosedPeriod,
            'nextMonthEnd' => $nextMonthEnd,
        ]);
    }

    /**
     * Calculate COGS in a period
     */
    private function calculateCOGS($businessId, $startDate = null, $endDate = null)
    {
        $query = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->where('sales.business_id', $businessId);

        if ($startDate) $query->where('sales.sale_date', '>=', $startDate);
        if ($endDate) $query->where('sales.sale_date', '<=', $endDate);

        $cogs = $query->select(DB::raw('SUM(sale_items.quantity * products.cost_price) as total_cogs'))->first();
        return (float)($cogs->total_cogs ?? 0);
    }

    /**
     * Sum expenses in a period (expects expenses.date_spent)
     */
    private function sumExpenses($businessId, $startDate = null, $endDate = null)
    {
        $query = Expense::where('business_id', $businessId);
        if ($startDate) $query->where('date_spent', '>=', $startDate);
        if ($endDate) $query->where('date_spent', '<=', $endDate);
        return (float) ($query->sum('amount') ?? 0);
    }

    /**
     * Weekly sales by weekday (Mon–Sun) for current week
     */
    private function getSalesWeeklyMonSun($businessId, Carbon $weekStart, Carbon $weekEnd)
    {
        $base = [];
        for ($i = 0; $i < 7; $i++) {
            $d = $weekStart->copy()->addDays($i);
            $base[$d->format('Y-m-d')] = [
                'label' => $d->format('D'),
                'date' => $d->format('Y-m-d'),
                'revenue' => 0.0
            ];
        }

        $rows = Sale::where('business_id', $businessId)
            ->whereBetween('sale_date', [$weekStart, $weekEnd])
            ->select(DB::raw('DATE(sale_date) as d'), DB::raw('COALESCE(SUM(total),0) as revenue'))
            ->groupBy('d')->get();

        foreach ($rows as $r) {
            if (isset($base[$r->d])) {
                $base[$r->d]['revenue'] = (float) $r->revenue;
            }
        }
        return collect(array_values($base));
    }

    /**
     * Weekly expenses by weekday (Mon–Sun)
     */
    private function getExpensesWeeklyMonSun($businessId, Carbon $weekStart)
    {
        $base = [];
        for ($i = 0; $i < 7; $i++) {
            $d = $weekStart->copy()->addDays($i);
            $base[$d->format('Y-m-d')] = [
                'label' => $d->format('D'),
                'date' => $d->format('Y-m-d'),
                'amount' => 0.0
            ];
        }

        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        $rows = Expense::where('business_id', $businessId)
            ->whereBetween('date_spent', [$weekStart, $weekEnd])
            ->select(DB::raw('DATE(date_spent) as d'), DB::raw('COALESCE(SUM(amount),0) as total'))
            ->groupBy('d')->get();

        foreach ($rows as $r) {
            if (isset($base[$r->d])) {
                $base[$r->d]['amount'] = (float) $r->total;
            }
        }
        return collect(array_values($base));
    }

    /**
     * Expenses monthly trend for a given year
     */
    private function getExpensesMonthlyTrend($businessId, $year)
    {
        $out = [];
        for ($i = 1; $i <= 12; $i++) {
            $start = Carbon::create($year, $i, 1)->startOfMonth();
            $end = Carbon::create($year, $i, 1)->endOfMonth();
            $amount = Expense::where('business_id', $businessId)
                ->whereBetween('date_spent', [$start, $end])
                ->sum('amount') ?? 0;
            $out[] = [
                'label' => $start->format('M'),
                'month' => $i,
                'amount' => (float) $amount,
            ];
        }
        return collect($out);
    }

    /**
     * Merge monthly Gross Profit trend with Expenses to produce Net Profit monthly
     */
    private function mergeNetProfitMonthly($monthlyProfitTrend, $expensesMonthlyTrend)
    {
        $expByMonth = $expensesMonthlyTrend->keyBy('month');
        return $monthlyProfitTrend->map(function ($row) use ($expByMonth) {
            $m = is_array($row) ? $row['month'] : $row->month;
            $label = is_array($row) ? $row['label'] : $row->label;
            $gross = (float) (is_array($row) ? $row['profit'] : $row->profit);
            $exp = (float) ($expByMonth->get($m)->amount ?? 0);
            return [
                'label' => $label,
                'month' => $m,
                'gross' => $gross,
                'expenses' => $exp,
                'net' => $gross - $exp,
            ];
        });
    }

    /**
     * Get daily profit trend (last 7 days)
     */
    private function getDailyProfitTrend($businessId)
    {
        $days = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $startDate = $date->copy()->startOfDay();
            $endDate = $date->copy()->endOfDay();

            $revenue = Sale::where('business_id', $businessId)
                ->whereBetween('sale_date', [$startDate, $endDate])->sum('total');

            $cogs = $this->calculateCOGS($businessId, $startDate, $endDate);
            $profit = $revenue - $cogs;

            $days[] = [
                'date' => $date->format('Y-m-d'),
                'label' => $date->format('D'),
                'revenue' => (float) $revenue,
                'cogs' => (float) $cogs,
                'profit' => (float) $profit,
                'profit_margin' => $revenue > 0 ? (($profit / $revenue) * 100) : 0,
            ];
        }
        return collect($days);
    }

    /**
     * Get weekly profit trend (last 12 weeks)
     */
    private function getWeeklyProfitTrend($businessId)
    {
        $weeks = [];
        for ($i = 11; $i >= 0; $i--) {
            $weekStart = now()->subWeeks($i)->startOfWeek();
            $weekEnd = now()->subWeeks($i)->endOfWeek();

            $revenue = Sale::where('business_id', $businessId)
                ->whereBetween('sale_date', [$weekStart, $weekEnd])->sum('total');

            $cogs = $this->calculateCOGS($businessId, $weekStart, $weekEnd);
            $profit = $revenue - $cogs;

            $weeks[] = [
                'label' => 'Week ' . $weekStart->weekOfYear,
                'date' => $weekStart->format('M d'),
                'revenue' => (float) $revenue,
                'cogs' => (float) $cogs,
                'profit' => (float) $profit,
                'profit_margin' => $revenue > 0 ? (($profit / $revenue) * 100) : 0,
            ];
        }
        return collect($weeks);
    }

    /**
     * Get monthly profit trend (selected year) - GROSS ONLY
     */
    private function getMonthlyProfitTrend($businessId, $year)
    {
        $months = [];
        for ($i = 1; $i <= 12; $i++) {
            $monthStart = Carbon::create($year, $i, 1)->startOfMonth();
            $monthEnd = Carbon::create($year, $i, 1)->endOfMonth();

            $revenue = Sale::where('business_id', $businessId)
                ->whereBetween('sale_date', [$monthStart, $monthEnd])->sum('total');

            $cogs = $this->calculateCOGS($businessId, $monthStart, $monthEnd);
            $profit = $revenue - $cogs;

            $months[] = [
                'label' => $monthStart->format('M'),
                'month' => $i,
                'revenue' => (float) $revenue,
                'cogs' => (float) $cogs,
                'profit' => (float) $profit,
                'profit_margin' => $revenue > 0 ? (($profit / $revenue) * 100) : 0,
            ];
        }
        return collect($months);
    }

    /**
     * SALES TREND HELPERS (existing)
     */
    private function getSalesTrend($businessId, $period, $year)
    {
        switch ($period) {
            case 'day': return $this->getDailySalesTrend($businessId);
            case 'week': return $this->getWeeklySalesTrend($businessId);
            case 'month': return $this->getMonthlySalesTrend($businessId, $year);
            case 'year': return $this->getYearlySalesTrend($businessId);
            default: return $this->getDailySalesTrend($businessId);
        }
    }

    private function getDailySalesTrend($businessId)
    {
        $trend = Sale::where('business_id', $businessId)
            ->whereBetween('sale_date', [now()->subDays(6)->startOfDay(), now()->endOfDay()])
            ->select(
                DB::raw('DATE(sale_date) as date'),
                DB::raw('COUNT(*) as count'),
                DB::raw('COALESCE(SUM(total), 0) as revenue')
            )->groupBy('date')->orderBy('date', 'asc')->get();

        return $this->fillMissingDates($trend, 7);
    }

    private function getWeeklySalesTrend($businessId)
    {
        $weeks = [];
        for ($i = 11; $i >= 0; $i--) {
            $weekStart = now()->subWeeks($i)->startOfWeek();
            $weekEnd = now()->subWeeks($i)->endOfWeek();

            $weekStats = Sale::where('business_id', $businessId)
                ->whereBetween('sale_date', [$weekStart, $weekEnd])
                ->select(
                    DB::raw('COUNT(*) as count'),
                    DB::raw('COALESCE(SUM(total), 0) as revenue')
                )->first();

            $weeks[] = [
                'label' => 'Week ' . $weekStart->weekOfYear,
                'date' => $weekStart->format('M d'),
                'count' => $weekStats->count ?? 0,
                'revenue' => $weekStats->revenue ?? 0,
            ];
        }
        return collect($weeks);
    }

    private function getMonthlySalesTrend($businessId, $year)
    {
        $monthlyData = Sale::where('business_id', $businessId)
            ->whereYear('sale_date', $year)
            ->select(
                DB::raw('MONTH(sale_date) as month'),
                DB::raw('COUNT(*) as count'),
                DB::raw('COALESCE(SUM(total), 0) as revenue')
            )->groupBy('month')->orderBy('month')->get()->keyBy('month');

        $months = [];
        for ($i = 1; $i <= 12; $i++) {
            $m = $monthlyData->get($i);
            $months[] = [
                'label' => date('M', mktime(0, 0, 0, $i, 1)),
                'month' => $i,
                'count' => $m ? $m->count : 0,
                'revenue' => $m ? $m->revenue : 0,
            ];
        }
        return collect($months);
    }

    private function getYearlySalesTrend($businessId)
    {
        $years = [];
        for ($i = 4; $i >= 0; $i--) {
            $year = now()->subYears($i)->year;

            $yearStats = Sale::where('business_id', $businessId)
                ->whereYear('sale_date', $year)
                ->select(
                    DB::raw('COUNT(*) as count'),
                    DB::raw('COALESCE(SUM(total), 0) as revenue')
                )->first();

            $years[] = [
                'label' => (string) $year,
                'year' => $year,
                'count' => $yearStats->count ?? 0,
                'revenue' => $yearStats->revenue ?? 0,
            ];
        }
        return collect($years);
    }

    private function fillMissingDates($salesTrend, $days = 7)
    {
        $dates = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dates[$date] = (object)[
                'date' => $date,
                'label' => now()->subDays($i)->format('M d'),
                'count' => 0,
                'revenue' => 0,
            ];
        }

        foreach ($salesTrend as $trend) {
            if (isset($dates[$trend->date])) {
                $dates[$trend->date] = (object)[
                    'date' => $trend->date,
                    'label' => Carbon::parse($trend->date)->format('M d'),
                    'count' => $trend->count,
                    'revenue' => $trend->revenue,
                ];
            }
        }
        return collect(array_values($dates));
    }

    /**
     * PROFITABILITY AND CATEGORY HELPERS
     */
    private function getTopProfitableProducts($businessId, $startDate = null, $endDate = null)
    {
        $query = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->where('sales.business_id', $businessId);

        if ($startDate && $endDate) {
            $query->whereBetween('sales.sale_date', [$startDate, $endDate]);
        }

        return $query->select(
                'products.id',
                'products.name',
                'products.image',
                'products.cost_price',
                'products.selling_price',
                DB::raw('SUM(sale_items.quantity) as units_sold'),
                DB::raw('SUM(sale_items.total) as revenue'),
                DB::raw('SUM(sale_items.quantity * products.cost_price) as total_cost'),
                DB::raw('SUM(sale_items.total - (sale_items.quantity * products.cost_price)) as profit'),
                DB::raw('((SUM(sale_items.total - (sale_items.quantity * products.cost_price)) / NULLIF(SUM(sale_items.total), 0)) * 100) as profit_margin')
            )
            ->groupBy('products.id', 'products.name', 'products.image', 'products.cost_price', 'products.selling_price')
            ->having('profit', '>', 0)
            ->orderByDesc('profit')
            ->limit(10)->get();
    }

    private function getLossMakingProducts($businessId, $startDate = null, $endDate = null)
    {
        $query = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->where('sales.business_id', $businessId);

        if ($startDate && $endDate) {
            $query->whereBetween('sales.sale_date', [$startDate, $endDate]);
        }

        return $query->select(
                'products.id',
                'products.name',
                'products.cost_price',
                DB::raw('AVG(sale_items.unit_price) as avg_selling_price'),
                DB::raw('SUM(sale_items.quantity) as units_sold'),
                DB::raw('SUM(sale_items.total) as revenue'),
                DB::raw('SUM(sale_items.quantity * products.cost_price) as total_cost'),
                DB::raw('SUM(sale_items.total - (sale_items.quantity * products.cost_price)) as loss')
            )
            ->groupBy('products.id', 'products.name', 'products.cost_price')
            ->having('loss', '<', 0)
            ->orderBy('loss', 'asc')
            ->limit(10)->get();
    }

    private function getProfitByCategory($businessId, $startDate = null, $endDate = null)
    {
        $query = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->where('sales.business_id', $businessId);

        if ($startDate && $endDate) {
            $query->whereBetween('sales.sale_date', [$startDate, $endDate]);
        }

        return $query->select(
                'categories.name',
                DB::raw('SUM(sale_items.total) as revenue'),
                DB::raw('SUM(sale_items.quantity * products.cost_price) as total_cost'),
                DB::raw('SUM(sale_items.total - (sale_items.quantity * products.cost_price)) as profit'),
                DB::raw('((SUM(sale_items.total - (sale_items.quantity * products.cost_price)) / NULLIF(SUM(sale_items.total), 0)) * 100) as profit_margin')
            )
            ->groupBy('categories.name')
            ->orderByDesc('profit')
            ->get();
    }

    /**
     * ANNUAL REPORT METHODS (unchanged from your original)
     */
    public function annual(Request $request)
    {
        $user = Auth::user();
        $businessId = $user->business_id;
        $userRole = $user->role->name;

        if (!in_array($userRole, ['admin', 'manager', 'owner'])) {
            abort(403, 'Access denied.');
        }

        $selectedYear = $request->get('year', now()->year);
        $previousYear = $selectedYear - 1;

        $annualStats = Sale::where('business_id', $businessId)
            ->whereYear('sale_date', $selectedYear)
            ->select(
                DB::raw('COUNT(*) as total_sales'),
                DB::raw('COALESCE(SUM(total), 0) as total_revenue'),
                DB::raw('COALESCE(AVG(total), 0) as avg_sale')
            )
            ->first();

        $totalSales = $annualStats->total_sales ?? 0;
        $totalRevenue = $annualStats->total_revenue ?? 0;
        $avgSale = $annualStats->avg_sale ?? 0;

        $yearStart = Carbon::create($selectedYear, 1, 1)->startOfYear();
        $yearEnd = Carbon::create($selectedYear, 12, 31)->endOfYear();
        $annualCOGS = $this->calculateCOGS($businessId, $yearStart, $yearEnd);
        $annualProfit = $totalRevenue - $annualCOGS;
        $annualProfitMargin = $totalRevenue > 0 ? ($annualProfit / $totalRevenue) * 100 : 0;

        $previousYearStats = Sale::where('business_id', $businessId)
            ->whereYear('sale_date', $previousYear)
            ->select(
                DB::raw('COUNT(*) as total_sales'),
                DB::raw('COALESCE(SUM(total), 0) as total_revenue')
            )
            ->first();

        $previousYearSales = $previousYearStats->total_sales ?? 0;
        $previousYearRevenue = $previousYearStats->total_revenue ?? 0;

        $prevYearStart = Carbon::create($previousYear, 1, 1)->startOfYear();
        $prevYearEnd = Carbon::create($previousYear, 12, 31)->endOfYear();
        $previousYearCOGS = $this->calculateCOGS($businessId, $prevYearStart, $prevYearEnd);
        $previousYearProfit = $previousYearRevenue - $previousYearCOGS;

        $revenueGrowth = $previousYearRevenue > 0
            ? (($totalRevenue - $previousYearRevenue) / $previousYearRevenue) * 100
            : 0;

        $salesGrowth = $previousYearSales > 0
            ? (($totalSales - $previousYearSales) / $previousYearSales) * 100
            : 0;

        $profitGrowth = $previousYearProfit > 0
            ? (($annualProfit - $previousYearProfit) / $previousYearProfit) * 100
            : 0;

        $monthlyData = Sale::where('business_id', $businessId)
            ->whereYear('sale_date', $selectedYear)
            ->select(
                DB::raw('MONTH(sale_date) as month'),
                DB::raw('COUNT(*) as sales'),
                DB::raw('COALESCE(SUM(total), 0) as revenue')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        $monthlyRevenue = [];
        $monthlySales = [];
        $monthlyProfit = [];
        $monthNames = [];

        for ($i = 1; $i <= 12; $i++) {
            $monthData = $monthlyData->get($i);
            $revenue = $monthData ? (float) $monthData->revenue : 0;

            $monthStart = Carbon::create($selectedYear, $i, 1)->startOfMonth();
            $monthEnd = Carbon::create($selectedYear, $i, 1)->endOfMonth();
            $cogs = $this->calculateCOGS($businessId, $monthStart, $monthEnd);
            $profit = $revenue - $cogs;

            $monthlyRevenue[] = $revenue;
            $monthlySales[] = $monthData ? (int) $monthData->sales : 0;
            $monthlyProfit[] = $profit;
            $monthNames[] = date('M', mktime(0, 0, 0, $i, 1));
        }

        $monthlyTopPerformers = [];

        for ($month = 1; $month <= 12; $month++) {
            $topStaff = User::where('business_id', $businessId)
                ->withSum(['sales' => function ($q) use ($selectedYear, $month) {
                    $q->whereYear('sale_date', $selectedYear)
                        ->whereMonth('sale_date', $month);
                }], 'total')
                ->withCount(['sales' => function ($q) use ($selectedYear, $month) {
                    $q->whereYear('sale_date', $selectedYear)
                        ->whereMonth('sale_date', $month);
                }])
                ->having('sales_count', '>', 0)
                ->orderByDesc('sales_sum_total')
                ->first();

            $monthlyTopPerformers[$month] = [
                'month_name' => date('F', mktime(0, 0, 0, $month, 1)),
                'staff' => $topStaff,
                'revenue' => $topStaff ? $topStaff->sales_sum_total : 0,
                'sales_count' => $topStaff ? $topStaff->sales_count : 0,
            ];
        }

        $quarters = [
            'Q1' => ['months' => [1, 2, 3], 'sales' => 0, 'revenue' => 0, 'profit' => 0],
            'Q2' => ['months' => [4, 5, 6], 'sales' => 0, 'revenue' => 0, 'profit' => 0],
            'Q3' => ['months' => [7, 8, 9], 'sales' => 0, 'revenue' => 0, 'profit' => 0],
            'Q4' => ['months' => [10, 11, 12], 'sales' => 0, 'revenue' => 0, 'profit' => 0],
        ];

        foreach ($quarters as $quarter => $data) {
            $quarterStats = Sale::where('business_id', $businessId)
                ->whereYear('sale_date', $selectedYear)
                ->whereIn(DB::raw('MONTH(sale_date)'), $data['months'])
                ->select(
                    DB::raw('COUNT(*) as sales'),
                    DB::raw('COALESCE(SUM(total), 0) as revenue')
                )->first();

            $revenue = $quarterStats->revenue ?? 0;

            $quarterStart = Carbon::create($selectedYear, $data['months'][0], 1)->startOfMonth();
            $quarterEnd = Carbon::create($selectedYear, $data['months'][2], 1)->endOfMonth();
            $cogs = $this->calculateCOGS($businessId, $quarterStart, $quarterEnd);
            $profit = $revenue - $cogs;

            $quarters[$quarter]['sales'] = $quarterStats->sales ?? 0;
            $quarters[$quarter]['revenue'] = $revenue;
            $quarters[$quarter]['profit'] = $profit;
        }

        $bestMonth = $monthlyData->sortByDesc('revenue')->first();
        $worstMonth = $monthlyData->where('revenue', '>', 0)->sortBy('revenue')->first();

        $paymentMethods = Sale::where('business_id', $businessId)
            ->whereYear('sale_date', $selectedYear)
            ->select(
                'payment_method',
                DB::raw('COUNT(*) as count'),
                DB::raw('COALESCE(SUM(total), 0) as total')
            )->groupBy('payment_method')->get();

        $topProducts = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->where('sales.business_id', $businessId)
            ->whereYear('sales.sale_date', $selectedYear)
            ->select(
                'products.id',
                'products.name',
                'products.image',
                DB::raw('SUM(sale_items.quantity) as units_sold'),
                DB::raw('COALESCE(SUM(sale_items.total), 0) as revenue')
            )->groupBy('products.id', 'products.name', 'products.image')
            ->orderByDesc('revenue')->limit(10)->get();

        $topCustomers = Customer::where('business_id', $businessId)
            ->withCount(['sales' => function ($q) use ($selectedYear) {
                $q->whereYear('sale_date', $selectedYear);
            }])
            ->withSum(['sales' => function ($q) use ($selectedYear) {
                $q->whereYear('sale_date', $selectedYear);
            }], 'total')
            ->having('sales_count', '>', 0)
            ->orderByDesc('sales_sum_total')
            ->limit(10)->get();

        $topStaff = User::where('business_id', $businessId)
            ->withCount(['sales' => function ($q) use ($selectedYear) {
                $q->whereYear('sale_date', $selectedYear);
            }])
            ->withSum(['sales' => function ($q) use ($selectedYear) {
                $q->whereYear('sale_date', $selectedYear);
            }], 'total')
            ->having('sales_count', '>', 0)
            ->orderByDesc('sales_sum_total')
            ->limit(10)->get();

        $uniqueCustomers = Sale::where('business_id', $businessId)
            ->whereYear('sale_date', $selectedYear)
            ->whereNotNull('customer_id')
            ->distinct('customer_id')
            ->count('customer_id');

        $availableYears = Sale::where('business_id', $businessId)
            ->selectRaw('DISTINCT YEAR(sale_date) as year')
            ->orderByDesc('year')->pluck('year');

        if ($availableYears->isEmpty() || !$availableYears->contains(now()->year)) {
            $availableYears->prepend(now()->year);
        }

        return view('dashboard.annual', compact(
            'selectedYear',
            'previousYear',
            'totalSales',
            'totalRevenue',
            'avgSale',
            'annualCOGS',
            'annualProfit',
            'annualProfitMargin',
            'previousYearSales',
            'previousYearRevenue',
            'previousYearProfit',
            'revenueGrowth',
            'salesGrowth',
            'profitGrowth',
            'monthlyRevenue',
            'monthlySales',
            'monthlyProfit',
            'monthNames',
            'monthlyTopPerformers',
            'quarters',
            'bestMonth',
            'worstMonth',
            'paymentMethods',
            'topProducts',
            'topCustomers',
            'topStaff',
            'uniqueCustomers',
            'availableYears'
        ));
    }

    /**
     * Export Annual Report to CSV
     */
    public function exportAnnual(Request $request)
    {
        $user = Auth::user();
        $businessId = $user->business_id;
        $selectedYear = $request->get('year', now()->year);

        $sales = Sale::where('business_id', $businessId)
            ->whereYear('sale_date', $selectedYear)
            ->with(['customer', 'user'])
            ->orderBy('sale_date')
            ->get();

        $filename = "Annual_Report_{$selectedYear}.csv";

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($sales, $selectedYear) {
            $file = fopen('php://output', 'w');

            // Title
            fputcsv($file, ["Annual Sales Report - {$selectedYear}"]);
            fputcsv($file, []);

            // Header row
            fputcsv($file, [
                'Date',
                'Sale Number',
                'Customer',
                'Total Amount (UGX)',
                'Payment Method',
                'Served By',
            ]);
            // Data rows
            foreach ($sales as $sale) {
                fputcsv($file, [
                    $sale->sale_date->format('Y-m-d H:i:s'),
                    $sale->sale_number,
                    $sale->customer->name ?? 'Walk-in',
                    number_format($sale->total, 0),
                    ucfirst(str_replace('_', ' ', $sale->payment_method)),
                    $sale->user->name,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}