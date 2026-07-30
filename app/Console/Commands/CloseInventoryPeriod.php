<?php

namespace App\Console\Commands;

use App\Models\{Business, InventoryPeriod, Product, StockAdjustment};
use App\Mail\PeriodClosedMail;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\{Mail, Log};

class CloseInventoryPeriod extends Command
{
    protected $signature = 'inventory:close-period {--business-id= : Close period for specific business only}';

    protected $description = 'Automatically close inventory period and create snapshot records for all products';

    public function handle()
    {
        try {
            $this->info('🔄 Starting inventory period closing process...');

            // Determine period dates (previous month)
            $now = Carbon::now();
            $periodEnd = $now->copy()->subMonth()->endOfMonth();
            $periodStart = $now->copy()->subMonth()->startOfMonth();

            $this->info("Period: {$periodStart->format('Y-m-d')} to {$periodEnd->format('Y-m-d')}");

            // Get businesses to process
            $query = Business::query();
            if ($businessId = $this->option('business-id')) {
                $query->where('id', $businessId);
            }
            $businesses = $query->get();

            if ($businesses->isEmpty()) {
                $this->warn('No businesses found to process.');
                return Command::FAILURE;
            }

            $totalRecords = 0;

            foreach ($businesses as $business) {
                $this->info("\nProcessing business: {$business->name}");

                // Get all products for this business
                $products = Product::where('business_id', $business->id)->get();

                foreach ($products as $product) {
                    // Calculate period data
                    $openingStock = $this->getOpeningStock($product, $periodStart);
                    
                    // Get purchases for period
                    $purchases = $product->purchaseItems()
                        ->whereHas('purchase', function ($q) use ($business, $periodStart, $periodEnd) {
                            $q->where('business_id', $business->id)
                                ->whereBetween('created_at', [$periodStart, $periodEnd]);
                        })
                        ->sum('quantity');

                    // Get sales for period
                    $sales = $product->saleItems()
                        ->whereHas('sale', function ($q) use ($business, $periodStart, $periodEnd) {
                            $q->where('business_id', $business->id)
                                ->whereBetween('created_at', [$periodStart, $periodEnd]);
                        })
                        ->sum('quantity');

                    // Get approved adjustments for period
                    $adjustments = StockAdjustment::where('product_id', $product->id)
                        ->where('status', 'approved')
                        ->whereBetween('created_at', [$periodStart, $periodEnd])
                        ->sum('variance');

                    // Calculate closing stock
                    $calculatedStock = $openingStock + $purchases - $sales + $adjustments;

                    // Check if physical count exists from stock taking
                    $physicalCount = StockAdjustment::where('product_id', $product->id)
                        ->where('status', 'approved')
                        ->whereBetween('created_at', [$periodStart, $periodEnd])
                        ->latest()
                        ->value('physical_count');

                    $closingStock = $physicalCount ?? $calculatedStock;
                    $variance = $closingStock - $calculatedStock;
                    $variancePercentage = $calculatedStock > 0 ? ($variance / $calculatedStock) * 100 : 0;

                    // Get purchases value
                    $purchasesValue = $product->purchaseItems()
                        ->whereHas('purchase', function ($q) use ($business, $periodStart, $periodEnd) {
                            $q->where('business_id', $business->id)
                                ->whereBetween('created_at', [$periodStart, $periodEnd]);
                        })
                        ->sum('total') ?? 0;

                    // Get sales cost value
                    $salesCostValue = $product->saleItems()
                        ->whereHas('sale', function ($q) use ($business, $periodStart, $periodEnd) {
                            $q->where('business_id', $business->id)
                                ->whereBetween('created_at', [$periodStart, $periodEnd]);
                        })
                        ->sum(\Illuminate\Support\Facades\DB::raw('COALESCE(cost_price_at_sale, unit_price) * quantity')) ?? 0;

                    $openingStockValue = $openingStock * $product->cost_price;
                    $closingStockValue = $closingStock * $product->cost_price;

                    // Create inventory period record
                    InventoryPeriod::updateOrCreate(
                        [
                            'product_id' => $product->id,
                            'period_start' => $periodStart,
                        ],
                        [
                            'business_id' => $business->id,
                            'period_end' => $periodEnd,
                            'opening_stock' => $openingStock,
                            'purchases' => $purchases,
                            'sales' => $sales,
                            'adjustments' => $adjustments,
                            'calculated_stock' => $calculatedStock,
                            'physical_count' => $physicalCount,
                            'closing_stock' => $closingStock,
                            'variance' => $variance,
                            'variance_percentage' => $variancePercentage,
                            'opening_stock_value' => $openingStockValue,
                            'closing_stock_value' => $closingStockValue,
                            'purchases_value' => $purchasesValue,
                            'sales_cost_value' => $salesCostValue,
                            'status' => 'closed',
                            'closed_by' => 1, // System user
                            'closed_at' => now(),
                        ]
                    );

                    // Update product's opening_stock for next period
                    $product->update([
                        'opening_stock' => $closingStock,
                        'last_period_closed_date' => now(),
                    ]);

                    $totalRecords++;
                }

                // Send notification email
                try {
                    $this->sendPeriodClosedNotification($business, $periodStart, $periodEnd);
                } catch (\Exception $e) {
                    $this->warn("Failed to send email for {$business->name}: {$e->getMessage()}");
                }

                $this->info("✓ Processed {$products->count()} products");
            }

            $this->info("\n✅ Inventory period closing completed successfully!");
            $this->info("Created/Updated: {$totalRecords} period records");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("❌ Error during period closing: {$e->getMessage()}");
            \Log::error('Inventory period closing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Get opening stock for a product at the start of a period
     * Either from previous period's closing stock or the current opening_stock
     */
    private function getOpeningStock(Product $product, Carbon $periodStart): float
    {
        // Try to get previous period's closing stock
        $previousPeriod = InventoryPeriod::where('product_id', $product->id)
            ->where('period_end', '<', $periodStart)
            ->latest('period_end')
            ->first();

        if ($previousPeriod) {
            return $previousPeriod->closing_stock;
        }

        // Fall back to product's opening_stock field
        return $product->opening_stock ?? 0;
    }

    /**
     * Send period closed notification to business manager
     */
    private function sendPeriodClosedNotification(Business $business, Carbon $periodStart, Carbon $periodEnd): void
    {
        // Get manager/owner user (prefer owner, fallback to active admin)
        $manager = $business->users()
            ->where('is_owner', true)
            ->orWhere('is_owner', false)
            ->first();
        
        if (!$manager) {
            return;
        }

        // Calculate variance summary
        $variances = InventoryPeriod::where('business_id', $business->id)
            ->whereBetween('period_start', [$periodStart, $periodStart])
            ->get();

        $totalVariance = $variances->sum('variance');
        $overstock = $variances->where('variance', '>', 0)->sum('variance');
        $shortage = abs($variances->where('variance', '<', 0)->sum('variance'));
        $productsWithVariance = $variances->where('variance', '!=', 0)->count();

        Mail::send(new PeriodClosedMail(
            $manager,
            $business,
            $periodStart,
            $periodEnd,
            $variances->count(),
            $totalVariance,
            $overstock,
            $shortage,
            $productsWithVariance
        ));
    }
}
