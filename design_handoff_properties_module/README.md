# Handoff: Properties Module (Real-Estate ERP)

## Overview

Two pages for **Star Unity Development Ltd.**'s real-estate ERP:

1. **Properties listing** — grid of all properties with KPI strip, search, status filter, per-card occupancy bar, unit breakdown (flats/shops/parking), engineer badge, and a "View details" button.
2. **Property detail** — a single property's hero (image carousel + facts), KPI strip, a creative *building cross-section* showing floors stacked with colored unit cells, an editable unit-inventory table, a photos/documents gallery, and a side drawer for adding/editing units. **Floors and units support drag-and-drop reordering.**

## About the Design Files

The files in `design/` are **design references created in HTML** — high-fidelity prototypes showing intended look and behavior, not production code to ship directly. The task is to **recreate them inside the existing Laravel + Blade application** using the project's established patterns (controllers, Eloquent models, Blade views, Tailwind/whatever CSS strategy is already in use).

The migrations in `migrations/` **are production-ready** and should be added to the project's `database/migrations/` folder (after the check described below).

## Fidelity

**High-fidelity.** The HTML mocks use final colors, typography, spacing, and interactions. Recreate the UI pixel-perfectly using the codebase's existing libraries and patterns. If the project already has a Tailwind config or a CSS variable system, map the colors below to those tokens rather than hard-coding hex values.

---

## Step 1 — Check existing tables and reconcile

**Before running the new migrations, inspect the current schema.** The project may already have some of these tables under different names. Do this first:

```bash
php artisan db:show
php artisan db:table properties      # if it exists
php artisan db:table property_floors # if it exists
php artisan db:table property_units  # or property_links
```

Reconciliation rules:

| If the table exists with… | Action |
|---|---|
| **Same name, same columns** | Skip the new migration. |
| **Same name, missing columns** | Write a new `ALTER`-style migration that adds *only* the missing columns from the field tables below. Do not drop or re-create the existing table. |
| **Same name, extra columns** | Leave the extras alone. Just ensure the columns this UI needs are present. |
| **Different name** (e.g. `property_links` instead of `property_units`) | Rename to match the UI's expectations, OR keep the old name and update the Eloquent model's `$table` property. Pick whichever is least disruptive to existing code. |
| **`fileables` already exists** | Confirm columns: `file_id`, `fileable_id`, `fileable_type`, `category`, `caption`, `is_cover`, `sort_order`. Add what's missing. |
| **`files` table doesn't have `path`, `mime_type`, `size`** | The fileables migration assumes `files.id` exists; if your files table has different column names, that's fine — only the foreign key constraint on `file_id` matters. |

After reconciling, run `php artisan migrate` for any new migrations.

---

## Step 2 — Field tables

### `engineers`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint | PK |
| `code` | string, unique | e.g. `ENG-001` — human-readable identifier |
| `name` | string | "Tareq Hossain" |
| `email` | string, nullable | |
| `phone` | string, nullable | |
| `designation` | string, nullable | "Site Engineer", "Project Engineer" |
| `is_active` | boolean, default true | indexed |
| `timestamps` | | |

### `properties`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint | PK |
| `code` | string, unique | `P-101`, `P-102` |
| `name` | string | "Shyamnagar Complex" |
| `address` | text, nullable | |
| `type` | string, nullable | "Residential", "Commercial", "Residential + Commercial" |
| `status` | enum | `active` \| `inactive` — indexed |
| `total_area` | decimal(12,2), nullable | square feet |
| `land_size` | decimal(10,2), nullable | katha or decimal |
| `engineer_id` | foreignId, nullable | → `engineers.id`, `nullOnDelete` |
| `registered_at` | date, nullable | |
| `remarks` | text, nullable | |
| `softDeletes` | | for historical bookings |

### `property_floors`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint | PK |
| `property_id` | foreignId | → `properties.id`, `cascadeOnDelete` |
| `code` | string(10) | `G`, `1`, `2`, `T` (terrace) |
| `label` | string | "Ground", "Floor 1", "Terrace" |
| `sort_order` | unsignedInt, default 0 | **persists drag-reorder** — index with `property_id` |
| `floor_area` | decimal(12,2), nullable | sft |
| `remarks` | text, nullable | |
| Unique | `(property_id, code)` | one of each per property |

