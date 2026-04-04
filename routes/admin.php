<?php

use App\Http\Controllers\Admin\FileUploadController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', \App\Livewire\Admin\Dashboard\Dashboard::class)->name('dashboard');

//user managements
Route::get('/users', App\Livewire\Admin\Users\Users::class)->name('users');

// Profile and Settings
Route::get('/profile', App\Livewire\Admin\Profile\Profile::class)->name('profile');
Route::get('/settings', App\Livewire\Admin\Settings\Settings::class)->name('settings');

//permissions
Route::get('/permissions/roles', App\Livewire\Admin\Permissions\RoleList::class)->name('roles.list');
Route::get('/permissions/role/create', App\Livewire\Admin\Permissions\RoleCreate::class)->name('roles.create');
Route::get('/permissions/role/edit/{id}', App\Livewire\Admin\Permissions\RoleEdit::class)->name('roles.edit');

//projects
Route::get('/projects', App\Livewire\Admin\Projects\ProjectList::class)->name('projects.list');
Route::get('/projects/create', App\Livewire\Admin\Projects\ProjectCreate::class)->name('projects.create');
Route::get('/projects/{project}', App\Livewire\Admin\Projects\ProjectDetails::class)->name('projects.details');

//floors
Route::get('/floors', App\Livewire\Admin\Projects\FloorList::class)->name('floors.list');

//units
Route::get('/units', App\Livewire\Admin\Projects\UnitList::class)->name('units.list');

//project calendar
Route::get('/project-calendar', App\Livewire\Admin\Projects\ProjectCalendar::class)->name('project.calendar');

//materials
Route::get('/materials/categories', App\Livewire\Admin\Materials\ProductCategories::class)->name('materials.categories');
Route::get('/materials/brands', App\Livewire\Admin\Materials\ProductBrands::class)->name('materials.brands');
Route::get('/materials/products', App\Livewire\Admin\Materials\Products::class)->name('materials.products');
Route::get('/materials/units', App\Livewire\Admin\Materials\ProductUnits::class)->name('materials.units');

//inventory dashboard
Route::get('/inventory/dashboard', App\Livewire\Admin\Inventory\Dashboard\InventoryDashboard::class)
    ->middleware('can:inventory.dashboard.view')
    ->name('inventory.dashboard');

//inventory stores
Route::get('/inventory/stores', App\Livewire\Admin\Inventory\Store\StoreList::class)->name('inventory.stores.index');
Route::get('/inventory/stores/create', App\Livewire\Admin\Inventory\Store\StoreForm::class)->name('inventory.stores.create');
Route::get('/inventory/stores/{store}/edit', App\Livewire\Admin\Inventory\Store\StoreForm::class)->name('inventory.stores.edit');

//inventory stock consumptions
Route::get('/inventory/stock-consumptions', App\Livewire\Admin\Inventory\StockConsumption\StockConsumptionList::class)->name('inventory.stock-consumptions.index');
Route::get('/inventory/stock-consumptions/create', App\Livewire\Admin\Inventory\StockConsumption\StockConsumptionForm::class)->name('inventory.stock-consumptions.create');
Route::get('/inventory/stock-consumptions/{stockConsumption}/edit', App\Livewire\Admin\Inventory\StockConsumption\StockConsumptionForm::class)->name('inventory.stock-consumptions.edit');
Route::get('/inventory/stock-consumptions/{stockConsumption}', App\Livewire\Admin\Inventory\StockConsumption\StockConsumptionView::class)->name('inventory.stock-consumptions.show');

//inventory suppliers
Route::get('/inventory/suppliers', App\Livewire\Admin\Inventory\Supplier\SupplierList::class)->name('inventory.suppliers.index');
Route::get('/inventory/suppliers/create', App\Livewire\Admin\Inventory\Supplier\SupplierForm::class)->name('inventory.suppliers.create');
Route::get('/inventory/suppliers/{supplier}/edit', App\Livewire\Admin\Inventory\Supplier\SupplierForm::class)->name('inventory.suppliers.edit');

