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
            'storemanager',
            'chiefengineer',
            'engineer',
            'chairman',
            'md',
            'supplier',
            'salesmarketing',
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

            ['id' => 59, 'name' => 'inventory.stock.adjustment.view'],
            ['id' => 60, 'name' => 'inventory.stock.adjustment.create'],
            ['id' => 61, 'name' => 'inventory.stock.adjustment.update'],
            ['id' => 62, 'name' => 'inventory.stock.adjustment.post'],
            ['id' => 63, 'name' => 'inventory.stock.adjustment.delete'],

            ['id' => 64, 'name' => 'inventory.purchase_order.view'],
            ['id' => 65, 'name' => 'inventory.purchase_order.create'],
            ['id' => 66, 'name' => 'inventory.purchase_order.update'],
            ['id' => 67, 'name' => 'inventory.purchase_order.submit'],
            ['id' => 68, 'name' => 'inventory.purchase_order.engineer_approve'],
            ['id' => 69, 'name' => 'inventory.purchase_order.chairman_approve'],
            ['id' => 70, 'name' => 'inventory.purchase_order.accounts_approve'],
            ['id' => 71, 'name' => 'inventory.purchase_order.fund_release'],
            ['id' => 72, 'name' => 'inventory.purchase_order.settle'],
            ['id' => 73, 'name' => 'inventory.purchase_order.complete'],
            ['id' => 74, 'name' => 'inventory.purchase_order.delete'],

            ['id' => 75, 'name' => 'inventory.purchase_return.view'],
            ['id' => 76, 'name' => 'inventory.purchase_return.create'],
            ['id' => 77, 'name' => 'inventory.purchase_return.update'],
            ['id' => 78, 'name' => 'inventory.purchase_return.post'],
            ['id' => 79, 'name' => 'inventory.purchase_return.delete'],

            ['id' => 80, 'name' => 'inventory.stock_request.view'],
            ['id' => 81, 'name' => 'inventory.stock_request.create'],
            ['id' => 82, 'name' => 'inventory.stock_request.update'],
            ['id' => 83, 'name' => 'inventory.stock_request.submit'],
            ['id' => 84, 'name' => 'inventory.stock_request.approve'],
            ['id' => 85, 'name' => 'inventory.stock_request.reject'],
            ['id' => 86, 'name' => 'inventory.stock_request.delete'],

            // Module Access Permissions
            ['id' => 87, 'name' => 'module.dashboard.access'],
            ['id' => 88, 'name' => 'module.uploads.access'],
            ['id' => 89, 'name' => 'module.materials.access'],
            ['id' => 90, 'name' => 'module.projects.access'],
            ['id' => 91, 'name' => 'module.suppliers.access'],
            ['id' => 92, 'name' => 'module.users.access'],
            ['id' => 93, 'name' => 'module.permissions.access'],
            ['id' => 94, 'name' => 'module.ui_components.access'],
            ['id' => 95, 'name' => 'module.inventory.access'],

            // Section Access Permissions
            ['id' => 96, 'name' => 'section.quick_actions.access'],
            ['id' => 97, 'name' => 'section.general.access'],
            ['id' => 98, 'name' => 'section.settings.access'],
            ['id' => 99, 'name' => 'section.ui_components.access'],

            // Accounts Module
            ['id' => 100, 'name' => 'module.accounts.access'],
            ['id' => 101, 'name' => 'accounts.chart.list'],
            ['id' => 102, 'name' => 'accounts.chart.create'],
            ['id' => 103, 'name' => 'accounts.chart.edit'],
            ['id' => 104, 'name' => 'accounts.chart.delete'],
            ['id' => 105, 'name' => 'accounts.transaction.list'],
            ['id' => 106, 'name' => 'accounts.transaction.view'],
            ['id' => 107, 'name' => 'accounts.payment.list'],
            ['id' => 108, 'name' => 'accounts.payment.create'],
            ['id' => 109, 'name' => 'accounts.payment.edit'],
            ['id' => 110, 'name' => 'accounts.payment.delete'],
            ['id' => 111, 'name' => 'accounts.collection.list'],
            ['id' => 112, 'name' => 'accounts.collection.create'],
            ['id' => 113, 'name' => 'accounts.collection.edit'],
            ['id' => 114, 'name' => 'accounts.collection.delete'],
            ['id' => 115, 'name' => 'accounts.expense.list'],
            ['id' => 116, 'name' => 'accounts.expense.create'],
            ['id' => 117, 'name' => 'accounts.expense.edit'],
            ['id' => 118, 'name' => 'accounts.expense.delete'],
            ['id' => 119, 'name' => 'accounts.purchase-payable.list'],
            ['id' => 120, 'name' => 'accounts.purchase-payable.create'],
            ['id' => 121, 'name' => 'accounts.purchase-payable.edit'],
            ['id' => 122, 'name' => 'accounts.purchase-payable.delete'],
            ['id' => 123, 'name' => 'accounts.purchase-payable.settle'],
            ['id' => 124, 'name' => 'accounts.payment.print'],
            ['id' => 125, 'name' => 'accounts.collection.print'],
            ['id' => 126, 'name' => 'accounts.expense.print'],
            ['id' => 127, 'name' => 'accounts.transaction-attachment.view'],
            ['id' => 128, 'name' => 'accounts.transaction-attachment.create'],
            ['id' => 129, 'name' => 'accounts.transaction-attachment.delete'],
            ['id' => 283, 'name' => 'accounts.settings.manage'],
            ['id' => 130, 'name' => 'module.hrm.access'],
            ['id' => 131, 'name' => 'hrm.departments.view'],
            ['id' => 132, 'name' => 'hrm.departments.create'],
            ['id' => 133, 'name' => 'hrm.departments.update'],
            ['id' => 134, 'name' => 'hrm.departments.delete'],
            ['id' => 135, 'name' => 'hrm.designations.view'],
            ['id' => 136, 'name' => 'hrm.designations.create'],
            ['id' => 137, 'name' => 'hrm.designations.update'],
            ['id' => 138, 'name' => 'hrm.designations.delete'],
            ['id' => 139, 'name' => 'hrm.employees.view'],
            ['id' => 140, 'name' => 'hrm.employees.create'],
            ['id' => 141, 'name' => 'hrm.employees.update'],
            ['id' => 142, 'name' => 'hrm.employees.delete'],
            ['id' => 143, 'name' => 'hrm.salary-structures.view'],
            ['id' => 144, 'name' => 'hrm.salary-structures.create'],
            ['id' => 145, 'name' => 'hrm.salary-structures.update'],
            ['id' => 146, 'name' => 'hrm.payrolls.view'],
            ['id' => 147, 'name' => 'hrm.payrolls.create'],
            ['id' => 148, 'name' => 'hrm.payrolls.update'],
            ['id' => 149, 'name' => 'hrm.payrolls.pay'],
            ['id' => 150, 'name' => 'hrm.payrolls.print'],
            ['id' => 151, 'name' => 'hrm.employee-advances.view'],
            ['id' => 152, 'name' => 'hrm.employee-advances.create'],
            ['id' => 153, 'name' => 'hrm.employee-advances.update'],
            ['id' => 154, 'name' => 'hrm.payroll-payments.view'],
            ['id' => 155, 'name' => 'hrm.payroll-payments.create'],

            // Inventory Permissions
            ['id' => 156, 'name' => 'inventory.purchase_order.edit'],
            // property
            ['id' => 157, 'name' => 'property.view'],
            ['id' => 158, 'name' => 'property.create'],
            ['id' => 159, 'name' => 'property.edit'],
            ['id' => 160, 'name' => 'property.delete'],

            ['id' => 161, 'name' => 'property.floor.view'],
            ['id' => 162, 'name' => 'property.floor.create'],
            ['id' => 163, 'name' => 'property.floor.edit'],
            ['id' => 164, 'name' => 'property.floor.delete'],

            ['id' => 165, 'name' => 'property.unit.view'],
            ['id' => 166, 'name' => 'property.unit.create'],
            ['id' => 167, 'name' => 'property.unit.edit'],
            ['id' => 168, 'name' => 'property.unit.delete'],

            // supplier
            ['id' => 169, 'name' => 'supplier.view'],
            ['id' => 170, 'name' => 'supplier.create'],
            ['id' => 171, 'name' => 'supplier.edit'],
            ['id' => 172, 'name' => 'supplier.delete'],
            ['id' => 173, 'name' => 'supplier.list.view'],
            ['id' => 175, 'name' => 'supplier.status.change'],
            ['id' => 201, 'name' => 'accounts.reports.statement.view'],
            ['id' => 202, 'name' => 'accounts.reports.statement.print'],
            ['id' => 203, 'name' => 'accounts.reports.statement.export'],
            ['id' => 204, 'name' => 'accounts.report.view'],
            ['id' => 205, 'name' => 'inventory.report.view'],
            ['id' => 206, 'name' => 'inventory.site_engineer.stock_request.received'],
            ['id' => 207, 'name' => 'inventory.site_engineer.stock_request.reject'],
            ['id' => 208, 'name' => 'inventory.site_engineer.stock_request.create'],
            ['id' => 209, 'name' => 'inventory.site_engineer.stock_request.update'],
            ['id' => 210, 'name' => 'inventory.site_engineer.stock_request.view'],
            ['id' => 211, 'name' => 'inventory.site_engineer.stock_request.delete'],
            ['id' => 212, 'name' => 'inventory.site_engineer.stock_request.submit'],
            ['id' => 213, 'name' => 'inventory.purchase_order.chiefengineer_update'],
            ['id' => 214, 'name' => 'inventory.purchase_order.chairman_update'],
            ['id' => 215, 'name' => 'inventory.purchase_order.accounts_update'],
            ['id' => 216, 'name' => 'inventory.stock_request.make_pending'],

            // Customer (CRM)
            ['id' => 217, 'name' => 'customer.view'],
            ['id' => 218, 'name' => 'customer.create'],
            ['id' => 219, 'name' => 'customer.edit'],
            ['id' => 220, 'name' => 'customer.delete'],

            // Property Sales
            ['id' => 221, 'name' => 'property_sale.view'],
            ['id' => 222, 'name' => 'property_sale.create'],
            ['id' => 223, 'name' => 'property_sale.edit'],
            ['id' => 224, 'name' => 'property_sale.delete'],

            // Purchase Invoice
            ['id' => 225, 'name' => 'inventory.purchase_invoice.view'],
            ['id' => 226, 'name' => 'inventory.purchase_invoice.approve'],
            ['id' => 227, 'name' => 'inventory.purchase_invoice.cancel'],
            ['id' => 228, 'name' => 'inventory.purchase_invoice.delete'],

            // Advance Refund
            ['id' => 229, 'name' => 'accounts.advance.refund'],

            // ── CRM Module ───────────────────────────────────────────────────
            // Leads
            ['id' => 230, 'name' => 'crm.lead.view'],
            ['id' => 231, 'name' => 'crm.lead.create'],
            ['id' => 232, 'name' => 'crm.lead.edit'],
            ['id' => 233, 'name' => 'crm.lead.delete'],
            ['id' => 234, 'name' => 'crm.lead.convert'],   // convert lead → customer

            // Lead Sources
            ['id' => 235, 'name' => 'crm.lead_source.view'],
            ['id' => 236, 'name' => 'crm.lead_source.create'],
            ['id' => 237, 'name' => 'crm.lead_source.edit'],
            ['id' => 238, 'name' => 'crm.lead_source.delete'],

            // CRM Tasks
            ['id' => 239, 'name' => 'crm.task.view'],
            ['id' => 240, 'name' => 'crm.task.create'],
            ['id' => 241, 'name' => 'crm.task.edit'],
            ['id' => 242, 'name' => 'crm.task.delete'],

            // Module-level access gate
            ['id' => 243, 'name' => 'module.crm.access'],

            // Marketing – Templates
            ['id' => 244, 'name' => 'marketing.template.view'],
            ['id' => 245, 'name' => 'marketing.template.create'],
            ['id' => 246, 'name' => 'marketing.template.edit'],
            ['id' => 247, 'name' => 'marketing.template.delete'],

            // Marketing – Audiences
            ['id' => 248, 'name' => 'marketing.audience.view'],
            ['id' => 249, 'name' => 'marketing.audience.create'],
            ['id' => 250, 'name' => 'marketing.audience.edit'],
            ['id' => 251, 'name' => 'marketing.audience.delete'],

            // Marketing – Campaigns
            ['id' => 252, 'name' => 'marketing.campaign.view'],
            ['id' => 253, 'name' => 'marketing.campaign.create'],
            ['id' => 254, 'name' => 'marketing.campaign.edit'],
            ['id' => 255, 'name' => 'marketing.campaign.delete'],
            ['id' => 256, 'name' => 'marketing.campaign.send'],

            // Marketing – Messages
            ['id' => 257, 'name' => 'marketing.message.view'],
            ['id' => 258, 'name' => 'marketing.message.send'],

            // Marketing – Automations
            ['id' => 259, 'name' => 'marketing.automation.view'],
            ['id' => 260, 'name' => 'marketing.automation.create'],
            ['id' => 261, 'name' => 'marketing.automation.edit'],
            ['id' => 262, 'name' => 'marketing.automation.delete'],

            // Settings – SMS Gateway
            ['id' => 263, 'name' => 'settings.sms_gateway.view'],
            ['id' => 264, 'name' => 'settings.sms_gateway.create'],
            ['id' => 265, 'name' => 'settings.sms_gateway.edit'],
            ['id' => 266, 'name' => 'settings.sms_gateway.delete'],

            // Reports Module
            ['id' => 267, 'name' => 'module.reports.access'],
            ['id' => 268, 'name' => 'reports.hub.view'],
            ['id' => 269, 'name' => 'reports.category.view'],

            // SMTP Configuration
            ['id' => 270, 'name' => 'settings.smtp.view'],

            // Sales Reports (from config/reports.php)
            ['id' => 271, 'name' => 'reports.sales.regular-client-statement.view'],
            ['id' => 282, 'name' => 'reports.sales.client-wise-statement.view'],
            ['id' => 272, 'name' => 'reports.sales.overdue-client-statement.view'],
            ['id' => 273, 'name' => 'reports.sales.classified-client-statement.view'],
            ['id' => 274, 'name' => 'reports.sales.detailed-client-statement.view'],
            ['id' => 275, 'name' => 'reports.sales.all-client-statement.view'],
            ['id' => 276, 'name' => 'reports.sales.upcoming-installments.view'],
            ['id' => 277, 'name' => 'reports.sales.collection-performance.view'],
            ['id' => 278, 'name' => 'reports.sales.defaulter-report.view'],
            ['id' => 279, 'name' => 'reports.sales.aging-outstanding.view'],
            ['id' => 280, 'name' => 'reports.sales.rent-collection.view'],
            ['id' => 281, 'name' => 'reports.sales.export'],

            // Finance Reports (from config/reports.php)
            ['id' => 283, 'name' => 'reports.finance.daily-statement.view'],
            ['id' => 284, 'name' => 'reports.finance.export'],
            ['id' => 285, 'name' => 'reports.finance.company-overview.view'],

            // Projects Reports (from config/reports.php)
            ['id' => 286, 'name' => 'reports.projects.property-list.view'],
            ['id' => 287, 'name' => 'reports.projects.export'],
            ['id' => 288, 'name' => 'reports.projects.project-list.view'],

            // Inventory Reports (from config/reports.php)
            ['id' => 289, 'name' => 'reports.inventory.stocks-report.view'],
            ['id' => 290, 'name' => 'reports.inventory.export'],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['id' => $permission['id'] ?? null],
                ['name' => $permission['name'] ?? null]
            );
        }

    }
}
