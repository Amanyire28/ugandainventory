<?php

namespace App\Services;

use App\Models\{
    Product,
    Sale,
    SaleItem,
    Invoice,
    InvoiceItem,
    Purchase,
    PurchaseItem,
    StockTransfer,
    StockTransferItem,
    StockAdjustment,
    Inventory,
    InventoryTransaction,
    AuditLog,
};
use Illuminate\Support\Facades\DB;

/**
 * InventoryService — the ONLY class that may physically change stock quantities.
 *
 * ┌──────────────────────────────────────────────────────────────────────────┐
 * │  ARCHITECTURE RULE                                                        │
 * │  Every stock mutation in the application MUST go through this service.   │
 * │  Controllers prepare data and call the service; they never call           │
 * │  $product->increment/decrement/setStock directly.                        │
 * └──────────────────────────────────────────────────────────────────────────┘
 *
 * CONCURRENCY STRATEGY
 * ─────────────────────
 * Every write operation:
 *   1. Runs inside a DB transaction (provided by the caller or opened here).
 *   2. Sorts products by ascending product_id before locking — this canonical
 *      ordering prevents deadlocks when two transactions touch overlapping sets.
 *   3. Acquires a SELECT … FOR UPDATE row-level lock on each product row
 *      BEFORE reading quantity — so the check and the decrement operate on the
 *      same consistent snapshot with no other writer able to intervene.
 *   4. Validates stock only after locking.
 *   5. Deducts / increments within the same transaction.
 *
 * METHODS
 * ────────
 *   deductForSale(Sale, array $items, int $userId)
 *   restoreFromVoid(Sale, int $userId)
 *   addFromPurchase(Purchase, array $items, int $userId)
 *   reverseFromPurchaseCancellation(Purchase, int $userId)
 *   deductForTransfer(StockTransfer, array $items, int $userId)
 *   applyStockAdjustment(Product, float $physicalQty, StockAdjustment, int $userId)
 */
class InventoryService
{
    // =========================================================================
    // SALES — deduct stock when a sale is created
    // =========================================================================

    /**
     * Deduct stock for a completed sale.
     *
     * MUST be called inside an active DB::transaction().
     *
     * @param  Sale   $sale     The persisted (but not yet item-populated) sale
     * @param  array  $items    Array of ['product_id' => int, 'quantity' => float, 'price' => float]
     * @param  int    $userId   The acting user (for audit trail)
     * @return void
     *
     * @throws \RuntimeException  If any product has insufficient stock
     */
    public function deductForSale(Sale $sale, array $items, int $userId): void
    {
        // Sort by product_id ascending — canonical lock order prevents deadlocks
        usort($items, fn($a, $b) => $a['product_id'] <=> $b['product_id']);

        foreach ($items as $item) {
            $productId = (int) $item['product_id'];
            $qty       = (float) $item['quantity'];

            // Lock the row — blocks any concurrent transaction touching the same product
            $product = Product::where('id', $productId)
                ->where('business_id', $sale->business_id)
                ->lockForUpdate()
                ->firstOrFail();

            // Validate AFTER locking — this is the only safe place to read stock
            if ($product->quantity < $qty) {
                throw new \RuntimeException(
                    "Insufficient stock for \"{$product->name}\". " .
                    "Available: {$product->quantity}, requested: {$qty}."
                );
            }

            // Create the sale item
            SaleItem::create([
                'sale_id'            => $sale->id,
                'product_id'         => $productId,
                'quantity'           => $qty,
                'unit_price'         => $item['price'],
                'selling_price'      => $item['price'],
                'cost_price_at_sale' => $product->cost_price,
                'subtotal'           => $qty * $item['price'],
                'total'              => $qty * $item['price'],
            ]);

            // Deduct from denormalised product total
            $product->decrement('quantity', $qty);

            // Deduct from location-aware inventory if location is set
            if ($sale->location_id) {
                $inv = Inventory::firstOrCreate(
                    ['product_id' => $productId, 'location_id' => $sale->location_id],
                    ['quantity' => 0, 'reorder_level' => $product->reorder_level ?? 5]
                );
                $inv->decrement('quantity', $qty);
            }

            // Inventory ledger entry
            InventoryTransaction::create([
                'business_id'      => $sale->business_id,
                'product_id'       => $productId,
                'transaction_type' => 'SALE',
                'quantity_in'      => 0,
                'quantity_out'     => $qty,
                'reference_type'   => Sale::class,
                'reference_id'     => $sale->id,
                'description'      => "POS Sale #{$sale->sale_number}",
                'created_by'       => $userId,
            ]);
        }
    }