//inventory purchase orders
Route::get('/inventory/purchase-orders', App\Livewire\Admin\Inventory\PurchaseOrder\PurchaseOrderList::class)->name('inventory.purchase-orders.index');
Route::get('/inventory/purchase-orders/create', App\Livewire\Admin\Inventory\PurchaseOrder\PurchaseOrderForm::class)->name('inventory.purchase-orders.create');
Route::get('/inventory/purchase-orders/{purchaseOrder}/view', App\Livewire\Admin\Inventory\PurchaseOrder\PurchaseOrderView::class)->name('inventory.purchase-orders.view');
Route::get('/inventory/purchase-orders/{purchaseOrder}/edit', App\Livewire\Admin\Inventory\PurchaseOrder\PurchaseOrderForm::class)->name('inventory.purchase-orders.edit');
Route::get('/inventory/purchase-orders/{purchaseOrder}/funds', App\Livewire\Admin\Inventory\PurchaseOrder\PurchaseFundForm::class)->name('inventory.purchase-orders.funds');
Route::get('/inventory/purchase-orders/{purchaseOrder}/settlement', App\Livewire\Admin\Inventory\PurchaseOrder\PurchaseSettlementForm::class)->name('inventory.purchase-orders.settlement');

//inventory stock receives
Route::get('/inventory/stock-receives', App\Livewire\Admin\Inventory\StockReceive\StockReceiveList::class)->name('inventory.stock-receives.index');
Route::get('/inventory/stock-receives/create', App\Livewire\Admin\Inventory\StockReceive\StockReceiveForm::class)->name('inventory.stock-receives.create');
Route::get('/inventory/stock-receives/{stockReceive}/view', App\Livewire\Admin\Inventory\StockReceive\StockReceiveView::class)->name('inventory.stock-receives.view');
Route::get('/inventory/stock-receives/{stockReceive}/edit', App\Livewire\Admin\Inventory\StockReceive\StockReceiveForm::class)->name('inventory.stock-receives.edit');

//inventory purchase returns
Route::get('/inventory/purchase-returns', App\Livewire\Admin\Inventory\PurchaseReturn\PurchaseReturnList::class)->name('inventory.purchase-returns.index');
Route::get('/inventory/purchase-returns/create', App\Livewire\Admin\Inventory\PurchaseReturn\PurchaseReturnForm::class)->name('inventory.purchase-returns.create');
Route::get('/inventory/purchase-returns/{purchaseReturn}/view', App\Livewire\Admin\Inventory\PurchaseReturn\PurchaseReturnView::class)->name('inventory.purchase-returns.view');
Route::get('/inventory/purchase-returns/{purchaseReturn}/edit', App\Livewire\Admin\Inventory\PurchaseReturn\PurchaseReturnForm::class)->name('inventory.purchase-returns.edit');

//inventory stock requests
Route::get('/inventory/stock-requests', App\Livewire\Admin\Inventory\StockRequest\StockRequestList::class)->name('inventory.stock-requests.index');
Route::get('/inventory/stock-requests/create', App\Livewire\Admin\Inventory\StockRequest\StockRequestForm::class)->name('inventory.stock-requests.create');
Route::get('/inventory/stock-requests/{stockRequest}/view', App\Livewire\Admin\Inventory\StockRequest\StockRequestView::class)->name('inventory.stock-requests.view');
Route::get('/inventory/stock-requests/{stockRequest}/edit', App\Livewire\Admin\Inventory\StockRequest\StockRequestForm::class)->name('inventory.stock-requests.edit');

