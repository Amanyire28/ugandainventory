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
        if (Schema::hasTable('expenses') && !Schema::hasColumn('expenses', 'vat_amount')) {
            Schema::table('expenses', function (Blueprint $table) {
                $table->decimal('vat_amount', 12, 2)->default(0.00)->after('amount');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('expenses') && Schema::hasColumn('expenses', 'vat_amount')) {
            Schema::table('expenses', function (Blueprint $table) {
                $table->dropColumn('vat_amount');
            });
        }
    }
};