    /**
     * Deduct stock for invoice items (credit sale / deferred payment).
     * Identical logic to deductForSale but references an Invoice.
     *
     * MUST be called inside an active DB::transaction().
     *
     * @param  Invoice $invoice
     * @param  array   $items   Array of ['product_id' => int, 'quantity' => float, 'price' => float]
     * @param  int     $userId
     */
    public function deductForInvoice(Invoice $invoice, array $items, int $userId): void
    {
        usort($items, fn($a, $b) => $a['product_id'] <=> $b['product_id']);

        foreach ($items as $item) {
            $productId = (int) $item['product_id'];
            $qty       = (float) $item['quantity'];

            $product = Product::where('id', $productId)
                ->where('business_id', $invoice->business_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($product->quantity < $qty) {
                throw new \RuntimeException(
                    "Insufficient stock for \"{$product->name}\". " .
                    "Available: {$product->quantity}, requested: {$qty}."
                );
            }

            InvoiceItem::create([
                'invoice_id'  => $invoice->id,
                'description' => $product->name,
                'product_id'  => $productId,
                'quantity'    => $qty,
                'unit_price'  => $item['price'],
                'total'       => $qty * $item['price'],
                'added_by'    => $userId,
            ]);

            $product->decrement('quantity', $qty);

            InventoryTransaction::create([
                'business_id'      => $invoice->business_id,
                'product_id'       => $productId,
                'transaction_type' => 'SALE',
                'quantity_in'      => 0,
                'quantity_out'     => $qty,
                'reference_type'   => Invoice::class,
                'reference_id'     => $invoice->id,
                'description'      => "Credit Invoice #{$invoice->invoice_number}",
                'created_by'       => $userId,
            ]);
        }
    }

    // =========================================================================
    // VOID — restore stock when a sale is voided
    // =========================================================================

    /**
     * Restore stock for all items in a voided sale.
     *
     * MUST be called inside an active DB::transaction().
     * Locks products in ascending ID order before modifying.
     *
     * @param  Sale  $sale    Already marked as 'voided'; items must be loaded
     * @param  int   $userId
     * @return array  Array of ['product' => name, 'quantity' => qty] for audit
     */
    public function restoreFromVoid(Sale $sale, int $userId): array
    {
        $sale->loadMissing('items.product');

        // Build sorted item list for canonical lock order
        $items = $sale->items->sortBy('product_id')->values();

        $restored = [];

        foreach ($items as $item) {
            if (!$item->product) {
                continue;
            }

            $qty = (float) $item->quantity;

            // Lock before writing — prevents concurrent void + sale collision
            $product = Product::where('id', $item->product_id)
                ->lockForUpdate()
                ->firstOrFail();

            $product->increment('quantity', $qty);

            if ($sale->location_id) {
                $inv = Inventory::where('product_id', $item->product_id)
                    ->where('location_id', $sale->location_id)
                    ->lockForUpdate()
                    ->first();

                if ($inv) {
                    $inv->increment('quantity', $qty);
                }
            }

            InventoryTransaction::create([
                'business_id'      => $sale->business_id,
                'product_id'       => $item->product_id,
                'transaction_type' => 'VOID_RESTOCK',
                'quantity_in'      => $qty,
                'quantity_out'     => 0,
                'reference_type'   => Sale::class,
                'reference_id'     => $sale->id,
                'description'      => "Void restock — Sale #{$sale->sale_number} reversed",
                'created_by'       => $userId,
            ]);

            $restored[] = ['product' => $product->name, 'quantity' => $qty];
        }

        return $restored;
    }

    // =========================================================================
    // PURCHASES — add stock when a purchase is received
    // =========================================================================

    /**
     * Increment stock for each item in a purchase.
     *
     * MUST be called inside an active DB::transaction().
     * Purchases add stock so there is no "insufficient" check, but we still
     * lock to prevent a concurrent purchase + void from creating a phantom read.
     *
     * @param  Purchase $purchase
     * @param  array    $items   [['product_id', 'quantity', 'unit_cost'], ...]
     * @param  int      $userId
     */
    public function addFromPurchase(Purchase $purchase, array $items, int $userId): void
    {
        usort($items, fn($a, $b) => $a['product_id'] <=> $b['product_id']);

        foreach ($items as $item) {
            $productId = (int) $item['product_id'];
            $qty       = (float) $item['quantity'];
            $unitCost  = (float) $item['unit_cost'];
            $lineTotal = $qty * $unitCost;

            PurchaseItem::create([
                'purchase_id' => $purchase->id,
                'product_id'  => $productId,
                'quantity'    => $qty,
                'unit_cost'   => $unitCost,
                'total'       => $lineTotal,
            ]);

            $product = Product::where('id', $productId)
                ->where('business_id', $purchase->business_id)
                ->lockForUpdate()
                ->firstOrFail();

            $product->increment('quantity', $qty);
            $product->update(['cost_price' => $unitCost]);

            InventoryTransaction::create([
                'business_id'      => $purchase->business_id,
                'product_id'       => $productId,
                'transaction_type' => 'PURCHASE',
                'quantity_in'      => $qty,
                'quantity_out'     => 0,
                'reference_type'   => Purchase::class,
                'reference_id'     => $purchase->id,
                'description'      => "Purchase #{$purchase->purchase_number}",
                'created_by'       => $userId,
            ]);
        }
    }

    /**
     * Reverse stock when a purchase is cancelled / deleted.
     *
     * MUST be called inside an active DB::transaction().
     *
     * @param  Purchase $purchase  Must have items.product loaded
     * @param  int      $userId
     */
    public function reverseFromPurchaseCancellation(Purchase $purchase, int $userId): void
    {
        $purchase->loadMissing('items.product');

        $items = $purchase->items->sortBy('product_id')->values();

        foreach ($items as $item) {
            if (!$item->product || $item->product->business_id !== $purchase->business_id) {
                continue;
            }

            $qty = (float) $item->quantity;

            $product = Product::where('id', $item->product_id)
                ->lockForUpdate()
                ->firstOrFail();

            $product->decrement('quantity', $qty);

            InventoryTransaction::create([
                'business_id'      => $purchase->business_id,
                'product_id'       => $item->product_id,
                'transaction_type' => 'PURCHASE_REVERSAL',
                'quantity_in'      => 0,
                'quantity_out'     => $qty,
                'reference_type'   => Purchase::class,
                'reference_id'     => $purchase->id,
                'description'      => "Purchase #{$purchase->purchase_number} cancelled — stock reversed",
                'created_by'       => $userId,
            ]);
        }
    }

    // =========================================================================
    // STOCK TRANSFERS — move stock between locations
    // =========================================================================

    /**
     * Deduct from source location and add to destination location.
     *
     * MUST be called inside an active DB::transaction().
     * Locks source inventory row before checking available quantity.
     *
     * @param  StockTransfer $transfer
     * @param  array         $items   [['product_id', 'qty'], ...]
     * @param  int           $fromLocationId
     * @param  int           $toLocationId
     * @param  int           $userId
     */
    public function deductForTransfer(
        StockTransfer $transfer,
        array $items,
        int $fromLocationId,
        int $toLocationId,
        int $userId
    ): void {
        usort($items, fn($a, $b) => $a['product_id'] <=> $b['product_id']);

        foreach ($items as $item) {
            $productId   = (int) $item['product_id'];
            $transferQty = (float) $item['qty'];

            // Lock the product row first
            $product = Product::where('id', $productId)
                ->where('business_id', $transfer->business_id)
                ->lockForUpdate()
                ->firstOrFail();

            // Validate the source inventory has enough
            if ($product->quantity < $transferQty) {
                throw new \RuntimeException(
                    "Insufficient stock for \"{$product->name}\" to transfer. " .
                    "Available: {$product->quantity}, requested: {$transferQty}."
                );
            }

            // Record the transfer item
            StockTransferItem::create([
                'stock_transfer_id' => $transfer->id,
                'product_id'        => $productId,
                'quantity'          => $transferQty,
                'unit_cost'         => $product->cost_price ?? 0,
            ]);

            // Deduct from source branch inventory
            $sourceInv = Inventory::firstOrCreate(
                ['product_id' => $productId, 'location_id' => $fromLocationId],
                ['quantity' => 0, 'reorder_level' => $product->reorder_level ?? 5]
            );
            $sourceInv->decrement('quantity', $transferQty);

            // Add to destination branch inventory
            $destInv = Inventory::firstOrCreate(
                ['product_id' => $productId, 'location_id' => $toLocationId],
                ['quantity' => 0, 'reorder_level' => $product->reorder_level ?? 5]
            );
            $destInv->increment('quantity', $transferQty);

            // The product.quantity (total across all locations) does not change
            // on a transfer — it is just moving between locations.

            InventoryTransaction::create([
                'business_id'      => $transfer->business_id,
                'product_id'       => $productId,
                'transaction_type' => 'TRANSFER_OUT',
                'quantity_in'      => 0,
                'quantity_out'     => $transferQty,
                'reference_type'   => StockTransfer::class,
                'reference_id'     => $transfer->id,
                'description'      => "Transferred OUT to location #{$toLocationId} via {$transfer->transfer_number}",
                'created_by'       => $userId,
            ]);

            InventoryTransaction::create([
                'business_id'      => $transfer->business_id,
                'product_id'       => $productId,
                'transaction_type' => 'TRANSFER_IN',
                'quantity_in'      => $transferQty,
                'quantity_out'     => 0,
                'reference_type'   => StockTransfer::class,
                'reference_id'     => $transfer->id,
                'description'      => "Transferred IN from location #{$fromLocationId} via {$transfer->transfer_number}",
                'created_by'       => $userId,
            ]);
        }
    }

    // =========================================================================
    // STOCK ADJUSTMENTS — physical count reconciliation
    // =========================================================================

    /**
     * Apply a physical count adjustment to a product.
     *
     * MUST be called inside an active DB::transaction().
     * Locks the product row before setting the absolute quantity.
     *
     * @param  Product         $product
     * @param  float           $physicalQty  The counted quantity to set
     * @param  StockAdjustment $adjustment   Already persisted adjustment record
     * @param  int             $businessId
     * @param  int             $userId
     * @return float  The variance (physicalQty - previous qty)
     */
    public function applyStockAdjustment(
        Product $product,
        float $physicalQty,
        StockAdjustment $adjustment,
        int $businessId,
        int $userId
    ): float {
        // Lock the row — prevents a concurrent sale from reading stale data
        $product = Product::where('id', $product->id)
            ->lockForUpdate()
            ->firstOrFail();

        $oldQty   = (float) $product->quantity;
        $variance = $physicalQty - $oldQty;

        // Set absolute quantity
        $product->quantity = $physicalQty;
        $product->save();

        // Sync location-aware inventory
        $inventory = Inventory::where('business_id', $businessId)
            ->where('product_id', $product->id)
            ->lockForUpdate()
            ->first();

        if ($inventory) {
            $inventory->quantity = $physicalQty;
            $inventory->save();
        }

        $qtyIn  = $variance > 0 ? $variance  : 0;
        $qtyOut = $variance < 0 ? abs($variance) : 0;

        InventoryTransaction::create([
            'business_id'      => $businessId,
            'product_id'       => $product->id,
            'transaction_type' => 'ADJUSTMENT',
            'quantity_in'      => $qtyIn,
            'quantity_out'     => $qtyOut,
            'reference_type'   => StockAdjustment::class,
            'reference_id'     => $adjustment->id,
            'description'      => sprintf(
                'Stock Adjustment. System: %.2f → Physical: %.2f (Variance: %+.2f).',
                $oldQty,
                $physicalQty,
                $variance
            ),
            'created_by' => $userId,
        ]);

        AuditLog::log(
            'stock_adjustment',
            Product::class,
            $product->id,
            ['quantity' => $oldQty],
            ['quantity' => $physicalQty, 'variance' => $variance]
        );

        return $variance;
    }
}
