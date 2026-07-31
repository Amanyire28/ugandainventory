<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Enhance inventory_periods with financial values, session link, and immutability flag
        Schema::table('inventory_periods', function (Blueprint $table) {
            // Financial value of the stock variance (variance_qty × cost_price)
            $table->decimal('adjustment_value', 15, 2)->default(0.00)
                ->comment('Variance quantity × product cost price (negative = loss value)')
                ->after('variance_percentage');

            // Link to the stock-taking session that generated this period close
            $table->unsignedBigInteger('stock_taking_session_id')->nullable()
                ->comment('Session that triggered this period close (null = system close only)')
                ->after('adjustment_value');

            // Immutability flag — locked periods cannot be overwritten
            $table->boolean('is_locked')->default(false)
                ->comment('Locked periods cannot be modified; create a new adjustment instead')
                ->after('stock_taking_session_id');

            $table->foreign('stock_taking_session_id')
                ->references('id')->on('stock_taking_sessions')->onDelete('set null');
        });

        // Add period_month to stock_taking_sessions for easy period linking
        Schema::table('stock_taking_sessions', function (Blueprint $table) {
            $table->date('period_month')->nullable()
                ->comment('YYYY-MM-01 date representing which accounting period this session belongs to')
                ->after('notes');

            $table->index('period_month');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_periods', function (Blueprint $table) {
            $table->dropForeign(['stock_taking_session_id']);
            $table->dropColumn(['adjustment_value', 'stock_taking_session_id', 'is_locked']);
        });

        Schema::table('stock_taking_sessions', function (Blueprint $table) {
            $table->dropIndex(['period_month']);
            $table->dropColumn('period_month');
        });
    }
};
