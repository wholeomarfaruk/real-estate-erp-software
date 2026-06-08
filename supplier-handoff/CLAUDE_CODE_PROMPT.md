# Supplier Module — Laravel implementation brief

> Hand this folder to **Claude Code**. It builds the **Supplier List** page (with a
> create-supplier modal) for the Star Unity ERP, faithful to the approved mockup.
>
> **Source of truth for the design:** `ui-reference/Suppliers.html` — open it in a
> browser. The Blade view must render **pixel-identical** to it.

---

## 0. Stack (fixed)

- **Laravel 11+, PHP 8.2+**
- **Livewire 3.6** — data, filters, pagination, create form, status actions
- **Alpine.js** (ships with Livewire 3) — modal open/close, row kebab menus,
  document-row UI. *All pure-UI interactions run in Alpine so they're instant and
  never round-trip to the server* (this was an explicit requirement).
- **Tailwind CSS 4** (CSS-first config) — see `resources/css/suppliers.css`

**Interaction split — keep it this way:**

| Concern | Handled by | Why |
|---|---|---|
| Search, status/balance/sort filters, pagination | Livewire (`wire:model.live`, `wire:click`) | needs the DB |
| Save supplier, activate/deactivate/block | Livewire actions | writes data |
| Modal show/hide, kebab dropdowns, doc rows | **Alpine** (`x-data`, `x-show`) | instant, no latency |

---

## 1. Files in this package

```
app/
  Models/Supplier.php                      ← model: relations, casts, status accessors, scopes
  Livewire/
    Forms/SupplierForm.php                 ← Livewire Form object (validation + toAttributes())
    Suppliers/SupplierList.php             ← the component: filters, KPIs, save(), stubs
database/
  migrations/..._create_suppliers_table.php
  seeders/SupplierSeeder.php               ← demo rows matching the mockup
resources/
  css/suppliers.css                        ← Tailwind 4 @theme tokens + 1:1 ported component classes
  views/livewire/suppliers/supplier-list.blade.php
ui-reference/
  Suppliers.html                           ← the approved design (DO NOT change the look)
```

Drop each file into the matching path in your app. Adjust namespaces only if your
app differs.

---

## 2. Install steps

```bash
# 1. Move files into place (paths above already match a standard app)

# 2. Tailwind 4 — import the module CSS from your main stylesheet
#    resources/css/app.css:
#       @import "tailwindcss";
#       @import "./suppliers.css";

# 3. Fonts — add to your layout <head> (or self-host):
#    Inter + IBM Plex Mono (see top of suppliers.css)

# 4. Route — resources/views uses a full-page Livewire component:
#    routes/web.php:
#       use App\Livewire\Suppliers\SupplierList;
#       Route::get('/admin/supplier/suppliers', SupplierList::class)
#            ->name('suppliers.index');

# 5. Migrate + seed
php artisan migrate
php artisan db:seed --class=Database\\Seeders\\SupplierSeeder

# 6. Build assets
npm run dev
```

The component is route-registered as a **full-page component**, so it needs a layout
with `{{ $slot }}` (default `resources/views/components/layouts/app.blade.php`). Make
sure `@livewireStyles`/`@livewireScripts` (or `@vite`) are in that layout.

---

## 3. Database — `suppliers` (final schema)

Already written in the migration. Key points:

- `code` — unique, **auto-generated** `SUP-000001` in `Supplier::booted()`
- `status` (bool) + `is_blocked` (bool) → combine into **Active / Inactive / Blocked**
- `image_id`, `cover_image_id` — attachment IDs (FK to your media table)
- **`documents` (json) = an array of FILE IDs ONLY** — e.g. `[40192, 40193]`.
  No filename/size/metadata in this column. Cast is `'array'`.
- `created_by` / `updated_by`, `SoftDeletes`

---

## 4. Where the numbers come from

The list shows two computed figures per supplier — **wire them to your purchase
ledger** (the models are referenced in `Supplier` relations):

- **Balance = advance − due** (net position)
  - `< 0` → we owe them → **payable** (amber) / **overdue** (rose, when unpaid invoices exist)
  - `> 0` → **advance** held by supplier (green)
  - `0`  → **settled**
  - Computed in `SupplierList::render()` as a correlated sub-select on
    `purchase_payables(advance_amount, due_amount)` so it can be **sorted & filtered in SQL** (no N+1). Adjust the columns to your real ledger.
- **Invoice count** — `withCount('purchaseInvoices')` + an `unpaid_invoices_count`
  filtered count. Shown as `N` + “X unpaid”.

The KPI strip (`SupplierList::stats()`) aggregates totals the same way. Until the
purchase tables exist, balance shows `৳ 0 / settled` and counts show `0` — expected.

---

## 5. The "always show the button" rule

**Every button in the mockup stays in the markup even if its backend isn't ready.**
Don't delete a control just because the method is a stub. Already done for:

- **Export** → `SupplierList::export()` is a stub that toasts “coming soon”. Button stays.
- **Download POs** (row menu) → `downloadPo()` stub.
- **View / Edit** (row + menu) → `view()` / `edit()` stubs with TODO routes.

Fill these in later; the UI never regresses in the meantime.

---

## 6. Create-supplier modal

- Opened by the header **“New supplier”** button — **Alpine** (`modalOpen`), instant.
- Fields bound to the **`SupplierForm`** Form object with validation.
- Status is a 3-way segmented control (`active|inactive|blocked`); `toAttributes()`
  maps it back to the `status` + `is_blocked` columns.
- **Documents**: Alpine manages the visible rows and pushes the file-ID array into
  `form.documents` via `$wire.set(...)`. Replace the demo `attach()` with your real
  uploader (Livewire `WithFileUploads` or your media picker) that returns a file ID.
- On `save()` success the component dispatches `supplier-saved`; Alpine closes the
  modal. A `toast` event is also dispatched — hook it to your notification system.

---

## 7. Do / Don't

- ✅ Keep `resources/css/suppliers.css` class names — they make the page match the mockup exactly.
- ✅ Keep filters on Livewire (correct server-side pagination) and chrome on Alpine (speed).
- ❌ Don't store metadata in `documents` — **file IDs only**.
- ❌ Don't invent new colours/fonts — tokens are in `suppliers.css` `@theme`.
- ❌ Don't remove UI buttons whose backend isn't built yet — stub the method instead.

---

## 8. Next (not in this package)

- **Supplier detail (View) page** — header, 3 info cards (Basic / Contact / Compliance),
  4 stat counters, panels (Latest Purchases / Pending Invoices / Payment History).
- Reuse the modal in **edit** mode (`edit($id)` → fill `$form` → same modal).

Ask the user before creating tables that may already exist
(`users`, media/attachments, `purchase_invoices`, `purchase_payables`).
