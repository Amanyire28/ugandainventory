<?php

namespace App\Services;

use App\Models\{
    Product,
    InventoryPeriod,
    InventoryTransaction,
    StockAdjustment,
    StockTakingSession,
    AuditLog,
    Inventory,
};
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MonthlyClosingService
{
    /**
     * Lazy-generate inventory periods for all past months up to the current month.
     * Transitions past ended 'open' periods to 'pending_reconciliation'.
     *
     * @param int $businessId
     * @return void
     */
    public static function ensurePeriodsExist(int $businessId): void
    {
        $earliestProduct = Product::where('business_id', $businessId)->orderBy('created_at', 'asc')->first();
        // Fallback to 3 months ago if no products
        $startDate = $earliestProduct ? Carbon::parse($earliestProduct->created_at)->startOfMonth() : now()->subMonths(3)->startOfMonth();
        $currentMonthStart = now()->startOfMonth();

        // Loop from start month to current month
        $tempDate = $startDate->copy();
        while ($tempDate->lte($currentMonthStart)) {
            $periodStart = $tempDate->copy()->startOfMonth();
            $periodEnd = $tempDate->copy()->endOfMonth()->endOfDay();
            $isCurrentMonth = $periodStart->equalTo($currentMonthStart);

            $products = Product::where('business_id', $businessId)->get();

            foreach ($products as $product) {
                // Check if period exists
                $period = InventoryPeriod::where('product_id', $product->id)
                    ->where('period_start', $periodStart)
                    ->first();

                // If locked, skip
                if ($period && $period->is_locked) {
                    continue;
                }

                // Calculate calculations
                $previousClosing = InventoryPeriod::getPreviousClosingStock($product->id, $periodStart->toDateString());
                $openingStock = $previousClosing ?? (float) $product->opening_stock;

                // Sum purchases (voided purchases are not a concept in this system, so sum all)
                $purchases = $product->purchaseItems()
                    ->whereHas('purchase', function ($q) use ($businessId, $periodStart, $periodEnd) {
                        $q->where('business_id', $businessId)
                          ->whereBetween('created_at', [$periodStart, $periodEnd]);
                    })
                    ->sum('quantity');

                // Sum sales (excluding voided sales)
                $sales = $product->saleItems()
                    ->whereHas('sale', function ($q) use ($businessId, $periodStart, $periodEnd) {
                        $q->where('business_id', $businessId)
                          ->whereBetween('sale_date', [$periodStart, $periodEnd])
                          ->where(function ($sq) {
                              $sq->whereNull('status')->orWhere('status', '!=', 'voided');
                          });
                    })
                    ->sum('quantity');

                // Sum prior ADJUSTMENT transactions in period (excludes MONTH_CLOSE and LATE_ADJUSTMENT)
                $adjustments = InventoryTransaction::where('business_id', $businessId)
                    ->where('product_id', $product->id)
                    ->where('transaction_type', 'ADJUSTMENT')
                    ->whereBetween('created_at', [$periodStart, $periodEnd])
                    ->selectRaw('SUM(quantity_in) - SUM(quantity_out) as net')
                    ->value('net') ?? 0;

                $systemCalculated = $openingStock + $purchases - $sales + $adjustments;

                // Financial values
                $costPrice = (float) $product->cost_price;
                $openingStockValue = $openingStock * $costPrice;
                $purchasesValue = (float) ($product->purchaseItems()
                    ->whereHas('purchase', function ($q) use ($businessId, $periodStart, $periodEnd) {
                        $q->where('business_id', $businessId)
                          ->whereBetween('created_at', [$periodStart, $periodEnd]);
                    })
                    ->selectRaw('SUM(quantity * unit_cost) as val')
                    ->value('val') ?? ($purchases * $costPrice));
                $salesCostValue = $sales * $costPrice;

                $status = $isCurrentMonth ? 'open' : 'pending_reconciliation';

                InventoryPeriod::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'period_start' => $periodStart,
                    ],
                    [
                        'business_id' => $businessId,
                        'period_end' => $periodEnd->toDateString(),
                        'opening_stock' => $openingStock,
                        'purchases' => $purchases,
                        'sales' => $sales,
                        'adjustments' => $adjustments,
                        'calculated_stock' => $systemCalculated,
                        'closing_stock' => $systemCalculated,
                        'variance' => 0,
                        'variance_percentage' => 0,
                        'adjustment_value' => 0,
                        'opening_stock_value' => $openingStockValue,
                        'purchases_value' => $purchasesValue,
                        'sales_cost_value' => $salesCostValue,
                        'closing_stock_value' => $systemCalculated * $costPrice,
                        'status' => $status,
                        'is_locked' => false,
                    ]
                );
            }

            $tempDate->addMonth();
        }
    }

    /**
     * Close/Reconcile the accounting period for all products in a business.
     * Supports both Scenario 1 (Month end stock take) and Scenario 2 (Late stock take).
     *
     * @param  int       $businessId
     * @param  int       $year
     * @param  int       $month
     * @param  int       $closedBy
     * @param  int|null  $sessionId
     * @return array
     */
    public static function closeMonth(
        int $businessId,
        int $year,
        int $month,
        int $closedBy,
        ?int $sessionId = null
    ): array {
        // Ensure all periods are lazy-created first
        self::ensurePeriodsExist($businessId);

        $periodStart = Carbon::create($year, $month, 1)->startOfDay();
        $periodEnd   = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();

        // Detect if count is closed late (current month starts after the target period)
        $isLate = now()->startOfMonth()->greaterThan($periodStart);

        $products = Product::where('business_id', $businessId)->get();

        $summary = [
            'period'          => $periodStart->format('F Y'),
            'products_closed' => 0,
            'already_locked'  => 0,
            'total_stock_loss'   => 0.0,
            'total_stock_gain'   => 0.0,
            'total_loss_value'   => 0.0,
            'total_gain_value'   => 0.0,
            'is_late'         => $isLate,
        ];

        DB::transaction(function () use (
            $businessId, $periodStart, $periodEnd, $isLate,
            $year, $month, $closedBy, $sessionId,
            $products, &$summary
        ) {
            foreach ($products as $product) {
                // Get period record (already created by ensurePeriodsExist)
                $period = InventoryPeriod::where('product_id', $product->id)
                    ->where('period_start', $periodStart)
                    ->first();

                if (!$period) {
                    $previousClosing = InventoryPeriod::getPreviousClosingStock($product->id, $periodStart->toDateString());
                    $openingStock = $previousClosing ?? (float) $product->opening_stock;
                    $costPrice = (float) $product->cost_price;

                    $period = InventoryPeriod::create([
                        'business_id' => $businessId,
                        'product_id' => $product->id,
                        'period_start' => $periodStart,
                        'period_end' => $periodEnd->toDateString(),
                        'opening_stock' => $openingStock,
                        'purchases' => 0,
                        'sales' => 0,
                        'adjustments' => 0,
                        'calculated_stock' => $openingStock,
                        'closing_stock' => $openingStock,
                        'variance' => 0,
                        'variance_percentage' => 0,
                        'adjustment_value' => 0,
                        'opening_stock_value' => $openingStock * $costPrice,
                        'purchases_value' => 0,
                        'sales_cost_value' => 0,
                        'closing_stock_value' => $openingStock * $costPrice,
                        'status' => 'pending_reconciliation',
                        'is_locked' => false,
                    ]);
                }

                if ($period->is_locked) {
                    $summary['already_locked']++;
                    continue;
                }

                $openingStock = (float) $period->opening_stock;

                // Re-query live figures from source tables (period record may be stale from ensurePeriodsExist)
                $purchases = (float) $product->purchaseItems()
                    ->whereHas('purchase', function ($q) use ($businessId, $periodStart, $periodEnd) {
                        $q->where('business_id', $businessId)
                          ->whereBetween('created_at', [$periodStart, $periodEnd]);
                    })
                    ->sum('quantity');

                $sales = (float) $product->saleItems()
                    ->whereHas('sale', function ($q) use ($businessId, $periodStart, $periodEnd) {
                        $q->where('business_id', $businessId)
                          ->whereBetween('sale_date', [$periodStart, $periodEnd])
                          ->where(function ($sq) {
                              $sq->whereNull('status')->orWhere('status', '!=', 'voided');
                          });
                    })
                    ->sum('quantity');

                $adjustments = (float) InventoryTransaction::where('business_id', $businessId)
                    ->where('product_id', $product->id)
                    ->where('transaction_type', 'ADJUSTMENT')
                    ->whereBetween('created_at', [$periodStart, $periodEnd])
                    ->selectRaw('COALESCE(SUM(quantity_in) - SUM(quantity_out), 0) as net')
                    ->value('net');

                $systemCalculated = $openingStock + $purchases - $sales + $adjustments;

                // Keep the period purchases/sales/adjustments up-to-date
                $period->purchases   = $purchases;
                $period->sales       = $sales;
                $period->adjustments = $adjustments;
                $period->calculated_stock = $systemCalculated;
                $period->save();

                // Find approved physical count
                $physicalCount = null;
                $latestAdjustment = null;

                if ($sessionId) {
                    $latestAdjustment = StockAdjustment::where('stock_taking_session_id', $sessionId)
                        ->where('product_id', $product->id)
                        ->where('status', 'approved')
                        ->latest('adjustment_date')
                        ->first();
                }

                if (!$latestAdjustment) {
                    $latestAdjustment = StockAdjustment::where('business_id', $businessId)
                        ->where('product_id', $product->id)
                        ->where('status', 'approved')
                        ->whereBetween('adjustment_date', [$periodStart, $periodEnd])
                        ->latest('adjustment_date')
                        ->first();
                }

                if ($latestAdjustment) {
                    $physicalCount = (float) $latestAdjustment->physical_count;
                }

                if ($physicalCount !== null) {
                    $variance = $physicalCount - $systemCalculated;
                    $closingStock = $physicalCount;
                } else {
                    $variance = 0;
                    $closingStock = $systemCalculated;
                }

                $variancePct = $systemCalculated > 0 ? ($variance / $systemCalculated) * 100 : 0;
                $adjustmentValue = $variance * (float) $product->cost_price;
                $stockLoss = $variance < -0.001 ? abs($variance) : 0;
                $stockGain = $variance > 0.001 ? $variance : 0;

                // Financial values
                $costPrice = (float) $product->cost_price;
                $closingStockValue = $closingStock * $costPrice;

                // ── SCENARIO 2: LATE STOCK RECONCILIATION ────────────────────────────────────
                if ($isLate && abs($variance) > 0.001) {
                    // Fetch the current open period (August) to apply adjustment
                    $currentPeriodStart = now()->startOfMonth();
                    $currentPeriod = InventoryPeriod::where('product_id', $product->id)
                        ->where('period_start', $currentPeriodStart)
                        ->first();

                    if ($currentPeriod) {
                        $currentPeriod->adjustments += $variance;
                        $currentPeriod->calculated_stock += $variance;
                        $currentPeriod->closing_stock += $variance;
                        $currentPeriod->closing_stock_value = $currentPeriod->closing_stock * $costPrice;
                        $currentPeriod->save();
                    }

                    // Create LATE_ADJUSTMENT transaction dated now()
                    InventoryTransaction::create([
                        'business_id' => $businessId,
                        'product_id' => $product->id,
                        'transaction_type' => 'LATE_ADJUSTMENT',
                        'quantity_in' => $stockGain,
                        'quantity_out' => $stockLoss,
                        'reference_type' => InventoryPeriod::class,
                        'reference_id' => $period->id,
                        'description' => sprintf(
                            'Late stock reconciliation adjustment for period %s. Discovered on %s.',
                            $periodStart->format('F Y'),
                            now()->format('Y-m-d')
                        ),
                        'created_by' => $closedBy,
                    ]);

                    // Adjust product actual stock quantities
                    $oldQty = (float) $product->quantity;
                    $product->quantity = $oldQty + $variance;
                    $product->save();

                    // Adjust location inventory
                    $inventory = Inventory::where('business_id', $businessId)
                        ->where('product_id', $product->id)
                        ->first();
                    if ($inventory) {
                        $inventory->quantity = $inventory->quantity + $variance;
                        $inventory->save();
                    }

                    // Log Audit entry for late adjustment
                    AuditLog::log(
                        'late_stock_reconciliation',
                        Product::class,
                        $product->id,
                        ['quantity' => $oldQty],
                        [
                            'quantity' => $product->quantity,
                            'variance' => $variance,
                            'period' => $periodStart->format('Y-m')
                        ]
                    );

                } else {
                    // ── SCENARIO 1: MONTH-END STOCK TAKING OR SYSTEM CALCULATED ──────────────
                    // Direct quantity update to physical count
                    $oldQty = (float) $product->quantity;
                    $product->quantity = $closingStock;
                    $product->save();

                    // Sync location inventory
                    $inventory = Inventory::where('business_id', $businessId)
                        ->where('product_id', $product->id)
                        ->first();
                    if ($inventory) {
                        $inventory->quantity = $closingStock;
                        $inventory->save();
                    }

                    // Create normal MONTH_CLOSE adjustment transaction if variance exists
                    if (abs($variance) > 0.001) {
                        InventoryTransaction::create([
                            'business_id' => $businessId,
                            'product_id' => $product->id,
                            'transaction_type' => 'MONTH_CLOSE',
                            'quantity_in' => $stockGain,
                            'quantity_out' => $stockLoss,
                            'reference_type' => InventoryPeriod::class,
                            'reference_id' => $period->id,
                            'description' => sprintf(
                                'Month close adjustment for %s. Discrepancy reconciled.',
                                $periodStart->format('F Y')
                            ),
                            'created_by' => $closedBy,
                        ]);
                    }

                    // Log normal monthly close
                    AuditLog::log(
                        'monthly_close',
                        Product::class,
                        $product->id,
                        ['quantity' => $oldQty],
                        [
                            'quantity' => $closingStock,
                            'variance' => $variance,
                            'period' => $periodStart->format('Y-m')
                        ]
                    );
                }

                // Update July target Period details and Lock it
                $period->update([
                    'physical_count' => $physicalCount,
                    'variance' => $variance,
                    'variance_percentage' => $variancePct,
                    'closing_stock' => $closingStock,
                    'closing_stock_value' => $closingStockValue,
                    'adjustment_value' => $adjustmentValue,
                    'stock_taking_session_id' => $sessionId,
                    'status' => 'locked',
                    'is_locked' => true,
                    'closed_by' => $closedBy,
                    'closed_at' => now(),
                ]);

                // Accumulate summary details
                $summary['products_closed']++;
                $summary['total_stock_loss'] += $stockLoss;
                $summary['total_stock_gain'] += $stockGain;
                $summary['total_loss_value'] += ($adjustmentValue < 0 ? abs($adjustmentValue) : 0);
                $summary['total_gain_value'] += ($adjustmentValue > 0 ? $adjustmentValue : 0);
            }
        });

        return $summary;
    }

    /**
     * Get a summary of all locked periods for a business grouped by month.
     */
    public static function getMonthlySummary(int $businessId): array
    {
        return InventoryPeriod::where('business_id', $businessId)
            ->where('is_locked', true)
            ->orderBy('period_start', 'desc')
            ->get()
            ->groupBy(fn ($p) => Carbon::parse($p->period_start)->format('Y-m'))
            ->map(function ($periods, $monthKey) {
                return [
                    'month_label'      => Carbon::parse($monthKey . '-01')->format('F Y'),
                    'products'         => $periods->count(),
                    'total_opening'    => $periods->sum('opening_stock'),
                    'total_purchases'  => $periods->sum('purchases'),
                    'total_sales'      => $periods->sum('sales'),
                    'total_calculated' => $periods->sum('calculated_stock'),
                    'total_physical'   => $periods->whereNotNull('physical_count')->sum('physical_count'),
                    'total_closing'    => $periods->sum('closing_stock'),
                    'total_variance'   => $periods->sum('variance'),
                    'total_loss'       => $periods->filter(fn ($p) => $p->variance < -0.001)->sum('variance') * -1,
                    'total_gain'       => $periods->filter(fn ($p) => $p->variance > 0.001)->sum('variance'),
                    'total_loss_value' => $periods->filter(fn ($p) => $p->adjustment_value < -0.001)->sum('adjustment_value') * -1,
                    'total_gain_value' => $periods->filter(fn ($p) => $p->adjustment_value > 0.001)->sum('adjustment_value'),
                    'has_physical_count' => $periods->whereNotNull('physical_count')->count() > 0,
                ];
            })
            ->values()
            ->toArray();
    }
}
