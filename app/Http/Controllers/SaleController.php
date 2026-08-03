<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB};

class SaleController extends Controller
{
    /**
     * Display sales list
     */
    public function index()
    {
        $user = Auth::user();
        $businessId = $user->business_id;
        $userRole = $user->role->name;

        $query = Sale::where('business_id', $businessId)
            ->with(['customer', 'user', 'items.product']);

        // ✅ CASHIERS SEE ONLY THEIR SALES
        if ($userRole === 'cashier') {
            $query->where('user_id', $user->id);
        }

        // ✅ CALCULATE STATS BEFORE PAGINATION (Excluding voided sales from revenue)
        $statsQuery = Sale::where('business_id', $businessId)
            ->where(function($q) {
                $q->whereNull('status')->orWhere('status', '!=', 'voided');
            });
        
        if ($userRole === 'cashier') {
            $statsQuery->where('user_id', $user->id);
        }

        $totalSales = (clone $statsQuery)->count();
        $totalRevenue = (clone $statsQuery)->sum('total');
        $todaySales = (clone $statsQuery)->whereDate('sale_date', today())->count();
        $todayRevenue = (clone $statsQuery)->whereDate('sale_date', today())->sum('total');

        // ✅ NOW DO PAGINATION
        $sales = $query->latest('sale_date')->paginate(20);

        // ✅ RETURN SAME VARIABLES FOR ALL ROLES
        if ($userRole === 'cashier') {
            return view('cashier.sales-index', compact('sales', 'totalSales', 'totalRevenue', 'todaySales', 'todayRevenue'));
        }

        return view('sales.index', compact('sales', 'totalSales', 'totalRevenue', 'todaySales', 'todayRevenue'));
    }

