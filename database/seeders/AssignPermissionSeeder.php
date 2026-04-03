<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AssignPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::findByName('superadmin')->syncPermissions(Permission::all());

        $storeManagerPermissions = Permission::query()
            ->whereIn('name', [
                'inventory.stock.consumption.view',
                'inventory.stock.consumption.create',
                'inventory.stock.consumption.update',
                'inventory.stock.consumption.post',
                'inventory.stock.ledger.view',
            ])
            ->get();

        Role::findByName('store manager')->syncPermissions($storeManagerPermissions);
    }
}
