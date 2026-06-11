# Plan: Sales & Rents — Regular Client Statement Report

## Context

The Reports hub (`admin/reports`) already exists with a `sales` category where all items point to `'route' => '#'`. This plan implements the **first report only**: Regular Client Statement — all clients with any outstanding balance, regardless of overdue status.

The architecture follows the **exact pattern** of `AccountReportService` + `BaseAccountReport` + `AccountReportExportController` already in the codebase.

### Key constraint from user
- All reports live under the **Reports module** (`admin/reports/sales/...`), NOT under accounts/properties/others
- Each report has its **own dedicated Service class**
- All reports in **one Livewire directory**: `app/Livewire/Admin/Reports/Sales/`
- All services in **one directory**: `app/Services/Reports/Sales/`
- Future-proof: adding report #2–10 later just means adding another Service + Livewire component + route

---

## Directory Structure (future-proof pattern)

```
app/
├── Livewire/Admin/Reports/
│   └── Sales/
│       └── RegularClientStatement.php        ← Report #1 (this plan)
│       └── OverdueClientStatement.php        ← Report #2 (future)
│       └── ...
│
├── Services/Reports/
│   └── Sales/
│       └── RegularClientStatementService.php ← Report #1 (this plan)
│       └── OverdueClientStatementService.php ← Report #2 (future)
│       └── ...
│
├── Http/Controllers/Admin/Reports/
│   └── SalesReportExportController.php       ← shared export controller for all sales reports

resources/views/
├── livewire/admin/reports/
│   └── sales/
│       └── regular-client-statement.blade.php
│       └── ...
│
├── admin/reports/
│   └── sales/
│       └── exports/
│           ├── report-pdf.blade.php           ← shared PDF template
│           └── report-excel.blade.php         ← shared Excel template
```

---

## Report Spec: Regular Client Statement

**Title:** Regular Client Statement — All Pending

**Columns:**
| # | Column | Source |
|---|---|---|
| 1 | Client Name | `customers.name` |
| 2 | Unit/Property | `property_units.name` + `properties.name` |
| 3 | Booking Date | `property_sales.sale_date` |
| 4 | Contract Value | `property_sales.net_amount` |
| 5 | Total Paid | sum of `payment_schedules.paid_amount` |
| 6 | Outstanding Balance | sum of `payment_schedules.due_amount` |
| 7 | Next Installment Due Date | min `due_date` of unpaid schedules |
| 8 | Due Amount | `amount` of that next installment |
| 9 | Status | `Current` (due_date >= today) / `Overdue` (due_date < today) |

**Summary row:**
- Total Clients (count of rows)
- Total Outstanding (sum of outstanding_balance)
- Total Due This Month (sum of due_amount where next due_date is within current month)

**Filters:**
- Project (dropdown)
- Client (dropdown — customers list)
- Property/Unit (dropdown)
- Date Range (from_date / to_date — filters by `sale_date`)
- Sale Type: `sale` / `rent` / `all`

**Exports:** PDF (A4, landscape if columns > 5) + Excel (.xls) + Print View

---

## Files to Create (5 new) + Modify (2 existing)

### New File 1 — Service
**`app/Services/Reports/Sales/RegularClientStatementService.php`**

```php
namespace App\Services\Reports\Sales;

class RegularClientStatementService
{
    public function build(array $filters): array
    // Returns: ['title', 'slug', 'columns'[{key,label,align}], 'rows'[...], 'summary'[...], 'meta'[...]]

    public function getProjects(): Collection
    public function getCustomers(): Collection
    public function getProperties(): Collection
}
```

Query logic:
- Base: `PropertySale::with(['customer','propertyUnit.property','paymentSchedules'])`
- `where` outstanding > 0: only sales where `sum(payment_schedules.due_amount) > 0`
- Filter by `project_id` via `propertyUnit.property.project_id`
- Filter by `customer_id`
- Filter by `property_unit_id`
- Filter by `sale_date` between `from_date` and `to_date`
- Filter by `sale_type`
- Next installment: first unpaid schedule ordered by `due_date ASC`
- Status: computed — `due_date < today` = `Overdue`, else `Current`
- `meta['file_name']`: `regular-client-statement-{date}`

---

### New File 2 — Livewire Component
**`app/Livewire/Admin/Reports/Sales/RegularClientStatement.php`**

```php
namespace App\Livewire\Admin\Reports\Sales;

class RegularClientStatement extends Component
{
    public ?int    $projectId    = null;
    public ?int    $customerId   = null;
    public ?int    $propertyId   = null;
    public string  $fromDate     = '';
    public string  $toDate       = '';
    public string  $saleType     = 'all';   // 'all' | 'sale' | 'rent'
    public string  $preset       = 'month'; // today | month | year | custom

    // mount() — check permission 'reports.sales.regular-client-statement.view'
    // render() — calls service->build(), passes printUrl + excelUrl + pdfUrl
    // applyPreset(), resetFilters(), filterPayload(), exportQuery()
}
```

