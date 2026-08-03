<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Adds unique constraints on document number columns as a final DB-level
 * safeguard against duplicates — defence in depth beyond the sequence service.
 *
 * SAFE FOR PRODUCTION: Uses IF NOT EXISTS / ignoreErrors pattern so it won't
 * fail if the index already exists from a prior migration attempt.
 *
 * We check for duplicates first and deduplicate before adding the index so
 * existing data with collisions (from the old rand() logic) doesn't block the
 * migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── sales ────────────────────────────────────────────────────────────
        if (Schema::hasTable('sales')) {
            // Deduplicate: keep only the lowest id when duplicates exist
            DB::statement("
                DELETE s1 FROM sales s1
                INNER JOIN sales s2
                    ON s1.business_id = s2.business_id
                    AND s1.sale_number = s2.sale_number
                    AND s1.id > s2.id
            ");

            Schema::table('sales', function (Blueprint $table) {
                // Only add if it doesn't already exist
                $indexes = collect(DB::select("SHOW INDEX FROM sales WHERE Key_name = 'uq_sales_business_number'"));
                if ($indexes->isEmpty()) {
                    $table->unique(['business_id', 'sale_number'], 'uq_sales_business_number');
                }
            });
        }

        // ── invoices ─────────────────────────────────────────────────────────
        if (Schema::hasTable('invoices')) {
            DB::statement("
                DELETE i1 FROM invoices i1
                INNER JOIN invoices i2
                    ON i1.business_id = i2.business_id
                    AND i1.invoice_number = i2.invoice_number
                    AND i1.id > i2.id
            ");

            Schema::table('invoices', function (Blueprint $table) {
                $indexes = collect(DB::select("SHOW INDEX FROM invoices WHERE Key_name = 'uq_invoices_business_number'"));
                if ($indexes->isEmpty()) {
                    $table->unique(['business_id', 'invoice_number'], 'uq_invoices_business_number');
                }
            });
        }

        // ── purchases ────────────────────────────────────────────────────────
        if (Schema::hasTable('purchases')) {
            DB::statement("
                DELETE p1 FROM purchases p1
                INNER JOIN purchases p2
                    ON p1.business_id = p2.business_id
                    AND p1.purchase_number = p2.purchase_number
                    AND p1.id > p2.id
            ");

            Schema::table('purchases', function (Blueprint $table) {
                $indexes = collect(DB::select("SHOW INDEX FROM purchases WHERE Key_name = 'uq_purchases_business_number'"));
                if ($indexes->isEmpty()) {
                    $table->unique(['business_id', 'purchase_number'], 'uq_purchases_business_number');
                }
            });
        }

        // ── stock_transfers ──────────────────────────────────────────────────
        if (Schema::hasTable('stock_transfers')) {
            DB::statement("
                DELETE t1 FROM stock_transfers t1
                INNER JOIN stock_transfers t2
                    ON t1.business_id = t2.business_id
                    AND t1.transfer_number = t2.transfer_number
                    AND t1.id > t2.id
            ");

            Schema::table('stock_transfers', function (Blueprint $table) {
                $indexes = collect(DB::select("SHOW INDEX FROM stock_transfers WHERE Key_name = 'uq_transfers_business_number'"));
                if ($indexes->isEmpty()) {
                    $table->unique(['business_id', 'transfer_number'], 'uq_transfers_business_number');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sales')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->dropUnique('uq_sales_business_number');
            });
        }
        if (Schema::hasTable('invoices')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropUnique('uq_invoices_business_number');
            });
        }
        if (Schema::hasTable('purchases')) {
            Schema::table('purchases', function (Blueprint $table) {
                $table->dropUnique('uq_purchases_business_number');
            });
        }
        if (Schema::hasTable('stock_transfers')) {
            Schema::table('stock_transfers', function (Blueprint $table) {
                $table->dropUnique('uq_transfers_business_number');
            });
        }
    }
};
