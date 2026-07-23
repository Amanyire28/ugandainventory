<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\SaleItem;
use App\Models\PurchaseItem;
use App\Models\StockTakingSession;
use App\Models\StockAdjustment;
use App\Models\InventoryPeriod;
use App\Services\StockReconciliationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    /**
     * Display inventory overview
     */
    public function index()
    {
        $businessId = Auth::user()->business_id;

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

        // Get all products
        $products = Product::where('business_id', $businessId)
            ->with('category')
            ->orderBy('name')
            ->get();

        // Calculate stock movements for each product
        $inventoryActivities = $products->map(function($product) use ($businessId) {
            // Get opening stock from products table (fixed value)
            $openingStock = $product->opening_stock;

            // Get total sales quantity for this product
            $totalSales = SaleItem::whereHas('sale', function($q) use ($businessId) {
                $q->where('business_id', $businessId);
            })
            ->where('product_id', $product->id)
            ->sum('quantity');

            // Get total purchases quantity for this product
            $totalPurchases = PurchaseItem::whereHas('purchase', function($q) use ($businessId) {
                $q->where('business_id', $businessId);
            })
            ->where('product_id', $product->id)
            ->sum('quantity');

            // Calculate current stock: Opening + Purchases - Sales
            $currentStock = $openingStock + $totalPurchases - $totalSales;

            return [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'category' => $product->category->name ?? 'N/A',
                'opening_stock' => $openingStock,
                'purchases' => $totalPurchases,
                'sales' => $totalSales,
                'current_stock' => $currentStock,
                'cost_price' => $product->cost_price,
                'selling_price' => $product->selling_price,
                'opening_value' => $openingStock * $product->cost_price,
                'purchases_value' => $totalPurchases * $product->cost_price,
                'sales_value' => $totalSales * $product->selling_price,
                'current_value' => $currentStock * $product->cost_price,
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

        // Get the product
        $product = Product::where('business_id', $businessId)
            ->where('id', $id)
            ->with('category')
            ->firstOrFail();

        // Get total sales quantity for this product
        $totalSales = SaleItem::whereHas('sale', function($q) use ($businessId) {
            $q->where('business_id', $businessId);
        })
        ->where('product_id', $product->id)
        ->sum('quantity');

        // Get total purchases quantity for this product
        $totalPurchases = PurchaseItem::whereHas('purchase', function($q) use ($businessId) {
            $q->where('business_id', $businessId);
        })
        ->where('product_id', $product->id)
        ->sum('quantity');

        // Calculate opening stock — use the stored opening_stock field (same as activities())
        $openingStock = $product->opening_stock ?? 0;
        $currentStock = $product->quantity;

        $activity = [
            'product_id'      => $product->id,
            'product_name'    => $product->name,
            'category'        => $product->category->name ?? 'N/A',
            'opening_stock'   => $openingStock,
            'purchases'       => $totalPurchases,
            'sales'           => $totalSales,
            'current_stock'   => $currentStock,
            'cost_price'      => $product->cost_price,
            'selling_price'   => $product->selling_price,
            'opening_value'   => $openingStock * $product->cost_price,
            'purchases_value' => $totalPurchases * $product->cost_price,
            'sales_value'     => $totalSales * $product->selling_price,
            'current_value'   => $currentStock * $product->cost_price,
        ];


        return view('inventory.show', compact('product', 'activity'));
    }

    /**
     * Display stock taking index page
     */
    public function stockTakingIndex()
    {
        $businessId = Auth::user()->business_id;

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
     * Close a stock taking session
     */
    public function closeSession($id)
    {
        $businessId = Auth::user()->business_id;

        $session = StockTakingSession::where('business_id', $businessId)->findOrFail($id);

        DB::beginTransaction();
        try {
            // Apply all pending stock adjustments to actual products
            $adjustments = StockAdjustment::where('stock_taking_session_id', $session->id)
                ->where('status', 'pending')
                ->get();

            foreach ($adjustments as $adjustment) {
                $product = Product::find($adjustment->product_id);
                if ($product) {
                    $product->quantity = $adjustment->physical_count;
                    $product->save();
                    
                    $adjustment->update([
                        'status' => 'approved',
                        'approved_by' => Auth::id(),
                        'approved_at' => now(),
                    ]);
                }
            }

            $session->update([
                'status' => 'closed',
                'closed_by' => Auth::id(),
                'closed_at' => now(),
            ]);

            DB::commit();
            return back()->with('success', 'Stock taking session closed successfully! Product quantities have been updated.');
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

        $periods = \App\Models\InventoryPeriod::where('business_id', $businessId)
            ->with('product')
            ->latest('period_end')
            ->paginate(50);

        return view('inventory.periods', compact('periods'));
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
