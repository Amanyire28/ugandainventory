<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Update status column in inventory_periods table to support the new statuses:
        // ['open', 'pending_reconciliation', 'reconciled', 'locked']
        Schema::table('inventory_periods', function (Blueprint $table) {
            $table->string('status', 30)->default('open')->change();
        });
    }

    public function down(): void
    {
        Schema::table('inventory_periods', function (Blueprint $table) {
            // Revert back to the original enum for safety
            $table->enum('status', ['open', 'closed', 'locked'])->default('open')->change();
        });
    }
};
