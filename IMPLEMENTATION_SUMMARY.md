# Implementation Summary: Regular Client Statement Report

## Overview
Implemented the first Sales & Rents client statement report: **Regular Client Statement** — showing all clients with outstanding balances regardless of overdue status.

**Date Completed:** 2026-06-11  
**Architecture:** Future-proof modular structure for adding 9 more reports with minimal code

---

## What Was Built

### 1. Service Class
**File:** `app/Services/Reports/Sales/RegularClientStatementService.php`

- Single responsibility: build structured report data
- Query: `PropertySale` with associated `PaymentSchedule` and `Customer` data
- Filters: project, client, property/unit, date range, sale type
- Calculations:
  - Total paid, total due (from `HasPaymentSchedules` trait)
  - Next installment (first unpaid schedule by date)
  - Status: `Current` if due_date ≥ today, else `Overdue`
- Returns standardized array with: `title`, `slug`, `columns`, `rows`, `summary`, `meta`
- Helper methods: `getProjects()`, `getCustomers()`, `getProperties()`

### 2. Livewire Component
**File:** `app/Livewire/Admin/Reports/Sales/RegularClientStatement.php`

- Filter properties: `projectId`, `customerId`, `propertyId`, `fromDate`, `toDate`, `saleType`, `preset`
- Date preset logic: today/month/year/custom with sync detection
- Livewire lifecycle: `mount()`, `updated()`, `render()`
- Permission: `reports.sales.regular-client-statement.view`
- Export URLs generated for PDF, Excel, and Print views

### 3. Export Controller
**File:** `app/Http/Controllers/Admin/Reports/SalesReportExportController.php`

- Slug → Service class mapping: `'regular-client-statement' => RegularClientStatementService::class`
- Three export methods:
  - `pdf()` — DomPDF A4, landscape if columns > 6
  - `excel()` — HTML table as `.xls` file
  - `print()` — browser-friendly view for printing
- Permission: `reports.sales.export`
- Future-proof: adding report #2 just means adding another service to the map

### 4. Views (5 files)

**Livewire View:**
- `resources/views/livewire/admin/reports/sales/regular-client-statement.blade.php`
  - Filter bar: project/client/property/date dropdowns + preset buttons
  - Summary KPIs: total clients, total outstanding, total due this month
  - 9-column data table with hover effects
  - Export buttons: Print, PDF, Excel

**Export Views:**
- `resources/views/admin/reports/sales/exports/report-pdf.blade.php`
  - Professional DomPDF layout with company header, report title, filters summary
  - Striped table rows, right-aligned numbers, colored status badges
  - Summary section with totals

- `resources/views/admin/reports/sales/exports/report-excel.blade.php`
  - Plain HTML table for Excel compatibility
  - Inline borders, minimal CSS
  - Header row + data rows + summary totals

- `resources/views/admin/reports/sales/exports/report-print.blade.php`
  - Browser-optimized layout for printing
  - Tailwind CSS styling, KPI cards, clean typography
  - Print button (hidden in print), back button

### 5. Routes
**File:** `routes/admin.php` (added 4 routes)

```
GET  /admin/reports/sales/regular-client-statement     → RegularClientStatement (Livewire)
GET  /admin/reports/sales/export/{report}/pdf          → SalesReportExportController@pdf
GET  /admin/reports/sales/export/{report}/excel        → SalesReportExportController@excel
GET  /admin/reports/sales/export/{report}/print        → SalesReportExportController@print
```

All routes protected with middleware: `can:reports.sales.*`

### 6. ReportController Update
**File:** `app/Http/Controllers/Admin/ReportController.php`

- Added "Regular Client Statement" as first item in `sales` category with live route
- Route: `route('admin.reports.sales.regular-client-statement')`
- Card now links to the report (not `#`)

---

## Report Columns (9)

| # | Column | Source | Type |
|---|---|---|---|
| 1 | Client Name | `customers.name` | Text |
| 2 | Unit / Property | `property_units.name` + `properties.name` | Text |
| 3 | Booking Date | `property_sales.sale_date` | Date |
| 4 | Contract Value | `property_sales.net_amount` | Currency |
| 5 | Total Paid | sum of `payment_schedules.paid_amount` | Currency |
| 6 | Outstanding Balance | sum of `payment_schedules.due_amount` | Currency |
| 7 | Next Due Date | earliest unpaid schedule's `due_date` | Date |
| 8 | Due Amount | amount of next installment | Currency |
| 9 | Status | `Current` or `Overdue` (computed) | Badge |

