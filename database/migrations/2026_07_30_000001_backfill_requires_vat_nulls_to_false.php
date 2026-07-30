<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Backfill NULL requires_vat values to false.
     *
     * Products created before the requires_vat column was added have NULL,
     * which caused the ?? true fallback in controllers to incorrectly apply VAT.
     * We set NULL → false (no VAT) as the safe conservative default.
     * Owners can then explicitly enable VAT per-product if needed.
     */
    public function up(): void
    {
        if (Schema::hasTable('products') && Schema::hasColumn('products', 'requires_vat')) {
            DB::table('products')
                ->whereNull('requires_vat')
                ->update(['requires_vat' => false]);
        }
    }

    public function down(): void
    {
        // Intentionally not reversible — setting back to NULL would re-introduce the bug
    }
};
