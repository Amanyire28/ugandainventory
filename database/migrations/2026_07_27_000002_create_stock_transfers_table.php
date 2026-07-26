<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('stock_transfers')) {
            Schema::create('stock_transfers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id');
                $table->string('transfer_number', 50)->unique();
                $table->unsignedBigInteger('from_location_id');
                $table->unsignedBigInteger('to_location_id');
                $table->unsignedBigInteger('created_by_user_id')->nullable();
                $table->string('status', 20)->default('completed'); // pending, completed, cancelled
                $table->text('notes')->nullable();
                $table->timestamp('transferred_at')->useCurrent();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
                $table->foreign('from_location_id')->references('id')->on('locations')->onDelete('cascade');
                $table->foreign('to_location_id')->references('id')->on('locations')->onDelete('cascade');
                $table->foreign('created_by_user_id')->references('id')->on('users')->onDelete('set null');
            });
        }

        if (!Schema::hasTable('stock_transfer_items')) {
            Schema::create('stock_transfer_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('stock_transfer_id');
                $table->unsignedBigInteger('product_id');
                $table->decimal('quantity', 15, 2);
                $table->decimal('unit_cost', 15, 2)->default(0);
                $table->timestamps();

                $table->foreign('stock_transfer_id')->references('id')->on('stock_transfers')->onDelete('cascade');
                $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfer_items');
        Schema::dropIfExists('stock_transfers');
    }
};