---

## Report Filters

| Filter | Type | Values |
|---|---|---|
| Project | Dropdown | All projects from `projects` table |
| Client | Dropdown | All customers from `customers` table |
| Unit / Property | Dropdown | All units from `property_units` table |
| Date Range | Input (from/to) | Filters by `property_sales.sale_date` |
| Sale Type | Dropdown | all / sale / rent |
| Presets | Buttons | Today / This Month / This Year / Custom |

---

## Summary Metrics

| Metric | Calculation |
|---|---|
| Total Clients | Count of filtered sales with outstanding > 0 |
| Total Outstanding | Sum of `outstanding_balance` across all rows |
| Total Due This Month | Sum of `due_amount` where `next_due_date` is in current month |

---

## File Structure (Future-Proof)

```
app/
├── Services/Reports/Sales/
│   └── RegularClientStatementService.php       ← Report 1
│   └── OverdueClientStatementService.php       ← Report 2 (future)
│   └── ...
├── Livewire/Admin/Reports/Sales/
│   └── RegularClientStatement.php              ← Report 1
│   └── OverdueClientStatement.php              ← Report 2 (future)
│   └── ...
└── Http/Controllers/Admin/Reports/
    └── SalesReportExportController.php         ← Shared by all reports

resources/views/
├── livewire/admin/reports/sales/
│   └── regular-client-statement.blade.php      ← Report 1
│   └── overdue-client-statement.blade.php      ← Report 2 (future)
│   └── ...
└── admin/reports/sales/exports/
    ├── report-pdf.blade.php                    ← Shared PDF template
    ├── report-excel.blade.php                  ← Shared Excel template
    └── report-print.blade.php                  ← Shared Print view
```

---

## Adding the Next Report

To implement Report #2 (Overdue Client Statement):

1. **Create service** — `app/Services/Reports/Sales/OverdueClientStatementService.php`
   - Copy `RegularClientStatementService` structure
   - Filter `paymentSchedules` where `overdueCount() > 0`
   - Adjust columns as needed

2. **Create component** — `app/Livewire/Admin/Reports/Sales/OverdueClientStatement.php`
   - Copy component structure
   - Adjust filter properties for this report type
   - Update permission and route names

3. **Create view** — `resources/views/livewire/admin/reports/sales/overdue-client-statement.blade.php`
   - Copy and customize filter section as needed
   - Reuse same table structure (columns will adapt automatically)

4. **Add route** — one line in `routes/admin.php`
   ```php
   Route::get('/overdue-client-statement', OverdueClientStatement::class)
       ->middleware('can:reports.sales.overdue-client-statement.view')
       ->name('overdue-client-statement');
   ```

5. **Update export controller map** — one line in `SalesReportExportController.php`
   ```php
   'overdue-client-statement' => OverdueClientStatementService::class,
   ```

6. **Update ReportController** — add one item to `sales` category with route

**No structural changes needed** — the export controller and view templates are already shared and will work with any report service.

---

## Tests & Verification

✅ **Syntax:** All PHP files pass `php -l` check  
✅ **Routes:** 4 routes registered correctly with proper middleware  
✅ **Permissions:** Routes protected with `can:reports.sales.*` middleware  
✅ **Server:** Application boots successfully with `php artisan serve`  
✅ **Navigation:** Report accessible from Reports hub (`admin/reports/sales` category)

---

## Database Requirements

No migrations needed. Uses existing tables:
- `property_sales` (with `sale_type`, `customer_id`, `property_unit_id`, `sale_date`, `net_amount`, `payment_status`)
- `payment_schedules` (with `property_sale_id`, `due_date`, `amount`, `paid_amount`, `due_amount`, `status`)
- `customers` (with `id`, `name`)
- `property_units` (with `id`, `name`, `property_id`)
- `properties` (with `id`, `name`, `project_id`)
- `projects` (with `id`, `name`)

---

## Ready for Testing

The implementation is complete and ready for:
1. Manual testing with seeded data (need sample property sales + payment schedules)
2. Permission setup in roles/abilities
3. Adding the remaining 9 reports using the same pattern
