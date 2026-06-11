# Reports Registration System Analysis

## Current System (Status Quo)

### How It Works Now

```
ReportController::categories()
├── Hard-coded array with all 9 categories (crm, sales, finance, etc)
├── Each category contains items array with report metadata
│   ├── name, desc, route (manual route() call or '#')
│   └── HARDCODED in code (lines 14-109)
│
SalesReportExportController
├── $reportServices array (manual map)
│   ├── 'regular-client-statement' => RegularClientStatementService::class
│   └── Must update manually when adding reports
│
Routes (admin.php)
├── Livewire component routes added manually
├── Export routes added manually
└── Must remember exact naming conventions
```

### Issues with Current System

| Issue | Impact |
|---|---|
| **Hard-coded report data** | Adding report #2 requires: edit ReportController + controller + view + route (5 changes) |
| **No single source of truth** | Report slug in ReportController != route name != service map key = maintenance burden |
| **Manual string matching** | 'regular-client-statement' repeated 8+ times across files |
| **Scaling problem** | Adding 9 more reports = ~45 manual updates across files |
| **Siloed registration** | Report exists in ReportController but export controller doesn't know about it |
| **Type safety** | No IDE autocomplete for report slugs, easy to mistype |
| **No metadata validation** | Can't verify report components exist until runtime |
| **View discovery manual** | No way to know if `regular-client-statement.blade.php` is missing until user clicks |

---

## Proposed Solution: Enum-Based Registration System

### Architecture Overview

```
app/Enums/Reports/SalesReport.php (NEW)
└── Enum cases with report metadata
    ├── slug
    ├── title
    ├── service class
    ├── livewire component
    ├── view path
    ├── icon
    ├── description
    ├── category
    ├── permission

ReportRegistry.php (NEW Service)
├── Discovers all enums in Reports namespace
├── Builds route map
├── Returns report definitions
└── Used by ReportController + SalesReportExportController

ReportController::categories()
├── Calls ReportRegistry->getCategorized()
├── Gets dynamic category structure
└── No more hard-coded data

SalesReportExportController
├── Calls ReportRegistry->getServiceMap()
├── Gets slug → service class mapping
└── Auto-discovers all reports

Routes (admin.php)
├── Loop through ReportRegistry->getReports()
├── Auto-generate routes
└── Single source of truth
```

---

## Detailed Design: Enum-Based Approach

### 1. Create Report Enum

**File:** `app/Enums/Reports/SalesReport.php`

```php
<?php
namespace App\Enums\Reports;

enum SalesReport: string
{
    case REGULAR_CLIENT_STATEMENT = 'regular-client-statement';
    case OVERDUE_CLIENT_STATEMENT = 'overdue-client-statement';
    case CLASSIFIED_CLIENT_STATEMENT = 'classified-client-statement';
    // ... future reports

    public function title(): string
    {
        return match($this) {
            self::REGULAR_CLIENT_STATEMENT => 'Regular Client Statement',
            self::OVERDUE_CLIENT_STATEMENT => 'Overdue Client Statement',
            // ...
        };
    }

    public function description(): string
    {
        return match($this) {
            self::REGULAR_CLIENT_STATEMENT => 'All clients with outstanding balances...',
            // ...
        };
    }

    public function serviceClass(): string
    {
        return match($this) {
            self::REGULAR_CLIENT_STATEMENT => \App\Services\Reports\Sales\RegularClientStatementService::class,
            // ...
        };
    }

    public function componentClass(): string
    {
        return match($this) {
            self::REGULAR_CLIENT_STATEMENT => \App\Livewire\Admin\Reports\Sales\RegularClientStatement::class,
            // ...
        };
    }

    public function viewPath(): string
    {
        return match($this) {
            self::REGULAR_CLIENT_STATEMENT => 'livewire.admin.reports.sales.regular-client-statement',
            // ...
        };
    }

    public function icon(): string
    {
        return 'house'; // Or inline SVG
    }

    public function permission(): string
    {
        return 'reports.sales.' . str($this->value)->slug() . '.view';
    }

    public function category(): string
    {
        return 'sales';
    }

    public function exportPermission(): string
    {
        return 'reports.sales.export';
    }
}
```

