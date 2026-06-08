# Supplier Detail — Laravel implementation brief

> Hand this folder to **Claude Code**. It builds the **Supplier Detail** screen —
> a hero topbar + KPI strip + tab navigation, split into **four separate Livewire
> modules** (Details · Invoices · Purchase Orders · Advance Payments) that share
> one chrome and link to each other on the nav tabs.
>
> **Design source of truth:** `ui-reference/Supplier Detail.html` — open it in a
> browser. The rendered Blade must be **pixel-identical** to it.
>
> **Golden rule:** reproduce the UI exactly. If a backend method/route/table for a
> button doesn't exist yet, **keep the button** — wire it to a stub (already done)
> and fill it in later. The UI must never regress because the backend isn't ready.

---

## 0. Stack (fixed)

- Laravel 11+, PHP 8.2+
- **Livewire 3.6** — one full-page component per tab/module
- **Alpine.js** (ships with Livewire 3) — modal show/hide, the trend-chart drawing
- **Tailwind CSS 4** (CSS-first) — `resources/css/supplier-detail.css`

Interaction split (keep it this way):

| Concern | Handled by |
|---|---|
| Tab navigation between modules | `<a wire:navigate>` (route links — SPA feel) |
| Tables, KPIs, record-payment, status data | Livewire (server) |
| Payment modal open/close, escape-to-close | Alpine (instant) |
| Trend SVG chart | Alpine (`x-data="supplierTrend"`), drawn from PHP data |

---

## 1. Architecture — one shell, four modules

```
                       ┌─────────────────────────────────────────┐
   <x-supplier.shell>  │  breadcrumb · HERO · KPI strip · TAB NAV │  ← shared chrome
                       └─────────────────────────────────────────┘
                                       │ {{ $slot }}
        ┌───────────────┬──────────────┼───────────────┬────────────────┐
   Details (1)     Invoices (2)   Purchase Orders (3)  Advance Payments (4)
   route:.details  route:.invoices route:.orders        route:.advances
```

- **`resources/views/components/supplier/shell.blade.php`** — the breadcrumb, hero
  topbar, KPI strip and tab nav. Each tab view wraps its body in this component and
  passes `:supplier` + `active="…"`. The tab nav renders `<a wire:navigate>` links
  to the four routes, highlighting the active one.
- Each module is its **own full-page Livewire component** — separate file, separate
  route, separate concern. Swapping tabs navigates routes (no giant single component).

```
app/Livewire/Suppliers/Show/
  Details.php      → livewire.suppliers.show.details   (summary, relations, trend, contact, docs, notes, activity)
  Invoices.php     → livewire.suppliers.show.invoices  (invoice table + PAYMENT MODAL)
  Orders.php       → livewire.suppliers.show.orders    (PO table + View/PDF)
  Advances.php     → livewire.suppliers.show.advances  (advance table + PDF)
resources/views/
  components/supplier/shell.blade.php
  livewire/suppliers/show/{details,invoices,orders,advances}.blade.php
resources/css/supplier-detail.css
routes/web.php      (snippet)
ui-reference/Supplier Detail.html
```

---

## 2. Install

```bash
# 1. Move files into the matching paths (already standard).

# 2. Requires App\Models\Supplier (from the earlier supplier-handoff package).
#    The views use null-safe fallbacks ($supplier->contact_person ?? 'Rafiqul Islam'),
#    so they render with demo data even before fields/relations exist.

# 3. Tailwind 4 — import the module CSS from resources/css/app.css:
#       @import "tailwindcss";
#       @import "./supplier-detail.css";

# 4. Fonts — add Inter + IBM Plex Mono <link> to your layout <head>
#    (see top of supplier-detail.css).

# 5. Routes — add the snippet from routes/web.php. Needs a layout with {{ $slot }}
#    and @livewireScripts / @vite (Alpine + wire:navigate).

php artisan livewire:layout   # if you don't already have components/layouts/app.blade.php
npm run dev
```

Open `/admin/supplier/suppliers/{id}` → lands on **Details**. The tabs navigate to
the other three modules.

---

## 3. The four modules

### Details (`Details.php`)
Account-summary progress bar, **linked-records** grid (cards link to the other tabs
via `wire:navigate`), **purchase & payment trend** SVG chart (Alpine `supplierTrend`
draws it from the `$trend` array — replace with a real monthly aggregate), contact &
compliance facts, **documents** (rendered from the `documents` json = *array of file
IDs only*), notes, recent activity.

### Invoices (`Invoices.php`) — has the payment modal
- Table of purchase invoices (`@foreach $this->invoices`).
- Each row: **Pay now** when `due > 0`, else **Details**; plus a **PDF** button.
- Clicking opens **one modal** (`openPay($id)` → Alpine `pay-modal-open`): amount
  summary, a **payment form** (only when there's a balance) with Full-due / Half
  quick-fills, and the **payment history** list. Paid invoices show the same modal
  titled "Payment details" with the form hidden.
- `recordPayment()` validates and is a **stub** — wire it to your ledger
  (`PurchasePayment::create(...)`, then recompute the invoice paid/due/status).

### Purchase Orders (`Orders.php`)
Table + **View** (stub → PO detail route) and **PDF** (stub) per row.

### Advance Payments (`Advances.php`)
Table of advances (amount / adjusted / balance) + **PDF** per row, "New advance" button.

> Every module ships **demo data** in a `#[Computed]` so the UI renders immediately.
> Each has a one-line comment showing the real query to swap in
> (`$this->supplier->purchaseInvoices()->paginate()` etc.).

---

## 4. Open from the supplier list

In the list component (`supplier-handoff`) point "View detail" at this screen:

```php
public function view(int $id)
{
    return $this->redirectRoute('suppliers.show.details', $id);
}
```

or make the row a link: `<a href="{{ route('suppliers.show.details', $s) }}" wire:navigate>`.

---

## 5. Do / Don't

- ✅ Keep `supplier-detail.css` class names — they make the page match the mockup exactly.
- ✅ Keep each tab a separate component/route; link them with `wire:navigate`.
- ✅ Keep every button even when its method is a stub (Export, PDF, View, Record payment, New advance…).
- ❌ Don't store metadata in `documents` — **file IDs only**.
- ❌ Don't invent colours/fonts — tokens live in `supplier-detail.css` `@theme` / `:root`.
- ❌ Don't merge the four tabs into one mega-component — the split is intentional.

---

## 6. Swap demo data → real queries (checklist)

- [ ] `Details::render()` `$trend` → monthly purchase/payment aggregate
- [ ] `Invoices::invoices()` → `$this->supplier->purchaseInvoices()->withPayments()->paginate(15)`
- [ ] `Invoices::recordPayment()` → persist payment + recompute invoice
- [ ] `Orders::orders()` → `$this->supplier->purchaseOrders()->paginate(15)`
- [ ] `Advances::advances()` → `$this->supplier->purchaseFunds()->paginate(15)`
- [ ] Shell KPI/badge numbers → real counts/sums on the Supplier model
- [ ] `downloadPdf()` / `view()` stubs → real PDF streams / detail routes
