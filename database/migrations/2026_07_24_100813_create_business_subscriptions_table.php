<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            $table->string('package_slug')->nullable();       // e.g. 'basic', 'premium'
            $table->decimal('amount', 12, 2)->default(0);    // amount paid
            $table->string('currency', 10)->default('UGX');
            $table->enum('status', ['pending', 'paid', 'failed', 'refunded', 'cancelled'])->default('pending');
            $table->string('payment_method')->nullable();    // e.g. 'Mobile Money', 'Bank Transfer', 'Cash'
            $table->string('reference')->nullable();         // receipt / transaction reference
            $table->text('notes')->nullable();
            $table->date('period_start')->nullable();        // subscription period covered
            $table->date('period_end')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_subscriptions');
    }
};
