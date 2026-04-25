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
                'section.general.access',
                'module.accounts.access',
                'accounts.chart.list',
                'accounts.chart.create',
                'accounts.chart.edit',
                'accounts.chart.delete',
                'accounts.transaction.list',
                'accounts.transaction.view',
                'accounts.payment.list',
                'accounts.payment.create',
                'accounts.payment.edit',
                'accounts.payment.delete',
                'accounts.payment.print',
                'accounts.collection.list',
                'accounts.collection.create',
                'accounts.collection.edit',
                'accounts.collection.delete',
                'accounts.collection.print',
                'accounts.expense.list',
                'accounts.expense.create',
                'accounts.expense.edit',
                'accounts.expense.delete',
                'accounts.expense.print',
                'accounts.purchase-payable.list',
                'accounts.purchase-payable.create',
                'accounts.purchase-payable.edit',
                'accounts.purchase-payable.delete',
                'accounts.purchase-payable.settle',
                'accounts.reports.statement.view',
                'accounts.reports.statement.print',
                'accounts.reports.statement.export',
                'accounts.transaction-attachment.view',
                'accounts.transaction-attachment.create',
                'accounts.transaction-attachment.delete',
                'module.hrm.access',
                'hrm.departments.view',
                'hrm.departments.create',
                'hrm.departments.update',
                'hrm.departments.delete',
                'hrm.designations.view',
                'hrm.designations.create',
                'hrm.designations.update',
                'hrm.designations.delete',
                'hrm.employees.view',
                'hrm.employees.create',
                'hrm.employees.update',
                'hrm.employees.delete',
                'hrm.salary-structures.view',
                'hrm.salary-structures.create',
                'hrm.salary-structures.update',
                'hrm.payrolls.view',
                'hrm.payrolls.create',
                'hrm.payrolls.update',
                'hrm.payrolls.pay',
                'hrm.payrolls.print',
                'hrm.employee-advances.view',
                'hrm.employee-advances.create',
                'hrm.employee-advances.update',
                'hrm.payroll-payments.view',
                'hrm.payroll-payments.create',
            ])
            ->get();

        Role::findByName('admin')->syncPermissions($adminPermissions);

        $accountsPermissions = Permission::query()
            ->whereIn('name', [
                'module.suppliers.access',
                'supplier.dashboard.view',
                'supplier.list.view',
                'supplier.create',
                'supplier.edit',
                'supplier.view',
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
                'supplier.reports.supplier-wise',
                'supplier.reports.product-wise',
                'supplier.reports.due',
                'supplier.reports.aging',

                'module.inventory.access',
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
                'section.general.access',
                'module.accounts.access',
                'accounts.chart.list',
                'accounts.chart.create',
                'accounts.chart.edit',
                'accounts.chart.delete',
                'accounts.transaction.list',
                'accounts.transaction.view',
                'accounts.payment.list',
                'accounts.payment.create',
                'accounts.payment.edit',
                'accounts.payment.delete',
                'accounts.payment.print',
                'accounts.collection.list',
                'accounts.collection.create',
                'accounts.collection.edit',
                'accounts.collection.delete',
                'accounts.collection.print',
                'accounts.expense.list',
                'accounts.expense.create',
                'accounts.expense.edit',
                'accounts.expense.delete',
                'accounts.expense.print',
                'accounts.purchase-payable.list',
                'accounts.purchase-payable.create',
                'accounts.purchase-payable.edit',
                'accounts.purchase-payable.delete',
                'accounts.purchase-payable.settle',
                'accounts.reports.statement.view',
                'accounts.reports.statement.print',
                'accounts.reports.statement.export',
                'accounts.transaction-attachment.view',
                'accounts.transaction-attachment.create',
                'accounts.transaction-attachment.delete',
                'module.hrm.access',
                'hrm.departments.view',
                'hrm.departments.create',
                'hrm.departments.update',
                'hrm.departments.delete',
                'hrm.designations.view',
                'hrm.designations.create',
                'hrm.designations.update',
                'hrm.designations.delete',
                'hrm.employees.view',
                'hrm.employees.create',
                'hrm.employees.update',
                'hrm.employees.delete',
                'hrm.salary-structures.view',
                'hrm.salary-structures.create',
                'hrm.salary-structures.update',
                'hrm.payrolls.view',
                'hrm.payrolls.create',
                'hrm.payrolls.update',
                'hrm.payrolls.pay',
                'hrm.payrolls.print',
                'hrm.employee-advances.view',
                'hrm.employee-advances.create',
                'hrm.employee-advances.update',
                'hrm.payroll-payments.view',
                'hrm.payroll-payments.create',
            ])
            ->get();

        Role::findByName('accounts')->syncPermissions($accountsPermissions);

        $storeManagerPermissions = Permission::query()
            ->whereIn('name', [
                'supplier.dashboard.view',
                'supplier.list.view',
                'supplier.view',
                'inventory.dashboard.view',
                'inventory.stock.receive.view',
                'inventory.stock.receive.create',
                'inventory.stock.receive.update',
                'inventory.stock.receive.post',
                'inventory.stock.receive.delete',
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
                //transfer
                'inventory.stock.transfer.view',
                'inventory.stock.transfer.create',
                'inventory.stock.transfer.update',
                'inventory.stock.transfer.request',
                'inventory.stock.transfer.approve',
                'inventory.stock.transfer.complete',
                'inventory.stock.transfer.delete',
                //adjustment
                'inventory.stock.adjustment.view',
                'inventory.stock.adjustment.create',
                'inventory.stock.adjustment.update',
                'inventory.stock.adjustment.post',
                'inventory.stock.adjustment.delete',
                //purchase order
                'inventory.purchase_order.view',
                'inventory.purchase_order.create',
                'inventory.purchase_order.update',
                'inventory.purchase_order.edit',
                'inventory.purchase_order.submit',
                'inventory.purchase_order.engineer_approve',
                'inventory.purchase_order.chairman_approve',
                'inventory.purchase_order.accounts_approve',
                'inventory.purchase_order.fund_release',
                'inventory.purchase_order.settle',
                'inventory.purchase_order.complete',
                'inventory.purchase_order.delete',

                //section and modules
                'section.general.access',
                'module.materials.access',
                'module.suppliers.access',
                'module.inventory.access',
            ])
            ->get();

        Role::findByName('storemanager')->syncPermissions($storeManagerPermissions);

        $engineerPermissions = Permission::query()
            ->whereIn('name', [
                'section.general.access',
                'module.inventory.access',
                'module.suppliers.access',
                'module.materials.access',
                'inventory.purchase_order.view',
                'inventory.purchase_order.engineer_approve',
                'inventory.stock_request.view',
                'inventory.stock_request.approve',
                'inventory.stock_request.reject',

            ])
            ->get();

        Role::findByName('chiefengineer')->syncPermissions($engineerPermissions);

        $chairmanPermissions = Permission::query()
            ->whereIn('name', [
                'section.general.access',
                'module.projects.access',
                'module.inventory.access',
                'inventory.purchase_order.view',
                'inventory.purchase_order.chairman_approve',
                'inventory.stock.report.view',
                'inventory.stock.ledger.view',
                'module.materials.access',
                'module.suppliers.access',
                'module.hrm.access',
                'hrm.payrolls.view',
                'module.accounts.access',
                'inventory.dashboard.view',
            ])
            ->get();

        Role::findByName('chairman')->syncPermissions($chairmanPermissions);


        $siteEngineerPermissions = Permission::query()
            ->whereIn('name', [
                'inventory.stock_request.view',
                'inventory.stock_request.create',
                'inventory.stock_request.update',
                'inventory.stock_request.submit',

            ])
            ->get();

        $engineerPermissions = Permission::query()
            ->whereIn('name', [
                'section.general.access',
                'module.inventory.access',
                'module.suppliers.access',
                'module.materials.access',
                'module.projects.access',
                'inventory.purchase_order.view',
                'inventory.purchase_order.engineer_approve',
                'inventory.stock_request.view',
                'inventory.stock_request.approve',
                'inventory.stock_request.reject',
                'inventory.purchase_return.view',

            ])
            ->get();
        Role::findByName('engineer')->syncPermissions(
            $siteEngineerPermissions->merge($engineerPermissions)->unique('id')->values()
        );


        $adminPanelId = Panel::where('slug', 'admin')->value('id');

        $this->assignRoleAndPanel('superadmin@gmail.com', 'superadmin', $adminPanelId);
        $this->assignRoleAndPanel('admin@gmail.com', 'admin', $adminPanelId);
        $this->assignRoleAndPanel('storemanager@gmail.com', 'storemanager', $adminPanelId);
        $this->assignRoleAndPanel('chiefengineer@gmail.com', 'chiefengineer', $adminPanelId);
        $this->assignRoleAndPanel('chairman@gmail.com', 'chairman', $adminPanelId);
        $this->assignRoleAndPanel('accountant@gmail.com', 'accounts', $adminPanelId);

    }

    protected function assignRoleAndPanel(string $email, string $role, ?int $panelId): void
    {
        $user = User::where('email', $email)->first();
        if (! $user) {
            return;
        }

        $user->assignRole($role);

        if ($panelId) {
            $user->panels()->syncWithoutDetaching([$panelId]);
        }
    }
}
