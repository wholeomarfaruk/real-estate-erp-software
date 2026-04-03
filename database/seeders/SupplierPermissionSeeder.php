<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class SupplierPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'inventory.supplier.view',
            'inventory.supplier.create',
            'inventory.supplier.update',
            'inventory.supplier.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::query()->updateOrCreate([
                'name' => $permission,
            ]);
        }
    }
}
