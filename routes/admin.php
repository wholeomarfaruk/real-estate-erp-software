<?php

use App\Http\Controllers\Admin\Accounts\AccountReportExportController;
use App\Http\Controllers\Admin\Projects\ProjectPdfController;
use App\Http\Controllers\Admin\Accounts\DailyStatementReportController;
use App\Http\Controllers\Admin\Accounts\StatementReportController;
use App\Http\Controllers\Admin\Accounts\TransactionAttachmentController;
use App\Http\Controllers\Admin\FileUploadController;
use App\Http\Controllers\Admin\Hrm\PayrollDocumentController;
use App\Http\Controllers\Admin\Inventory\PurchaseOrderDocumentController;
use App\Http\Controllers\Admin\Inventory\SupplierPurchaseOrderDownloadController;
use App\Http\Controllers\Admin\Property\ReceiptController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', \App\Livewire\Admin\Dashboard\Dashboard::class)->name('dashboard');

// CRM - Customers
Route::get('/crm/customers', App\Livewire\Admin\Customers\CustomerList::class)->name('crm.customers.index');
Route::get('/crm/customers/{customer}', App\Livewire\Admin\Customers\CustomerShow::class)->name('crm.customers.show');

// CRM - Leads
Route::get('/crm/leads', App\Livewire\Admin\Crm\Lead\LeadList::class)->name('crm.leads.index');
Route::get('/crm/leads/{lead}', App\Livewire\Admin\Crm\Lead\LeadShow::class)->name('crm.leads.show');

// CRM - Lead Sources
Route::get('/crm/lead-sources', App\Livewire\Admin\Crm\LeadSource\LeadSourceList::class)->name('crm.lead-sources.index');

// CRM - Tasks
Route::get('/crm/tasks', App\Livewire\Admin\Crm\Task\CrmTaskList::class)->name('crm.tasks.index');

// user managements
Route::get('/users', App\Livewire\Admin\Users\Users::class)->name('users');

// Profile and Settings
Route::get('/profile', App\Livewire\Admin\Profile\Profile::class)->name('profile');
Route::get('/settings', App\Livewire\Admin\Settings\Settings::class)->name('settings');

// permissions
Route::get('/permissions/roles', App\Livewire\Admin\Permissions\RoleList::class)->name('roles.list');
Route::get('/permissions/role/create', App\Livewire\Admin\Permissions\RoleCreate::class)->name('roles.create');
Route::get('/permissions/role/edit/{id}', App\Livewire\Admin\Permissions\RoleEdit::class)->name('roles.edit');

// projects
Route::get('/projects', App\Livewire\Admin\Projects\ProjectList::class)->name('projects.list');
Route::get('/projects/{project}/details', App\Livewire\Admin\Projects\ProjectDetails::class)->name('projects.details');
Route::get('/projects/{project}/estimates', App\Livewire\Admin\Projects\ProjectEstimates::class)->name('projects.estimates');
Route::get('/projects/{project}/consumption', App\Livewire\Admin\Projects\ProjectConsumption::class)->name('projects.consumption');
Route::get('/projects/{project}/expenses', App\Livewire\Admin\Projects\ProjectExpenses::class)->name('projects.expenses');
Route::get('/projects/{project}/reports', App\Livewire\Admin\Projects\ProjectReports::class)->name('projects.reports');
Route::get('/projects/{project}/pdf/details', [ProjectPdfController::class, 'details'])->name('projects.pdf.details');
Route::get('/projects/{project}/estimates/{estimate}/pdf', [ProjectPdfController::class, 'estimate'])->name('projects.estimates.pdf');

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

// project calendar
Route::get('/project-calendar', App\Livewire\Admin\Projects\ProjectCalendar::class)->name('project.calendar');
// units (project-level)
Route::get('/units', App\Livewire\Admin\Projects\UnitList::class)->name('units.list');
Route::get('/units/create', App\Livewire\Admin\Projects\UnitForm::class)->name('units.create');
Route::get('/units/{unit}/edit', App\Livewire\Admin\Projects\UnitForm::class)->name('units.edit');
Route::get('/units/{unit}', App\Livewire\Admin\Projects\UnitView::class)->name('units.view');