### 2. Create Report Registry Service

**File:** `app/Services/Reports/ReportRegistry.php`

```php
<?php
namespace App\Services\Reports;

use ReflectionClass;
use Illuminate\Support\Collection;

class ReportRegistry
{
    private ?Collection $reports = null;

    /**
     * Get all available reports grouped by category
     */
    public function getCategorized(): array
    {
        $reports = $this->all();

        return [
            'sales' => [
                'key' => 'sales',
                'name' => 'Sales & Rents Reports',
                'desc' => 'Complete picture of bookings...',
                'icon' => '...',
                'items' => $reports->filter(fn($r) => $r->category() === 'sales')
                    ->map(fn($r) => [
                        'name' => $r->title(),
                        'desc' => $r->description(),
                        'route' => route('admin.reports.sales.' . str($r->value)->slug()),
                    ])->values(),
            ],
            'finance' => [...],
            // etc
        ];
    }

    /**
     * Get slug => service class mapping for export controller
     */
    public function getServiceMap(): array
    {
        return $this->all()
            ->mapWithKeys(fn($report) => [
                $report->value => $report->serviceClass(),
            ])->toArray();
    }

    /**
     * Get all route definitions
     */
    public function getRoutes(): Collection
    {
        return $this->all()->map(fn($report) => [
            'slug' => $report->value,
            'component' => $report->componentClass(),
            'permission' => $report->permission(),
            'exportPermission' => $report->exportPermission(),
            'category' => $report->category(),
        ]);
    }

    /**
     * Discover all report enums
     */
    protected function all(): Collection
    {
        if ($this->reports) {
            return $this->reports;
        }

        $enums = [];

        // Find all report enum classes
        foreach (glob(app_path('Enums/Reports/*.php')) as $file) {
            $class = 'App\\Enums\\Reports\\' . basename($file, '.php');
            
            if (enum_exists($class)) {
                $reflection = new ReflectionClass($class);
                foreach ($reflection->getMethod('cases')->invoke(null) as $case) {
                    $enums[] = $case;
                }
            }
        }

        return $this->reports = collect($enums);
    }

    /**
     * Get report by slug
     */
    public function find(string $slug): ?mixed
    {
        return $this->all()->firstWhere('value', $slug);
    }
}
```

### 3. Update ReportController

**Before:**
```php
private function categories(): array
{
    // 100+ lines of hard-coded data
}
```

**After:**
```php
public function category(string $key, ReportRegistry $registry)
{
    $all = $registry->getCategorized();
    $category = collect($all)->firstWhere('key', $key);
    abort_if(! $category, 404);

    return view('admin.reports.category', [
        'category' => $category,
        'allCategories' => $all,
    ]);
}
```

### 4. Update SalesReportExportController

**Before:**
```php
private array $reportServices = [
    'regular-client-statement' => RegularClientStatementService::class,
];
```

**After:**
```php
public function __construct(private ReportRegistry $registry) {}

public function pdf(string $report, Request $request): Response
{
    $this->authorizePermission('reports.sales.export');
    
    $reportDef = $this->registry->find($report);
    abort_unless($reportDef, 404, 'Report not found.');

    $serviceClass = $reportDef->serviceClass();
    $service = app($serviceClass);
    $payload = $service->build($request->all());
    
    // ... rest of logic
}
```

### 5. Update Routes (Dynamic Generation)

**Before:**
```php
Route::prefix('reports/sales')->name('reports.sales.')->group(function () {
    Route::get('/regular-client-statement', RegularClientStatement::class)...
    Route::get('/overdue-client-statement', OverdueClientStatement::class)...
    // Manual for each report
});
```

**After:**
```php
// In a RouteServiceProvider or routes file
$reportRegistry = app(ReportRegistry::class);

foreach ($reportRegistry->getRoutes() as $reportDef) {
    if ($reportDef['category'] === 'sales') {
        Route::get('/' . $reportDef['slug'],
            $reportDef['component'])
            ->middleware('can:' . $reportDef['permission'])
            ->name('reports.sales.' . str($reportDef['slug'])->slug());
    }
}
```

