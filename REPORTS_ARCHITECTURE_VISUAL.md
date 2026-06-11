# Reports Architecture: Visual Guide

## Current System (Report #1 Only)

```
┌─────────────────────────────────────────────────────────────────┐
│  NAVIGATION                                                     │
│  /admin/reports → /admin/reports/sales → report page           │
└─────────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────────┐
│  ReportController::category('sales')                            │
│  └─ categories() method (100+ lines)                           │
│     └─ Hard-coded array with all 9 categories                  │
│        └─ sales category has 8 items                           │
│           └─ item 1: Regular Client Statement ✅ (has route)   │
│           └─ item 2-8: Booking, Unit Sales, etc. (route='#')   │
└─────────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────────┐
│  category.blade.php                                             │
│  └─ Loops through $category['items']                           │
│     └─ For each item, show card with link                      │
│        └─ "Regular Client Statement" → linked ✅              │
│        └─ "Overdue Client Statement" → unlinked (#)           │
└─────────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────────┐
│  USER CLICKS "Regular Client Statement"                         │
│  → GET /admin/reports/sales/regular-client-statement           │
└─────────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────────┐
│  RegularClientStatement Livewire Component                      │
│  ├─ Filter state (projectId, customerId, etc)                 │
│  ├─ Calls RegularClientStatementService::build()             │
│  └─ Renders livewire.admin.reports.sales.regular-client-statement
└─────────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────────┐
│  regular-client-statement.blade.php                             │
│  ├─ Filter bar (project/client/property dropdowns)             │
│  ├─ Summary KPI cards (total clients, outstanding, due)        │
│  ├─ Data table (9 columns)                                     │
│  └─ Export buttons (PDF/Excel/Print)                           │
└─────────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────────┐
│  USER CLICKS "Export PDF"                                       │
│  → GET /admin/reports/sales/export/regular-client-statement/pdf│
└─────────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────────┐
│  SalesReportExportController::pdf($report='regular-...')       │
│  ├─ Lookup: $this->reportServices[$report]                    │
│  ├─ Get: RegularClientStatementService::class                 │
│  ├─ Call: app($serviceClass)->build($filters)                 │
│  └─ Render: report-pdf.blade.php into DomPDF                  │
└─────────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────────┐
│  Download: regular-client-statement-2026-06-11.pdf             │
└─────────────────────────────────────────────────────────────────┘

📊 PROBLEM: 'regular-client-statement' mentioned 8+ times in codebase
            Adding Report #2 requires touching 5+ files
```

---

## Proposed: Config-Based System

```
┌─────────────────────────────────────────────────────────────────┐
│  config/reports.php (SINGLE SOURCE OF TRUTH)                   │
│  ┌───────────────────────────────────────────────────────────┐ │
│  │ return [                                                  │ │
│  │   'sales' => [                                            │ │
│  │     'name' => 'Sales & Rents Reports',                   │ │
│  │     'reports' => [                                        │ │
│  │       'regular-client-statement' => [                    │ │
│  │         'title' => 'Regular Client Statement',           │ │
│  │         'service' => RegularClientStatementService::..., │ │
│  │         'component' => RegularClientStatement::class,    │ │
│  │         'view' => 'livewire.admin.reports.sales.regular..│ │
│  │       ],                                                  │ │
│  │       'overdue-client-statement' => [                    │ │
│  │         'title' => 'Overdue Client Statement',           │ │
│  │         'service' => OverdueClientStatementService::..., │ │
│  │         // ... 8 more reports                            │ │
│  │       ],                                                  │ │
│  │     ],                                                    │ │
│  │   ],                                                      │ │
│  │ ];                                                        │ │
│  └───────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────────┐
│  app/Services/Reports/ConfigBasedRegistry.php                   │
│  ├─ getCategorized() → builds category array from config      │
│  ├─ getServiceMap() → builds slug→service map                 │
│  └─ find($slug) → gets report config                          │
│                                                                 │
│  $registry = new ConfigBasedRegistry();                        │
│  $all = $registry->getCategorized();                           │
│  // Now $all has ALL categories auto-built from config        │
└─────────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────────┐
│  ReportController (SIMPLIFIED)                                  │
│  ├─ category($key) → $all = $registry->getCategorized()       │
│  ├─ (no more hard-coded categories() method!)                 │
│  └─ Returns same structure as before                          │
└─────────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────────┐
│  category.blade.php (NO CHANGES)                                │
│  └─ Still loops through $category['items']                    │
│     (now populated from config, not hard-coded)               │
└─────────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────────┐
│  USER CLICKS "Overdue Client Statement" (NEW!)                  │
│  → GET /admin/reports/sales/overdue-client-statement          │
│  (route auto-generated from config via routes/admin.php)      │
└─────────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────────┐
│  OverdueClientStatement Component                               │
│  (must exist, but routes/config handle discovery)              │
└─────────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────────┐
│  USER CLICKS "Export PDF"                                       │
│  → GET /admin/reports/sales/export/overdue-client-statement/pdf│
└─────────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────────┐
│  SalesReportExportController::pdf($report='overdue-...')       │
│  ├─ $services = $registry->getServiceMap()                    │
│  ├─ Get: $services['overdue-client-statement']                │
│  ├─ Call: app($serviceClass)->build($filters)                │
│  └─ Render: report-pdf.blade.php into DomPDF                  │
│                                                                 │
│  ✅ No manual service map maintenance!                        │
└─────────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────────┐
│  Download: overdue-client-statement-2026-06-11.pdf             │
└─────────────────────────────────────────────────────────────────┘

✅ BENEFIT: Add Report #2-10 by just editing config/reports.php
            No controller changes, no route changes
            3 minutes per report instead of 15 minutes
```

