<?php

namespace App\Http\Controllers;

use App\Models\{Sale, User, Expense};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB};
use Carbon\Carbon;

class ProfitController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $userRole = $user->role->name;
        $businessId = $user->business_id;

        // ==========================================
        // DATE FILTERS + YEAR SELECTION
        // ==========================================
        $hasFilters = $request->filled('start_date') || $request->filled('end_date') || $request->filled('cashier_id');
        
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));
        $selectedYear = $request->input('year', now()->year);

        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        $canViewAll = in_array($userRole, ['owner', 'admin', 'manager', 'sales_manager']);
        $selectedCashierId = null;

        // ==========================================
        // ALL TIME STATS (For Cards - Initial Load)
        // ==========================================
        
        $allTimeSalesQuery = Sale::where('business_id', $businessId);
        
        if (! $canViewAll) {
            $allTimeSalesQuery->where('user_id', $user->id);
        }

        $allTimeRevenue = (clone $allTimeSalesQuery)->sum('total');

        $allTimeCostQuery = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->where('sales.business_id', $businessId);

        if (!$canViewAll) {
            $allTimeCostQuery->where('sales.user_id', $user->id);
        }

        $allTimeCost = $allTimeCostQuery->sum(DB::raw('COALESCE(sale_items.cost_price_at_sale, products.cost_price) * sale_items.quantity'));
        $allTimeGrossProfit = $allTimeRevenue - $allTimeCost;

        // All time expenses
        $allTimeExpensesQuery = Expense::where('business_id', $businessId);
        if (!$canViewAll) {
            $allTimeExpensesQuery->where('user_id', $user->id);
        }
        $allTimeExpenses = $allTimeExpensesQuery->sum('amount');
        $allTimeNetProfit = $allTimeGrossProfit - $allTimeExpenses;
        $allTimeProfitMargin = $allTimeRevenue > 0 ?  ($allTimeNetProfit / $allTimeRevenue) * 100 : 0;

        // ==========================================
        // FILTERED DATA (When filters are applied)
        // ==========================================
        
        $salesQuery = Sale::where('business_id', $businessId)
            ->whereBetween('sale_date', [$start, $end]);

        if (!$canViewAll) {
            $salesQuery->where('user_id', $user->id);
        }

        if ($canViewAll && $request->filled('cashier_id')) {
            $selectedCashierId = $request->cashier_id;
            $salesQuery->where('user_id', $selectedCashierId);
        }

        $totalRevenue = (clone $salesQuery)->sum('total');

        $costQuery = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->where('sales.business_id', $businessId)
            ->whereBetween('sales.sale_date', [$start, $end]);

        if (!$canViewAll) {
            $costQuery->where('sales.user_id', $user->id);
        }

        if ($selectedCashierId) {
            $costQuery->where('sales. user_id', $selectedCashierId);
        }

        $totalCost = $costQuery->sum(DB::raw('COALESCE(sale_items.cost_price_at_sale, products.cost_price) * sale_items.quantity'));
        $grossProfit = $totalRevenue - $totalCost;

        // Filtered expenses
        $expensesQuery = Expense::where('business_id', $businessId)
            ->whereBetween('date_spent', [$start, $end]);
        
        if (!$canViewAll) {
            $expensesQuery->where('user_id', $user->id);
        }

        if ($selectedCashierId) {
            $expensesQuery->where('user_id', $selectedCashierId);
        }

        $totalExpenses = $expensesQuery->sum('amount');
        $totalProfit = $grossProfit - $totalExpenses; // Net Profit
        $profitMargin = $totalRevenue > 0 ?  ($totalProfit / $totalRevenue) * 100 : 0;

        // ==========================================
        // DECIDE WHICH DATA TO SHOW IN CARDS
        // ==========================================
        
        if ($hasFilters) {
            // Show filtered data
            $cardRevenue = $totalRevenue;
            $cardCost = $totalCost;
            $cardGrossProfit = $grossProfit;
            $cardExpenses = $totalExpenses;
            $cardProfit = $totalProfit;
            $cardMargin = $profitMargin;
        } else {
            // Show all time data
            $cardRevenue = $allTimeRevenue;
            $cardCost = $allTimeCost;
            $cardGrossProfit = $allTimeGrossProfit;
            $cardExpenses = $allTimeExpenses;
            $cardProfit = $allTimeNetProfit;
            $cardMargin = $allTimeProfitMargin;
        }

        // ==========================================
        // DAILY PROFIT BREAKDOWN
        // ==========================================
        
        $dailyProfit = DB::table('sales')
            ->leftJoin('sale_items', 'sales.id', '=', 'sale_items.sale_id')
            ->leftJoin('products', 'sale_items.product_id', '=', 'products.id')
            ->where('sales.business_id', $businessId)
            ->whereBetween('sales.sale_date', [$start, $end])
            ->when(!$canViewAll, fn($q) => $q->where('sales.user_id', $user->id))
            ->when($selectedCashierId, fn($q) => $q->where('sales.user_id', $selectedCashierId))
            ->select(
                DB::raw('DATE(sales.sale_date) as date'),
                DB::raw('SUM(sales.total) as revenue'),
                DB::raw('SUM(COALESCE(sale_items.cost_price_at_sale, products.cost_price) * sale_items.quantity) as cost')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Add expenses per day
        $dailyExpenses = Expense::where('business_id', $businessId)
            ->whereBetween('date_spent', [$start, $end])
            ->when(!$canViewAll, fn($q) => $q->where('user_id', $user->id))
            ->when($selectedCashierId, fn($q) => $q->where('user_id', $selectedCashierId))
            ->select(
                DB::raw('DATE(date_spent) as date'),
                DB::raw('SUM(amount) as expenses')
            )
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        $dailyProfit = $dailyProfit->map(function($item) use ($dailyExpenses) {
            $expenses = $dailyExpenses->get($item->date)->expenses ??  0;
            $item->expenses = $expenses;
            $item->gross_profit = $item->revenue - $item->cost;
            $item->profit = $item->gross_profit - $expenses; // Net profit
            return $item;
        });

        // ==========================================
        // EXPENSE BREAKDOWN BY PURPOSE
        // ==========================================
        
        $expensesByPurpose = Expense::where('business_id', $businessId)
            ->whereBetween('date_spent', [$start, $end])
            ->when(!$canViewAll, fn($q) => $q->where('user_id', $user->id))
            ->when($selectedCashierId, fn($q) => $q->where('user_id', $selectedCashierId))
            ->select(
                'purpose',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(amount) as total')
            )
            ->groupBy('purpose')
            ->orderByDesc('total')
            ->get();

        // ==========================================
        // MONTHLY PROFIT TREND (Jan-Dec for Selected Year)
        // ==========================================
        
        $yearStart = Carbon::create($selectedYear, 1, 1)->startOfDay();
        $yearEnd = Carbon::create($selectedYear, 12, 31)->endOfDay();

        // Generate all 12 months
        $monthlyTrend = collect(range(1, 12))->map(function($month) use ($selectedYear) {
            $date = Carbon::create($selectedYear, $month, 1);
            return (object)[
                'month' => $month,
                'label' => $date->format('M'),
                'full_label' => $date->format('F Y'),
                'revenue' => 0,
                'cost' => 0,
                'expenses' => 0,
                'gross_profit' => 0,
                'net_profit' => 0
            ];
        })->keyBy('month');

        // Get revenue and cost per month
        $monthlySales = DB::table('sales')
            ->leftJoin('sale_items', 'sales.id', '=', 'sale_items.sale_id')
            ->leftJoin('products', 'sale_items.product_id', '=', 'products.id')
            ->where('sales.business_id', $businessId)
            ->whereBetween('sales.sale_date', [$yearStart, $yearEnd])
            ->when(!$canViewAll, fn($q) => $q->where('sales.user_id', $user->id))
            ->when($selectedCashierId, fn($q) => $q->where('sales.user_id', $selectedCashierId))
            ->select(
                DB::raw('MONTH(sales.sale_date) as month'),
                DB::raw('SUM(sales.total) as revenue'),
                DB::raw('SUM(COALESCE(sale_items.cost_price_at_sale, products.cost_price) * sale_items.quantity) as cost')
            )
            ->groupBy('month')
            ->get()
            ->keyBy('month');

        // Get expenses per month
        $monthlyExpensesData = Expense::where('business_id', $businessId)
            ->whereBetween('date_spent', [$yearStart, $yearEnd])
            ->when(!$canViewAll, fn($q) => $q->where('user_id', $user->id))
            ->when($selectedCashierId, fn($q) => $q->where('user_id', $selectedCashierId))
            ->select(
                DB::raw('MONTH(date_spent) as month'),
                DB::raw('SUM(amount) as expenses')
            )
            ->groupBy('month')
            ->get()
            ->keyBy('month');

        // Combine data
        $monthlyTrend = $monthlyTrend->map(function($item) use ($monthlySales, $monthlyExpensesData) {
            $sales = $monthlySales->get($item->month);
            $expenses = $monthlyExpensesData->get($item->month);
            
            $item->revenue = $sales->revenue ??  0;
            $item->cost = $sales->cost ?? 0;
            $item->expenses = $expenses->expenses ?? 0;
            $item->gross_profit = $item->revenue - $item->cost;
            $item->net_profit = $item->gross_profit - $item->expenses;
            
            return $item;
        })->values();

        // Totals for pie chart
        $yearlyRevenueTotal = $monthlyTrend->sum('revenue');
        $yearlyCostTotal = $monthlyTrend->sum('cost');
        $yearlyExpensesTotal = $monthlyTrend->sum('expenses');
        $yearlyGrossProfitTotal = $monthlyTrend->sum('gross_profit');
        $yearlyNetProfitTotal = $monthlyTrend->sum('net_profit');

        // ==========================================
        // AVAILABLE YEARS (for dropdown)
        // ==========================================
        
        $availableYears = Sale::where('business_id', $businessId)
            ->when(!$canViewAll, fn($q) => $q->where('user_id', $user->id))
            ->selectRaw('DISTINCT YEAR(sale_date) as year')
            ->orderBy('year', 'desc')
            ->pluck('year');

        // If no sales yet, include current year
        if ($availableYears->isEmpty()) {
            $availableYears = collect([now()->year]);
        }

        // ==========================================
        // TOP PROFITABLE PRODUCTS
        // ==========================================
        
        $topProfitableProducts = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->where('sales.business_id', $businessId)
            ->whereBetween('sales.sale_date', [$start, $end])
            ->when(!$canViewAll, fn($q) => $q->where('sales. user_id', $user->id))
            ->when($selectedCashierId, fn($q) => $q->where('sales. user_id', $selectedCashierId))
            ->select(
                'products.id',
                'products.name as product_name',
                'products.sku as product_sku',
                'products.cost_price as buying_price',
                DB::raw('AVG(sale_items.unit_price) as unit_price'),
                DB::raw('SUM(sale_items. quantity) as total_quantity'),
                DB::raw('SUM(
                    CASE 
                        WHEN sales.subtotal > 0 
                        THEN (sale_items.total / sales.subtotal) * sales.total
                        ELSE sale_items.total
                    END
                ) as total_revenue'),
                DB::raw('SUM(products.cost_price * sale_items.quantity) as total_cost')
            )
            ->groupBy('products.id', 'products.name', 'products.sku', 'products.cost_price')
            ->get()
            ->map(function($item) {
                $item->profit = $item->total_revenue - $item->total_cost;
                $item->margin = $item->total_revenue > 0 ? ($item->profit / $item->total_revenue) * 100 : 0;
                return $item;
            })
            ->sortByDesc('profit')
            ->take(10)
            ->values();

        // ==========================================
        // TOP SELLING PRODUCTS BY QUANTITY
        // ==========================================
        
        $topSellingByQuantity = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->where('sales.business_id', $businessId)
            ->whereBetween('sales.sale_date', [$start, $end])
            ->when(! $canViewAll, fn($q) => $q->where('sales.user_id', $user->id))
            ->when($selectedCashierId, fn($q) => $q->where('sales.user_id', $selectedCashierId))
            ->select(
                'products.id',
                'products.name as product_name',
                'products.sku as product_sku',
                'products.cost_price',
                DB::raw('SUM(sale_items.quantity) as total_quantity_sold'),
                DB::raw('COUNT(DISTINCT sales.id) as number_of_sales'),
                DB::raw('SUM(
                    CASE 
                        WHEN sales.subtotal > 0 
                        THEN (sale_items.total / sales.subtotal) * sales. total
                        ELSE sale_items.total
                    END
                ) as total_revenue'),
                DB::raw('SUM(products.cost_price * sale_items.quantity) as total_cost')
            )
            ->groupBy('products.id', 'products.name', 'products.sku', 'products.cost_price')
            ->orderByDesc('total_quantity_sold')
            ->limit(10)
            ->get()
            ->map(function($item) {
                $item->profit = $item->total_revenue - $item->total_cost;
                return $item;
            });

        // ==========================================
        // TOP SELLING PRODUCTS BY REVENUE
        // ==========================================
        
        $topSellingByRevenue = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->where('sales.business_id', $businessId)
            ->whereBetween('sales.sale_date', [$start, $end])
            ->when(!$canViewAll, fn($q) => $q->where('sales.user_id', $user->id))
            ->when($selectedCashierId, fn($q) => $q->where('sales.user_id', $selectedCashierId))
            ->select(
                'products.id',
                'products.name as product_name',
                'products.sku as product_sku',
                'products.cost_price',
                DB::raw('SUM(sale_items.quantity) as total_quantity_sold'),
                DB::raw('COUNT(DISTINCT sales.id) as number_of_sales'),
                DB::raw('SUM(
                    CASE 
                        WHEN sales. subtotal > 0 
                        THEN (sale_items. total / sales.subtotal) * sales.total
                        ELSE sale_items.total
                    END
                ) as total_revenue'),
                DB::raw('SUM(products.cost_price * sale_items.quantity) as total_cost')
            )
            ->groupBy('products.id', 'products.name', 'products.sku', 'products.cost_price')
            ->orderByDesc('total_revenue')
            ->limit(10)
            ->get()
            ->map(function($item) {
                $item->profit = $item->total_revenue - $item->total_cost;
                return $item;
            });

        // ==========================================
        // LOW MARGIN PRODUCTS
        // ==========================================
        
        $lowMarginProducts = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->where('sales.business_id', $businessId)
            ->whereBetween('sales.sale_date', [$start, $end])
            ->when(!$canViewAll, fn($q) => $q->where('sales. user_id', $user->id))
            ->when($selectedCashierId, fn($q) => $q->where('sales. user_id', $selectedCashierId))
            ->select(
                'products.name as product_name',
                DB::raw('SUM(sale_items.quantity) as total_quantity'),
                DB::raw('SUM(
                    CASE 
                        WHEN sales.subtotal > 0 
                        THEN (sale_items.total / sales.subtotal) * sales. total
                        ELSE sale_items.total
                    END
                ) as total_revenue'),
                DB::raw('SUM(products.cost_price * sale_items.quantity) as total_cost')
            )
            ->groupBy('products.id', 'products.name')
            ->get()
            ->map(function($item) {
                $item->profit = $item->total_revenue - $item->total_cost;
                $item->margin = $item->total_revenue > 0 ? ($item->profit / $item->total_revenue) * 100 : 0;
                return $item;
            })
            ->filter(fn($item) => $item->margin < 15)
            ->sortBy('margin')
            ->values();

        // ==========================================
        // WEEKLY COMPARISON
        // ==========================================
        
        $thisWeekStart = now()->startOfWeek();
        $lastWeekStart = now()->subWeek()->startOfWeek();
        $lastWeekEnd = now()->subWeek()->endOfWeek();

        // This Week
        $thisWeekRevenue = Sale::where('business_id', $businessId)
            ->whereBetween('sale_date', [$thisWeekStart, now()])
            ->when(!$canViewAll, fn($q) => $q->where('user_id', $user->id))
            ->when($selectedCashierId, fn($q) => $q->where('user_id', $selectedCashierId))
            ->sum('total');

        $thisWeekCost = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->where('sales.business_id', $businessId)
            ->whereBetween('sales.sale_date', [$thisWeekStart, now()])
            ->when(!$canViewAll, fn($q) => $q->where('sales. user_id', $user->id))
            ->when($selectedCashierId, fn($q) => $q->where('sales. user_id', $selectedCashierId))
            ->sum(DB::raw('products.cost_price * sale_items.quantity'));

        $thisWeekExpenses = Expense::where('business_id', $businessId)
            ->whereBetween('date_spent', [$thisWeekStart, now()])
            ->when(!$canViewAll, fn($q) => $q->where('user_id', $user->id))
            ->when($selectedCashierId, fn($q) => $q->where('user_id', $selectedCashierId))
            ->sum('amount');

        $thisWeekProfit = $thisWeekRevenue - $thisWeekCost - $thisWeekExpenses;

        // Last Week
        $lastWeekRevenue = Sale::where('business_id', $businessId)
            ->whereBetween('sale_date', [$lastWeekStart, $lastWeekEnd])
            ->when(!$canViewAll, fn($q) => $q->where('user_id', $user->id))
            ->when($selectedCashierId, fn($q) => $q->where('user_id', $selectedCashierId))
            ->sum('total');

        $lastWeekCost = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->where('sales.business_id', $businessId)
            ->whereBetween('sales.sale_date', [$lastWeekStart, $lastWeekEnd])
            ->when(!$canViewAll, fn($q) => $q->where('sales.user_id', $user->id))
            ->when($selectedCashierId, fn($q) => $q->where('sales.user_id', $selectedCashierId))
            ->sum(DB::raw('products.cost_price * sale_items.quantity'));

        $lastWeekExpenses = Expense::where('business_id', $businessId)
            ->whereBetween('date_spent', [$lastWeekStart, $lastWeekEnd])
            ->when(!$canViewAll, fn($q) => $q->where('user_id', $user->id))
            ->when($selectedCashierId, fn($q) => $q->where('user_id', $selectedCashierId))
            ->sum('amount');

        $lastWeekProfit = $lastWeekRevenue - $lastWeekCost - $lastWeekExpenses;

        $weeklyProfitChange = $lastWeekProfit > 0 
            ? (($thisWeekProfit - $lastWeekProfit) / $lastWeekProfit) * 100 
            : 0;

        // ==========================================
        // MONTHLY SUMMARY (Current Month)
        // ==========================================
        
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $monthTotalSales = Sale::where('business_id', $businessId)
            ->whereBetween('sale_date', [$monthStart, $monthEnd])
            ->when(! $canViewAll, fn($q) => $q->where('user_id', $user->id))
            ->when($selectedCashierId, fn($q) => $q->where('user_id', $selectedCashierId))
            ->count();

        $monthRevenue = Sale::where('business_id', $businessId)
            ->whereBetween('sale_date', [$monthStart, $monthEnd])
            ->when(! $canViewAll, fn($q) => $q->where('user_id', $user->id))
            ->when($selectedCashierId, fn($q) => $q->where('user_id', $selectedCashierId))
            ->sum('total');

        $monthCost = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->where('sales.business_id', $businessId)
            ->whereBetween('sales.sale_date', [$monthStart, $monthEnd])
            ->when(!$canViewAll, fn($q) => $q->where('sales.user_id', $user->id))
            ->when($selectedCashierId, fn($q) => $q->where('sales.user_id', $selectedCashierId))
            ->sum(DB::raw('products.cost_price * sale_items.quantity'));

        $monthExpenses = Expense::where('business_id', $businessId)
            ->whereBetween('date_spent', [$monthStart, $monthEnd])
            ->when(!$canViewAll, fn($q) => $q->where('user_id', $user->id))
            ->when($selectedCashierId, fn($q) => $q->where('user_id', $selectedCashierId))
            ->sum('amount');

        $monthProfit = $monthRevenue - $monthCost - $monthExpenses;

        // ==========================================
        // CASHIER PERFORMANCE
        // ==========================================
        
        $cashierPerformance = collect();
        if ($canViewAll && ! $selectedCashierId) {
            $revenues = Sale::where('business_id', $businessId)
                ->whereBetween('sale_date', [$start, $end])
                ->select(
                    'user_id',
                    DB::raw('COUNT(*) as total_sales'),
                    DB::raw('SUM(total) as revenue')
                )
                ->groupBy('user_id')
                ->get()
                ->keyBy('user_id');

            $costs = DB::table('sale_items')
                ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                ->join('products', 'sale_items.product_id', '=', 'products.id')
                ->where('sales.business_id', $businessId)
                ->whereBetween('sales.sale_date', [$start, $end])
                ->select(
                    'sales.user_id',
                    DB::raw('SUM(products.cost_price * sale_items.quantity) as cost')
                )
                ->groupBy('sales.user_id')
                ->get()
                ->keyBy('user_id');

            $expenses = Expense::where('business_id', $businessId)
                ->whereBetween('date_spent', [$start, $end])
                ->select(
                    'user_id',
                    DB::raw('SUM(amount) as expenses')
                )
                ->groupBy('user_id')
                ->get()
                ->keyBy('user_id');

            $cashierPerformance = User::where('business_id', $businessId)
                ->whereHas('role', fn($q) => $q->where('name', 'cashier'))
                ->get()
                ->map(function($cashier) use ($revenues, $costs, $expenses) {
                    $revenue = $revenues->get($cashier->id);
                    $cost = $costs->get($cashier->id);
                    $expense = $expenses->get($cashier->id);

                    $rev = $revenue->revenue ??  0;
                    $cst = $cost->cost ?? 0;
                    $exp = $expense->expenses ?? 0;

                    return (object)[
                        'user_id' => $cashier->id,
                        'cashier_name' => $cashier->name,
                        'total_sales' => $revenue->total_sales ??  0,
                        'revenue' => $rev,
                        'cost' => $cst,
                        'expenses' => $exp,
                        'profit' => $rev - $cst - $exp,
                    ];
                })
                ->sortByDesc('profit')
                ->values();
        }

        // ==========================================
        // CASHIERS LIST
        // ==========================================
        
        $cashiers = collect();
        if ($canViewAll) {
            $cashiers = User::where('business_id', $businessId)
                ->whereHas('role', fn($q) => $q->where('name', 'cashier'))
                ->orderBy('name')
                ->get();
        }

        // ==========================================
        // RETURN VIEW
        // ==========================================
        
        $viewData = compact(
            // Date filters
            'startDate', 
            'endDate', 
            'selectedYear', 
            'availableYears',
            
            // Card stats
            'cardRevenue', 
            'cardCost', 
            'cardGrossProfit', 
            'cardExpenses', 
            'cardProfit', 
            'cardMargin',
            
            // Filtered period stats
            'totalRevenue', 
            'totalCost', 
            'grossProfit', 
            'totalExpenses', 
            'totalProfit', 
            'profitMargin',
            
            // Daily and expense data
            'dailyProfit', 
            'expensesByPurpose',
            
            // Monthly trends (yearly)
            'monthlyTrend', 
            'yearlyRevenueTotal', 
            'yearlyCostTotal', 
            'yearlyExpensesTotal', 
            'yearlyGrossProfitTotal', 
            'yearlyNetProfitTotal',
            
            // Top products
            'topProfitableProducts', 
            'topSellingByQuantity', 
            'topSellingByRevenue',
            'lowMarginProducts',
            
            // Weekly comparison
            'thisWeekProfit', 
            'lastWeekProfit', 
            'weeklyProfitChange',
            
            // Monthly summary (current month)
            'monthTotalSales', 
            'monthRevenue', 
            'monthCost', 
            'monthExpenses', 
            'monthProfit',
            
            // Access control & filters
            'canViewAll', 
            'cashierPerformance', 
            'cashiers', 
            'selectedCashierId', 
            'hasFilters'
        );

       /* dd([
    'selectedYear' => $selectedYear,
    'availableYears' => $availableYears->toArray(),
    'monthlyTrend' => $monthlyTrend->toArray(),
    'monthlyTrendCount' => $monthlyTrend->count(),
    'yearlyTotals' => [
        'revenue' => $yearlyRevenueTotal,
        'cost' => $yearlyCostTotal,
        'expenses' => $yearlyExpensesTotal,
        'netProfit' => $yearlyNetProfitTotal
    ],
    'firstMonth' => $monthlyTrend->first(),
    'lastMonth' => $monthlyTrend->last()
]);*/

        if ($userRole === 'cashier') {
            return view('cashier.profit-report', $viewData);
        }

        return view('profit.index', $viewData);
    }

    public function cashFlow(Request $request)
    {
        $user = Auth::user();
        $businessId = $user->business_id;
        $userRole = $user->role->name;

        if (!in_array($userRole, ['owner', 'admin', 'manager', 'sales_manager'])) {
            abort(403, 'Unauthorized access to cash flow report.');
        }

        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));

        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        // 1. Direct Sales Cash Inflow (excludes sales recorded as 'credit' which come from invoices)
        $salesCashPayments = Sale::where('business_id', $businessId)
            ->where('status', '!=', 'voided')
            ->where('payment_method', '!=', 'credit')
            ->whereBetween('sale_date', [$start, $end])
            ->sum('total');

        // 2. Customer Payments on Invoices
        $invoicePayments = DB::table('payments')
            ->join('invoices', 'payments.invoice_id', '=', 'invoices.id')
            ->where('invoices.business_id', $businessId)
            ->whereBetween('payments.paid_at', [$start, $end])
            ->sum('payments.amount_paid');

        $totalCashIn = $salesCashPayments + $invoicePayments;

        // 3. Business Expenses Paid Out
        $expenses = Expense::where('business_id', $businessId)
            ->whereBetween('date_spent', [$start, $end])
            ->sum('amount');

        // 4. Supplier Payments Made
        $supplierPayments = \App\Models\SupplierTransaction::whereHas('supplier', function($q) use ($businessId) {
                $q->where('business_id', $businessId);
            })
            ->where('transaction_type', 'PAYMENT')
            ->whereBetween('created_at', [$start, $end])
            ->sum('debit');

        $totalCashOut = $expenses + $supplierPayments;

        $netCashFlow = $totalCashIn - $totalCashOut;

        return view('profit.cash-flow', compact(
            'startDate',
            'endDate',
            'salesCashPayments',
            'invoicePayments',
            'totalCashIn',
            'expenses',
            'supplierPayments',
            'totalCashOut',
            'netCashFlow'
        ));
    }
}