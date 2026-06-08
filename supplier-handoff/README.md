# Supplier module — Claude Code handoff

Build the **Supplier List** page (+ create-supplier modal) for Star Unity ERP.

**Stack:** Laravel 11 · Livewire 3.6 · Alpine.js · Tailwind CSS 4

## Start here
1. Open **`ui-reference/Suppliers.html`** in a browser — that's the exact design to match.
2. Read **`CLAUDE_CODE_PROMPT.md`** — full install steps, schema, and the rules.
3. Drop the files into your app (paths already match), wire the route, migrate + seed.

## What's included
- `app/Models/Supplier.php` — relations, casts, Active/Inactive/Blocked accessors, scopes
- `app/Livewire/Forms/SupplierForm.php` — validated Form object
- `app/Livewire/Suppliers/SupplierList.php` — filters, KPIs, save, status actions, stubs
- `resources/views/livewire/suppliers/supplier-list.blade.php` — the page (Alpine modal + menus)
- `resources/css/suppliers.css` — Tailwind 4 tokens + 1:1 ported component styles
- `database/migrations` + `database/seeders` — table + demo rows

## Two things to remember
- **`documents` json = array of file IDs only** (`[40192, 40193]`), no metadata.
- **Keep every UI button** even when its backend is a stub (Export, Download POs, View, Edit).
  Snappy interactions (modal, dropdowns) run in **Alpine**, not Livewire round-trips.
