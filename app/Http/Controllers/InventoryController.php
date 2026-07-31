<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\SaleItem;
use App\Models\PurchaseItem;
use App\Models\StockTakingSession;
use App\Models\StockAdjustment;
use App\Models\InventoryPeriod;
use App\Models\Inventory;
use App\Services\StockReconciliationService;
use App\Services\MonthlyClosingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InventoryController extends Controller
{
    /**
     * Display inventory overview
     */
    public function index()
    {
        $businessId = Auth::user()->business_id;
        MonthlyClosingService::ensurePeriodsExist($businessId);

        // Get all products with stock info
        $products = Product::where('business_id', $businessId)
            ->with('category')
            ->orderBy('name')
            ->paginate(50);

        // Calculate statistics
        $totalProducts = Product::where('business_id', $businessId)->count();
        
        $lowStockCount = Product::where('business_id', $businessId)
            ->whereColumn('quantity', '<=', 'reorder_level')
            ->where('quantity', '>', 0)
            ->count();
        
        $outOfStockCount = Product::where('business_id', $businessId)
            ->where('quantity', '<=', 0)
            ->count();
        
        $totalValue = Product::where('business_id', $businessId)
            ->selectRaw('SUM(quantity * cost_price) as total')
            ->value('total') ?? 0;

        return view('inventory.index', compact(
            'products',
            'totalProducts',
            'lowStockCount',
            'outOfStockCount',
            'totalValue'
        ));
    }

    /**
     * Display inventory activities (accounting view)
     * Shows opening stock, sales, purchases, and current stock
     */
    public function activities(Request $request)
    {
        $businessId = Auth::user()->business_id;
        MonthlyClosingService::ensurePeriodsExist($businessId);

        // Get all products
        $products = Product::where('business_id', $businessId)
            ->with('category')
            ->orderBy('name')
            ->get();

        $startDate = Carbon::parse('2020-01-01');
        $endDate = now();

        // Calculate stock movements for each product using dynamic calculations
        $inventoryActivities = $products->map(function($product) use ($businessId, $startDate, $endDate) {
            $state = \App\Services\StockReconciliationService::getInventoryStateForRange($product, $startDate, $endDate);

            return [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'category' => $product->category->name ?? 'N/A',
                'opening_stock' => $state['opening_stock'],
                'purchases' => $state['purchases'],
                'sales' => $state['sales'],
                'current_stock' => $state['closing_stock'],
                'cost_price' => $product->cost_price,
                'selling_price' => $product->selling_price,
                'opening_value' => $state['opening_stock_value'],
                'purchases_value' => $state['purchases_value'],
                'sales_value' => $state['sales'] * $product->selling_price,
                'current_value' => $state['closing_stock_value'],
            ];
        });

        // Calculate totals
        $totals = [
            'opening_stock' => $inventoryActivities->sum('opening_stock'),
            'purchases' => $inventoryActivities->sum('purchases'),
            'sales' => $inventoryActivities->sum('sales'),
            'current_stock' => $inventoryActivities->sum('current_stock'),
            'opening_value' => $inventoryActivities->sum('opening_value'),
            'purchases_value' => $inventoryActivities->sum('purchases_value'),
            'sales_value' => $inventoryActivities->sum('sales_value'),
            'current_value' => $inventoryActivities->sum('current_value'),
        ];

        return view('inventory.activities', compact('inventoryActivities', 'totals'));
    }

    /**
     * Display a specific inventory product
     */
    public function show($id)
    {
        $businessId = Auth::user()->business_id;
        MonthlyClosingService::ensurePeriodsExist($businessId);

        // Get the product
        $product = Product::where('business_id', $businessId)
            ->where('id', $id)
            ->with('category')
            ->firstOrFail();

        $startDate = Carbon::parse('2020-01-01');
        $endDate = now();
        $state = \App\Services\StockReconciliationService::getInventoryStateForRange($product, $startDate, $endDate);

        $activity = [
            'product_id'      => $product->id,
            'product_name'    => $product->name,
            'category'        => $product->category->name ?? 'N/A',
            'opening_stock'   => $state['opening_stock'],
            'purchases'       => $state['purchases'],
            'sales'           => $state['sales'],
            'current_stock'   => $state['closing_stock'],
            'cost_price'      => $product->cost_price,
            'selling_price'   => $product->selling_price,
            'opening_value'   => $state['opening_stock_value'],
            'purchases_value' => $state['purchases_value'],
            'sales_value'     => $state['sales'] * $product->selling_price,
            'current_value'   => $state['closing_stock_value'],
        ];


        return view('inventory.show', compact('product', 'activity'));
    }

    /**
     * Display stock taking index page
     */
    public function stockTakingIndex()
    {
        $businessId = Auth::user()->business_id;
        MonthlyClosingService::ensurePeriodsExist($businessId);

        $sessions = StockTakingSession::where('business_id', $businessId)
            ->with('adjustments')
            ->orderBy('session_date', 'desc')
            ->paginate(20);

        return view('inventory.stock-taking.index', compact('sessions'));
    }

    /**
     * Create a new stock taking session
     */
    public function createSession(Request $request)
    {
        $validated = $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        $businessId = Auth::user()->business_id;

        $session = StockTakingSession::create([
            'business_id' => $businessId,
            'session_date' => now(),
            'notes' => $validated['notes'] ?? null,
            'status' => 'active',
            'initiated_by' => Auth::id(),
        ]);

        return redirect()->route('stock-taking.session', $session->id)
            ->with('success', 'Stock taking session created successfully!');
    }

    /**
     * Display stock taking session
     */
    public function stockTakingSession($id)
    {
        $businessId = Auth::user()->business_id;
        MonthlyClosingService::ensurePeriodsExist($businessId);

        $session = StockTakingSession::where('business_id', $businessId)->findOrFail($id);

        // Get all products for counting
        $products = Product::where('business_id', $businessId)
            ->with('category')
            ->orderBy('name')
            ->get();

        // Get all adjustment records for this session (with notes)
        $adjustments = StockAdjustment::where('stock_taking_session_id', $id)
            ->with('product')
            ->get();

        return view('inventory.stock-taking.session', compact('session', 'products', 'adjustments'));
    }

    /**
     * Record physical count for a product
     */
    public function recordCount(Request $request)
    {
        $validated = $request->validate([
            'stock_taking_session_id' => 'required|exists:stock_taking_sessions,id',
            'product_id' => 'required|exists:products,id',
            'physical_count' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $businessId = Auth::user()->business_id;
        $product = Product::where('business_id', $businessId)->findOrFail($validated['product_id']);
        $session = StockTakingSession::where('business_id', $businessId)->findOrFail($validated['stock_taking_session_id']);

        // Calculate variance
        $systemQty = $product->quantity;
        $physicalQty = $validated['physical_count'];
        $variance = $physicalQty - $systemQty;
        $adjustmentQty = $variance; // Need to adjust by this amount

        // Create or update adjustment record
        $adjustment = StockAdjustment::updateOrCreate(
            [
                'stock_taking_session_id' => $session->id,
                'product_id' => $product->id,
            ],
            [
                'business_id' => $businessId,
                'adjustment_date' => now(),
                'physical_count' => $physicalQty,
                'system_quantity' => $systemQty,
                'variance' => $variance,
                'adjustment_quantity' => $adjustmentQty,
                'reason' => 'Stock Take',
                'notes' => $validated['notes'] ?? null,
                'status' => 'pending',
                'recorded_by' => Auth::id(),
            ]
        );

        return back()->with('success', "{$product->name} count recorded successfully!");
    }

    /**
     * Close a stock taking session — applies physical counts, updates stock,
     * records ADJUSTMENT inventory transactions and audit logs.
     * Note: This does NOT create a locked InventoryPeriod. Use closeMonth() for that.
     */
    public function closeSession($id)
    {
        $businessId = Auth::user()->business_id;

        $session = StockTakingSession::where('business_id', $businessId)->findOrFail($id);

        if ($session->status === 'closed') {
            return back()->with('error', 'This session is already closed.');
        }

        DB::beginTransaction();
        try {
            $adjustments = StockAdjustment::where('stock_taking_session_id', $session->id)
                ->where('status', 'pending')
                ->get();

            foreach ($adjustments as $adjustment) {
                $product = Product::find($adjustment->product_id);
                if (!$product) continue;

                $oldQty = $product->quantity;

                // Apply physical count to products table
                $product->quantity = $adjustment->physical_count;
                $product->save();

                // Sync location-aware inventory table
                $inventory = Inventory::where('business_id', $businessId)
                    ->where('product_id', $product->id)
                    ->first();
                if ($inventory) {
                    $inventory->quantity = $adjustment->physical_count;
                    $inventory->save();
                }

                // Mark adjustment as approved
                $adjustment->update([
                    'status'      => 'approved',
                    'approved_by' => Auth::id(),
                    'approved_at' => now(),
                ]);

                // Record Inventory Transaction
                $qtyIn  = $adjustment->variance > 0 ? $adjustment->variance : 0;
                $qtyOut = $adjustment->variance < 0 ? abs($adjustment->variance) : 0;

                \App\Models\InventoryTransaction::create([
                    'business_id'      => $businessId,
                    'product_id'       => $product->id,
                    'transaction_type' => 'ADJUSTMENT',
                    'quantity_in'      => $qtyIn,
                    'quantity_out'     => $qtyOut,
                    'reference_type'   => StockAdjustment::class,
                    'reference_id'     => $adjustment->id,
                    'description'      => sprintf(
                        'Stock Take Session #%d. System: %.2f → Physical: %.2f (Variance: %+.2f). Notes: %s',
                        $session->id,
                        $oldQty,
                        $adjustment->physical_count,
                        $adjustment->variance,
                        $adjustment->notes ?? 'None'
                    ),
                    'created_by'       => Auth::id(),
                ]);

                // Audit Log
                \App\Models\AuditLog::log(
                    'stock_adjustment',
                    Product::class,
                    $product->id,
                    ['quantity' => $oldQty],
                    [
                        'quantity'       => $adjustment->physical_count,
                        'variance'       => $adjustment->variance,
                        'session_id'     => $session->id,
                        'physical_count' => $adjustment->physical_count,
                        'system_qty'     => $adjustment->system_quantity,
                    ]
                );
            }

            // Stamp the session with the period it belongs to (first day of session month)
            $periodMonth = Carbon::parse($session->session_date)->startOfMonth()->toDateString();

            $session->update([
                'status'       => 'closed',
                'closed_by'    => Auth::id(),
                'closed_at'    => now(),
                'period_month' => $periodMonth,
            ]);

            DB::commit();
            return back()->with('success',
                'Stock taking session closed. Product quantities updated. ' .
                'Use "Close Month" on the Periods page to lock the official closing inventory record.'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to close session: ' . $e->getMessage());
        }
    }

    /**
     * Display period closing history
     */
    public function periods()
    {
        $businessId = Auth::user()->business_id;
        MonthlyClosingService::ensurePeriodsExist($businessId);

        $periods = \App\Models\InventoryPeriod::where('business_id', $businessId)
            ->with(['product', 'session'])
            ->latest('period_end')
            ->paginate(50);

        // Monthly summary for the Close Month form
        $monthlySummary = MonthlyClosingService::getMonthlySummary($businessId);

        // Fetch closed stock taking sessions to associate with month closing
        $closedSessions = StockTakingSession::where('business_id', $businessId)
            ->where('status', 'closed')
            ->orderBy('session_date', 'desc')
            ->get();

        return view('inventory.periods', compact('periods', 'monthlySummary', 'closedSessions'));
    }

    /**
     * Close the accounting period for all products in the business.
     * This is the explicit "Close Month" user action.
     */
    public function closeMonth(Request $request)
    {
        $validated = $request->validate([
            'year'       => 'required|integer|min:2020|max:2099',
            'month'      => 'required|integer|min:1|max:12',
            'session_id' => 'nullable|exists:stock_taking_sessions,id',
        ]);

        $businessId = Auth::user()->business_id;

        // Validate the session belongs to this business if provided
        if (!empty($validated['session_id'])) {
            $session = StockTakingSession::where('business_id', $businessId)
                ->where('status', 'closed')
                ->find($validated['session_id']);
            if (!$session) {
                return back()->with('error', 'Invalid or open session selected. Close the session first.');
            }
        }

        try {
            $summary = MonthlyClosingService::closeMonth(
                $businessId,
                (int) $validated['year'],
                (int) $validated['month'],
                Auth::id(),
                $validated['session_id'] ?? null
            );

            $msg = sprintf(
                'Period "%s" closed. %d product(s) processed, %d already locked. ' .
                'Stock losses: %.2f units (UGX %s). Stock gains: %.2f units (UGX %s).',
                $summary['period'],
                $summary['products_closed'],
                $summary['already_locked'],
                $summary['total_stock_loss'],
                number_format($summary['total_loss_value']),
                $summary['total_stock_gain'],
                number_format($summary['total_gain_value'])
            );

            return redirect()->route('inventory.periods')->with('success', $msg);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to close month: ' . $e->getMessage());
        }
    }

    /**
     * Show detailed stock reconciliation for a period
     */
    public function showReconciliation($periodId)
    {
        $businessId = Auth::user()->business_id;

        $period = InventoryPeriod::where('business_id', $businessId)
            ->with(['product', 'product.category'])
            ->findOrFail($periodId);

        $product = $period->product;
        $reconciliation = StockReconciliationService::getReconciliationFromPeriod($period);

        return view('inventory.reconciliation', compact('period', 'product', 'reconciliation'));
    }

    /**
     * Get reconciliation for any product and period (API endpoint)
     */
    public function getReconciliation($productId, $periodStart = null, $periodEnd = null)
    {
        $businessId = Auth::user()->business_id;

        $product = Product::where('business_id', $businessId)->findOrFail($productId);

        // Use last month if not specified
        if (!$periodStart || !$periodEnd) {
            $now = \Carbon\Carbon::now();
            $periodEnd = $now->copy()->subMonth()->endOfMonth();
            $periodStart = $now->copy()->subMonth()->startOfMonth();
        } else {
            $periodStart = \Carbon\Carbon::parse($periodStart);
            $periodEnd = \Carbon\Carbon::parse($periodEnd);
        }

        $reconciliation = StockReconciliationService::calculateReconciliation($product, $periodStart, $periodEnd);

        return response()->json($reconciliation);
    }
}