    /**
     * Void / Reverse a sale transaction.
     * Restores stock back to inventory and adjusts revenue.
     */
    public function voidSale(Request $request, Sale $sale)
    {
        $user = Auth::user();

        if ($sale->business_id !== $user->business_id) {
            abort(403, 'Unauthorized access.');
        }

        if ($sale->isVoided()) {
            return back()->with('error', 'This sale transaction has already been voided.');
        }

        $validated = $request->validate([
            'void_reason' => 'required|string|min:3|max:500',
        ]);

        DB::transaction(function () use ($sale, $validated, $user) {

            // ── Capture pre-void state for audit ──────────────────────────────
            $originalTotal     = $sale->total;
            $originalTaxAmount = $sale->tax_amount ?? 0;

            // 1. Mark Sale as Voided ──────────────────────────────────────────
            $sale->update([
                'status'      => 'voided',
                'void_reason' => $validated['void_reason'],
                'voided_at'   => now(),
                'voided_by'   => $user->id,
            ]);

            // 2. Restore stock via InventoryService — locks each product row
            //    in ascending product_id order before incrementing.
            $sale->load('items.product');
            $stockRestored = (new InventoryService())->restoreFromVoid($sale, $user->id);

            // 3. Reverse customer balance if sale had a linked customer ────────
            if ($sale->customer_id) {
                // Find the original customer transaction for this sale
                $originalTx = \App\Models\CustomerTransaction::where('sale_id', $sale->id)
                    ->whereIn('transaction_type', ['SALE', 'INVOICE', 'CREDIT'])
                    ->orderByDesc('id')
                    ->first();

                // Determine outstanding amount created by the original sale
                $originalDebit  = $originalTx ? (float) $originalTx->debit  : $originalTotal;
                $originalCredit = $originalTx ? (float) $originalTx->credit : 0;
                $outstandingFromSale = $originalDebit - $originalCredit; // > 0 means customer still owes

                // Get current running balance
                $prevBal = \App\Models\CustomerTransaction::where('customer_id', $sale->customer_id)
                    ->orderByDesc('id')
                    ->value('balance') ?? 0;

                if ($outstandingFromSale > 0) {
                    // Credit sale or partial payment — reverse the outstanding balance
                    $newBal = max(0, $prevBal - $outstandingFromSale);
                    \App\Models\CustomerTransaction::create([
                        'customer_id'      => $sale->customer_id,
                        'sale_id'          => $sale->id,
                        'transaction_type' => 'VOID_REVERSAL',
                        'debit'            => 0,
                        'credit'           => $outstandingFromSale,
                        'balance'          => $newBal,
                        'notes'            => "Void & Reversal of Sale #{$sale->sale_number} — outstanding balance reduced by UGX " . number_format($outstandingFromSale, 0),
                    ]);
                } else {
                    // Fully-paid cash sale — no outstanding balance was created;
                    // record a VOID_REFUND note for audit completeness
                    \App\Models\CustomerTransaction::create([
                        'customer_id'      => $sale->customer_id,
                        'sale_id'          => $sale->id,
                        'transaction_type' => 'VOID_REFUND',
                        'debit'            => 0,
                        'credit'           => 0,
                        'balance'          => $prevBal,
                        'notes'            => "Cash refund — Void of Sale #{$sale->sale_number} (UGX " . number_format($originalTotal, 0) . " refunded)",
                    ]);
                }
            }

            // 4. Comprehensive Audit Log ────────────────────────────────────────
            \App\Models\AuditLog::log('void_sale', Sale::class, $sale->id,
                [
                    'status'         => 'completed',
                    'total'          => $originalTotal,
                    'tax_amount'     => $originalTaxAmount,
                    'payment_status' => $sale->payment_status,
                    'payment_method' => $sale->payment_method,
                ],
                [
                    'status'         => 'voided',
                    'void_reason'    => $validated['void_reason'],
                    'voided_by'      => $user->name,
                    'voided_at'      => now()->toDateTimeString(),
                    'revenue_reversed' => $originalTotal,
                    'vat_reversed'     => $originalTaxAmount,
                    'stock_restored'   => $stockRestored,
                    'sale_number'      => $sale->sale_number,
                    'customer_id'      => $sale->customer_id,
                ]
            );
        });

        return back()->with('success', "Sale #{$sale->sale_number} has been voided and fully reversed. Revenue, VAT, and stock have been corrected.");
    }

    /**
     * Show single sale details
     * ✅ FIXED: Using 'discount_amount' column
     */
    public function show(Sale $sale)
    {
        $user = Auth::user();
        $userRole = $user->role->name;

        // ✅ CASHIERS CAN ONLY VIEW THEIR OWN SALES
        if ($userRole === 'cashier' && $sale->user_id !== $user->id) {
            abort(403, 'You can only view your own sales.');
        }

        if ($sale->business_id !== $user->business_id) {
            abort(403);
        }

        $sale->load(['customer', 'user', 'items.product']);

        // ✅ GET DISCOUNT FROM 'discount_amount' COLUMN
        $discountAmount = $sale->discount_amount ?? 0;
        $discountPercent = 0;

        // ✅ LOAD DIFFERENT VIEW BASED ON ROLE
        if ($userRole === 'cashier') {
            return view('cashier.sales-show', compact('sale', 'discountAmount', 'discountPercent'));
        }

        return view('sales.show', compact('sale', 'discountAmount', 'discountPercent'));
    }