Export URLs:
```php
route('admin.reports.sales.export.pdf',   ['report' => 'regular-client-statement', ...filters])
route('admin.reports.sales.export.excel', ['report' => 'regular-client-statement', ...filters])
route('admin.reports.sales.export.print', ['report' => 'regular-client-statement', ...filters])
```

---

### New File 3 — Export Controller
**`app/Http/Controllers/Admin/Reports/SalesReportExportController.php`**

```php
namespace App\Http\Controllers\Admin\Reports;

class SalesReportExportController extends Controller
{
    // report slug → service class map
    private array $reportServices = [
        'regular-client-statement' => RegularClientStatementService::class,
        // future: 'overdue-client-statement' => OverdueClientStatementService::class,
    ];

    public function pdf(string $report, Request $request): Response
    // DomPDF, A4, landscape if columns > 6

    public function excel(string $report, Request $request): Response
    // HTML → application/vnd.ms-excel

    public function print(string $report, Request $request): View
    // Browser print view
}
```

---

### New File 4 — Livewire View
**`resources/views/livewire/admin/reports/sales/regular-client-statement.blade.php`**

Sections:
1. **Filter bar** — Project dropdown, Client dropdown, Property dropdown, Date range inputs, Sale type select, Preset buttons (This Month / This Year / Custom), Reset button
2. **Summary KPI cards** — Total Clients · Total Outstanding · Total Due This Month
3. **Data table** — 9 columns as per spec, striped, sortable display
4. **Action bar** — Print · Export PDF · Export Excel buttons
5. Uses `->layout('layouts.admin.admin')`

---

### New File 5 — Export Views (2 shared templates)
**`resources/views/admin/reports/sales/exports/report-pdf.blade.php`**
- DejaVu Sans font (matches accounts PDF style)
- Company name + report title + filter summary in header
- Data table with column headers + rows + summary footer
- A4 landscape

**`resources/views/admin/reports/sales/exports/report-excel.blade.php`**
- Plain HTML table, inline borders only
- No external CSS (Excel compatibility)
- Header row + data rows + summary row

---

### Modify File 1 — Routes
**`routes/admin.php`** — add after line 454 (after the Reports module group):

```php
// Sales Reports
Route::prefix('reports/sales')->name('reports.sales.')->group(function () {
    Route::get('/regular-client-statement',
        App\Livewire\Admin\Reports\Sales\RegularClientStatement::class)
        ->middleware('can:reports.sales.regular-client-statement.view')
        ->name('regular-client-statement');

    // Exports (shared controller, {report} slug selects the service)
    Route::get('/export/{report}/pdf',
        [App\Http\Controllers\Admin\Reports\SalesReportExportController::class, 'pdf'])
        ->middleware('can:reports.sales.export')
        ->name('export.pdf');

    Route::get('/export/{report}/excel',
        [App\Http\Controllers\Admin\Reports\SalesReportExportController::class, 'excel'])
        ->middleware('can:reports.sales.export')
        ->name('export.excel');

    Route::get('/export/{report}/print',
        [App\Http\Controllers\Admin\Reports\SalesReportExportController::class, 'print'])
        ->middleware('can:reports.sales.export')
        ->name('export.print');
});
```

---

### Modify File 2 — ReportController categories
**`app/Http/Controllers/Admin/ReportController.php`**

In `categories()`, update the `sales` array — replace first item's `'route' => '#'` with:
```php
['name'=>'Regular Client Statement', 'desc'=>'All clients with outstanding balances regardless of overdue status.', 'route'=> route('admin.reports.sales.regular-client-statement')],
```
Remaining 6 items stay as `'route' => '#'` until those reports are built.

---

## Implementation Order

1. `RegularClientStatementService` — data foundation
2. `SalesReportExportController` — with slug→service map
3. Export views (PDF + Excel templates)
4. `RegularClientStatement` Livewire component
5. Livewire view
6. Routes
7. ReportController update

---

## Adding Future Reports (the pattern)

When report #2 (Overdue Client Statement) is needed:
1. Add `app/Services/Reports/Sales/OverdueClientStatementService.php`
2. Add `app/Livewire/Admin/Reports/Sales/OverdueClientStatement.php`
3. Add `resources/views/livewire/admin/reports/sales/overdue-client-statement.blade.php`
4. Add one route line in the same `reports/sales` group
5. Add `'overdue-client-statement' => OverdueClientStatementService::class` to the export controller map
6. Update `ReportController` categories with the new route

No structural changes needed — the export controller and view templates are already shared.

---

## Verification

1. Visit `admin/reports/sales` → "Regular Client Statement" card links (not `#`)
2. Visit `admin/reports/sales/regular-client-statement` → page loads, filters render
3. With seeded data → table shows clients with `outstanding_balance > 0`
4. Apply project filter → rows narrow correctly
5. Click "Export PDF" → downloads `regular-client-statement-{date}.pdf`
6. Click "Export Excel" → downloads `regular-client-statement-{date}.xls`
7. Click "Print" → browser print view opens
8. `php artisan route:list --name=reports.sales` → shows 4 routes