// materials
Route::get('/materials/categories', App\Livewire\Admin\Materials\ProductCategories::class)->name('materials.categories');
Route::get('/materials/brands', App\Livewire\Admin\Materials\ProductBrands::class)->name('materials.brands');
Route::get('/materials/products', App\Livewire\Admin\Materials\Products::class)->name('materials.products');
Route::get('/materials/units', App\Livewire\Admin\Materials\ProductUnits::class)->name('materials.units');

// inventory dashboard
Route::get('/inventory/dashboard', App\Livewire\Admin\Inventory\Dashboard\InventoryDashboard::class)
    ->middleware('can:inventory.dashboard.view')
    ->name('inventory.dashboard');

// inventory stores
Route::get('/inventory/stores', App\Livewire\Admin\Inventory\Store\StoreList::class)->name('inventory.stores.index');
Route::get('/inventory/stores/create', App\Livewire\Admin\Inventory\Store\StoreForm::class)->name('inventory.stores.create');
Route::get('/inventory/stores/{store}/edit', App\Livewire\Admin\Inventory\Store\StoreForm::class)->name('inventory.stores.edit');

// inventory stock consumptions
Route::get('/inventory/stock-consumptions', App\Livewire\Admin\Inventory\StockConsumption\StockConsumptionList::class)->name('inventory.stock-consumptions.index');
Route::get('/inventory/stock-consumptions/create', App\Livewire\Admin\Inventory\StockConsumption\StockConsumptionForm::class)->name('inventory.stock-consumptions.create');
Route::get('/inventory/stock-consumptions/{stockConsumption}/edit', App\Livewire\Admin\Inventory\StockConsumption\StockConsumptionForm::class)->name('inventory.stock-consumptions.edit');
Route::get('/inventory/stock-consumptions/{stockConsumption}', App\Livewire\Admin\Inventory\StockConsumption\StockConsumptionView::class)->name('inventory.stock-consumptions.show');

// supplier module
Route::prefix('supplier')->name('supplier.')->group(function (): void {
    Route::get('/suppliers', App\Livewire\Admin\Supplier\Supplier\SupplierList::class)
        ->middleware('can:supplier.list.view')
        ->name('suppliers.index');

    Route::get('/suppliers/create', App\Livewire\Admin\Supplier\Supplier\SupplierForm::class)
        ->middleware('can:supplier.create')
        ->name('suppliers.create');

    Route::get('/suppliers/{supplier}', App\Livewire\Admin\Supplier\Supplier\SupplierView::class)
        ->middleware('can:supplier.view')
        ->name('suppliers.view');

    // Supplier Detail — four tab modules (Details · Invoices · Purchase Orders · Advance Payments)
    Route::get('/suppliers/{supplier}/show',          App\Livewire\Suppliers\Show\Details::class)->name('suppliers.show.details');
    Route::get('/suppliers/{supplier}/show/invoices', App\Livewire\Suppliers\Show\Invoices::class)->name('suppliers.show.invoices');
    Route::get('/suppliers/{supplier}/show/orders',   App\Livewire\Suppliers\Show\Orders::class)->name('suppliers.show.orders');
    Route::get('/suppliers/{supplier}/show/advances', App\Livewire\Suppliers\Show\Advances::class)->name('suppliers.show.advances');

    Route::get('/suppliers/{supplier}/edit', App\Livewire\Admin\Supplier\Supplier\SupplierForm::class)
        ->middleware('can:supplier.edit')
        ->name('suppliers.edit');

    Route::get('/suppliers/{supplier}/purchase-orders/download', [SupplierPurchaseOrderDownloadController::class, 'download'])
        ->middleware('can:supplier.view')
        ->name('suppliers.purchase-orders.download');
});

// inventory suppliers alias (redirects to supplier module)
Route::get('/suppliers/list', App\Livewire\Admin\Supplier\Supplier\SupplierList::class)->name('inventory.suppliers.index');
Route::get('/suppliers/list/create', App\Livewire\Admin\Supplier\Supplier\SupplierForm::class)->name('inventory.suppliers.create');
Route::get('/suppliers/list/{supplier}/edit', App\Livewire\Admin\Supplier\Supplier\SupplierForm::class)->name('inventory.suppliers.edit');
Route::get('/suppliers/list/{supplier}/purchase-orders/download', [SupplierPurchaseOrderDownloadController::class, 'download'])
    ->middleware('can:supplier.view')
    ->name('inventory.suppliers.purchase-orders.download');

