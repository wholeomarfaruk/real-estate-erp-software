# Supplier Detail — Claude Code handoff

Builds the **Supplier Detail** screen for Star Unity ERP, split into **four separate
Livewire modules** sharing one hero/KPI/tab shell.

**Stack:** Laravel 11 · Livewire 3.6 · Alpine.js · Tailwind CSS 4

## Start here
1. Open **`ui-reference/Supplier Detail.html`** — the exact design to match.
2. Read **`CLAUDE_CODE_PROMPT.md`** — architecture, install, and the rules.
3. Drop files into your app, add the routes, `npm run dev`.

## Tabs = modules (linked on the nav with `wire:navigate`)
```
Supplier
 ├─ Details            app/Livewire/Suppliers/Show/Details.php
 ├─ Invoices           app/Livewire/Suppliers/Show/Invoices.php   (+ payment modal)
 ├─ Purchase Orders    app/Livewire/Suppliers/Show/Orders.php
 └─ Advance Payments   app/Livewire/Suppliers/Show/Advances.php
```
Shared chrome: `resources/views/components/supplier/shell.blade.php`
Styles: `resources/css/supplier-detail.css` (1:1 port of the mockup)

## Two things to remember
- **Reproduce the UI exactly.** If a button's backend isn't built, keep the button —
  it's already wired to a stub. Don't delete UI.
- **`documents` = array of file IDs only** (no metadata).

Opens from the supplier list's **View detail** → `route('suppliers.show.details', $id)`.
Every module ships demo data so it renders immediately; each has a comment showing the
real query to swap in.
