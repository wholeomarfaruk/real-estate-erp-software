<?php

use App\Http\Controllers\Admin\Accounts\AccountingDocumentController;
use App\Http\Controllers\Admin\Accounts\TransactionAttachmentController;
use App\Http\Controllers\Admin\FileUploadController;
use App\Http\Controllers\Admin\Hrm\PayrollDocumentController;
use App\Http\Controllers\Admin\Inventory\PurchaseOrderDocumentController;
use App\Http\Controllers\Admin\Inventory\SupplierPurchaseOrderDownloadController;
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
Route::get('/projects/{project}/details', App\Livewire\Admin\Projects\ProjectDetails::class)->name('projects.details');

// properties (linked to projects) - Property Management module
Route::get('/projects/properties', App\Livewire\Admin\Properties\PropertyList::class)->name('projects.properties');
Route::get('/projects/properties/create', App\Livewire\Admin\Properties\PropertyCreate::class)->name('projects.properties.create');
Route::get('/projects/properties/{property}/details', App\Livewire\Admin\Properties\PropertyDetails::class)->name('projects.properties.details');
Route::get('/projects/properties/{property}/floors', App\Livewire\Admin\Properties\FloorList::class)->name('projects.properties.floors');
Route::get('/projects/properties/{property}/floors/create', App\Livewire\Admin\Properties\FloorForm::class)->name('projects.properties.floors.create');
Route::get('/projects/properties/{property}/floors/{floor}/edit', App\Livewire\Admin\Properties\FloorForm::class)->name('projects.properties.floors.edit');
Route::get('/projects/properties/{property}/floors/{floor}', App\Livewire\Admin\Properties\FloorView::class)->name('projects.properties.floors.view');
Route::get('/projects/properties/{property}/units', App\Livewire\Admin\Properties\UnitList::class)->name('projects.properties.units');
Route::get('/projects/properties/{property}/units/create', App\Livewire\Admin\Properties\UnitForm::class)->name('projects.properties.units.create');
Route::get('/projects/properties/{property}/units/{unit}/edit', App\Livewire\Admin\Properties\UnitForm::class)->name('projects.properties.units.edit');
Route::get('/projects/properties/{property}/units/{unit}', App\Livewire\Admin\Properties\UnitView::class)->name('projects.properties.units.view');
Route::get('/projects/properties/{property}/overview', App\Livewire\Admin\Properties\Overview::class)->name('projects.properties.overview');


//project calendar
Route::get('/project-calendar', App\Livewire\Admin\Projects\ProjectCalendar::class)->name('project.calendar');
// units (project-level)
Route::get('/units', App\Livewire\Admin\Projects\UnitList::class)->name('units.list');
Route::get('/units/create', App\Livewire\Admin\Projects\UnitForm::class)->name('units.create');
Route::get('/units/{unit}/edit', App\Livewire\Admin\Projects\UnitForm::class)->name('units.edit');
Route::get('/units/{unit}', App\Livewire\Admin\Projects\UnitView::class)->name('units.view');

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
Route::get('/inventory/suppliers/{supplier}/purchase-orders/download', [SupplierPurchaseOrderDownloadController::class, 'download'])
    ->middleware('can:supplier.view')
    ->name('inventory.suppliers.purchase-orders.download');

