<?php

namespace Database\Seeders;

use App\Models\Panel;
use App\Models\User;
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
                'supplier.dashboard.view',
                'supplier.list.view',
                'supplier.create',
                'supplier.edit',
                'supplier.view',
                'supplier.status.change',
                'supplier.delete',
                'supplier.bill.list',
                'supplier.bill.create',
                'supplier.bill.edit',
                'supplier.bill.view',
                'supplier.bill.cancel',
                'supplier.bill.pending.view',
                'supplier.payment.list',
                'supplier.payment.create',
                'supplier.payment.edit',
                'supplier.payment.view',
                'supplier.payment.cancel',
                'supplier.payment.allocate',
                'supplier.return.list',
                'supplier.return.create',
                'supplier.return.edit',
                'supplier.return.view',
                'supplier.return.approve',
                'supplier.return.cancel',
                'supplier.ledger.view',
                'supplier.statement.view',
                'supplier.statement.print',
                'supplier.reports.supplier-wise',
                'supplier.reports.product-wise',
                'supplier.reports.due',
                'supplier.reports.aging',
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
                'inventory.purchase_return.view',
                'inventory.purchase_return.create',
                'inventory.purchase_return.update',
                'inventory.purchase_return.post',
                'inventory.purchase_return.delete',
                'inventory.stock_request.view',
                'inventory.stock_request.create',
                'inventory.stock_request.update',
                'inventory.stock_request.submit',
                'inventory.stock_request.approve',
                'inventory.stock_request.reject',
                'inventory.stock_request.delete',
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
                'inventory.purchase_return.view',
                'inventory.purchase_return.post',
                'inventory.stock_request.view',
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
                'inventory.purchase_return.view',
                'inventory.purchase_return.create',
                'inventory.purchase_return.update',
                'inventory.purchase_return.post',
                'inventory.stock_request.view',
                'inventory.stock_request.create',
                'inventory.stock_request.update',
                'inventory.stock_request.submit',
            ])
            ->get();

        Role::findByName('storemanager')->givePermissionTo($storeManagerPermissions);

        $engineerPermissions = Permission::query()
            ->whereIn('name', [
                'inventory.purchase_order.view',
                'inventory.purchase_order.engineer_approve',
                'inventory.stock_request.view',
                'inventory.stock_request.approve',
                'inventory.stock_request.reject',
            ])
            ->get();

        Role::findByName('chiefengineer')->givePermissionTo($engineerPermissions);

        $chairmanPermissions = Permission::query()
            ->whereIn('name', [
                'inventory.purchase_order.view',
                'inventory.purchase_order.chairman_approve',
            ])
            ->get();

        Role::findByName('chairman')->givePermissionTo($chairmanPermissions);


        $siteEngineerPermissions = Permission::query()
            ->whereIn('name', [
                'inventory.stock_request.view',
                'inventory.stock_request.create',
                'inventory.stock_request.update',
                'inventory.stock_request.submit',
            ])
            ->get();

        Role::findByName('engineer')->givePermissionTo($siteEngineerPermissions);

        $superadminAssign = User::where('email', 'superadmin@gmail.com')->first();
        $superadminAssign->assignRole('superadmin');
        $superadminAssign->panels()->attach(Panel::where('slug', 'admin')->first()->id);

        $adminAssign = User::where('email', 'admin@gmail.com')->first();
        $adminAssign->assignRole('admin');
        $adminAssign->panels()->attach(Panel::where('slug', 'admin')->first()->id);

        $storeManagerAssign = User::where('email', 'storemanager@gmail.com')->first();
        $storeManagerAssign->assignRole('storemanager');
        $storeManagerAssign->panels()->attach(Panel::where('slug', 'admin')->first()->id);

    }
}
