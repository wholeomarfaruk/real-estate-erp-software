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
            UnitTypeSeeder::class,

            ProductSeeder::class,
            SupplierSeeder::class,
            ChartOfAccountsSeeder::class,
            PropertySeeder::class,

            //last one
            UserSeeder::class,
            EmployeeSeeder::class,
            CustomerSeeder::class,
            AssignPermissionSeeder::class,

        ]);
    }
}
