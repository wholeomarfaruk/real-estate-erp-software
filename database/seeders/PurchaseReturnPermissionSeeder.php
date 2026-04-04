<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PurchaseReturnPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'inventory.purchase_return.view',
            'inventory.purchase_return.create',
            'inventory.purchase_return.update',
            'inventory.purchase_return.post',
            'inventory.purchase_return.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::query()->updateOrCreate([
                'name' => $permission,
            ]);
        }
    }
}
