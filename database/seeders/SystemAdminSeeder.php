<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;

class SystemAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a default Super Admin for the System Admin panel
        Admin::updateOrCreate(
            ['email' => 'admin@dukaflow.com'],
            [
                'name' => 'System Administrator',
                'password' => Hash::make('password'),
                'is_active' => true,
                'is_superadmin' => true,
            ]
        );
    }
}
