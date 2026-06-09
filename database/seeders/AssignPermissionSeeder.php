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
                'supplier.list.view',
                'supplier.create',
                'supplier.edit',
                'supplier.view',
                'supplier.status.change',
                'supplier.delete',
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
                'inventory.purchase_invoice.view',
                'inventory.purchase_invoice.approve',
                'inventory.purchase_invoice.cancel',
                'inventory.purchase_invoice.delete',
                'accounts.advance.refund',
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
                'accounts.report.view',
                'inventory.report.view',
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
                'customer.view',
                'customer.create',
                'customer.edit',
                'customer.delete',
                'property_sale.view',
                'property_sale.create',
                'property_sale.edit',
                'property_sale.delete',
                // CRM
                'module.crm.access',
                'crm.lead.view',
                'crm.lead.create',
                'crm.lead.edit',
                'crm.lead.delete',
                'crm.lead.convert',
                'crm.lead_source.view',
                'crm.lead_source.create',
                'crm.lead_source.edit',
                'crm.lead_source.delete',
                'crm.task.view',
                'crm.task.create',
                'crm.task.edit',
                'crm.task.delete',
                // Marketing – full access for admin
                'marketing.template.view',
                'marketing.template.create',
                'marketing.template.edit',
                'marketing.template.delete',
                'marketing.audience.view',
                'marketing.audience.create',
                'marketing.audience.edit',
                'marketing.audience.delete',
                'marketing.campaign.view',
                'marketing.campaign.create',
                'marketing.campaign.edit',
                'marketing.campaign.delete',
                'marketing.campaign.send',
                'marketing.message.view',
                'marketing.message.send',
                'marketing.automation.view',
                'marketing.automation.create',
                'marketing.automation.edit',
                'marketing.automation.delete',
                'settings.sms_gateway.view',
                'settings.sms_gateway.create',
                'settings.sms_gateway.edit',
                'settings.sms_gateway.delete',
            ])
            ->get();

        Role::findByName('admin')->syncPermissions($adminPermissions);

        $accountsPermissions = Permission::query()
            ->whereIn('name', [
                'module.suppliers.access',
                'supplier.list.view',
                'supplier.create',
                'supplier.edit',
                'supplier.view',
                'supplier.delete',

                'module.inventory.access',
                'inventory.dashboard.view',
                'inventory.stock.ledger.view',
                'inventory.stock.report.view',
                'inventory.purchase_order.view',
                'inventory.purchase_order.accounts_approve',
                'inventory.purchase_order.chiefengineer_update',
                'inventory.purchase_order.fund_release',
                'inventory.purchase_order.settle',
                'inventory.purchase_order.complete',
                'inventory.purchase_return.view',
                'inventory.purchase_return.post',
                'inventory.purchase_invoice.view',
                'inventory.purchase_invoice.approve',
                'inventory.purchase_invoice.cancel',
                'accounts.advance.refund',
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
                'accounts.report.view',
                'inventory.report.view',
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

                // Properties
                'property.view',
                'property.create',
                'property.edit',
                'property.delete',
                'property.floor.view',
                'property.floor.create',
                'property.floor.edit',
                'property.floor.delete',
                'property.unit.view',
                'property.unit.create',
                'property.unit.edit',
                'property.unit.delete',
                'property_sale.view',
                'property_sale.create',
                'property_sale.edit',
                'property_sale.delete',
                // CRM — accounts view only
                'module.crm.access',
                'crm.lead.view',
                'crm.lead_source.view',
                'crm.task.view',
                // Marketing — view only
                'marketing.template.view',
                'marketing.audience.view',
                'marketing.campaign.view',
                'marketing.message.view',
                'marketing.automation.view',
            ])
            ->get();

        Role::findByName('accounts')->syncPermissions($accountsPermissions);

        $storeManagerPermissions = Permission::query()
            ->whereIn('name', [
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
                // transfer
                'inventory.stock.transfer.view',
                'inventory.stock.transfer.create',
                'inventory.stock.transfer.update',
                'inventory.stock.transfer.request',
                'inventory.stock.transfer.approve',
                'inventory.stock.transfer.complete',
                'inventory.stock.transfer.delete',

                // adjustment
                'inventory.stock.adjustment.view',
                'inventory.stock.adjustment.create',
                'inventory.stock.adjustment.update',
                'inventory.stock.adjustment.post',
                'inventory.stock.adjustment.delete',

                // purchase order
                'inventory.purchase_order.view',
                'inventory.purchase_order.create',
                'inventory.purchase_order.update',
                'inventory.purchase_order.edit',
                'inventory.purchase_order.submit',
                'inventory.report.view',
                'inventory.purchase_order.delete',

                // section and modules
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
                'inventory.purchase_order.chiefengineer_update',
                'inventory.stock_request.view',
                'inventory.stock_request.approve',
                'inventory.stock_request.reject',
                'inventory.stock_request.create',
                'inventory.stock_request.update',
                'inventory.stock_request.submit',
                'inventory.stock_request.delete',
                'inventory.stock_request.make_pending',

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
                'inventory.purchase_order.chairman_update',
                'inventory.stock.report.view',
                'inventory.stock.ledger.view',
                'inventory.report.view',
                'module.materials.access',
                'module.suppliers.access',
                'module.hrm.access',
                'hrm.payrolls.view',
                'module.accounts.access',
                'inventory.dashboard.view',
                // CRM — chairman view only
                'module.crm.access',
                'crm.lead.view',
                'crm.lead_source.view',
                'crm.task.view',
                // Marketing — view only
                'marketing.template.view',
                'marketing.audience.view',
                'marketing.campaign.view',
                'marketing.message.view',
                'marketing.automation.view',
            ])
            ->get();

        Role::findByName('chairman')->syncPermissions($chairmanPermissions);


        $siteengineerPermissions = Permission::query()
            ->whereIn('name', [
                'module.dashboard.access',
                'module.inventory.access',
                'module.projects.access',
                'dashboard.view',
                // 'inventory.stock_request.view',
                // 'inventory.stock_request.create',
                // 'inventory.stock_request.update',
                // 'inventory.stock_request.submit',
                // 'inventory.stock_request.delete',
                'section.general.access',

                'inventory.site_engineer.stock_request.create',
                'inventory.site_engineer.stock_request.update',
                'inventory.site_engineer.stock_request.view',
                'inventory.site_engineer.stock_request.delete',
                'inventory.site_engineer.stock_request.submit',
            ])
            ->get();

        Role::findByName('engineer')->syncPermissions($siteengineerPermissions);

        $employeePermissions = Permission::query()
            ->whereIn('name', [
                'section.general.access',
                'module.dashboard.access',
                'dashboard.view',
                'dashboard.readonly',
            ])
            ->get();

        Role::findByName('employee')->syncPermissions($employeePermissions);

        $supplierPermissions = Permission::query()
            ->whereIn('name', [
                'module.suppliers.access',
                'supplier.list.view',
                'section.general.access',
            ])
            ->get();

        Role::findByName('supplier')->syncPermissions($supplierPermissions);

        $salesMarketingPermissions = Permission::query()
            ->whereIn('name', [
                'section.general.access',
                'module.dashboard.access',
                'dashboard.view',
                'customer.view',
                'customer.create',
                'customer.edit',
                'customer.delete',
                'property_sale.view',
                'property_sale.create',
                'property_sale.edit',
                'property_sale.delete',
                'property.view',
                'property.floor.view',
                'property.unit.view',
                // CRM — sales team gets full lead & task access
                'module.crm.access',
                'crm.lead.view',
                'crm.lead.create',
                'crm.lead.edit',
                'crm.lead.delete',
                'crm.lead.convert',
                'crm.lead_source.view',
                'crm.lead_source.create',
                'crm.lead_source.edit',
                'crm.task.view',
                'crm.task.create',
                'crm.task.edit',
                // Marketing — sales team: full access
                'marketing.template.view',
                'marketing.template.create',
                'marketing.template.edit',
                'marketing.template.delete',
                'marketing.audience.view',
                'marketing.audience.create',
                'marketing.audience.edit',
                'marketing.audience.delete',
                'marketing.campaign.view',
                'marketing.campaign.create',
                'marketing.campaign.edit',
                'marketing.campaign.delete',
                'marketing.campaign.send',
                'marketing.message.view',
                'marketing.message.send',
                'marketing.automation.view',
                'marketing.automation.create',
                'marketing.automation.edit',
                'marketing.automation.delete',
                'settings.sms_gateway.view',
            ])
            ->get();

        Role::findByName('salesmarketing')->syncPermissions($salesMarketingPermissions);

        $mdPermissions = Permission::query()
            ->whereIn('name', [
                'section.general.access',
                'module.projects.access',
                'module.inventory.access',
                'module.accounts.access',
                'module.hrm.access',
                'module.suppliers.access',
                'module.materials.access',
                'module.dashboard.access',
                'dashboard.view',
                'inventory.dashboard.view',
                'inventory.purchase_order.view',
                'inventory.purchase_order.fund_release',
                'inventory.stock.report.view',
                'inventory.stock.ledger.view',
                'inventory.report.view',
                'accounts.chart.list',
                'accounts.transaction.list',
                'accounts.transaction.view',
                'accounts.payment.list',
                'accounts.collection.list',
                'accounts.expense.list',
                'accounts.purchase-payable.list',
                'accounts.reports.statement.view',
                'accounts.reports.statement.print',
                'accounts.reports.statement.export',
                'accounts.report.view',
                'supplier.list.view',
                'hrm.departments.view',
                'hrm.designations.view',
                'hrm.employees.view',
                'hrm.salary-structures.view',
                'hrm.payrolls.view',
                'hrm.payrolls.create',
                'hrm.payrolls.update',
                'hrm.payrolls.pay',
                'hrm.payrolls.print',
                'hrm.employee-advances.view',
                'hrm.payroll-payments.view',
                'customer.view',
                'property_sale.view',
                // CRM — MD gets read-only overview
                'module.crm.access',
                'crm.lead.view',
                'crm.lead_source.view',
                'crm.task.view',
                // Marketing — MD: view-only
                'marketing.template.view',
                'marketing.audience.view',
                'marketing.campaign.view',
                'marketing.message.view',
                'marketing.automation.view',
            ])
            ->get();

        Role::findByName('md')->syncPermissions($mdPermissions);

        $adminPanelId = Panel::where('slug', 'admin')->value('id');

        $this->assignRoleAndPanel('superadmin@gmail.com', 'superadmin', $adminPanelId);
        $this->assignRoleAndPanel('admin@gmail.com', 'admin', $adminPanelId);
        $this->assignRoleAndPanel('storemanager@gmail.com', 'storemanager', $adminPanelId);
        $this->assignRoleAndPanel('chiefengineer@gmail.com', 'chiefengineer', $adminPanelId);
        $this->assignRoleAndPanel('chairman@gmail.com', 'chairman', $adminPanelId);
        $this->assignRoleAndPanel('accountant@gmail.com', 'accounts', $adminPanelId);
        $this->assignRoleAndPanel('engineer@gmail.com', 'engineer', $adminPanelId);
        $this->assignRoleAndPanel('md@gmail.com', 'md', $adminPanelId);
        $this->assignRoleAndPanel('employee@gmail.com', 'employee', $adminPanelId);
        $this->assignRoleAndPanel('supplier@gmail.com', 'supplier', $adminPanelId);
        $this->assignRoleAndPanel('salesmarketing@gmail.com', 'salesmarketing', $adminPanelId);

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