---

## Proposed: Enum-Based System

```
┌─────────────────────────────────────────────────────────────────┐
│  app/Enums/Reports/SalesReport.php (SINGLE SOURCE OF TRUTH)    │
│  ┌───────────────────────────────────────────────────────────┐ │
│  │ enum SalesReport: string {                               │ │
│  │   case REGULAR_CLIENT_STATEMENT = 'regular-...'         │ │
│  │   case OVERDUE_CLIENT_STATEMENT = 'overdue-...'         │ │
│  │   case CLASSIFIED_CLIENT_STATEMENT = 'classified-...'   │ │
│  │   // ... 7 more cases                                    │ │
│  │                                                          │ │
│  │   public function title(): string { /* match ... */ }   │ │
│  │   public function description(): string { /* ... */ }   │ │
│  │   public function serviceClass(): string { /* ... */ }  │ │
│  │   public function componentClass(): string { /* ... */} │ │
│  │   public function viewPath(): string { /* ... */ }      │ │
│  │   public function permission(): string { /* ... */ }    │ │
│  │   public function category(): string { return 'sales'; }│ │
│  │ }                                                        │ │
│  └───────────────────────────────────────────────────────────┘ │
│                                                                 │
│  ✅ Type-safe: SalesReport::REGULAR_CLIENT_STATEMENT           │
│  ✅ IDE autocomplete: SalesReport:: → shows all 10 cases      │
│  ✅ Self-documenting: one enum = all metadata                 │
└─────────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────────┐
│  app/Services/Reports/EnumBasedRegistry.php                     │
│  ├─ Discovers all SalesReport enum cases via reflection       │
│  ├─ getCategorized() → builds category array                  │
│  ├─ getServiceMap() → builds slug→service map                 │
│  ├─ find($slug) → gets enum case                              │
│  └─ Caches at boot (no reflection overhead)                   │
│                                                                 │
│  $case = SalesReport::REGULAR_CLIENT_STATEMENT;               │
│  $title = $case->title();              // Type-safe!         │
│  $service = $case->serviceClass();     // Type-safe!         │
│  $component = $case->componentClass(); // Type-safe!         │
└─────────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────────┐
│  ReportController (SIMPLIFIED)                                  │
│  ├─ category($key) → $all = $registry->getCategorized()       │
│  ├─ (no more hard-coded categories() method!)                 │
│  └─ Returns same structure as before                          │
│                                                                 │
│  ✅ Plus: Can do $registry->find($slug)->permission()         │
│     (type-safe permission retrieval)                          │
└─────────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────────┐
│  Routes Auto-Generated from Enum                                │
│  foreach (SalesReport::cases() as $report) {                   │
│      Route::get('/' . $report->value,                          │
│          $report->componentClass())                            │
│          ->middleware('can:' . $report->permission())          │
│          ->name('reports.sales.' . $report->value);            │
│  }                                                              │
│                                                                 │
│  ✅ Routes auto-discovered from enum                          │
│  ✅ Impossible to have route without enum case               │
│  ✅ Permissions derived from enum metadata                    │
└─────────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────────┐
│  SalesReportExportController::pdf($report)                      │
│  ├─ $reportEnum = SalesReport::tryFrom($report)               │
│  ├─ abort_unless($reportEnum, 404)                            │
│  ├─ Call: app($reportEnum->serviceClass())->build()           │
│  └─ Render PDF                                                 │
│                                                                 │
│  ✅ Type-safe lookup (not string array)                       │
│  ✅ IDE knows $reportEnum is SalesReport enum                │
│  ✅ Impossible to access non-existent report                 │
└─────────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────────┐
│  Download: regular-client-statement-2026-06-11.pdf             │
└─────────────────────────────────────────────────────────────────┘

✅ BENEFIT: Type-safe, IDE autocomplete, self-documenting
            Adding Report #2-10: add enum case + match arms
            4 minutes per report, but with full type safety
```

---

## Side-by-Side: Adding Report #2

### Current System
```
1. Create service file
   app/Services/Reports/Sales/OverdueClientStatementService.php

2. Create component file  
   app/Livewire/Admin/Reports/Sales/OverdueClientStatement.php

3. Create view file
   resources/views/livewire/admin/reports/sales/overdue-client-statement.blade.php

4. Edit ReportController.php
   ├─ Find sales items array
   ├─ Add new item with name, desc, route
   └─ Must call route('admin.reports.sales.overdue-client-statement')

5. Edit SalesReportExportController.php
   ├─ Find $reportServices array
   ├─ Add 'overdue-client-statement' => OverdueClientStatementService::class
   └─ Two places to typo the slug!

6. Edit routes/admin.php
   ├─ Add Route::get(...OverdueClientStatement::class)
   ├─ Add export route for PDF
   ├─ Add export route for Excel
   ├─ Add export route for Print
   └─ 4 more places to typo!

⏱️ Time: 15 minutes
🐛 Risk: 6+ places to get slug wrong
```