//inventory stock transfers
Route::get('/inventory/stock-transfers', App\Livewire\Admin\Inventory\StockTransfer\StockTransferList::class)->name('inventory.stock-transfers.index');
Route::get('/inventory/stock-transfers/create', App\Livewire\Admin\Inventory\StockTransfer\StockTransferForm::class)->name('inventory.stock-transfers.create');
Route::get('/inventory/stock-transfers/{transferTransaction}/view', App\Livewire\Admin\Inventory\StockTransfer\StockTransferView::class)->name('inventory.stock-transfers.view');
Route::get('/inventory/stock-transfers/{transferTransaction}/edit', App\Livewire\Admin\Inventory\StockTransfer\StockTransferForm::class)->name('inventory.stock-transfers.edit');

//inventory stock adjustments
Route::get('/inventory/stock-adjustments', App\Livewire\Admin\Inventory\StockAdjustment\StockAdjustmentList::class)->name('inventory.stock-adjustments.index');
Route::get('/inventory/stock-adjustments/create', App\Livewire\Admin\Inventory\StockAdjustment\StockAdjustmentForm::class)->name('inventory.stock-adjustments.create');
Route::get('/inventory/stock-adjustments/{stockAdjustment}/view', App\Livewire\Admin\Inventory\StockAdjustment\StockAdjustmentView::class)->name('inventory.stock-adjustments.view');
Route::get('/inventory/stock-adjustments/{stockAdjustment}/edit', App\Livewire\Admin\Inventory\StockAdjustment\StockAdjustmentForm::class)->name('inventory.stock-adjustments.edit');

//inventory reports
Route::get('/inventory/reports/product-ledger', App\Livewire\Admin\Inventory\Reports\ProductLedger::class)->name('inventory.reports.product-ledger');
Route::get('/inventory/reports/store-ledger', App\Livewire\Admin\Inventory\Reports\StoreLedger::class)->name('inventory.reports.store-ledger');
Route::get('/inventory/reports/project-ledger', App\Livewire\Admin\Inventory\Reports\ProjectLedger::class)->name('inventory.reports.project-ledger');
Route::get('/inventory/reports/supplier-purchase-history', App\Livewire\Admin\Inventory\Reports\SupplierPurchaseHistory::class)->name('inventory.reports.supplier-purchase-history');
Route::get('/inventory/reports/stock-movement', App\Livewire\Admin\Inventory\Reports\StockMovementReport::class)->name('inventory.reports.stock-movement');
Route::get('/inventory/reports/total-stock-summary', App\Livewire\Admin\Inventory\Reports\TotalStockSummary::class)->name('inventory.reports.total-stock-summary');
Route::get('/inventory/reports/office-store-summary', App\Livewire\Admin\Inventory\Reports\OfficeStoreSummary::class)->name('inventory.reports.office-store-summary');
Route::get('/inventory/reports/project-store-summary', App\Livewire\Admin\Inventory\Reports\ProjectStoreSummary::class)->name('inventory.reports.project-store-summary');
Route::get('/inventory/reports/product-stock-summary', App\Livewire\Admin\Inventory\Reports\ProductStockSummary::class)->name('inventory.reports.product-stock-summary');
Route::get('/inventory/reports/low-stock', App\Livewire\Admin\Inventory\Reports\LowStockReport::class)->name('inventory.reports.low-stock');
Route::get('/inventory/reports/out-of-stock', App\Livewire\Admin\Inventory\Reports\OutOfStockReport::class)->name('inventory.reports.out-of-stock');
Route::get('/inventory/reports/store-stock-value', App\Livewire\Admin\Inventory\Reports\StoreStockValueSummary::class)->name('inventory.reports.store-stock-value');

//uploads
Route::get('/uploads', App\Livewire\Admin\File\Uploads::class)->name('uploads');
Route::post('/upload', [FileUploadController::class, 'storeAdmin']);
Route::delete('/upload/revert', [FileUploadController::class, 'revertAdmin']);

//Ui Components
Route::get('/ui/layouts', App\Livewire\Admin\Ui\Layouts\Layouts::class)->name('ui.layouts');
