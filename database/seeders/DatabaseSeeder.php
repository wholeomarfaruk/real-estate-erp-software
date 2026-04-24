<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            PanelSeeder::class,
            StoreSeeder::class,
            ProductUnitSeeder::class,

            ProductSeeder::class,
            SupplierSeeder::class,
            ChartOfAccountsSeeder::class,

            //last one
            UserSeeder::class,
            AssignPermissionSeeder::class,
        ]);
    }
}
