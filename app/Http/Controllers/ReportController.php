<?php

namespace App\Http\Controllers;

use App\Models\{Sale, Product, Customer, Category, SaleItem, User};
use App\Services\StockReconciliationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB};
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Sales Report (Admin, Manager, Owner only)
     */
    public function sales(Request $request)
    {
        $user = Auth::user();
        $businessId = $user->business_id;

        // Get date range
        $startDate = $request->filled('start_date') 
            ? Carbon::parse($request->start_date)->startOfDay() 
            : now()->startOfMonth();
        
        $endDate = $request->filled('end_date') 
            ? Carbon::parse($request->end_date)->endOfDay() 
            : now()->endOfDay();

        // Build query
        $query = Sale::where('business_id', $businessId)
            ->notVoided()
            ->whereBetween('sale_date', [$startDate, $endDate])
            ->with(['customer', 'user', 'items.product']);

        // Filter by customer
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        // ✅ FILTER BY STAFF (for managers to analyze specific staff)
        if ($request->filled('staff_id')) {
            $query->where('user_id', $request->staff_id);
        }

        $sales = $query->latest('sale_date')->paginate(20);

        // Calculate totals
        $allSales = Sale::where('business_id', $businessId)
            ->notVoided()
            ->whereBetween('sale_date', [$startDate, $endDate]);
        
        if ($request->filled('customer_id')) {
            $allSales->where('customer_id', $request->customer_id);
        }

        if ($request->filled('staff_id')) {
            $allSales->where('user_id', $request->staff_id);
        }

        $totalSales = $allSales->count();
        $totalRevenue = $allSales->sum('total');
        $totalDiscount = $allSales->sum('discount_amount');
        $averageSale = $totalSales > 0 ? $totalRevenue / $totalSales : 0;

        // Get customers for filter
        $customers = Customer::where('business_id', $businessId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // ✅ Get staff list for filter
        $staffList = User::where('business_id', $businessId)
            ->with('role')
            ->orderBy('name')
            ->get();

        return view('reports.sales', compact(
            'sales',
            'totalSales',
            'totalRevenue',
            'totalDiscount',
            'averageSale',
            'customers',
            'staffList'
        ));
    }

    /**
     * Product Performance Report
     */
    public function products(Request $request)
    {
        $businessId = Auth::user()->business_id;

        $startDate = $request->filled('start_date') 
            ? Carbon::parse($request->start_date)->startOfDay() 
            : now()->startOfMonth();
        
        $endDate = $request->filled('end_date') 
            ? Carbon::parse($request->end_date)->endOfDay() 
            : now()->endOfDay();

        $query = Product::where('products.business_id', $businessId)
            ->leftJoin('sale_items', 'products.id', '=', 'sale_items.product_id')
            ->leftJoin('sales', function($join) use ($startDate, $endDate) {
                $join->on('sale_items.sale_id', '=', 'sales.id')
                     ->whereBetween('sales.sale_date', [$startDate, $endDate])
                     ->where(function($q) {
                         $q->whereNull('sales.status')->orWhere('sales.status', '!=', 'voided');
                     });
            })
            ->select(
                'products.id',
                'products.name',
                'products.sku',
                'products.category_id',
                'products.quantity',
                'products.reorder_level',
                'products.image',
                DB::raw('COALESCE(SUM(CASE WHEN sales.id IS NOT NULL THEN sale_items.quantity ELSE 0 END), 0) as units_sold'),
                DB::raw('COALESCE(SUM(CASE WHEN sales.id IS NOT NULL THEN sale_items.total ELSE 0 END), 0) as revenue'),
                DB::raw('COALESCE(AVG(CASE WHEN sales.id IS NOT NULL THEN sale_items.unit_price ELSE NULL END), 0) as avg_price')
            )
            ->groupBy(
                'products.id',
                'products.name',
                'products.sku',
                'products.category_id',
                'products.quantity',
                'products.reorder_level',
                'products.image'
            );

        if ($request->filled('category_id')) {
            $query->where('products.category_id', $request->category_id);
        }

        $products = $query->orderByDesc('revenue')->paginate(20);
        $products->load('category');

        $categories = Category::where('business_id', $businessId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('reports.products', compact('products', 'categories'));
    }

    /**
     * Top Selling Products Report
     */
    public function topSelling(Request $request)
    {
        $businessId = Auth::user()->business_id;

        $period = $request->input('period', 'month');
        
        switch ($period) {
            case 'today':
                $startDate = now()->startOfDay();
                $endDate = now()->endOfDay();
                break;
            case 'week':
                $startDate = now()->startOfWeek();
                $endDate = now()->endOfWeek();
                break;
            case 'month':
                $startDate = now()->startOfMonth();
                $endDate = now()->endOfMonth();
                break;
            case 'year':
                $startDate = now()->startOfYear();
                $endDate = now()->endOfYear();
                break;
            case 'custom':
                $startDate = Carbon::parse($request->input('start_date', now()->startOfMonth()))->startOfDay();
                $endDate = Carbon::parse($request->input('end_date', now()))->endOfDay();
                break;
            default:
                $startDate = now()->startOfMonth();
                $endDate = now()->endOfMonth();
        }

        $topProducts = Product::where('products.business_id', $businessId)
            ->join('sale_items', 'products.id', '=', 'sale_items.product_id')
            ->join('sales', function($join) use ($startDate, $endDate) {
                $join->on('sale_items.sale_id', '=', 'sales.id')
                     ->whereBetween('sales.sale_date', [$startDate, $endDate])
                     ->where(function($q) {
                         $q->whereNull('sales.status')->orWhere('sales.status', '!=', 'voided');
                     });
            })
            ->select(
                'products.id',
                'products.name',
                'products.sku',
                'products.category_id',
                'products.image',
                DB::raw('SUM(sale_items.quantity) as units_sold'),
                DB::raw('SUM(sale_items.total) as revenue'),
                DB::raw('AVG(sale_items.unit_price) as avg_price')
            )
            ->groupBy(
                'products.id',
                'products.name',
                'products.sku',
                'products.category_id',
                'products.image'
            )
            ->orderByDesc('revenue')
            ->limit(50)
            ->get();

        $topProducts->load('category');
        $totalRevenue = $topProducts->sum('revenue');

        return view('reports.top-selling', compact('topProducts', 'totalRevenue'));
    }

    /**
     * Custom Report Generator
     */
    public function custom()
    {
        $businessId = Auth::user()->business_id;

        $categories = Category::where('business_id', $businessId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $customers = Customer::where('business_id', $businessId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // ✅ Get staff list for custom reports
        $staffList = User::where('business_id', $businessId)
            ->with('role')
            ->orderBy('name')
            ->get();

        return view('reports.custom', compact('categories', 'customers', 'staffList'));
    }

    /**
     * Generate Custom Report
     */
    public function generate(Request $request)
    {
        $validated = $request->validate([
            'report_type' => 'required|in:sales,products,inventory',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'category_id' => 'nullable|exists:categories,id',
            'customer_id' => 'nullable|exists:customers,id',
            'staff_id' => 'nullable|exists:users,id',
            'payment_method' => 'nullable|in:cash,mobile_money,card,bank_transfer',
            'export_format' => 'required|in:view,pdf,excel',
        ]);

        $businessId = Auth::user()->business_id;
        $reportType = $validated['report_type'];
        $startDate = Carbon::parse($validated['start_date'])->startOfDay();
        $endDate = Carbon::parse($validated['end_date'])->endOfDay();

        $reportHtml = '';

        switch ($reportType) {
            case 'sales':
                $reportHtml = $this->generateSalesReport($businessId, $startDate, $endDate, $request);
                break;
            case 'products':
                $reportHtml = $this->generateProductsReport($businessId, $startDate, $endDate, $request);
                break;
            case 'inventory':
                $reportHtml = $this->generateInventoryReport($businessId, $startDate, $endDate, $request);
                break;
        }

        if ($validated['export_format'] === 'view') {
            return view('reports.result', compact('reportHtml', 'reportType', 'startDate', 'endDate'));
        } elseif ($validated['export_format'] === 'pdf') {
            return back()->with('info', 'PDF export coming soon!');
        } elseif ($validated['export_format'] === 'excel') {
            return back()->with('info', 'Excel export coming soon!');
        }
    }

    /**
     * Generate Sales Report HTML
     */
    private function generateSalesReport($businessId, $startDate, $endDate, $request)
    {
        $query = Sale::where('business_id', $businessId)
            ->notVoided()
            ->whereBetween('sale_date', [$startDate, $endDate])
            ->with(['customer', 'user', 'items.product']);

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }
        if ($request->filled('staff_id')) {
            $query->where('user_id', $request->staff_id);
        }

        $sales = $query->latest('sale_date')->get();

        $html = '<table class="min-w-full">';
        $html .= '<thead class="bg-gray-50">';
        $html .= '<tr>';
        $html .= '<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sale #</th>';
        $html .= '<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>';
        $html .= '<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>';
        $html .= '<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Staff</th>';
        $html .= '<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Items</th>';
        $html .= '<th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>';
        $html .= '<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Payment</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody class="divide-y divide-gray-200">';

        $totalAmount = 0;

        foreach ($sales as $sale) {
            $totalAmount += $sale->total;
            $html .= '<tr>';
            $html .= '<td class="px-4 py-3 text-sm font-medium">' . $sale->sale_number . '</td>';
            $html .= '<td class="px-4 py-3 text-sm">' . $sale->sale_date->format('M d, Y h:i A') . '</td>';
            $html .= '<td class="px-4 py-3 text-sm">' . ($sale->customer->name ?? 'Walk-in') . '</td>';
            $html .= '<td class="px-4 py-3 text-sm">' . $sale->user->name . '</td>';
            $html .= '<td class="px-4 py-3 text-sm">' . $sale->items->count() . ' items</td>';
            $html .= '<td class="px-4 py-3 text-sm text-right font-semibold">UGX ' . number_format($sale->total, 0) . '</td>';
            $html .= '<td class="px-4 py-3 text-sm">' . ucfirst(str_replace('_', ' ', $sale->payment_method)) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '<tfoot class="bg-gray-50 font-bold">';
        $html .= '<tr>';
        $html .= '<td colspan="5" class="px-4 py-3 text-right">TOTAL:</td>';
        $html .= '<td class="px-4 py-3 text-right text-green-600">UGX ' . number_format($totalAmount, 0) . '</td>';
        $html .= '<td></td>';
        $html .= '</tr>';
        $html .= '</tfoot>';
        $html .= '</table>';

        return $html;
    }

    /**
     * Generate Products Report HTML
     */
    private function generateProductsReport($businessId, $startDate, $endDate, $request)
    {
        $query = Product::where('products.business_id', $businessId)
            ->leftJoin('sale_items', 'products.id', '=', 'sale_items.product_id')
            ->leftJoin('sales', function($join) use ($startDate, $endDate) {
                $join->on('sale_items.sale_id', '=', 'sales.id')
                     ->whereBetween('sales.sale_date', [$startDate, $endDate])
                     ->where(function($q) {
                         $q->whereNull('sales.status')->orWhere('sales.status', '!=', 'voided');
                     });
            })
            ->select(
                'products.id',
                'products.name',
                'products.sku',
                'products.category_id',
                'products.quantity',
                DB::raw('COALESCE(SUM(sale_items.quantity), 0) as units_sold'),
                DB::raw('COALESCE(SUM(sale_items.total), 0) as revenue')
            )
            ->groupBy(
                'products.id',
                'products.name',
                'products.sku',
                'products.category_id',
                'products.quantity'
            );

        if ($request->filled('category_id')) {
            $query->where('products.category_id', $request->category_id);
        }

        $products = $query->orderByDesc('revenue')->get();
        $products->load('category');

        $html = '<table class="min-w-full">';
        $html .= '<thead class="bg-gray-50">';
        $html .= '<tr>';
        $html .= '<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>';
        $html .= '<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">SKU</th>';
        $html .= '<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>';
        $html .= '<th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Units Sold</th>';
        $html .= '<th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Revenue</th>';
        $html .= '<th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Stock</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody class="divide-y divide-gray-200">';

        foreach ($products as $product) {
            $html .= '<tr>';
            $html .= '<td class="px-4 py-3 text-sm font-medium">' . $product->name . '</td>';
            $html .= '<td class="px-4 py-3 text-sm">' . $product->sku . '</td>';
            $html .= '<td class="px-4 py-3 text-sm">' . ($product->category->name ?? 'N/A') . '</td>';
            $html .= '<td class="px-4 py-3 text-sm text-right">' . number_format($product->units_sold, 0) . '</td>';
            $html .= '<td class="px-4 py-3 text-sm text-right font-semibold text-green-600">UGX ' . number_format($product->revenue, 0) . '</td>';
            $html .= '<td class="px-4 py-3 text-sm text-right">' . number_format($product->quantity, 0) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';

        return $html;
    }

    /**
     * Generate Inventory Report HTML
     */
    private function generateInventoryReport($businessId, $startDate, $endDate, $request)
    {
        $query = Product::where('business_id', $businessId);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $products = $query->with('category')->orderBy('name')->get();

        $html = '<table class="min-w-full">';
        $html .= '<thead class="bg-gray-50">';
        $html .= '<tr>';
        $html .= '<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>';
        $html .= '<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">SKU</th>';
        $html .= '<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>';
        $html .= '<th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Opening Qty</th>';
        $html .= '<th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Purchases Qty</th>';
        $html .= '<th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Sales Qty</th>';
        $html .= '<th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Adjustments</th>';
        $html .= '<th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase font-bold">Closing Qty</th>';
        $html .= '<th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Unit Cost</th>';
        $html .= '<th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Closing Value</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody class="divide-y divide-gray-200">';

        $totalValue = 0;

        foreach ($products as $product) {
            $state = \App\Services\StockReconciliationService::getInventoryStateForRange($product, $startDate, $endDate);
            $totalValue += $state['closing_stock_value'];

            $html .= '<tr>';
            $html .= '<td class="px-4 py-3 text-sm font-medium">' . $product->name . '</td>';
            $html .= '<td class="px-4 py-3 text-sm">' . $product->sku . '</td>';
            $html .= '<td class="px-4 py-3 text-sm">' . ($product->category->name ?? 'N/A') . '</td>';
            $html .= '<td class="px-4 py-3 text-sm text-right">' . number_format($state['opening_stock'], 1) . '</td>';
            $html .= '<td class="px-4 py-3 text-sm text-right text-green-600 font-semibold">+' . number_format($state['purchases'], 1) . '</td>';
            $html .= '<td class="px-4 py-3 text-sm text-right text-red-600 font-semibold">-' . number_format($state['sales'], 1) . '</td>';
            $html .= '<td class="px-4 py-3 text-sm text-right">' . ($state['adjustments'] >= 0 ? '+' : '') . number_format($state['adjustments'], 1) . '</td>';
            $html .= '<td class="px-4 py-3 text-sm text-right font-bold bg-gray-50">' . number_format($state['closing_stock'], 1) . '</td>';
            $html .= '<td class="px-4 py-3 text-sm text-right">UGX ' . number_format($product->cost_price, 0) . '</td>';
            $html .= '<td class="px-4 py-3 text-sm text-right font-semibold">UGX ' . number_format($state['closing_stock_value'], 0) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '<tfoot class="bg-gray-50 font-bold">';
        $html .= '<tr>';
        $html .= '<td colspan="9" class="px-4 py-3 text-right">TOTAL INVENTORY VALUE:</td>';
        $html .= '<td class="px-4 py-3 text-right text-green-600">UGX ' . number_format($totalValue, 0) . '</td>';
        $html .= '</tr>';
        $html .= '</tfoot>';
        $html .= '</table>';

        return $html;
    }

    /**
     * VAT Return & Compliance Report
     */
    public function vat(Request $request)
    {
        $user       = Auth::user();
        $businessId = $user->business_id;

        $period = $request->get('period', 'month');

        $startDate = match ($period) {
            'today'   => now()->startOfDay(),
            'week'    => now()->startOfWeek(),
            'month'   => now()->startOfMonth(),
            'quarter' => now()->startOfQuarter(),
            'year'    => now()->startOfYear(),
            'custom'  => $request->filled('start_date') ? Carbon::parse($request->start_date)->startOfDay() : now()->startOfMonth(),
            default   => now()->startOfMonth(),
        };

        $endDate = match ($period) {
            'custom'  => $request->filled('end_date') ? Carbon::parse($request->end_date)->endOfDay() : now()->endOfDay(),
            default   => now()->endOfDay(),
        };

        $vatSummary = \App\Services\VatService::calculateVatSummary($businessId, $startDate, $endDate);

        return view('reports.vat', compact('vatSummary', 'period', 'startDate', 'endDate'));
    }

    /**
     * Monthly Stock Movement Report
     * Shows: Opening Stock, Purchases, Sales, Closing Stock per product for a period.
     * Uses StockReconciliationService::getInventoryStateForRange() for historically-accurate figures.
     */
    public function stockMovement(Request $request)
    {
        $businessId = Auth::user()->business_id;

        // ── Date range resolution ──────────────────────────────────────────────────
        $mode = $request->input('mode', 'month'); // month | custom

        if ($mode === 'custom' && $request->filled('start_date') && $request->filled('end_date')) {
            $startDate   = Carbon::parse($request->start_date)->startOfDay();
            $endDate     = Carbon::parse($request->end_date)->endOfDay();
            $periodLabel = $startDate->format('d M Y') . ' – ' . $endDate->format('d M Y');
        } else {
            // Default: calendar month selector  (YYYY-MM)
            $monthInput  = $request->input('month', now()->format('Y-m'));
            $startDate   = Carbon::parse($monthInput . '-01')->startOfDay();
            $endDate     = $startDate->copy()->endOfMonth()->endOfDay();
            $periodLabel = $startDate->format('F Y');
        }

        // ── Product filters ────────────────────────────────────────────────────────
        $query = Product::where('business_id', $businessId)
            ->where('is_active', true)
            ->with('category')
            ->orderBy('name');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('product_id')) {
            $query->where('id', $request->product_id);
        }

        $products = $query->get();

        // ── Per-product movement calculation ──────────────────────────────────────
        $movements   = [];
        $warnings    = [];
        $totals      = ['opening' => 0, 'purchases' => 0, 'sales' => 0, 'closing' => 0];

        foreach ($products as $product) {
            $state = StockReconciliationService::getInventoryStateForRange($product, $startDate, $endDate);

            $opening   = (float) $state['opening_stock'];
            $purchases = (float) $state['purchases'];
            $sales     = (float) $state['sales'];
            $adjustments = (float) $state['adjustments'];
            $closing   = (float) $state['closing_stock'];

            // Internal validation:
            // When a physical count was performed, the closing_stock = physical_count (not formula-calculated).
            // The difference between formula and physical_count is the stock-take variance, which is EXPECTED.
            // A true mismatch is when closing != formula AND no physical count justifies the difference.
            $formulaClosing = $opening + $purchases - $sales + $adjustments;
            $variance       = $state['variance'];  // stock-take variance recorded in the locked period
            $physicalCount  = $state['physical_count'];

            // If a physical count exists, the reconciled closing = formula + variance (the stock-take delta)
            $reconciledExpected = $physicalCount !== null
                ? $formulaClosing + $variance
                : $formulaClosing;

            $mismatch = abs($reconciledExpected - $closing) > 0.01;

            if ($mismatch) {
                $warnings[] = [
                    'product'  => $product->name,
                    'expected' => $reconciledExpected,
                    'actual'   => $closing,
                    'delta'    => $closing - $reconciledExpected,
                ];
            }

            // Only include products that had any stock activity OR have stock
            $movements[] = [
                'product'     => $product,
                'opening'     => $opening,
                'purchases'   => $purchases,
                'sales'       => $sales,
                'adjustments' => $adjustments,
                'closing'     => $closing,
                'has_variance'=> $state['variance'] != 0,
                'mismatch'    => $mismatch,
                'physical_count' => $state['physical_count'],
            ];

            $totals['opening']   += $opening;
            $totals['purchases'] += $purchases;
            $totals['sales']     += $sales;
            $totals['closing']   += $closing;
        }

        // Filter list of categories and products for the filter dropdowns
        $categories  = Category::where('business_id', $businessId)->where('is_active', true)->orderBy('name')->get();
        $allProducts = Product::where('business_id', $businessId)->where('is_active', true)->orderBy('name')->get();

        return view('reports.stock-movement', compact(
            'movements',
            'totals',
            'warnings',
            'periodLabel',
            'startDate',
            'endDate',
            'mode',
            'categories',
            'allProducts'
        ));
    }
}