// accounts module
Route::prefix('accounts')->name('accounts.')->group(function (): void {
    Route::get('/chart-of-accounts', App\Livewire\Admin\Accounts\Account\AccountList::class)
        ->middleware('can:accounts.chart.list')
        ->name('chart-of-accounts.index');

    Route::get('/banks', App\Livewire\Admin\Accounts\Assets\BankList::class)
        ->name('banks.list');

    Route::get('/transactions', App\Livewire\Admin\Accounts\Transaction\TransactionList::class)
        ->middleware('can:accounts.transaction.list')
        ->name('transactions.index');

    Route::get('/advance-refund', App\Livewire\Admin\Accounts\AdvanceRefundForm::class)
        ->middleware('can:accounts.advance.refund')
        ->name('advance-refund');

    Route::get('/transaction-categories', App\Livewire\Admin\Accounts\TransactionCategoryManager::class)
        ->name('transaction-categories');

    Route::prefix('expenses')->name('expenses.')->group(function (): void {
        Route::get('/', App\Livewire\Admin\Accounts\Expense\ExpenseList::class)
            ->middleware('can:accounts.expense.list')
            ->name('index');
        Route::get('/create', App\Livewire\Admin\Accounts\Expense\ExpenseForm::class)
            ->middleware('can:accounts.expense.create')
            ->name('create');
        Route::get('/{expense}', App\Livewire\Admin\Accounts\Expense\ExpenseForm::class)
            ->middleware('can:accounts.expense.list')
            ->name('show');
    });

    Route::get('/transactions/{transaction}/attachments/{file}/download', [TransactionAttachmentController::class, 'download'])
        ->middleware('can:accounts.transaction-attachment.view')
        ->name('transactions.attachments.download');

    Route::get('/reports/statement', App\Livewire\Admin\Accounts\Reports\StatementReport::class)
        ->middleware('can:accounts.reports.statement.view')
        ->name('reports.statement');

    Route::get('/reports/daily-statement', App\Livewire\Admin\Accounts\Reports\DailyStatementView::class)
        ->middleware('can:accounts.reports.statement.view')
        ->name('reports.daily-statement');

    Route::get('/reports/daily-statement/preview', [DailyStatementReportController::class, 'preview'])
        ->middleware('can:accounts.reports.statement.view')
        ->name('reports.daily-statement.preview');

    Route::get('/reports/daily-statement/pdf', [DailyStatementReportController::class, 'export'])
        ->middleware('can:accounts.reports.statement.export')
        ->name('reports.daily-statement.pdf');

    Route::get('/reports/statement/print', [StatementReportController::class, 'print'])
        ->middleware('can:accounts.reports.statement.print')
        ->name('reports.statement.print');

    Route::get('/reports/statement/pdf', [StatementReportController::class, 'export'])
        ->middleware('can:accounts.reports.statement.export')
        ->name('reports.statement.export');

    Route::get('/reports/assets', App\Livewire\Admin\Accounts\Reports\AssetReport::class)
        ->middleware('can:accounts.report.view')
        ->name('reports.assets');

    Route::get('/reports/liability', App\Livewire\Admin\Accounts\Reports\LiabilityReport::class)
        ->middleware('can:accounts.report.view')
        ->name('reports.liability');

    Route::get('/reports/cash-book', App\Livewire\Admin\Accounts\Reports\CashBookReport::class)
        ->middleware('can:accounts.report.view')
        ->name('reports.cash-book');

    Route::get('/reports/bank-book', App\Livewire\Admin\Accounts\Reports\BankBookReport::class)
        ->middleware('can:accounts.report.view')
        ->name('reports.bank-book');

    Route::get('/reports/customer-ledger', App\Livewire\Admin\Accounts\Reports\CustomerLedgerReport::class)
        ->middleware('can:accounts.report.view')
        ->name('reports.customer-ledger');

    Route::get('/reports/trial-balance', App\Livewire\Admin\Accounts\Reports\TrialBalanceReport::class)
        ->middleware('can:accounts.report.view')
        ->name('reports.trial-balance');

    Route::get('/reports/profit-loss', App\Livewire\Admin\Accounts\Reports\ProfitLossReport::class)
        ->middleware('can:accounts.report.view')
        ->name('reports.profit-loss');

    Route::get('/reports/balance-sheet', App\Livewire\Admin\Accounts\Reports\BalanceSheetReport::class)
        ->middleware('can:accounts.report.view')
        ->name('reports.balance-sheet');

    Route::get('/reports/daily-summary', App\Livewire\Admin\Accounts\Reports\DailySummaryReport::class)
        ->middleware('can:accounts.report.view')
        ->name('reports.daily-summary');

    Route::get('/reports/account-ledger', App\Livewire\Admin\Accounts\Reports\AccountLedgerReport::class)
        ->middleware('can:accounts.report.view')
        ->name('reports.account-ledger');

    Route::get('/reports/export/{report}/excel', [AccountReportExportController::class, 'excel'])
        ->middleware('can:accounts.report.view')
        ->name('reports.export.excel');

    Route::get('/reports/export/{report}/pdf', [AccountReportExportController::class, 'pdf'])
        ->middleware('can:accounts.report.view')
        ->name('reports.export.pdf');

    Route::prefix('banking')->name('banking.')->group(function (): void {
        Route::get('/', App\Livewire\Admin\Accounts\Banking\BankingManagement::class)
            ->name('index');

        Route::get('/reports', App\Livewire\Admin\Accounts\Banking\BankingReports::class)
            ->name('reports');
    });
});

