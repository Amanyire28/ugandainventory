<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Update sale_items table
        Schema::table('sale_items', function (Blueprint $table) {
            $table->decimal('selling_price', 15, 2)->nullable();
            $table->decimal('cost_price_at_sale', 15, 2)->nullable();
            $table->decimal('discount', 15, 2)->default(0.00);
            $table->decimal('vat', 15, 2)->default(0.00);
            $table->decimal('subtotal', 15, 2)->nullable();
        });

        // Backfill sale_items values
        try {
            DB::statement("
                UPDATE sale_items 
                INNER JOIN products ON sale_items.product_id = products.id 
                SET sale_items.selling_price = sale_items.unit_price, 
                    sale_items.subtotal = sale_items.total, 
                    sale_items.cost_price_at_sale = products.cost_price
            ");
        } catch (\Exception $e) {
            // Ignore if tables are empty or query fails during seed/fresh
        }

        // 2. Create inventory_transactions table
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('product_id');
            $table->string('transaction_type'); // PURCHASE, SALE, ADJUSTMENT, TRANSFER, etc.
            $table->decimal('quantity_in', 15, 2)->default(0.00);
            $table->decimal('quantity_out', 15, 2)->default(0.00);
            $table->string('reference_type')->nullable(); // Model name
            $table->unsignedBigInteger('reference_id')->nullable(); // Model ID
            $table->text('description')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });

        // 3. Update inventory_periods table
        Schema::table('inventory_periods', function (Blueprint $table) {
            $table->decimal('opening_stock_value', 15, 2)->default(0.00);
            $table->decimal('closing_stock_value', 15, 2)->default(0.00);
            $table->decimal('purchases_value', 15, 2)->default(0.00);
            $table->decimal('sales_cost_value', 15, 2)->default(0.00);
        });

        // 4. Create customer_transactions table
        Schema::create('customer_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('sale_id')->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->string('transaction_type'); // INVOICE, PAYMENT, REFUND, etc.
            $table->decimal('debit', 15, 2)->default(0.00);  // Increases outstanding balance
            $table->decimal('credit', 15, 2)->default(0.00); // Decreases outstanding balance
            $table->decimal('balance', 15, 2)->default(0.00); // Running outstanding balance
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('sale_id')->references('id')->on('sales')->onDelete('set null');
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('set null');
        });

        // 5. Create supplier_transactions table
        Schema::create('supplier_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supplier_id');
            $table->unsignedBigInteger('purchase_id')->nullable();
            $table->string('transaction_type'); // PURCHASE, PAYMENT, etc.
            $table->decimal('debit', 15, 2)->default(0.00);  // Decreases outstanding debt (e.g. payment made)
            $table->decimal('credit', 15, 2)->default(0.00); // Increases outstanding debt (e.g. purchase invoice received)
            $table->decimal('balance', 15, 2)->default(0.00); // Running outstanding balance
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('cascade');
            $table->foreign('purchase_id')->references('id')->on('purchases')->onDelete('set null');
        });

        // 6. Create audit_logs table
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action');
            $table->string('model')->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->timestamp('timestamp')->useCurrent();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('supplier_transactions');
        Schema::dropIfExists('customer_transactions');
        
        Schema::table('inventory_periods', function (Blueprint $table) {
            $table->dropColumn(['opening_stock_value', 'closing_stock_value', 'purchases_value', 'sales_cost_value']);
        });

        Schema::dropIfExists('inventory_transactions');

        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn(['selling_price', 'cost_price_at_sale', 'discount', 'vat', 'subtotal']);
        });
    }
};
