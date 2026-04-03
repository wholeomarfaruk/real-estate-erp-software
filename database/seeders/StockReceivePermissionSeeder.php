<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class StockReceivePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'inventory.stock.receive.view',
            'inventory.stock.receive.create',
            'inventory.stock.receive.update',
            'inventory.stock.receive.post',
            'inventory.stock.receive.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::query()->updateOrCreate([
                'name' => $permission,
            ]);
        }
    }
}
