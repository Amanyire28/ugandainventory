<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            BusinessCategorySeeder::class,
            RoleSeeder::class,
            PermissionSeeder::class,
            SystemAdminSeeder::class,
            PackageSeeder::class,
        ]);
    }
}