//standalone supplier module
Route::prefix('supplier')->name('supplier.')->group(function (): void {
    Route::get('/dashboard', App\Livewire\Admin\Supplier\Dashboard\SupplierDashboard::class)
        ->middleware('can:supplier.dashboard.view')
        ->name('dashboard');

    Route::get('/bills', App\Livewire\Admin\Supplier\Bill\BillList::class)
        ->middleware('can:supplier.bill.list')
        ->name('bills.index');

    Route::get('/bills/pending', App\Livewire\Admin\Supplier\Bill\PendingBillList::class)
        ->middleware('can:supplier.bill.pending.view')
        ->name('bills.pending');

    Route::get('/bills/create', App\Livewire\Admin\Supplier\Bill\BillForm::class)
        ->middleware('can:supplier.bill.create')
        ->name('bills.create');

    Route::get('/bills/{bill}', App\Livewire\Admin\Supplier\Bill\BillView::class)
        ->middleware('can:supplier.bill.view')
        ->name('bills.view');

    Route::get('/bills/{bill}/edit', App\Livewire\Admin\Supplier\Bill\BillForm::class)
        ->middleware('can:supplier.bill.edit')
        ->name('bills.edit');

    Route::get('/payments', App\Livewire\Admin\Supplier\Payment\PaymentList::class)
        ->middleware('can:supplier.payment.list')
        ->name('payments.index');

    Route::get('/payments/create', App\Livewire\Admin\Supplier\Payment\PaymentForm::class)
        ->middleware('can:supplier.payment.create')
        ->name('payments.create');

    Route::get('/payments/{payment}', App\Livewire\Admin\Supplier\Payment\PaymentView::class)
        ->middleware('can:supplier.payment.view')
        ->name('payments.view');

    Route::get('/payments/{payment}/edit', App\Livewire\Admin\Supplier\Payment\PaymentForm::class)
        ->middleware('can:supplier.payment.edit')
        ->name('payments.edit');

    Route::get('/returns', App\Livewire\Admin\Supplier\Return\SupplierReturnList::class)
        ->middleware('can:supplier.return.list')
        ->name('returns.index');

    Route::get('/returns/create', App\Livewire\Admin\Supplier\Return\SupplierReturnForm::class)
        ->middleware('can:supplier.return.create')
        ->name('returns.create');

    Route::get('/returns/{return}', App\Livewire\Admin\Supplier\Return\SupplierReturnView::class)
        ->middleware('can:supplier.return.view')
        ->name('returns.view');

    Route::get('/returns/{return}/edit', App\Livewire\Admin\Supplier\Return\SupplierReturnForm::class)
        ->middleware('can:supplier.return.edit')
        ->name('returns.edit');

    Route::get('/ledger', App\Livewire\Admin\Supplier\Ledger\SupplierLedger::class)
        ->middleware('can:supplier.ledger.view')
        ->name('ledger.index');

    Route::get('/statement', App\Livewire\Admin\Supplier\Ledger\SupplierStatement::class)
        ->middleware('can:supplier.statement.view')
        ->name('statement.index');

    Route::get('/reports/supplier-wise', App\Livewire\Admin\Supplier\Reports\SupplierWiseReport::class)
        ->middleware('can:supplier.reports.supplier-wise')
        ->name('reports.supplier-wise');

    Route::get('/reports/product-wise', App\Livewire\Admin\Supplier\Reports\ProductWiseSupplierReport::class)
        ->middleware('can:supplier.reports.product-wise')
        ->name('reports.product-wise');

    Route::get('/reports/due', App\Livewire\Admin\Supplier\Reports\SupplierDueReport::class)
        ->middleware('can:supplier.reports.due')
        ->name('reports.due');

    Route::get('/reports/aging', App\Livewire\Admin\Supplier\Reports\SupplierAgingReport::class)
        ->middleware('can:supplier.reports.aging')
        ->name('reports.aging');

    Route::get('/suppliers', App\Livewire\Admin\Supplier\Supplier\SupplierList::class)
        ->middleware('can:supplier.list.view')
        ->name('suppliers.index');

    Route::get('/suppliers/create', App\Livewire\Admin\Supplier\Supplier\SupplierForm::class)
        ->middleware('can:supplier.create')
        ->name('suppliers.create');

    Route::get('/suppliers/{supplier}', App\Livewire\Admin\Supplier\Supplier\SupplierView::class)
        ->middleware('can:supplier.view')
        ->name('suppliers.view');

    Route::get('/suppliers/{supplier}/edit', App\Livewire\Admin\Supplier\Supplier\SupplierForm::class)
        ->middleware('can:supplier.edit')
        ->name('suppliers.edit');
});

//accounts module
Route::prefix('accounts')->name('accounts.')->group(function (): void {
    Route::get('/chart-of-accounts', App\Livewire\Admin\Accounts\Account\AccountList::class)
        ->middleware('can:accounts.chart.list')
        ->name('chart-of-accounts.index');

    Route::get('/transactions', App\Livewire\Admin\Accounts\Transaction\TransactionList::class)
        ->middleware('can:accounts.transaction.list')
        ->name('transactions.index');

    Route::get('/transactions/{transaction}/attachments/{file}/download', [TransactionAttachmentController::class, 'download'])
        ->name('transactions.attachments.download');

    Route::get('/payments', App\Livewire\Admin\Accounts\Payment\PaymentList::class)
        ->middleware('can:accounts.payment.list')
        ->name('payments.index');

    Route::get('/payments/{payment}/print', [AccountingDocumentController::class, 'paymentPrint'])
        ->middleware('can:accounts.payment.list')
        ->name('payments.print');

    Route::get('/payments/{payment}/pdf', [AccountingDocumentController::class, 'paymentPdf'])
        ->middleware('can:accounts.payment.list')
        ->name('payments.pdf');

    Route::get('/collections', App\Livewire\Admin\Accounts\Collection\CollectionList::class)
        ->middleware('can:accounts.collection.list')
        ->name('collections.index');

    Route::get('/collections/{collection}/print', [AccountingDocumentController::class, 'collectionPrint'])
        ->middleware('can:accounts.collection.list')
        ->name('collections.print');

    Route::get('/collections/{collection}/pdf', [AccountingDocumentController::class, 'collectionPdf'])
        ->middleware('can:accounts.collection.list')
        ->name('collections.pdf');

    Route::get('/expenses', App\Livewire\Admin\Accounts\Expense\ExpenseList::class)
        ->middleware('can:accounts.expense.list')
        ->name('expenses.index');

    Route::get('/expenses/{expense}/print', [AccountingDocumentController::class, 'expensePrint'])
        ->middleware('can:accounts.expense.list')
        ->name('expenses.print');

    Route::get('/expenses/{expense}/pdf', [AccountingDocumentController::class, 'expensePdf'])
        ->middleware('can:accounts.expense.list')
        ->name('expenses.pdf');

    Route::get('/purchase-payables', App\Livewire\Admin\Accounts\PurchasePayable\PurchasePayableList::class)
        ->middleware('can:accounts.purchase-payable.list')
        ->name('purchase-payables.index');
});

