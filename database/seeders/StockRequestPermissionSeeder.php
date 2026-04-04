<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class StockRequestPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'inventory.stock_request.view',
            'inventory.stock_request.create',
            'inventory.stock_request.update',
            'inventory.stock_request.submit',
            'inventory.stock_request.approve',
            'inventory.stock_request.reject',
            'inventory.stock_request.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::query()->updateOrCreate([
                'name' => $permission,
            ]);
        }
    }
}
