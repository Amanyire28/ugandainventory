<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('products') && !Schema::hasColumn('products', 'requires_vat')) {
            Schema::table('products', function (Blueprint $table) {
                $table->boolean('requires_vat')->default(true)->after('selling_price');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('products') && Schema::hasColumn('products', 'requires_vat')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('requires_vat');
            });
        }
    }
};