//hrm module
Route::prefix('hrm')->name('hrm.')->group(function (): void {
    Route::get('/departments', App\Livewire\Admin\Hrm\Department\DepartmentList::class)
        ->middleware('can:hrm.departments.view')
        ->name('departments.index');

    Route::get('/designations', App\Livewire\Admin\Hrm\Designation\DesignationList::class)
        ->middleware('can:hrm.designations.view')
        ->name('designations.index');

    Route::get('/employees', App\Livewire\Admin\Hrm\Employee\EmployeeList::class)
        ->middleware('can:hrm.employees.view')
        ->name('employees.index');

    Route::get('/employees/create', App\Livewire\Admin\Hrm\Employee\EmployeeForm::class)
        ->middleware('can:hrm.employees.create')
        ->name('employees.create');

    Route::get('/employees/{employee}', App\Livewire\Admin\Hrm\Employee\EmployeeView::class)
        ->middleware('can:hrm.employees.view')
        ->name('employees.view');

    Route::get('/employees/{employee}/edit', App\Livewire\Admin\Hrm\Employee\EmployeeForm::class)
        ->middleware('can:hrm.employees.update')
        ->name('employees.edit');

    Route::get('/payrolls', App\Livewire\Admin\Hrm\Payroll\PayrollList::class)
        ->middleware('can:hrm.payrolls.view')
        ->name('payrolls.index');

    Route::get('/payrolls/{payroll}', App\Livewire\Admin\Hrm\Payroll\PayrollView::class)
        ->middleware('can:hrm.payrolls.view')
        ->name('payrolls.view');

    Route::get('/payrolls/{payroll}/payslip/print', [PayrollDocumentController::class, 'payslipPrint'])
        ->middleware('can:hrm.payrolls.print')
        ->name('payrolls.payslip.print');

    Route::get('/employee-advances', App\Livewire\Admin\Hrm\EmployeeAdvance\EmployeeAdvanceList::class)
        ->middleware('can:hrm.employee-advances.view')
        ->name('employee-advances.index');

    Route::get('/employee-advances/{employeeAdvance}', App\Livewire\Admin\Hrm\EmployeeAdvance\EmployeeAdvanceView::class)
        ->middleware('can:hrm.employee-advances.view')
        ->name('employee-advances.view');

    Route::get('/payroll-payments', App\Livewire\Admin\Hrm\PayrollPayment\PayrollPaymentList::class)
        ->middleware('can:hrm.payroll-payments.view')
        ->name('payroll-payments.index');
});

//inventory purchase orders
Route::get('/inventory/purchase-orders', App\Livewire\Admin\Inventory\PurchaseOrder\PurchaseOrderList::class)->name('inventory.purchase-orders.index');
Route::get('/inventory/purchase-orders/create', App\Livewire\Admin\Inventory\PurchaseOrder\PurchaseOrderForm::class)->name('inventory.purchase-orders.create');
Route::get('/inventory/purchase-orders/{purchaseOrder}/view', App\Livewire\Admin\Inventory\PurchaseOrder\PurchaseOrderView::class)->name('inventory.purchase-orders.view');
Route::get('/inventory/purchase-orders/{purchaseOrder}/edit', App\Livewire\Admin\Inventory\PurchaseOrder\PurchaseOrderForm::class)->name('inventory.purchase-orders.edit');
Route::get('/inventory/purchase-orders/{purchaseOrder}/print', [PurchaseOrderDocumentController::class, 'print'])
    ->middleware('can:inventory.purchase_order.view')
    ->name('inventory.purchase-orders.print');
Route::get('/inventory/purchase-orders/{purchaseOrder}/pdf', [PurchaseOrderDocumentController::class, 'pdf'])
    ->middleware('can:inventory.purchase_order.view')
    ->name('inventory.purchase-orders.pdf');
Route::get('/inventory/purchase-orders/{purchaseOrder}/download', [PurchaseOrderDocumentController::class, 'download'])
    ->middleware('can:inventory.purchase_order.view')
    ->name('inventory.purchase-orders.download');
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
