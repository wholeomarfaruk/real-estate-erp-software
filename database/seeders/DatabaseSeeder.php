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
            StockReceivePermissionSeeder::class,
            PurchaseReturnPermissionSeeder::class,
            StockRequestPermissionSeeder::class,
            StockTransferPermissionSeeder::class,
            SupplierPermissionSeeder::class,
            PropertyPermissionSeeder::class,

            //last one
            UserSeeder::class,
             AssignPermissionSeeder::class,
        ]);
    }
}
