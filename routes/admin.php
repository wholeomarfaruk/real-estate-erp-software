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

//inventory stock receives
Route::get('/inventory/stock-receives', App\Livewire\Admin\Inventory\StockReceive\StockReceiveList::class)->name('inventory.stock-receives.index');
Route::get('/inventory/stock-receives/create', App\Livewire\Admin\Inventory\StockReceive\StockReceiveForm::class)->name('inventory.stock-receives.create');
Route::get('/inventory/stock-receives/{stockReceive}/view', App\Livewire\Admin\Inventory\StockReceive\StockReceiveView::class)->name('inventory.stock-receives.view');
Route::get('/inventory/stock-receives/{stockReceive}/edit', App\Livewire\Admin\Inventory\StockReceive\StockReceiveForm::class)->name('inventory.stock-receives.edit');

//inventory stock transfers
Route::get('/inventory/stock-transfers', App\Livewire\Admin\Inventory\StockTransfer\StockTransferList::class)->name('inventory.stock-transfers.index');
Route::get('/inventory/stock-transfers/create', App\Livewire\Admin\Inventory\StockTransfer\StockTransferForm::class)->name('inventory.stock-transfers.create');
Route::get('/inventory/stock-transfers/{transferTransaction}/view', App\Livewire\Admin\Inventory\StockTransfer\StockTransferView::class)->name('inventory.stock-transfers.view');
Route::get('/inventory/stock-transfers/{transferTransaction}/edit', App\Livewire\Admin\Inventory\StockTransfer\StockTransferForm::class)->name('inventory.stock-transfers.edit');

//uploads
Route::get('/uploads', App\Livewire\Admin\File\Uploads::class)->name('uploads');
Route::post('/upload', [FileUploadController::class, 'storeAdmin']);
Route::delete('/upload/revert', [FileUploadController::class, 'revertAdmin']);

//Ui Components
Route::get('/ui/layouts', App\Livewire\Admin\Ui\Layouts\Layouts::class)->name('ui.layouts');
