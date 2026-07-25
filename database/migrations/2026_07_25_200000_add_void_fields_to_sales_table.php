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
        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasColumn('sales', 'status')) {
                $table->string('status', 30)->default('completed')->after('payment_method');
            }
            if (!Schema::hasColumn('sales', 'void_reason')) {
                $table->text('void_reason')->nullable()->after('status');
            }
            if (!Schema::hasColumn('sales', 'voided_at')) {
                $table->timestamp('voided_at')->nullable()->after('void_reason');
            }
            if (!Schema::hasColumn('sales', 'voided_by')) {
                $table->unsignedBigInteger('voided_by')->nullable()->after('voided_at');

                $table->foreign('voided_by')
                      ->references('id')
                      ->on('users')
                      ->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['voided_by']);
            $table->dropColumn(['status', 'void_reason', 'voided_at', 'voided_by']);
        });
    }
};
