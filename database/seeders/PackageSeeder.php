<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Package;

class PackageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Free Trial
        Package::updateOrCreate(
            ['slug' => 'trial'],
            [
                'name' => 'Free Trial',
                'description' => 'Perfect for evaluating DukaFlow features. Limited access.',
                'features' => ['pos', 'products', 'customers'],
                'price' => 0.00,
                'billing_cycle_days' => 30,
                'is_active' => true,
            ]
        );

        // 2. Basic Plan
        Package::updateOrCreate(
            ['slug' => 'basic'],
            [
                'name' => 'Basic Retailer',
                'description' => 'Essential tools for small shops and retail points.',
                'features' => ['pos', 'products', 'customers', 'suppliers', 'expenses'],
                'price' => 20000.00,
                'billing_cycle_days' => 30,
                'is_active' => true,
            ]
        );

        // 3. Standard Plan
        Package::updateOrCreate(
            ['slug' => 'standard'],
            [
                'name' => 'Standard Pro',
                'description' => 'Includes invoices, inventory tracking, VAT management, and stock sessions.',
                'features' => ['pos', 'products', 'customers', 'suppliers', 'expenses', 'invoices', 'inventory', 'vat'],
                'price' => 50000.00,
                'billing_cycle_days' => 30,
                'is_active' => true,
            ]
        );

        // 4. Premium Plan
        Package::updateOrCreate(
            ['slug' => 'premium'],
            [
                'name' => 'Premium Enterprise',
                'description' => 'Full access to sales reports, multi-branch management, stock transfers, and financial analytics.',
                'features' => ['pos', 'products', 'customers', 'suppliers', 'expenses', 'invoices', 'inventory', 'reports', 'vat', 'branches', 'stock_transfers'],
                'price' => 100000.00,
                'billing_cycle_days' => 30,
                'is_active' => true,
            ]
        );
    }
}
