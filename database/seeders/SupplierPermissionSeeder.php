<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class SupplierPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
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
