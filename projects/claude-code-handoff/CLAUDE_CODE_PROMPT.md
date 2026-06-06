# Project Module — Laravel Implementation Brief

> Hand this folder to Claude Code in VS Code. It contains everything needed to build the **Project module** of a Real Estate / Construction ERP: UI reference mockups, database migrations, enums, and this brief.

---

## 0. What you are building

A **Project module** with 5 tabs. Each tab is a separate page/route, linked by a shared tab bar:

```
Project
 ├─ Details        → project info, team, construction progress, documents
 ├─ Estimates      → BOQ-style budget versions + approval workflow
 ├─ Consumption    → material usage (estimate vs actual, from inventory issues)
 ├─ Expenses       → labour + other costs (estimate vs actual)
 └─ Reports        → consolidated cost analysis dashboard
```

**Pixel-accurate HTML mockups for every page are in `ui-reference/`. Open them in a browser — they are the source of truth for layout, colours, spacing, and component structure.** Recreate them as Blade views (or your frontend stack) faithfully.

---

## 1. Tech assumptions

- **Laravel 11+**, PHP 8.2+
- MySQL / MariaDB
- Blade + your existing CSS framework (the mockups use plain CSS — port the design tokens below)
- Auth + users table already exist
- A `media_files` table already exists for uploads (referenced by `image_id`, `documents`, `attachments`, `files`)
- An `accounts` table (chart of accounts) and `bank_accounts` table already exist (see banking module). Expenses reference `account_id`.

If any of these don't exist, ask the user before scaffolding them.

---

## 2. Design tokens (port these exactly)

```css
--ink:#14181f; --ink-2:#2a2f3a; --muted:#6b7280;
--rule:#e4e4e7; --paper:#ffffff; --canvas:#f6f6f7;
--accent:#0d2a4a;            /* deep navy — primary */
--accent-soft:#eaf0f8;
--ok:#1f6f43; --warn:#a16207; --info:#0e63a8; --danger:#8a1212;
/* cost-type colours */
--material:#0d2a4a; --labour:#0e7490; --other:#a16207;
```

Fonts: **Instrument Serif** (headings/figures), **Inter** (body), **JetBrains Mono** (codes, numbers, IDs).
Currency: always `BDT 1,50,000.00` format (Bangladeshi). Use a helper `bdt($n)` → `'BDT '.number_format($n, 2)`.

---

## 3. Database

Migrations are in `database/migrations/`. Summary:

| Table | Purpose |
|---|---|
| `projects` | Project header (code, name, types, engineer, budget, status, dates, docs) |
| `project_estimates` | Estimate versions (header + approval) |
| `project_estimate_items` | BOQ line items (material/labour/overhead, qty×rate) |
| `expense_categories` | Labour / Other categories |
| `project_expenses` | Expense entries (vendor, invoice, amount, method, account) |

**Data sources (read-only on their pages):**
- **Consumption** reads from `inventory_transactions` (or `stock_movements`) where `transaction_type = 'issue'` and `project_id = X`. **No manual entry** on this page — it's a view over inventory issues vs estimate.
- **Reports** aggregates everything: material (from consumption), labour + other (from expenses), against the approved estimate.

See `specs/` for the full column-by-column spec the user provided.

---

## 4. Page-by-page requirements

### 4a. Details (`ui-reference/Project Details.html`)
- Hero: cover image, code, name, type badges, status badge, construction-progress bar
- Tab bar (Details active)
- KPI strip: Budget · Spent · Remaining · Days to handover
- Cards: Basic Info · Location & Area · Timeline (visual node bar) · **Construction Progress** (overall ring + phase-by-phase bars) · Description · Documents
- Side: **Project Team** (Chief Engineer + Site Engineer) · Record meta · quick links to other tabs
- Note: add `chief_engineer_id` to projects if storing chief engineer (currently only `site_engineer_id` in spec)