// hrm module
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

// inventory purchase orders
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

// inventory stock receives
Route::get('/inventory/stock-receives', App\Livewire\Admin\Inventory\StockReceive\StockReceiveList::class)->name('inventory.stock-receives.index');
Route::get('/inventory/stock-receives/create', App\Livewire\Admin\Inventory\StockReceive\StockReceiveForm::class)->name('inventory.stock-receives.create');
Route::get('/inventory/stock-receives/{stockReceive}/view', App\Livewire\Admin\Inventory\StockReceive\StockReceiveView::class)->name('inventory.stock-receives.view');
Route::get('/inventory/stock-receives/{stockReceive}/edit', App\Livewire\Admin\Inventory\StockReceive\StockReceiveForm::class)->name('inventory.stock-receives.edit');

// purchase invoices
Route::get('/inventory/purchase-invoices', App\Livewire\Admin\Inventory\PurchaseInvoice\PurchaseInvoiceList::class)->name('inventory.purchase-invoices.index');
Route::get('/inventory/purchase-invoices/{purchaseInvoice}/view', App\Livewire\Admin\Inventory\PurchaseInvoice\PurchaseInvoiceApprovalForm::class)->name('inventory.purchase-invoices.view');
Route::get('/inventory/purchase-invoices/{purchaseInvoice}/approve', App\Livewire\Admin\Inventory\PurchaseInvoice\PurchaseInvoiceApprovalForm::class)->name('inventory.purchase-invoices.approve');
Route::get('/inventory/purchase-invoices/{purchaseInvoice}/pdf', [App\Http\Controllers\Admin\Inventory\PurchaseInvoicePdfController::class, 'download'])->name('inventory.purchase-invoices.pdf');

// inventory purchase returns
Route::get('/inventory/purchase-returns', App\Livewire\Admin\Inventory\PurchaseReturn\PurchaseReturnList::class)->name('inventory.purchase-returns.index');
Route::get('/inventory/purchase-returns/create', App\Livewire\Admin\Inventory\PurchaseReturn\PurchaseReturnForm::class)->name('inventory.purchase-returns.create');
Route::get('/inventory/purchase-returns/{purchaseReturn}/view', App\Livewire\Admin\Inventory\PurchaseReturn\PurchaseReturnView::class)->name('inventory.purchase-returns.view');
Route::get('/inventory/purchase-returns/{purchaseReturn}/edit', App\Livewire\Admin\Inventory\PurchaseReturn\PurchaseReturnForm::class)->name('inventory.purchase-returns.edit');

