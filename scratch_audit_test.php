<?php

// Bootstrap Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\{Business, Product, InventoryPeriod, Sale, Purchase, StockAdjustment, StockTakingSession, User, BusinessCategory, Location};
use App\Services\{MonthlyClosingService, StockReconciliationService};
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

$results = [];

function getCategory() {
    $cat = BusinessCategory::first();
    if (!$cat) {
        $cat = BusinessCategory::create([
            'name' => 'Audit Category',
            'is_active' => true,
        ]);
    }
    return $cat;
}

function getBusiness() {
    $biz = Business::first();
    if (!$biz) {
        $cat = getCategory();
        $biz = Business::create([
            'name' => 'Verification Audit Biz',
            'email' => 'audit@biz.com',
            'slug' => 'verification-audit-biz-' . uniqid(),
            'business_category_id' => $cat->id,
            'phone' => '0700000000',
        ]);
    }
    return $biz;
}

function getLocation($businessId) {
    $loc = Location::where('business_id', $businessId)->first();
    if (!$loc) {
        $loc = Location::create([
            'business_id' => $businessId,
            'name' => 'Audit Store',
        ]);
    }
    return $loc;
}

function test($name, $callback) {
    global $results;
    try {
        $callback();
        $results[] = ['name' => $name, 'status' => 'PASS', 'message' => 'Passed successfully.'];
    } catch (\Throwable $e) {
        $results[] = ['name' => $name, 'status' => 'FAIL', 'message' => $e->getMessage() . "\n" . $e->getTraceAsString()];
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// AUDIT TEST 1: PERIOD CREATION
// ─────────────────────────────────────────────────────────────────────────────
test('1. Lazy Period Creation & Status Assignment', function() {
    $business = getBusiness();

    $uid = uniqid();
    // Create a product created on July 15, 2026
    Carbon::setTestNow(Carbon::create(2026, 7, 15, 12, 0, 0));
    $product = Product::create([
        'business_id' => $business->id,
        'name' => 'Audit Widget ' . $uid,
        'sku' => 'AUD-WID-' . $uid,
        'quantity' => 100,
        'cost_price' => 10000,
        'selling_price' => 15000,
        'opening_stock' => 100,
    ]);

    // Fast-forward to August 10, 2026
    Carbon::setTestNow(Carbon::create(2026, 8, 10, 12, 0, 0));

    // Execute ensure periods exist
    MonthlyClosingService::ensurePeriodsExist($business->id);

    // Verify July period is pending_reconciliation
    $julyPeriod = InventoryPeriod::where('product_id', $product->id)
        ->where('period_start', Carbon::create(2026, 7, 1)->startOfMonth())
        ->first();
    
    if (!$julyPeriod) {
        throw new \Exception('July period was not created.');
    }
    if ($julyPeriod->status !== 'pending_reconciliation') {
        throw new \Exception("July status should be pending_reconciliation, got: {$julyPeriod->status}");
    }

    // Verify August period is open
    $augPeriod = InventoryPeriod::where('product_id', $product->id)
        ->where('period_start', Carbon::create(2026, 8, 1)->startOfMonth())
        ->first();
        
    if (!$augPeriod) {
        throw new \Exception('August period was not created.');
    }
    if ($augPeriod->status !== 'open') {
        throw new \Exception("August status should be open, got: {$augPeriod->status}");
    }

    // Ensure no future periods exist
    $sepPeriod = InventoryPeriod::where('product_id', $product->id)
        ->where('period_start', Carbon::create(2026, 9, 1)->startOfMonth())
        ->first();
    if ($sepPeriod) {
        throw new \Exception('Unnecessary future period was created for September.');
    }

    // Cleanup
    InventoryPeriod::where('product_id', $product->id)->delete();
    $product->delete();
    Carbon::setTestNow(null);
});

// ─────────────────────────────────────────────────────────────────────────────
// AUDIT TEST 2: LATE STOCK TAKE (SCENARIO 2)
// ─────────────────────────────────────────────────────────────────────────────
test('2. Late Stock Take Reconciliation (Scenario 2)', function() {
    $business = getBusiness();
    $location = getLocation($business->id);
    
    $uid = uniqid();
    Carbon::setTestNow(Carbon::create(2026, 7, 15, 12, 0, 0));
    $product = Product::create([
        'business_id' => $business->id,
        'name' => 'Late Widget ' . $uid,
        'sku' => 'LT-WID-' . $uid,
        'quantity' => 100,
        'cost_price' => 10000,
        'selling_price' => 15000,
        'opening_stock' => 100,
    ]);

    // Create July transactions: Purchase 50 units, Sale 30 units.
    $julyPurchase = Purchase::create([
        'business_id' => $business->id,
        'purchase_number' => 'P-JUL-' . $uid,
        'supplier_id' => 1,
        'user_id' => 1,
        'total' => 500000,
        'status' => 'received',
        'location_id' => $location->id,
    ]);
    DB::table('purchases')->where('id', $julyPurchase->id)->update([
        'created_at' => Carbon::create(2026, 7, 20, 12, 0, 0),
        'updated_at' => Carbon::create(2026, 7, 20, 12, 0, 0)
    ]);

    $julyPurchaseItem = $julyPurchase->items()->create([
        'product_id' => $product->id,
        'quantity' => 50,
        'unit_cost' => 10000,
        'total' => 500000,
    ]);

    $julySale = Sale::create([
        'business_id' => $business->id,
        'sale_number' => 'S-JUL-' . $uid,
        'user_id' => 1,
        'subtotal' => 450000,
        'total' => 450000,
        'payment_method' => 'cash',
        'sale_date' => Carbon::create(2026, 7, 25, 12, 0, 0),
    ]);
    DB::table('sales')->where('id', $julySale->id)->update([
        'sale_date' => Carbon::create(2026, 7, 25, 12, 0, 0),
        'created_at' => Carbon::create(2026, 7, 25, 12, 0, 0),
        'updated_at' => Carbon::create(2026, 7, 25, 12, 0, 0)
    ]);

    $julySaleItem = $julySale->items()->create([
        'product_id' => $product->id,
        'quantity' => 30,
        'unit_price' => 15000,
        'total' => 450000,
        'cost_price_at_sale' => 10000,
    ]);

    // Transition to August 5, 2026
    Carbon::setTestNow(Carbon::create(2026, 8, 5, 12, 0, 0));
    MonthlyClosingService::ensurePeriodsExist($business->id);

    // Verify July expected closing: 100 (Opening) + 50 (purchases) - 30 (sales) = 120
    $julyPeriod = InventoryPeriod::where('product_id', $product->id)
        ->where('period_start', Carbon::create(2026, 7, 1)->startOfMonth())
        ->first();

    if (!$julyPeriod) {
        throw new \Exception('July period was not found in database.');
    }
    if ((float)$julyPeriod->calculated_stock !== 120.0) {
        throw new \Exception("July calculated stock should be 120, got: {$julyPeriod->calculated_stock}");
    }

    // August transactions: Purchases 5, Sales 10. System stock would be 120 + 5 - 10 = 115
    $augPurchase = Purchase::create([
        'business_id' => $business->id,
        'purchase_number' => 'P-AUG-' . $uid,
        'supplier_id' => 1,
        'user_id' => 1,
        'total' => 50000,
        'status' => 'received',
        'location_id' => $location->id,
    ]);
    DB::table('purchases')->where('id', $augPurchase->id)->update([
        'created_at' => Carbon::create(2026, 8, 2, 12, 0, 0),
        'updated_at' => Carbon::create(2026, 8, 2, 12, 0, 0)
    ]);

    $augPurchaseItem = $augPurchase->items()->create([
        'product_id' => $product->id,
        'quantity' => 5,
        'unit_cost' => 10000,
        'total' => 50000,
    ]);

    $augSale = Sale::create([
        'business_id' => $business->id,
        'sale_number' => 'S-AUG-' . $uid,
        'user_id' => 1,
        'subtotal' => 150000,
        'total' => 150000,
        'payment_method' => 'cash',
        'sale_date' => Carbon::create(2026, 8, 3, 12, 0, 0),
    ]);
    DB::table('sales')->where('id', $augSale->id)->update([
        'sale_date' => Carbon::create(2026, 8, 3, 12, 0, 0),
        'created_at' => Carbon::create(2026, 8, 3, 12, 0, 0),
        'updated_at' => Carbon::create(2026, 8, 3, 12, 0, 0)
    ]);

    $augSaleItem = $augSale->items()->create([
        'product_id' => $product->id,
        'quantity' => 10,
        'unit_price' => 15000,
        'total' => 150000,
        'cost_price_at_sale' => 10000,
    ]);

    // Force system stock to reflect 115 (unadjusted August stock)
    $product->update(['quantity' => 115]);

    // Late stock count July is approved on August 5. Count: 117.
    // Variance: 117 - 120 = -3
    $session = StockTakingSession::create([
        'business_id' => $business->id,
        'session_date' => Carbon::create(2026, 7, 31, 23, 59, 59),
        'status' => 'closed',
        'initiated_by' => 1,
    ]);

    $adjustment = StockAdjustment::create([
        'business_id' => $business->id,
        'product_id' => $product->id,
        'stock_taking_session_id' => $session->id,
        'adjustment_date' => Carbon::create(2026, 7, 31, 23, 59, 59),
        'physical_count' => 117,
        'system_quantity' => 120,
        'variance' => -3,
        'adjustment_quantity' => -3,
        'status' => 'approved',
        'recorded_by' => 1,
    ]);

    // Run closeMonth for July (Month 7)
    $summary = MonthlyClosingService::closeMonth($business->id, 2026, 7, 1, $session->id);

    // Verify July is locked, physical count is 117, variance is -3
    $julyPeriod->refresh();
    if (!$julyPeriod->is_locked || $julyPeriod->status !== 'locked') {
        throw new \Exception('July period was not locked.');
    }
    if ((float)$julyPeriod->physical_count !== 117.0 || (float)$julyPeriod->variance !== -3.0) {
        throw new \Exception("July period counts wrong: physical={$julyPeriod->physical_count}, var={$julyPeriod->variance}");
    }

    // Verify August period calculations
    $augPeriod = InventoryPeriod::where('product_id', $product->id)
        ->where('period_start', Carbon::create(2026, 8, 1)->startOfMonth())
        ->first();
    
    // August adjustments should be incremented by July's variance (-3)
    if ((float)$augPeriod->adjustments !== -3.0) {
        throw new \Exception("August adjustments should be -3.0, got: {$augPeriod->adjustments}");
    }
    // August closing stock: 120 (Opening) + 5 (Purchases) - 10 (Sales) + (-3 Adjustments) = 112
    if ((float)$augPeriod->closing_stock !== 112.0) {
        throw new \Exception("August closing stock should be 112.0, got: {$augPeriod->closing_stock}");
    }

    // Verify current stock on product is 112
    $product->refresh();
    if ((float)$product->quantity !== 112.0) {
        throw new \Exception("Current product quantity should be 112, got: {$product->quantity}");
    }

    // Verify Late Adjustment transaction is created
    $transaction = \App\Models\InventoryTransaction::where('product_id', $product->id)
        ->where('transaction_type', 'LATE_ADJUSTMENT')
        ->first();
    if (!$transaction) {
        throw new \Exception('Late Adjustment transaction was not created.');
    }
    if ((float)$transaction->quantity_out !== 3.0) {
        throw new \Exception("Late transaction quantity_out should be 3.0, got: {$transaction->quantity_out}");
    }

    // Cleanup
    \App\Models\InventoryTransaction::where('product_id', $product->id)->delete();
    $adjustment->delete();
    $session->delete();
    
    $augSaleItem->delete();
    $augSale->delete();
    $augPurchaseItem->delete();
    $augPurchase->delete();
    
    $julySaleItem->delete();
    $julySale->delete();
    $julyPurchaseItem->delete();
    $julyPurchase->delete();
    
    InventoryPeriod::where('product_id', $product->id)->delete();
    $product->delete();
    Carbon::setTestNow(null);
});

// ─────────────────────────────────────────────────────────────────────────────
// AUDIT TEST 3: DATE RANGE REPORTING
// ─────────────────────────────────────────────────────────────────────────────
test('3. Dynamic Date Range calculations (getInventoryStateForRange)', function() {
    $business = getBusiness();
    $location = getLocation($business->id);
    
    $uid = uniqid();
    Carbon::setTestNow(Carbon::create(2026, 7, 1, 12, 0, 0));
    $product = Product::create([
        'business_id' => $business->id,
        'name' => 'Report Widget ' . $uid,
        'sku' => 'RP-WID-' . $uid,
        'quantity' => 100,
        'cost_price' => 10000,
        'selling_price' => 15000,
        'opening_stock' => 100,
    ]);

    // Let's create a locked period for July ending July 31 with closing stock 117
    $julyPeriod = InventoryPeriod::create([
        'business_id' => $business->id,
        'product_id' => $product->id,
        'period_start' => Carbon::create(2026, 7, 1)->startOfMonth(),
        'period_end' => Carbon::create(2026, 7, 31)->endOfMonth(),
        'opening_stock' => 100,
        'purchases' => 50,
        'sales' => 30,
        'adjustments' => 0,
        'calculated_stock' => 120,
        'physical_count' => 117,
        'closing_stock' => 117,
        'variance' => -3,
        'variance_percentage' => -2.5,
        'adjustment_value' => -30000,
        'is_locked' => true,
        'status' => 'locked',
        'closed_by' => 1,
        'closed_at' => now(),
    ]);

    // August transactions:
    // Aug 5: Purchase 10 units
    // Aug 10: Sale 5 units
    // Aug 15: Sale 3 units
    
    Carbon::setTestNow(Carbon::create(2026, 8, 5, 12, 0, 0));
    // Create dummy purchase
    $purchase = Purchase::create([
        'business_id' => $business->id,
        'purchase_number' => 'P-001-' . $uid,
        'supplier_id' => 1,
        'user_id' => 1,
        'total' => 100000,
        'status' => 'received',
        'location_id' => $location->id,
    ]);
    DB::table('purchases')->where('id', $purchase->id)->update([
        'created_at' => Carbon::create(2026, 8, 5, 12, 0, 0),
        'updated_at' => Carbon::create(2026, 8, 5, 12, 0, 0)
    ]);

    $purchaseItem = $purchase->items()->create([
        'product_id' => $product->id,
        'quantity' => 10,
        'unit_cost' => 10000,
        'total' => 100000,
    ]);

    Carbon::setTestNow(Carbon::create(2026, 8, 10, 12, 0, 0));
    // Create dummy sale
    $sale1 = Sale::create([
        'business_id' => $business->id,
        'sale_number' => 'S-001-' . $uid,
        'user_id' => 1,
        'subtotal' => 75000,
        'total' => 75000,
        'payment_method' => 'cash',
        'sale_date' => now(),
    ]);
    DB::table('sales')->where('id', $sale1->id)->update([
        'sale_date' => Carbon::create(2026, 8, 10, 12, 0, 0),
        'created_at' => Carbon::create(2026, 8, 10, 12, 0, 0),
        'updated_at' => Carbon::create(2026, 8, 10, 12, 0, 0)
    ]);

    $saleItem1 = $sale1->items()->create([
        'product_id' => $product->id,
        'quantity' => 5,
        'unit_price' => 15000,
        'total' => 75000,
        'cost_price_at_sale' => 10000,
    ]);

    Carbon::setTestNow(Carbon::create(2026, 8, 15, 12, 0, 0));
    $sale2 = Sale::create([
        'business_id' => $business->id,
        'sale_number' => 'S-002-' . $uid,
        'user_id' => 1,
        'subtotal' => 45000,
        'total' => 45000,
        'payment_method' => 'cash',
        'sale_date' => now(),
    ]);
    DB::table('sales')->where('id', $sale2->id)->update([
        'sale_date' => Carbon::create(2026, 8, 15, 12, 0, 0),
        'created_at' => Carbon::create(2026, 8, 15, 12, 0, 0),
        'updated_at' => Carbon::create(2026, 8, 15, 12, 0, 0)
    ]);

    $saleItem2 = $sale2->items()->create([
        'product_id' => $product->id,
        'quantity' => 3,
        'unit_price' => 15000,
        'total' => 45000,
        'cost_price_at_sale' => 10000,
    ]);

    // Assert getInventoryStateForRange from Aug 8 to Aug 12
    // Expected opening (at Aug 8): 117 (July Close) + 10 (Aug 5 Purchase) = 127
    // Purchases inside range (Aug 8 - Aug 12): 0
    // Sales inside range: 5 (Aug 10 Sale)
    // Adjustments inside range: 0
    // Expected closing: 127 + 0 - 5 = 122
    $state = StockReconciliationService::getInventoryStateForRange($product, Carbon::create(2026, 8, 8), Carbon::create(2026, 8, 12));
    
    // Cleanup
    $saleItem1->delete();
    $saleItem2->delete();
    $sale1->delete();
    $sale2->delete();
    $purchaseItem->delete();
    $purchase->delete();
    InventoryPeriod::where('product_id', $product->id)->delete();
    $product->delete();
    Carbon::setTestNow(null);

    if ((float)$state['opening_stock'] !== 127.0) {
        throw new \Exception("Opening stock should be 127.0, got: {$state['opening_stock']}");
    }
    if ((float)$state['purchases'] !== 0.0) {
        throw new \Exception("Purchases should be 0.0, got: {$state['purchases']}");
    }
    if ((float)$state['sales'] !== 5.0) {
        throw new \Exception("Sales should be 5.0, got: {$state['sales']}");
    }
    if ((float)$state['closing_stock'] !== 122.0) {
        throw new \Exception("Closing stock should be 122.0, got: {$state['closing_stock']}");
    }
});

// Run all audits
foreach ($results as $r) {
    echo "{$r['name']}: {$r['status']} - {$r['message']}\n";
}
