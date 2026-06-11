# Quick Reference: Sales Reports Module

## Current Report Implementation Status

✅ **Report 1: Regular Client Statement** — COMPLETE
- Shows all clients with outstanding balances
- 9 columns with filtering by project/client/property/date/type
- PDF/Excel/Print exports available

⏳ **Reports 2–10** — Ready to be implemented using the same pattern

---

## Access the Report

**URL:** `http://localhost:8000/admin/reports/sales/regular-client-statement`

**From Reports Hub:**
1. Go to `/admin/reports`
2. Click "Sales & Rents Reports" card
3. Click "Regular Client Statement" card

---

## File Locations Reference

### Service Classes
```
app/Services/Reports/Sales/
├── RegularClientStatementService.php       ← Query builder, data transformation
└── [Future reports go here]
```

### Livewire Components
```
app/Livewire/Admin/Reports/Sales/
├── RegularClientStatement.php              ← Filter state, render logic
└── [Future reports go here]
```

### Views
```
resources/views/livewire/admin/reports/sales/
├── regular-client-statement.blade.php      ← Filter UI + data table
└── [Future reports go here]

resources/views/admin/reports/sales/exports/
├── report-pdf.blade.php                    ← Shared PDF template
├── report-excel.blade.php                  ← Shared Excel template
├── report-print.blade.php                  ← Shared Print view
└── [Reused by all reports]
```

### Controller & Routes
```
app/Http/Controllers/Admin/Reports/
└── SalesReportExportController.php         ← Shared export logic

routes/admin.php                             ← routes/sales/* routes
```

---

## How It Works

### 1. User Loads Report Page
```
GET /admin/reports/sales/regular-client-statement
↓
RegularClientStatement component mounts
↓
Permission check: reports.sales.regular-client-statement.view
↓
Render with empty filters (default: this month)
```

### 2. User Applies Filters
```
Filter change (e.g., select a project)
↓
Livewire wire:model.live="projectId"
↓
Render() is called
↓
RegularClientStatementService->build() runs query with new filters
↓
Service returns structured array [columns, rows, summary, meta]
↓
View re-renders with new data
```

### 3. User Exports Report
```
Click "Export PDF" button
↓
GET /admin/reports/sales/export/regular-client-statement/pdf?filters=...
↓
SalesReportExportController->pdf('regular-client-statement')
↓
Maps slug → RegularClientStatementService class
↓
Service->build() with export filters
↓
Render report-pdf.blade.php into DomPDF
↓
Download file: regular-client-statement-2026-06-11-131522.pdf
```

---

## Permission Model

All routes require permissions. Set these in your roles:

| Permission | Used For |
|---|---|
| `reports.sales.regular-client-statement.view` | View report page |
| `reports.sales.export` | Export to PDF/Excel/Print |

Example in Filament/permissions:
```php
'reports' => [
    'sales' => [
        'regular-client-statement' => ['view'],
        'export' => true,
    ]
]
```

---

## Adding Report #2 Checklist

### Checklist Template for Next Report

```
[ ] Create service: app/Services/Reports/Sales/OverdueClientStatementService.php
    [ ] Copy structure from RegularClientStatementService
    [ ] Modify build() method filters/columns for this report
    [ ] Add getProjects(), getCustomers(), getProperties() helpers

[ ] Create component: app/Livewire/Admin/Reports/Sales/OverdueClientStatement.php
    [ ] Copy structure from RegularClientStatement
    [ ] Adjust filter properties for this report
    [ ] Update permission name

[ ] Create view: resources/views/livewire/admin/reports/sales/overdue-client-statement.blade.php
    [ ] Copy from regular-client-statement.blade.php
    [ ] Update filter labels/options if needed
    [ ] View file reuses export templates automatically

[ ] Add routes to routes/admin.php (1 line):
    ```php
    Route::get('/overdue-client-statement', OverdueClientStatement::class)
        ->middleware('can:reports.sales.overdue-client-statement.view')
        ->name('overdue-client-statement');
    ```

[ ] Update SalesReportExportController.php (1 line in $reportServices):
    ```php
    'overdue-client-statement' => OverdueClientStatementService::class,
    ```

[ ] Update ReportController.php (1 item in sales category):
    ```php
    ['name'=>'Overdue Client Statement', 'desc'=>'...', 'route'=>route('admin.reports.sales.overdue-client-statement')],
    ```

[ ] Test:
    [ ] php -l syntax check
    [ ] php artisan route:list --name=reports.sales
    [ ] Visit /admin/reports/sales/overdue-client-statement
    [ ] Try exporting PDF/Excel
```

---

## Debugging

### Service not found error?
```
Check: Is the service class in $reportServices map?
File: app/Http/Controllers/Admin/Reports/SalesReportExportController.php
```

### View not found error?
```
Check: Is the Livewire view path correct?
File: resources/views/livewire/admin/reports/sales/{component-slug}.blade.php
```

### Permission denied on export?
```
Check: Does user have 'reports.sales.export' permission?
Check: Is middleware on route correct?
File: routes/admin.php
```

### No data in table?
```
1. Check: Do you have seeded PropertySale + PaymentSchedule records?
2. Check: Are they not marked as 'cancelled' payment_status?
3. Check: Do they have due_amount > 0?
```

---

## Database Queries Used

Service uses these relationships (lazy-loaded):
- `PropertySale` → `PaymentSchedule` (HasMany)
- `PropertySale` → `Customer` (BelongsTo)
- `PropertySale` → `PropertyUnit` (BelongsTo)
- `PropertyUnit` → `Property` (BelongsTo)
- `Property` → `Project` (BelongsTo)

All calculations use the `HasPaymentSchedules` trait methods:
- `totalPaid()` — sum of `paid_amount`
- `totalDue()` — sum of `due_amount`
- `isOverdue()` — check if `due_date < today`

---

## Next Steps

1. **Set up permissions** in your role/ability definitions
2. **Seed test data** (PropertySale + PaymentSchedule records)
3. **Test manually** by logging in and visiting the report
4. **Plan Report #2** using the checklist above
