<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'superadmin',
            'admin',
            'employee',
            'accounts',
            'store manager',
            'engineers',
            'chairman',
            'md',
            'internal members',
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['name' => $role]);
        }

        $permissions = [
            ['id' => 1, 'name' => 'user.show'],
            ['id' => 2, 'name' => 'user.view'],
            ['id' => 3, 'name' => 'user.create'],
            ['id' => 4, 'name' => 'user.edit'],
            ['id' => 5, 'name' => 'user.delete'],
            ['id' => 6, 'name' => 'user.role_assign'],
            ['id' => 7, 'name' => 'user.role_remove'],

            ['id' => 8, 'name' => 'permission.show'],
            ['id' => 9, 'name' => 'permission.view'],
            ['id' => 10, 'name' => 'permission.create'],
            ['id' => 11, 'name' => 'permission.edit'],
            ['id' => 12, 'name' => 'permission.delete'],

            ['id' => 13, 'name' => 'role.view'],
            ['id' => 14, 'name' => 'role.create'],
            ['id' => 15, 'name' => 'role.edit'],
            ['id' => 16, 'name' => 'role.delete'],

            ['id' => 17, 'name' => 'panel.show'],
            ['id' => 18, 'name' => 'panel.view'],

            ['id' => 19, 'name' => 'dashboard.readonly'],
            ['id' => 20, 'name' => 'dashboard.view'],

            ['id' => 21, 'name' => 'ui.show'],
            ['id' => 22, 'name' => 'ui_components.view'],

            ['id' => 23, 'name' => 'project.view'],
            ['id' => 24, 'name' => 'project.create'],
            ['id' => 25, 'name' => 'project.edit'],
            ['id' => 26, 'name' => 'project.delete'],

            ['id' => 27, 'name' => 'inventory.store.view'],
            ['id' => 28, 'name' => 'inventory.store.create'],
            ['id' => 29, 'name' => 'inventory.store.update'],
            ['id' => 30, 'name' => 'inventory.store.delete'],

            ['id' => 31, 'name' => 'inventory.dashboard.view'],

            ['id' => 32, 'name' => 'inventory.product.view'],
            ['id' => 33, 'name' => 'inventory.product.create'],
            ['id' => 34, 'name' => 'inventory.product.update'],
            ['id' => 35, 'name' => 'inventory.product.delete'],

            ['id' => 36, 'name' => 'inventory.supplier.view'],
            ['id' => 37, 'name' => 'inventory.supplier.create'],
            ['id' => 38, 'name' => 'inventory.supplier.update'],
            ['id' => 39, 'name' => 'inventory.supplier.delete'],

            ['id' => 40, 'name' => 'inventory.stock.receive.view'],
            ['id' => 41, 'name' => 'inventory.stock.receive.create'],
            ['id' => 42, 'name' => 'inventory.stock.receive.update'],
            ['id' => 43, 'name' => 'inventory.stock.receive.post'],
            ['id' => 44, 'name' => 'inventory.stock.receive.delete'],

            ['id' => 45, 'name' => 'inventory.stock.transfer.view'],
            ['id' => 46, 'name' => 'inventory.stock.transfer.create'],
            ['id' => 47, 'name' => 'inventory.stock.transfer.update'],
            ['id' => 48, 'name' => 'inventory.stock.transfer.request'],
            ['id' => 49, 'name' => 'inventory.stock.transfer.approve'],
            ['id' => 50, 'name' => 'inventory.stock.transfer.complete'],
            ['id' => 51, 'name' => 'inventory.stock.transfer.delete'],

            ['id' => 52, 'name' => 'inventory.stock.consumption.view'],
            ['id' => 53, 'name' => 'inventory.stock.consumption.create'],
            ['id' => 54, 'name' => 'inventory.stock.consumption.update'],
            ['id' => 55, 'name' => 'inventory.stock.consumption.post'],
            ['id' => 56, 'name' => 'inventory.stock.consumption.delete'],

            ['id' => 57, 'name' => 'inventory.stock.ledger.view'],
            ['id' => 58, 'name' => 'inventory.stock.report.view'],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['id' => $permission['id'] ?? null],
                ['name' => $permission['name'] ?? null]
            );
        }
    }
}
