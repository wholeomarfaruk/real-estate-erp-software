<?php

namespace Tests\Feature\Reports;

use App\Enums\Inventory\StockReceiveStatus;
use App\Livewire\Admin\Accounts\Reports\ProductWiseCostReport as AccountsProductWiseCostReport;
use App\Livewire\Admin\Inventory\Reports\ProductWiseCostReport as InventoryProductWiseCostReport;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductCategory;
use App\Models\StockReceive;
use App\Models\StockReceiveItem;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\SupplierBill;
use App\Models\SupplierBillItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ProductWiseCostReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_accounts_product_wise_cost_report_shows_summary_and_details(): void
    {
        $user = $this->createUserWithPermissions(['accounts.report.view']);
        $product = $this->createProduct('Cement');
        $supplier = Supplier::query()->create([
            'name' => 'Alpha Supplier',
            'status' => true,
        ]);

        $bill = SupplierBill::query()->create([
            'supplier_id' => $supplier->id,
            'bill_no' => 'BILL-001',
            'bill_date' => '2026-04-20',
            'subtotal' => 640,
            'total_amount' => 640,
            'due_amount' => 640,
            'status' => 'open',
            'created_by' => $user->id,
        ]);

        SupplierBillItem::query()->create([
            'supplier_bill_id' => $bill->id,
            'product_id' => $product->id,
            'qty' => 8,
            'rate' => 80,
            'line_total' => 640,
        ]);

        Livewire::actingAs($user)
            ->test(AccountsProductWiseCostReport::class)
            ->set('from_date', '2026-04-01')
            ->set('to_date', '2026-04-30')
            ->call('applyFilters')
            ->assertSee('Product Wise Cost')
            ->assertSee('Cement')
            ->assertSee('8.000')
            ->assertSee('640.00')
            ->assertSee('80.00')
            ->call('loadDetails', $product->id)
            ->assertSet('showDetailsModal', true)
            ->assertSee('BILL-001')
            ->assertSee('Bill');
    }

    public function test_inventory_product_wise_cost_report_shows_posted_receive_summary_and_details(): void
    {
        $user = $this->createUserWithPermissions(['inventory.report.view']);
        $product = $this->createProduct('Rod');
        $store = Store::query()->create([
            'name' => 'Main Store',
            'code' => 'STR-001',
            'type' => 'office',
            'status' => true,
        ]);

        $postedReceive = StockReceive::query()->create([
            'receive_no' => 'SR-001',
            'receive_date' => '2026-04-22',
            'store_id' => $store->id,
            'status' => StockReceiveStatus::POSTED->value,
            'created_by' => $user->id,
        ]);

        StockReceiveItem::query()->create([
            'stock_receive_id' => $postedReceive->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'unit_price' => 120,
            'total_price' => 600,
        ]);

        $draftReceive = StockReceive::query()->create([
            'receive_no' => 'SR-002',
            'receive_date' => '2026-04-23',
            'store_id' => $store->id,
            'status' => StockReceiveStatus::DRAFT->value,
            'created_by' => $user->id,
        ]);

        StockReceiveItem::query()->create([
            'stock_receive_id' => $draftReceive->id,
            'product_id' => $product->id,
            'quantity' => 3,
            'unit_price' => 150,
            'total_price' => 450,
        ]);

        Livewire::actingAs($user)
            ->test(InventoryProductWiseCostReport::class)
            ->set('from_date', '2026-04-01')
            ->set('to_date', '2026-04-30')
            ->call('applyFilters')
            ->assertSee('Product Wise Cost')
            ->assertSee('Rod')
            ->assertSee('5.000')
            ->assertSee('600.00')
            ->assertSee('120.00')
            ->call('loadDetails', $product->id)
            ->assertSet('showDetailsModal', true)
            ->assertSee('SR-001')
            ->assertDontSee('SR-002')
            ->assertSee('Stock Receive');
    }

    protected function createProduct(string $name): Product
    {
        $category = ProductCategory::query()->create([
            'name' => $name.' Category',
            'slug' => str()->slug($name.' Category'),
        ]);

        $brand = ProductBrand::query()->create([
            'name' => $name.' Brand',
            'slug' => str()->slug($name.' Brand'),
        ]);

        return Product::query()->create([
            'name' => $name,
            'sku' => str()->upper(str()->slug($name, '-')),
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active',
        ]);
    }

    /**
     * @param  array<int, string>  $permissions
     */
    protected function createUserWithPermissions(array $permissions): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::query()->create([
            'name' => 'product-cost-report-role-'.count($permissions).'-'.uniqid(),
            'guard_name' => 'web',
        ]);

        foreach ($permissions as $permissionName) {
            $permission = Permission::query()->create([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);

            $role->givePermissionTo($permission);
        }

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
