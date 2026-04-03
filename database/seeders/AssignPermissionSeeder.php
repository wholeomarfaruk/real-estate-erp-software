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

        $adminPermissions = Permission::query()
            ->whereIn('name', [
                'inventory.dashboard.view',
                'inventory.stock.ledger.view',
                'inventory.stock.report.view',
                'inventory.stock.adjustment.view',
                'inventory.stock.adjustment.create',
                'inventory.stock.adjustment.update',
                'inventory.stock.adjustment.post',
                'inventory.stock.adjustment.delete',
                'inventory.purchase_order.view',
                'inventory.purchase_order.create',
                'inventory.purchase_order.update',
                'inventory.purchase_order.submit',
                'inventory.purchase_order.engineer_approve',
                'inventory.purchase_order.chairman_approve',
                'inventory.purchase_order.accounts_approve',
                'inventory.purchase_order.fund_release',
                'inventory.purchase_order.settle',
                'inventory.purchase_order.complete',
                'inventory.purchase_order.delete',
            ])
            ->get();

        Role::findByName('admin')->givePermissionTo($adminPermissions);

        $accountsPermissions = Permission::query()
            ->whereIn('name', [
                'inventory.dashboard.view',
                'inventory.stock.ledger.view',
                'inventory.stock.report.view',
                'inventory.purchase_order.view',
                'inventory.purchase_order.accounts_approve',
                'inventory.purchase_order.fund_release',
                'inventory.purchase_order.settle',
                'inventory.purchase_order.complete',
            ])
            ->get();

        Role::findByName('accounts')->givePermissionTo($accountsPermissions);

        $storeManagerPermissions = Permission::query()
            ->whereIn('name', [
                'inventory.dashboard.view',
                'inventory.stock.consumption.view',
                'inventory.stock.consumption.create',
                'inventory.stock.consumption.update',
                'inventory.stock.consumption.post',
                'inventory.stock.ledger.view',
                'inventory.purchase_order.view',
                'inventory.purchase_order.create',
                'inventory.purchase_order.update',
                'inventory.purchase_order.submit',
                'inventory.purchase_order.delete',
            ])
            ->get();

        Role::findByName('store manager')->givePermissionTo($storeManagerPermissions);

        $engineerPermissions = Permission::query()
            ->whereIn('name', [
                'inventory.purchase_order.view',
                'inventory.purchase_order.engineer_approve',
            ])
            ->get();

        Role::findByName('engineers')->givePermissionTo($engineerPermissions);

        $chairmanPermissions = Permission::query()
            ->whereIn('name', [
                'inventory.purchase_order.view',
                'inventory.purchase_order.chairman_approve',
            ])
            ->get();

        Role::findByName('chairman')->givePermissionTo($chairmanPermissions);
    }
}