// inventory stock requests
Route::get('/inventory/stock-requests', App\Livewire\Admin\Inventory\StockRequest\StockRequestList::class)->name('inventory.stock-requests.index');
Route::get('/inventory/stock-requests/create', App\Livewire\Admin\Inventory\StockRequest\StockRequestForm::class)->name('inventory.stock-requests.create');
Route::get('/inventory/stock-requests/{stockRequest}/view', App\Livewire\Admin\Inventory\StockRequest\StockRequestView::class)->name('inventory.stock-requests.view');
Route::get('/inventory/stock-requests/{stockRequest}/edit', App\Livewire\Admin\Inventory\StockRequest\StockRequestForm::class)->name('inventory.stock-requests.edit');
//for site engineer
// inventory stock requests
Route::get('/inventory/site-engineer/stock-requests', App\Livewire\Admin\Inventory\SiteEngineer\StockRequest\StockRequestList::class)->name('inventory.site_engineer.stock-requests.index');
Route::get('/inventory/site-engineer/stock-requests/create', App\Livewire\Admin\Inventory\SiteEngineer\StockRequest\StockRequestForm::class)->name('inventory.site_engineer.stock-requests.create');
Route::get('/inventory/site-engineer/stock-requests/{stockRequest}/view', App\Livewire\Admin\Inventory\SiteEngineer\StockRequest\StockRequestView::class)->name('inventory.site_engineer.stock-requests.view');
Route::get('/inventory/site-engineer/stock-requests/{stockRequest}/edit', App\Livewire\Admin\Inventory\SiteEngineer\StockRequest\StockRequestForm::class)->name('inventory.site_engineer.stock-requests.edit');

// inventory stock transfers
Route::get('/inventory/stock-transfers', App\Livewire\Admin\Inventory\StockTransfer\StockTransferList::class)->name('inventory.stock-transfers.index');
Route::get('/inventory/stock-transfers/create', App\Livewire\Admin\Inventory\StockTransfer\StockTransferForm::class)->name('inventory.stock-transfers.create');
Route::get('/inventory/stock-transfers/{transferTransaction}/view', App\Livewire\Admin\Inventory\StockTransfer\StockTransferView::class)->name('inventory.stock-transfers.view');
Route::get('/inventory/stock-transfers/{transferTransaction}/edit', App\Livewire\Admin\Inventory\StockTransfer\StockTransferForm::class)->name('inventory.stock-transfers.edit');

// inventory stock adjustments
Route::get('/inventory/stock-adjustments', App\Livewire\Admin\Inventory\StockAdjustment\StockAdjustmentList::class)->name('inventory.stock-adjustments.index');
Route::get('/inventory/stock-adjustments/create', App\Livewire\Admin\Inventory\StockAdjustment\StockAdjustmentForm::class)->name('inventory.stock-adjustments.create');
Route::get('/inventory/stock-adjustments/{stockAdjustment}/view', App\Livewire\Admin\Inventory\StockAdjustment\StockAdjustmentView::class)->name('inventory.stock-adjustments.view');
Route::get('/inventory/stock-adjustments/{stockAdjustment}/edit', App\Livewire\Admin\Inventory\StockAdjustment\StockAdjustmentForm::class)->name('inventory.stock-adjustments.edit');

// inventory reports
Route::get('/inventory/reports/product-ledger', App\Livewire\Admin\Inventory\Reports\ProductLedger::class)->name('inventory.reports.product-ledger');
Route::get('/inventory/reports/store-ledger', App\Livewire\Admin\Inventory\Reports\StoreLedger::class)->name('inventory.reports.store-ledger');
Route::get('/inventory/reports/project-ledger', App\Livewire\Admin\Inventory\Reports\ProjectLedger::class)->name('inventory.reports.project-ledger');
Route::get('/inventory/reports/supplier-purchase-history', App\Livewire\Admin\Inventory\Reports\SupplierPurchaseHistory::class)->name('inventory.reports.supplier-purchase-history');
Route::get('/inventory/reports/stock-movement', App\Livewire\Admin\Inventory\Reports\StockMovementReport::class)->name('inventory.reports.stock-movement');
Route::get('/inventory/reports/total-stock-summary', App\Livewire\Admin\Inventory\Reports\TotalStockSummary::class)->name('inventory.reports.total-stock-summary');
Route::get('/inventory/reports/office-store-summary', App\Livewire\Admin\Inventory\Reports\OfficeStoreSummary::class)->name('inventory.reports.office-store-summary');
Route::get('/inventory/reports/project-store-summary', App\Livewire\Admin\Inventory\Reports\ProjectStoreSummary::class)->name('inventory.reports.project-store-summary');
Route::get('/inventory/reports/product-stock-summary', App\Livewire\Admin\Inventory\Reports\ProductStockSummary::class)->name('inventory.reports.product-stock-summary');
Route::get('/inventory/reports/product-cost', App\Livewire\Admin\Inventory\Reports\ProductWiseCostReport::class)
    ->middleware('can:inventory.report.view')
    ->name('inventory.reports.product-cost');