### `property_units`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint | PK |
| `property_id` | foreignId | → `properties.id`, `cascadeOnDelete` |
| `property_floor_id` | foreignId | → `property_floors.id`, `cascadeOnDelete` |
| `code` | string(30) | `A-101`, `S-G01`, `P-G02` |
| `type` | enum | `flat` \| `shop` \| `parking` — indexed |
| `status` | enum | `available` \| `booked` \| `sold` \| `rented` — default `available`, indexed |
| `area` | decimal(12,2), nullable | sft |
| `price` | decimal(18,3), default 0 | sale price |
| `service_charge` | decimal(18,3), default 0 | monthly |
| `rent_amount` | decimal(18,3), default 0 | only used when status = `rented` |
| `facing` | string, nullable | "North", "South-East" |
| `notes` | text, nullable | |
| `sort_order` | unsignedInt, default 0 | **persists drag-reorder within floor** |
| `softDeletes` | | |
| Unique | `(property_id, code)` | |

### `fileables`

Polymorphic pivot between the project's existing `files` table and any model.

| Column | Type | Notes |
|---|---|---|
| `id` | bigint | PK |
| `file_id` | foreignId | → `files.id`, `cascadeOnDelete` |
| `fileable_id` | bigint | |
| `fileable_type` | string | morphs index |
| `category` | string(40) | `facade`, `lobby`, `floor_plan`, `interior`, `document`, `other` |
| `caption` | string, nullable | |
| `is_cover` | boolean, default false | |
| `sort_order` | unsignedInt, default 0 | |
| `timestamps` | | |

---

## Step 3 — Models

```php
class Engineer extends Model {
    protected $fillable = ['code','name','email','phone','designation','is_active'];
    public function properties() { return $this->hasMany(Property::class); }
}

class Property extends Model {
    use SoftDeletes, HasFiles;
    protected $fillable = ['code','name','address','type','status','total_area','land_size','engineer_id','registered_at','remarks'];
    protected $casts = ['registered_at' => 'date'];
    public function engineer() { return $this->belongsTo(Engineer::class); }
    public function floors()   { return $this->hasMany(PropertyFloor::class)->orderBy('sort_order'); }
    public function units()    { return $this->hasMany(PropertyUnit::class); }
}

class PropertyFloor extends Model {
    protected $fillable = ['property_id','code','label','sort_order','floor_area','remarks'];
    public function property() { return $this->belongsTo(Property::class); }
    public function units()    { return $this->hasMany(PropertyUnit::class)->orderBy('sort_order'); }
}

class PropertyUnit extends Model {
    use SoftDeletes, HasFiles;
    protected $fillable = ['property_id','property_floor_id','code','type','status','area','price','service_charge','rent_amount','facing','notes','sort_order'];
    public function property() { return $this->belongsTo(Property::class); }
    public function floor()    { return $this->belongsTo(PropertyFloor::class, 'property_floor_id'); }
}

// app/Models/Concerns/HasFiles.php
trait HasFiles {
    public function files() {
        return $this->morphToMany(File::class, 'fileable')
            ->withPivot(['category','caption','is_cover','sort_order'])
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }
    public function cover() {
        return $this->morphToMany(File::class, 'fileable')->wherePivot('is_cover', true);
    }
}
```

---

## Step 4 — Routes & Controllers

```php
// routes/web.php
Route::prefix('properties')->name('properties.')->group(function () {
    Route::get('/',          [PropertyController::class, 'index'])->name('index');
    Route::get('/create',    [PropertyController::class, 'create'])->name('create');
    Route::post('/',         [PropertyController::class, 'store'])->name('store');
    Route::get('/{property}',          [PropertyController::class, 'show'])->name('show');
    Route::put('/{property}',          [PropertyController::class, 'update'])->name('update');

    // Floors
    Route::post('/{property}/floors',                  [FloorController::class, 'store'])->name('floors.store');
    Route::post('/{property}/floors/reorder',          [FloorController::class, 'reorder'])->name('floors.reorder');

    // Units
    Route::post('/{property}/units',                   [UnitController::class, 'store'])->name('units.store');
    Route::put('/{property}/units/{unit}',             [UnitController::class, 'update'])->name('units.update');
    Route::post('/{property}/units/reorder',           [UnitController::class, 'reorder'])->name('units.reorder');

    // Files
    Route::post('/{property}/files',                   [PropertyFileController::class, 'store'])->name('files.store');
});
```

`PropertyController@index` must hand the Blade view a `properties` collection with these aggregates available per row (so the listing-page card stats match the design):

```php
$properties = Property::with('engineer')
    ->withCount([
        'units as available_count' => fn($q) => $q->where('status','available'),
        'units as booked_count'    => fn($q) => $q->where('status','booked'),
        'units as sold_count'      => fn($q) => $q->where('status','sold'),
        'units as rented_count'    => fn($q) => $q->where('status','rented'),
    ])
    ->withSum(['units as available_value' => fn($q) => $q->where('status','available')], 'price')
    ->withSum(['units as booked_value'    => fn($q) => $q->where('status','booked')],    'price')
    ->withSum(['units as sold_value'      => fn($q) => $q->where('status','sold')],      'price')
    ->withSum(['units as rented_value'    => fn($q) => $q->where('status','rented')],    'price')
    ->get();
```

