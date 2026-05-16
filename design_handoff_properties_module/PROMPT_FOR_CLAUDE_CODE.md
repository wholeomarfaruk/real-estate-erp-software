# Prompt for Claude Code

Copy everything below the line into a new Claude Code session inside your Laravel project root.

---

I have a real-estate ERP for **Star Unity Development Ltd.** built on Laravel + Blade. I need you to add two new pages: a **Properties listing** and a **Property detail** page, with full CRUD, drag-and-drop reorder of floors and units, and image upload via my existing `files` table.

I've placed a `design_handoff_properties_module/` folder at the project root containing:

- `design/Properties.html` — high-fidelity HTML mock of the listing page
- `design/Property Detail.html` — high-fidelity HTML mock of the detail page (the building cross-section with drag-and-drop is the centerpiece)
- `migrations/` — five Laravel migration files for `engineers`, `properties`, `property_floors`, `property_units`, and a polymorphic `fileables` pivot
- `README.md` — full spec with field tables, model definitions, routes, controllers, drag-and-drop endpoints, and design tokens

## Please do the following, in order

### 1. Inspect what already exists

Before doing anything, run:

```bash
php artisan db:show
php artisan db:table properties 2>/dev/null
php artisan db:table property_floors 2>/dev/null
php artisan db:table property_units 2>/dev/null
php artisan db:table property_links 2>/dev/null


```

Compare what's there against the field tables in `design_handoff_properties_module/README.md` → **Step 2 — Field tables**.

- If a table exists with the right columns: **skip** the new migration.
- If a table exists but is missing columns this UI needs: write a **new `ALTER`-style migration** that adds only the missing columns. Do not drop or re-create.
- If a table exists under a different name (e.g. `property_links` instead of `property_units`): rename to match the spec OR keep the old name and override `$table` on the Eloquent model — pick whichever is least disruptive.

Read the migration files in `migrations/` to see exact column definitions. Show me a summary of what you found and what you propose to change before running anything.

### 2. Run migrations after I approve

Once I confirm the reconciliation plan, copy the necessary migrations into `database/migrations/`, run `php artisan migrate`, and seed a couple of sample properties so the page isn't empty when I open it.

### 3. Build the models

Create `Engineer`, `Property`, `PropertyFloor`, `PropertyUnit` models with the exact relations described in **Step 3 — Models** of the README. Add the `HasFiles` trait. Follow the project's existing model conventions (namespace, base class, casts location, etc.) — match what's already in `app/Models/`.

### 4. Build the routes and controllers

Wire up `routes/web.php` and `app/Http/Controllers/` per **Step 4 — Routes & Controllers** and **Step 6 — Drag-and-drop endpoints** in the README. Use Form Requests for validation.

The listing controller MUST pass through these aggregate counts and value sums per property (the cards depend on them) — exact `withCount` / `withSum` snippet is in the README.

### 5. Recreate the views from the HTML refs

Convert the two HTML files in `design/` into Blade views:

- `resources/views/properties/index.blade.php` — from `Properties.html`
- `resources/views/properties/show.blade.php` — from `Property Detail.html`

**This is hi-fi** — match the design pixel-perfectly. Pull the colors, type, spacing from **Step 7 — Design tokens** in the README. If the project already has Tailwind tokens or CSS variables for the warm-paper palette, use those instead of hard-coding hex.

The HTML mocks embed a `<script id="data" type="application/json">` block — replace each one with `@json($properties)` / `@json($property)` so the existing JS hydrates from the server.

Wrap each view in the project's existing layout (`@extends('layouts.app')` or whatever's in use). Don't reinvent navigation, sidebar, breadcrumbs — fit into what's there.

### 6. Wire up drag-and-drop persistence

The detail page's JS reorders floors and units in memory. After every drop, POST the new order to the two endpoints from **Step 6**. Use CSRF tokens via the standard Laravel mechanism (`<meta name="csrf-token">` + a small `fetch` wrapper).

### 7. Wire up file uploads

The drawer and gallery let users upload images. Hook them to the existing `files` table via the `fileables` morph pivot. Files for a `Property` get `category` values like `facade`, `lobby`, `floor_plan`, `document`, `interior`; files for a `PropertyUnit` get `interior`, `floor_plan`, `document`. Set `is_cover` on the first uploaded image of each property.

### 8. Verify

After everything compiles:

```bash
php artisan route:list | grep properties
php artisan migrate:status
```

Visit `/properties` and `/properties/{id}` in the browser, confirm:
- Cards render with engineer name + avatar
- KPI strip totals match seeded data
- Status filter and search work live
- Detail page building view shows all floors top-down with colored unit cells
- Dragging a floor badge reorders, dragging a unit reorders/moves across floors, both persist after refresh
- Clicking a unit cell opens the drawer in edit mode; clicking + on a floor opens it in add mode
- Image upload through the drawer creates a `files` row and a `fileables` row

Walk me through anything that needs my decision before changing it. Don't bulk-edit the codebase silently.