Route::get('/inventory/reports/low-stock', App\Livewire\Admin\Inventory\Reports\LowStockReport::class)->name('inventory.reports.low-stock');
Route::get('/inventory/reports/out-of-stock', App\Livewire\Admin\Inventory\Reports\OutOfStockReport::class)->name('inventory.reports.out-of-stock');
Route::get('/inventory/reports/store-stock-value', App\Livewire\Admin\Inventory\Reports\StoreStockValueSummary::class)->name('inventory.reports.store-stock-value');

// uploads
Route::get('/uploads', App\Livewire\Admin\File\Uploads::class)->name('uploads');
Route::post('/upload', [FileUploadController::class, 'storeAdmin']);
Route::delete('/upload/revert', [FileUploadController::class, 'revertAdmin']);

// Ui Components
Route::get('/ui/layouts', App\Livewire\Admin\Ui\Layouts\Layouts::class)->name('ui.layouts');


//Site engineers
Route::get('/site-engineers', App\Livewire\Admin\SiteEngineer\Engineer::class)->name('engineers');

// ─── Real Estate ──────────────────────────────────────────────────────────────
Route::get('/properties', App\Livewire\Admin\Properties\PropertyCatalog::class)->name('properties.index');
Route::get('/properties/sales', App\Livewire\Admin\Properties\PropertySaleList::class)->name('properties.sales.index');
Route::get('/properties/sales/create', App\Livewire\Admin\Properties\PropertySaleCreate::class)->name('properties.sales.create');
Route::get('/properties/sales/{sale}', App\Livewire\Admin\Properties\PropertySaleDetails::class)->name('properties.sales.show');
Route::get('/properties/{property}', App\Livewire\Admin\Properties\PropertyShow::class)->name('properties.show');
Route::get('/properties/sales/receipts/{transaction}', [ReceiptController::class, 'show'])->name('properties.receipts.show');


Route::post('/properties/{property}/floors/reorder', function (\App\Models\Property $property, \Illuminate\Http\Request $request) {
    $order = $request->input('order', []);
    foreach ($order as $i => $floorId) {
        \App\Models\PropertyFloor::where('id', $floorId)
            ->where('property_id', $property->id)
            ->update(['sort_order' => $i + 1]);
    }
    return response()->json(['ok' => true]);
})->name('properties.floors.reorder');

Route::post('/properties/{property}/units/reorder', function (\App\Models\Property $property, \Illuminate\Http\Request $request) {
    $floors = $request->input('floors', []);
    foreach ($floors as $floorId => $unitIds) {
        foreach ($unitIds as $i => $unitId) {
            \App\Models\PropertyUnit::where('id', $unitId)
                ->where('property_id', $property->id)
                ->update(['sort_order' => $i + 1, 'property_floor_id' => $floorId]);
        }
    }
    return response()->json(['ok' => true]);
})->name('properties.units.reorder');

// Marketing module
Route::prefix('marketing')->name('marketing.')->group(function (): void {
    Route::get('/templates', App\Livewire\Admin\Marketing\Template\TemplateList::class)
        ->middleware('can:marketing.template.view')
        ->name('templates.index');

    Route::get('/audiences', App\Livewire\Admin\Marketing\Audience\AudienceList::class)
        ->middleware('can:marketing.audience.view')
        ->name('audiences.index');

    Route::get('/campaigns', App\Livewire\Admin\Marketing\Campaign\CampaignList::class)
        ->middleware('can:marketing.campaign.view')
        ->name('campaigns.index');

    Route::get('/messages', App\Livewire\Admin\Marketing\Message\MessageList::class)
        ->middleware('can:marketing.message.view')
        ->name('messages.index');

    Route::get('/automations', App\Livewire\Admin\Marketing\Automation\AutomationList::class)
        ->middleware('can:marketing.automation.view')
        ->name('automations.index');
});