The detail page should eager-load:

```php
$property->load([
    'engineer',
    'floors.units',  // ordered by sort_order on both
    'files',         // ordered by pivot sort_order
]);
```

---

## Step 5 — Views (recreate from HTML refs)

| Blade view | Source file in `design/` |
|---|---|
| `resources/views/properties/index.blade.php` | `Properties.html` |
| `resources/views/properties/show.blade.php`  | `Property Detail.html` |

The HTML files embed a static `<script id="data" type="application/json">` block. Replace it with `@json($properties)` / `@json($property)` so Alpine/vanilla JS can hydrate the page from the server.

---

## Step 6 — Drag-and-drop endpoints

Both pages reorder via `sort_order` columns. Wire two tiny endpoints that accept an ordered array of IDs:

```php
// FloorController@reorder
public function reorder(Property $property, Request $r) {
    $ids = $r->validate(['order' => 'required|array', 'order.*' => 'integer'])['order'];
    foreach ($ids as $i => $id) {
        $property->floors()->where('id', $id)->update(['sort_order' => $i]);
    }
    return response()->noContent();
}

// UnitController@reorder
// Accepts: { "floors": { "<floorId>": [unitId, unitId, ...] } }
public function reorder(Property $property, Request $r) {
    foreach ($r->input('floors', []) as $floorId => $unitIds) {
        foreach ($unitIds as $i => $unitId) {
            PropertyUnit::where('id', $unitId)->update([
                'property_floor_id' => $floorId,
                'sort_order'        => $i,
            ]);
        }
    }
    return response()->noContent();
}
```

In `Property Detail.html`'s drag-and-drop code, after a drop call:
- `POST /properties/{id}/floors/reorder` with the new floor ID order, OR
- `POST /properties/{id}/units/reorder` with the new floor→unit ID map

---

## Step 7 — Design tokens

The HTML files use a warm-paper palette. Map to your existing design system if you have one; otherwise pull these tokens straight in:

```css
--paper:  #FCFBF7;   /* card background */
--canvas: #F2EFE7;   /* page background */
--ink-1:  #1A1814;   /* primary text */
--ink-2:  #5C5648;   /* secondary text */
--ink-3:  #9B9686;   /* muted text */
--rule:   #EAE5D9;   /* borders */
--accent: #1F3A68;   /* navy — engineer avatar, logo mark */

/* Status colors */
--av-bg: #D2E7D5; --av-fg: #1F5A2C;   /* available  — green  */
--bk-bg: #F7E6C4; --bk-fg: #7A5418;   /* booked     — amber  */
--sd-bg: #D8E4F5; --sd-fg: #1F3D72;   /* sold       — blue   */
--rt-bg: #DCD9F2; --rt-fg: #3A3582;   /* rented     — purple */
--in-bg: #EFEAE0; --in-fg: #5C5648;   /* inactive   — sand   */
```

Typography:
- Body: **Inter**, 400/500/600/700 (`https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700`)
- Numbers / codes: **IBM Plex Mono**, 400/500/600
- All numeric cells use `font-variant-numeric: tabular-nums`

---

## Step 8 — Interactions to preserve

1. **Listing page** — live search (name/code/address), status pill filter (all/active/inactive), KPI strip recalculates against the visible subset.
2. **Detail page** — building view: click a unit cell → open drawer in edit mode; click `+` on a floor → open drawer in add mode pre-filled with that floor; drag a floor badge → reorder floors; drag a unit cell → move it within a floor or across floors. Drop indicators: 3px black line on the relevant edge.
3. **Drawer** — segmented radio for status (color-coded), image dropzone, save persists to backend and re-renders the building view + table.
4. **Image carousel** in hero — prev/next, clickable thumbnails, +Add tile triggers upload.

---

## Files in this bundle

```
design/
  Properties.html        # Listing page reference (warm-paper, 5 KPI cards, search, filter, cards w/ engineer)
  Property Detail.html   # Detail page reference (building cross-section, drawer, gallery, DnD)

migrations/
  2026_05_16_100001_create_engineers_table.php
  2026_05_16_100002_create_properties_table.php
  2026_05_16_100003_create_property_floors_table.php
  2026_05_16_100004_create_property_units_table.php
  2026_05_16_100005_create_fileables_table.php

PROMPT_FOR_CLAUDE_CODE.md  # Copy-paste prompt for the Claude Code session
README.md                  # This file
```