### 4b. Estimates (`ui-reference/Project Estimates.html`)
- Version cards (V1/V2/V3) with status chips + "Current Approved Version" badge
- Summary cards: Material / Labour / Other / Grand Total
- **Budget Monitoring**: Estimated · Actual Consumed · Remaining · Budget Difference (flips red when over)
- **Version Comparison** widget (V2 vs V1, % deltas) + **Attachments** panel
- Estimate info bar with **"Approved & Locked"** state → disables edit, only "Duplicate as new version" stays active
- **BOQ table** grouped by work phase, cost-type badges, Optional tag, phase subtotals, grand total
- Filters (cost type, phase) + Export PDF/Excel on the BOQ header
- Status workflow: `draft → submitted → approved → rejected`

### 4c. Consumption (`ui-reference/Project Consumption.html`)
- KPI cards: Estimated Material · Consumed · Remaining · **Over Consumption** (red)
- Over-consumption alert banner → "View materials" filters table
- Table grouped by phase: Material · Unit · Estimated Qty · Consumed Qty · Remaining Qty · Extra Qty · Progress % · **auto status** (Not Started / In Progress / Completed / Over Consumed)
- **No rates/cost in the table** — qty only
- Row click → **drawer**: Procurement Insight (Estimated / Purchased / Consumed) + Inventory Issues table (Date / Voucher / Store / Qty)
- Status logic: `consumed==0`→Not Started; `0<consumed<est`→In Progress; `consumed==est`→Completed; `consumed>est`→Over Consumed

### 4d. Expenses (`ui-reference/Project Expenses.html`)
- KPI cards: Total · This Month · Labour · Other
- **Estimate vs Actual** widget by category (estimate / actual / remaining / utilisation; over-categories flagged red)
- Filter tabs: All / Labour / Other / Drafts
- Table: Expense No · Category (labour/other badge) · **Vendor** · Method · Files · Amount · Status
- Row click → drawer: details + **Attachments** (Invoice / Bill Photo / Money Receipt / Challan) + description + approve/edit actions
- Note: spec adds **`vendor_id`** and **`invoice_no`** — both important, include them
- Status: `draft → approved`

### 4e. Reports (`ui-reference/Project Reports.html`)
- KPI strip: Approved Budget · Total Spent · Remaining · **Budget Difference**
- **Cost Composition** donut (Material / Labour / Other)
- **Estimate vs Actual** bars per cost type
- **Phase-wise Cost Summary** table (estimate vs actual per phase)
- **Monthly Spend Trend** stacked bar chart
- **Budget Difference** panel (over/under-budget highlights)
- All read-only; Export PDF/Excel

---

## 5. Suggested build order

1. `projects` migration + Project model + Details page (read view first, then create/edit form)
2. `expense_categories` seeder (the labour/other categories from spec)
3. `project_estimates` + `project_estimate_items` + Estimates page + approval workflow
4. `project_expenses` + Expenses page + estimate-vs-actual
5. Consumption page (read-only view over inventory issues)
6. Reports page (aggregation queries)

---

## 6. Validation rules (from spec)

- **Estimate item:** exactly one of `material_id` OR `expense_category_id` (never both)
- **Cost type:** `material | labour | overhead | indirect`
- **Work phase:** `foundation | structure | brick_work | plaster | electrical | plumbing | finishing | other`
- **Estimate status:** `draft | submitted | approved | rejected`
- Approved estimates are **locked** — edits require duplicating to a new version
- `estimated_amount = estimated_qty × estimated_rate` (compute server-side, don't trust client)

---

## 7. Exports

Both PDF and Excel buttons appear on Estimates (BOQ), Consumption, Expenses, Reports.
- PDF: `barryvdh/laravel-dompdf` (or Browsershot for pixel-perfect)
- Excel: `maatwebsite/excel`

---

## 8. What NOT to do

- ❌ Don't put rates/cost in the Consumption table — it's material-usage only
- ❌ Don't allow manual consumption entry — it's derived from inventory issues
- ❌ Don't invent new colours/fonts — use the tokens in §2
- ❌ Don't merge `accounts` and `bank_accounts` — keep them separate (see banking module notes)

---

Start by reading `ui-reference/Project Details.html` in a browser, then scaffold the `projects` table and Details page. Ask the user before creating tables that may already exist (`media_files`, `accounts`, `inventory_transactions`).