---

## Comparison Table

| Aspect | Current System | Enum-Based System |
|---|---|---|
| **Adding Report #2** | Edit 5 files (controller, enum, 2 views, routes) | Add 1 enum case + tests |
| **Type Safety** | String slug (typos possible) | Enum case (IDE autocomplete) |
| **Route Generation** | Manual for each report | Auto-generated from enum |
| **Source of Truth** | Multiple (ReportController, routes, export controller) | Single enum file |
| **Service Discovery** | Manual map in controller | Automatic via reflection |
| **Missing Component?** | Error at runtime | Can validate at boot |
| **Permission Management** | Hard-coded strings | Derived from enum |
| **Scaling to 10 reports** | 40+ manual changes | 10 enum cases |
| **IDE Autocomplete** | No | Yes (`SalesReport::REGULAR_CLIENT_STATEMENT`) |
| **Validation** | Manual testing | Type system guarantees |

---

## Benefits of Enum Approach

✅ **Single Source of Truth** — One enum per category defines all reports  
✅ **Type Safety** — IDE autocomplete prevents slug typos  
✅ **Auto-Discovery** — Registry discovers enums, no manual registration  
✅ **Scalable** — Adding report #10 doesn't require code in multiple places  
✅ **Testable** — Enum metadata is data, not logic  
✅ **Maintainable** — Easier to see all reports in one file  
✅ **DRY** — No slug duplication across files  
✅ **Future-proof** — Easy to add report status, categories, permissions later  

---

## Implementation Path

### Phase 1: Enum Setup (no breaking changes)
1. Create `app/Enums/Reports/SalesReport.php` with 1 case (regular-client-statement)
2. Create `app/Services/Reports/ReportRegistry.php`
3. Keep existing code working as-is

### Phase 2: Migration (one area at a time)
4. Update ReportController to use registry
5. Update SalesReportExportController to use registry
6. Update routes to use registry

### Phase 3: Scaling (add remaining reports)
7. Add 9 more enum cases to SalesReport
8. No other code changes needed

---

## Example: Adding Report #2 (Overdue Client Statement)

**With Enum System:**

1. Open `app/Enums/Reports/SalesReport.php`
2. Add enum case:
```php
case OVERDUE_CLIENT_STATEMENT = 'overdue-client-statement';
```

3. Add methods in match() statements:
```php
self::OVERDUE_CLIENT_STATEMENT => 'Overdue Client Statement',
self::OVERDUE_CLIENT_STATEMENT => 'Clients with 1-3 overdue...',
self::OVERDUE_CLIENT_STATEMENT => OverdueClientStatementService::class,
// ... etc
```

4. Create service/component/view (as before)
5. Routes auto-generate ✅

**Total changes:** 1 enum file + 3 implementation files  
**No ReportController changes needed** ✅  
**No route changes needed** ✅  

---

## Config-Based Alternative (Simpler, Less Type-Safe)

Instead of enums, use a config file: `config/reports.php`

```php
return [
    'sales' => [
        'category' => [
            'name' => 'Sales & Rents Reports',
            'icon' => '...',
        ],
        'reports' => [
            'regular-client-statement' => [
                'title' => 'Regular Client Statement',
                'service' => RegularClientStatementService::class,
                'component' => RegularClientStatement::class,
                'view' => 'livewire.admin.reports.sales.regular-client-statement',
            ],
            // Add report #2 here
        ],
    ],
];
```

**Pros:** Simple, easy to understand, no reflection needed  
**Cons:** Less type-safe, no IDE autocomplete, still strings

---

## Recommendation

**Use Enum + Registry approach because:**
1. Type-safe (IDE catches typos)
2. Scalable (no config bloat)
3. Maintainable (logic co-located with metadata)
4. Future-proof (can add computed properties)
5. Testable (immutable enum data)

If you prefer simplicity: use Config-based approach — still better than current system.