    /**
     * Today's sales
     */
    public function today()
    {
        $user = Auth::user();
        $businessId = $user->business_id;
        $userRole = $user->role->name;

        $query = Sale::where('business_id', $businessId)
            ->whereDate('sale_date', today())
            ->with(['customer', 'user', 'items.product']);

        if ($userRole === 'cashier') {
            $query->where('user_id', $user->id);
        }

        $sales = $query->latest('sale_date')->get();

        // ✅ CALCULATE STATS — excluding voided sales
        $totalSales  = $sales->filter(fn($s) => !$s->isVoided())->count();
        $totalAmount = $sales->filter(fn($s) => !$s->isVoided())->sum('total');
        $totalItems  = $sales->filter(fn($s) => !$s->isVoided())->sum(function ($sale) {
            return $sale->items->sum('quantity');
        });

        // Hourly breakdown — exclude voided
        $hourlyData = Sale::where('business_id', $businessId)
            ->notVoided()
            ->when($userRole === 'cashier', function($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->whereDate('sale_date', today())
            ->select(
                DB::raw('HOUR(sale_date) as hour'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total) as revenue')
            )
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        // Payment method breakdown — exclude voided
        $paymentBreakdown = Sale::where('business_id', $businessId)
            ->notVoided()
            ->when($userRole === 'cashier', function($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->whereDate('sale_date', today())
            ->select(
                'payment_method',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total) as total')
            )
            ->groupBy('payment_method')
            ->get();

        // ✅ RETURN SAME VARIABLES FOR ALL ROLES
        if ($userRole === 'cashier') {
            return view('cashier.sales-today', compact('sales', 'totalSales', 'totalAmount', 'totalItems', 'hourlyData', 'paymentBreakdown'));
        }

        return view('sales.today', compact('sales', 'totalSales', 'totalAmount', 'totalItems', 'hourlyData', 'paymentBreakdown'));
    }

    /**
     * Weekly sales
     */
    public function weekly()
    {
        $user = Auth::user();
        $businessId = $user->business_id;
        $userRole = $user->role->name;

        $weekStart = now()->startOfWeek();
        $weekEnd = now()->endOfWeek();

        $query = Sale::where('business_id', $businessId)
            ->whereBetween('sale_date', [$weekStart, $weekEnd])
            ->with(['customer', 'user', 'items.product']);

        if ($userRole === 'cashier') {
            $query->where('user_id', $user->id);
        }

        $sales = $query->latest('sale_date')->get();

        // ✅ CALCULATE STATS — excluding voided sales
        $totalSales  = $sales->filter(fn($s) => !$s->isVoided())->count();
        $totalAmount = $sales->filter(fn($s) => !$s->isVoided())->sum('total');

        // Daily breakdown — exclude voided
        $dailyBreakdown = $sales->filter(fn($s) => !$s->isVoided())->groupBy(function ($sale) {
            return $sale->sale_date->format('Y-m-d');
        })->map(function($daySales) {
            return [
                'sales' => $daySales->count(),
                'revenue' => $daySales->sum('total'),
            ];
        });

        // ✅ RETURN SAME VARIABLES FOR ALL ROLES
        if ($userRole === 'cashier') {
            return view('cashier.sales-weekly', compact('sales', 'totalSales', 'totalAmount', 'dailyBreakdown'));
        }

        return view('sales.weekly', compact('sales', 'totalSales', 'totalAmount', 'dailyBreakdown'));
    }

    /**
     * Monthly sales
     */
    public function monthly()
    {
        $user = Auth::user();
        $businessId = $user->business_id;
        $userRole = $user->role->name;

        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $query = Sale::where('business_id', $businessId)
            ->whereBetween('sale_date', [$monthStart, $monthEnd])
            ->with(['customer', 'user', 'items.product']);

        if ($userRole === 'cashier') {
            $query->where('user_id', $user->id);
        }

        $sales = $query->latest('sale_date')->get();

        // ✅ CALCULATE STATS — excluding voided sales
        $totalSales  = $sales->filter(fn($s) => !$s->isVoided())->count();
        $totalAmount = $sales->filter(fn($s) => !$s->isVoided())->sum('total');

        // Weekly breakdown — exclude voided
        $weeklyBreakdown = [];
        for ($i = 0; $i < 5; $i++) {
            $weekStart = now()->startOfMonth()->addWeeks($i);
            $weekEnd = now()->startOfMonth()->addWeeks($i)->endOfWeek();

            if ($weekStart->month !== now()->month) continue;

            $weekSales = $sales->filter(fn($s) => !$s->isVoided())->filter(function ($sale) use ($weekStart, $weekEnd) {
                return $sale->sale_date->between($weekStart, $weekEnd);
            });
            
            $weeklyBreakdown["Week " . ($i + 1)] = [
                'sales' => $weekSales->count(),
                'revenue' => $weekSales->sum('total'),
            ];
        }

        // ✅ RETURN SAME VARIABLES FOR ALL ROLES
        if ($userRole === 'cashier') {
            return view('cashier.sales-monthly', compact('sales', 'totalSales', 'totalAmount', 'weeklyBreakdown'));
        }

        return view('sales.monthly', compact('sales', 'totalSales', 'totalAmount', 'weeklyBreakdown'));
    }

    /**
     * Export today's sales to Excel
     */
    public function exportToday()
    {
        $user = Auth::user();
        $businessId = $user->business_id;
        $userRole = $user->role->name;

        $query = Sale::where('business_id', $businessId)
            ->notVoided()
            ->whereDate('sale_date', today())
            ->with(['customer', 'user', 'items.product']);

        if ($userRole === 'cashier') {
            $query->where('user_id', $user->id);
        }

        $sales = $query->latest('sale_date')->get();

        return $this->exportToCSV($sales, "Today's Sales - " . now()->format('Y-m-d'));
    }

    /**
     * Export weekly sales to Excel
     */
    public function exportWeekly()
    {
        $user = Auth::user();
        $businessId = $user->business_id;
        $userRole = $user->role->name;

        $weekStart = now()->startOfWeek();
        $weekEnd = now()->endOfWeek();

        $query = Sale::where('business_id', $businessId)
            ->notVoided()
            ->whereBetween('sale_date', [$weekStart, $weekEnd])
            ->with(['customer', 'user', 'items.product']);

        if ($userRole === 'cashier') {
            $query->where('user_id', $user->id);
        }

        $sales = $query->latest('sale_date')->get();

        return $this->exportToCSV($sales, "Weekly Sales - Week " . now()->weekOfYear);
    }

    /**
     * Export monthly sales to Excel
     */
    public function exportMonthly()
    {
        $user = Auth::user();
        $businessId = $user->business_id;
        $userRole = $user->role->name;

        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $query = Sale::where('business_id', $businessId)
            ->notVoided()
            ->whereBetween('sale_date', [$monthStart, $monthEnd])
            ->with(['customer', 'user', 'items.product']);

        if ($userRole === 'cashier') {
            $query->where('user_id', $user->id);
        }

        $sales = $query->latest('sale_date')->get();

        return $this->exportToCSV($sales, "Monthly Sales - " . now()->format('F Y'));
    }

    /**
     * Helper: Export sales to CSV
     * ✅ FIXED: Using 'discount_amount' column
     */
    private function exportToCSV($sales, $filename)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}.csv\"",
        ];

        $callback = function() use ($sales) {
            $file = fopen('php://output', 'w');
            
            // Header row
            fputcsv($file, [
                'Date & Time',
                'Sale Number',
                'Customer',
                'Items',
                'Subtotal (UGX)',
                'Discount (UGX)',
                'Tax (UGX)',
                'Total Amount (UGX)',
                'Payment Method',
                'Served By'
            ]);

            // Data rows
            foreach ($sales as $sale) {
                fputcsv($file, [
                    $sale->sale_date->format('Y-m-d H:i:s'),
                    $sale->sale_number,
                    $sale->customer->name ?? 'Walk-in',
                    $sale->items->count(),
                    number_format($sale->subtotal ?? 0, 0),
                    number_format($sale->discount_amount ?? 0, 0),  // ✅ Using 'discount_amount'
                    number_format($sale->tax_amount ?? 0, 0),       // ✅ Using 'tax_amount'
                    number_format($sale->total, 0),
                    ucfirst(str_replace('_', ' ', $sale->payment_method)),
                    $sale->user->name
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}