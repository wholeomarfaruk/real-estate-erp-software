<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class StockTransferPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'inventory.stock.transfer.view',
            'inventory.stock.transfer.create',
            'inventory.stock.transfer.update',
            'inventory.stock.transfer.request',
            'inventory.stock.transfer.approve',
            'inventory.stock.transfer.complete',
            'inventory.stock.transfer.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::query()->updateOrCreate([
                'name' => $permission,
            ]);
        }
    }
}