### Config-Based System
```
1. Create service file
   app/Services/Reports/Sales/OverdueClientStatementService.php

2. Create component file
   app/Livewire/Admin/Reports/Sales/OverdueClientStatement.php

3. Create view file
   resources/views/livewire/admin/reports/sales/overdue-client-statement.blade.php

4. Edit config/reports.php
   ├─ Add array element to sales.reports
   ├─ Set: title, service class, component class, view
   └─ 1 place for slug (typed as string key, but visible)

5. Done! ✅
   ├─ ReportController automatically sees it (registry builds from config)
   ├─ Routes auto-generate (if routes use registry)
   ├─ Export controller can find service (registry builds map)
   └─ No manual route registration needed

⏱️ Time: 5 minutes
🐛 Risk: 0 (config is data, not logic)
```

### Enum-Based System
```
1. Create service file
   app/Services/Reports/Sales/OverdueClientStatementService.php

2. Create component file
   app/Livewire/Admin/Reports/Sales/OverdueClientStatement.php

3. Create view file
   resources/views/livewire/admin/reports/sales/overdue-client-statement.blade.php

4. Edit app/Enums/Reports/SalesReport.php
   ├─ Add enum case: OVERDUE_CLIENT_STATEMENT = 'overdue-...'
   ├─ Add title match arm
   ├─ Add description match arm
   ├─ Add serviceClass match arm
   ├─ Add componentClass match arm
   ├─ Add viewPath match arm
   └─ 6 short match arms, no string duplication

5. Done! ✅
   ├─ Registry discovers new case automatically
   ├─ Routes auto-generate from enum
   ├─ Export controller finds service via enum
   └─ IDE knows about new report (type-safe)

⏱️ Time: 5 minutes
🐛 Risk: 0 (enum is typed, IDE catches errors)
✅ IDE Bonus: SalesReport::OVERDUE_CLIENT_STATEMENT auto-complete works
```

---

## Scaling View: Adding All 10 Reports

```
CURRENT SYSTEM (Manual):
  Report #1 ✅   done
  Report #2      edit 5 files (15 min)
  Report #3      edit 5 files (15 min)
  Report #4      edit 5 files (15 min)
  Report #5      edit 5 files (15 min)
  Report #6      edit 5 files (15 min)
  Report #7      edit 5 files (15 min)
  Report #8      edit 5 files (15 min)
  Report #9      edit 5 files (15 min)
  Report #10     edit 5 files (15 min)
  ─────────────────────────────────
  Total: 45 files touched, 135 minutes of repetitive edits
  Maintenance: NIGHTMARE (6+ places per report to maintain)


CONFIG-BASED SYSTEM (Recommended):
  Setup (Week 1)         Create registry (60 min) ⏲️
  Report #1 ✅          Done (already working)
  Report #2             Edit config (3 min)
  Report #3             Edit config (3 min)
  Report #4             Edit config (3 min)
  Report #5             Edit config (3 min)
  Report #6             Edit config (3 min)
  Report #7             Edit config (3 min)
  Report #8             Edit config (3 min)
  Report #9             Edit config (3 min)
  Report #10            Edit config (3 min)
  ─────────────────────────────────
  Total: 87 minutes (60 setup + 27 reports)
  Saved: 48 minutes vs current system
  Maintenance: SIMPLE (one config file)
  
  
ENUM-BASED SYSTEM (Future):
  Setup (Week 4)         Create enum + registry (90 min) ⏲️
  Report #1 ✅          Done (already working)
  Report #2             Edit enum (5 min)
  Report #3             Edit enum (5 min)
  Report #4             Edit enum (5 min)
  Report #5             Edit enum (5 min)
  Report #6             Edit enum (5 min)
  Report #7             Edit enum (5 min)
  Report #8             Edit enum (5 min)
  Report #9             Edit enum (5 min)
  Report #10            Edit enum (5 min)
  ─────────────────────────────────
  Total: 96 minutes (90 setup + 36 reports)
  Saved: 39 minutes vs current system
  Maintenance: EXCELLENT (type-safe enum)
  Long-term: Best for 20+ reports
```

---

## Summary: Choose Based on Timeline

```
IF ADDING 1-2 REPORTS:
   → Keep current system (works, no overhead)
   
IF ADDING 3-7 REPORTS (NEXT 2 MONTHS):
   → Use Config system (90 min setup ROI immediately)
   
IF ADDING 7-10 REPORTS (NEXT 3 MONTHS):
   → Use Enum system (safer, more scalable)
   
IF ADDING 100+ REPORTS (FUTURE):
   → Definitely Enum system (required for scale)
```
