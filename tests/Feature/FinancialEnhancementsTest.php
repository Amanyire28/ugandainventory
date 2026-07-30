<?php

use App\Models\User;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\CustomerTransaction;
use App\Models\InventoryTransaction;
use App\Models\Role;
use App\Models\Business;
use Illuminate\Support\Facades\Schema;

test('historical cost tracking and ledger records are created on POS sale', function () {
    // 1. Setup mock business and user
    $cat = \Illuminate\Support\Facades\DB::table('business_categories')->first();
    if (!$cat) {
        $categoryId = \Illuminate\Support\Facades\DB::table('business_categories')->insertGetId([
            'name' => 'General Retail',
            'slug' => 'general-retail',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    } else {
        $categoryId = $cat->id;
    }

    $business = Business::create([
        'name' => 'Test Business',
        'email' => 'test-' . rand(1000, 9999) . '@business.com',
        'phone' => '123456',
        'is_active' => true,
        'slug' => 'test-business-' . rand(1000, 9999),
        'business_category_id' => $categoryId,
    ]);

    $role = Role::where('name', 'admin')->first();
    if (!$role) {
        $role = Role::create([
            'name' => 'admin',
            'display_name' => 'Admin User',
            'description' => 'Admin User',
            'is_system_role' => true,
        ]);
    }

    $user = User::create([
        'name' => 'Admin User',
        'email' => 'admin_test@test.com',
        'password' => bcrypt('password'),
        'role_id' => $role->id,
        'business_id' => $business->id,
        'is_active' => true,
        'phone' => '0777777777',
    ]);

    $product = Product::create([
        'business_id' => $business->id,
        'name' => 'Test Product',
        'sku' => 'TEST-SKU-001',
        'cost_price' => 12000.00,
        'selling_price' => 15000.00,
        'quantity' => 10,
        'opening_stock' => 10,
        'unit' => 'pcs',
        'is_active' => true,
        'requires_vat' => false,
    ]);

    $customer = Customer::create([
        'business_id' => $business->id,
        'name' => 'Test Customer',
        'phone' => '0777777777',
        'is_active' => true,
    ]);

    // 2. Perform POS Checkout request
    $response = $this->actingAs($user)
        ->post(route('pos.process'), [
            'customer_option' => 'existing',
            'customer_id' => $customer->id,
            'discount' => 0,
            'tax' => 0,
            'amount_paid' => 30000.00,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'price' => 15000.00,
                ]
            ]
        ]);

    $response->assertStatus(200);

    // 3. Verify SaleItem cost_price_at_sale matches product cost_price
    $saleItem = SaleItem::first();
    expect($saleItem->cost_price_at_sale)->toEqual(12000.00);
    expect($saleItem->selling_price)->toEqual(15000.00);
    expect($saleItem->subtotal)->toEqual(30000.00);

    // 4. Verify stock decrement
    $product->refresh();
    expect($product->quantity)->toEqual(8.00);

    // 5. Verify Inventory Transaction ledger record
    $invTx = InventoryTransaction::first();
    expect($invTx)->not->toBeNull();
    expect($invTx->transaction_type)->toEqual('SALE');
    expect($invTx->quantity_out)->toEqual(2.00);

    // 6. Verify Customer Transaction ledger record
    $custTx = CustomerTransaction::first();
    expect($custTx)->not->toBeNull();
    expect($custTx->transaction_type)->toEqual('SALE');
    expect($custTx->debit)->toEqual(30000.00);
    expect($custTx->credit)->toEqual(30000.00); // Fully paid cash sale
    expect($custTx->balance)->toEqual(0.00);
});
