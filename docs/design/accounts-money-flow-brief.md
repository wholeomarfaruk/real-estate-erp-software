# Design Brief — Accounts: Money In, Money Out, Transactions

Design the Accounts area of a real-estate ERP used by a Bangladeshi property developer. Staff use it to record money coming in, money going out (always with a purpose), move money between their own accounts, approve payment requests, and look up any transaction with its double-entry detail.

Build it as a desktop-first admin UI (works down to tablet). Currency is **BDT**, shown as `BDT 1,25,000.00` style amounts in a monospace font, with 2 decimals.

---

## 1. Visual language (match the existing Banking screens)

Keep the look of the earlier Banking Management / Bank Accounts / Transactions designs.

| Token | Value | Use |
|---|---|---|
| `--canvas` | `#f6f6f7` | page background |
| `--paper` | `#ffffff` | cards, tables, drawers |
| `--ink` | `#14181f` | main text |
| `--muted` | `#6b7280` | secondary text |
| `--rule` / `--rule-soft` | `#e4e4e7` / `#ececec` | borders, dividers |
| `--accent` / `--accent-soft` | `#0d2a4a` / `#eaf0f8` | primary actions, active tab |
| `--ok` / `--ok-bg` / `--ok-bd` | `#1f6f43` / `#e9f4ee` / `#bfddc8` | **Money In**, completed |
| `--danger` / `--danger-bg` / `--danger-bd` | `#8a1212` / `#fbeaea` / `#f1c2c2` | **Money Out**, rejected |
| `--info` / `--info-bg` / `--info-bd` | `#0e63a8` / `#e9f2fb` / `#c2dcf3` | **Transfer / neutral**, approved |
| `--warn` / `--warn-bg` / `--warn-bd` | `#a16207` / `#fef9e7` / `#f3e3a8` | pending, adjustments |
| `--primary` / `--primary-bg` | `#5b3a99` / `#f0eafa` | advances |

Fonts: **Inter** (UI), **JetBrains Mono** (amounts, IDs, account codes, dates in tables), **Instrument Serif** (page titles only).

Cash & bank account type colours (used on account cards and chips):
Bank `#0d2a4a` · Cash `#b45309` · MFS (bKash/Nagad/Rocket) `#be185d` · Wallet/Card `#6d28d9`.

Direction is always shown with colour **and** a sign/arrow, never colour alone: In = `↓ +` green, Out = `↑ −` red, Transfer/Neutral = `⇄` blue.

---

## 2. Core concepts the UI must make clear

**Cash & bank accounts** — where money physically sits. Types: Bank, Cash, MFS, Wallet. Each one is linked to a ledger account, and its balance comes from that ledger.

**Transaction** — one money event. It has a header (date, type, method, reference no, voucher no, party name/phone, notes, attachments) and **two or more lines**. Each line hits one ledger account as a Debit or a Credit. Total debits always equal total credits.

**Direction (report group)** — every transaction type falls into one of three groups. This drives the In/Out colouring everywhere.

**Purpose** — what the money is for. It has two levels:
1. **Transaction type** (fixed list, see §3), and
2. **Category** (an editable tree managed by admins, see §4).

**Payment request** — money movements are first raised as requests and only hit the ledger when completed. Workflow: **Pending → Approved → Completed**. A request can be **Rejected** (with a reason) while Pending or Approved. There is no "Released" step.

**Entry method** — how the money moved: Cash, Bank, Cheque, bKash, Nagad, Rocket, Card, Bank Transfer, Journal Entry, Adjustment.

---

## 3. Transaction types by direction

**Money In (Receipt)**
- Income
- Customer Receipt
- Advance Receipt
- Owner Investment
- Loan Received

**Money Out (Payment)**
- Expense
- Office Expense
- Supplier Payment
- Purchase
- Salary Payment
- Labor Bill Payment
- Equipment Rent Payment
- Transportation Payment
- Utility Bill Payment
- Material Consumption
- Advance Payment
- Owner Withdrawal
- Loan Repayment

**Neutral** (no net in/out for the company)
- Transfer
- Adjustment
- Reverse
- Opening Balance
- Advance (Legacy)

Payment requests can also come from other modules. These sources are shown as their own badges: **Payroll**, **Employee Advance**, **Supplier** (invoice payment), **Landowner**, **Other**.

---

## 4. Purpose categories (seeded defaults, editable tree)

- **Income:** Property Sale, Property Rent, Booking Money, Installment Receive, Service Income, Commission Income, Other Income
- **Expense**
  - Project Expense: Material Purchase, Labor Cost, Contractor Payment, Utility Bills, Transport & Fuel, Maintenance, Equipment Rent, Security Expense
  - Office Expense: Office Rent, Internet & Communication, Printing & Stationery, Software Subscription
  - Payroll: Staff Salary, Bonus, Allowance
  - Marketing: Advertisement, Campaign Expense, Client Entertainment
  - Government & Legal: Trade License, VAT & Tax, Registration Fees, Legal Fees
  - Supplier Bill: Material Supply, Service Charge, Equipment Supply, Raw Material
  - Others
- **Advance:** Employee Advance, Supplier Advance, Customer Advance
- **Transfer:** Bank Transfer, Cash Transfer, MFS Transfer
- **Adjustment:** Advance Adjustment, Due Adjustment, Correction, Balance Adjustment
- **Opening Balance**

Categories can be active/inactive, and some are **locked** (system categories that cannot be edited or deleted).

---

## 5. Screens to design

The Accounts area has a shared tab bar: **Overview · Cash & Banks · Money In · Money Out · Transfers · Payment Requests · Transactions · Categories · Chart of Accounts · Reports**.

### 5.1 Overview (money flow dashboard)
- KPI row:
  - Total balance (all active accounts)
  - Today In
  - Today Out
  - Net today
  - Pending requests (count + amount)
  - Approved, awaiting completion (count + amount)
- Balance by account type: Bank / Cash / MFS / Wallet, each showing amount and account count.
- In vs Out over the last 30 days: paired bars per day, with a toggle for 7 / 30 / 90 days.
- Top purposes this month: two ranked lists, Money In by category and Money Out by category, with an amount and share bar.
- Recent transactions: the latest 10 rows in compact form.
- Requests needing action: Pending and Approved requests, with inline Approve / Complete / Reject.

### 5.2 Cash & Banks
- Totals bar: total balance and active account count, plus a chip for each account type.
- Account cards in a 3–4 column grid. Each card shows:
  - type colour strip and logo initial
  - bank name and account code
  - masked account number (`•••• 4821`)
  - branch / holder name
  - current balance (large, mono)
  - today's inflow (green) and outflow (red)
  - status (Active / Inactive)
  - quick actions: **Money In**, **Money Out**, **Transfer**, **View**
- Filters: search (name, account number, code), type, status.
- Detail drawer:
  - full bank info: holder, branch, routing code, SWIFT, address, phone, email
  - linked ledger account
  - attached files
  - balance
  - last 5 transactions, showing this account's own Dr/Cr
  - "Open full ledger" link
- Create/Edit account modal. Fields: type, code, bank name, A/C number, holder name, branch, routing code, SWIFT, address, phone, email, linked ledger account, note, status.

### 5.3 Money In (receive with purpose)
A form, opened as a page or a large modal, that creates a **payment request** in Pending status.

| Field | Notes |
|---|---|
| Receive into | Cash/bank account picker showing type colour, name, masked number and current balance |
| Type | Money-In types from §3 |
| Purpose category | Filtered tree for the chosen type; required for Advance |
| Amount | BDT, > 0 |
| Date | defaults to today |
| Method | entry method |
| Received from | name and phone (customer, owner, lender…) |
| Linked record | optional: project, property/booking, customer |
| Reference no / Voucher no | optional |
| Description | required, max 500 |
| Notes | optional |
| Attachments | receipt, cheque or slip images and PDFs |

- Right-side live preview of the double entry, for example:
  - **Dr** City Bank – 4821 BDT 50,000.00
  - **Cr** Booking Money (Income) BDT 50,000.00
  - a "Balanced ✓" badge
- After submitting: a toast and the new request in the Pending list.

### 5.4 Money Out (pay with purpose)
Same layout as Money In, but in red tones.

| Field | Notes |
|---|---|
| Pay from | Cash/bank account picker; warn when the amount is more than the balance |
| Type | Money-Out types from §3 |
| Purpose category | Filtered tree, e.g. Project Expense → Labor Cost |
| Amount, Date, Method | |
| Paid to | name and phone (supplier, employee, contractor, landowner) |
| Linked record | optional: project, supplier invoice, PO, employee |
| Reference no / Voucher no / Description / Notes / Attachments | as above |

- Double-entry preview, for example:
  - **Dr** Labor Cost (Expense) BDT 18,500
  - **Cr** Petty Cash BDT 18,500

### 5.5 Transfers (between own accounts)
- Fields: From account, To account (cannot be the same), amount, date, method, reference, notes.
- Show both balances before and after the transfer.
- Preview: **Dr** To-account / **Cr** From-account.
- History list of transfers, with from → to shown as two chips and an arrow.

### 5.6 Payment Requests (approval queue)
- Status KPI tiles, which also act as filters: **Pending · Approved · Completed · Rejected**, each with a count.
- Filters: search (request no, description), source/type, account, status.
- Table columns:
  - Request no (`BPR-261028-00042`, mono)
  - Date
  - Direction icon
  - Source/type badge
  - Purpose category
  - Party
  - Account
  - Amount
  - Status pill
  - Actions
- Row actions:
  - Pending → **Approve**, **Reject**
  - Approved → **Complete**, **Reject**
  - Completed / Rejected → view only
- Detail drawer:
  - header with amount and status
  - request details
  - linked source record (payroll run, invoice, employee advance…)
  - planned double entry (Dr/Cr accounts and amounts)
  - timeline: Requested by → Approved by → Completed by / Rejected by, each with a timestamp
  - once completed, the posted ledger transaction (`TXN-000123`) with its Dr/Cr lines and totals
  - sticky action buttons at the bottom
- Reject modal: required reason (5–500 characters).
- Complete: a confirmation step that shows exactly which accounts will be debited and credited.

### 5.7 Transactions (ledger)
- KPI strip: Total In, Total Out, Net Flow, Transaction count. The KPIs follow the current filters.
- Filters: search (reference no, voucher no, party, notes), type, method, account, date range (with presets: Today, This week, This month, Custom).
- Table columns:
  - Date/time
  - TXN no
  - Voucher no
  - Type badge (direction colour)
  - Purpose
  - Party
  - Method
  - Account(s)
  - Debit
  - Credit
  - Status marker
- Status marker: show a strikethrough or "Reversed" tag when a reversal has cancelled the transaction.
- Detail drawer:
  - header: type, amount, date, created by
  - Dr/Cr line table with account code + name, debit, credit, line notes and a balanced total row
  - linked reference (expense, invoice, payroll…) and the related payment request
  - **related transactions**: pair, advance clearing, refund, reversal
  - attachments: thumbnail grid with download and an "Add attachment" upload
  - **Reverse** action: confirm modal, which creates a new reversing transaction. The original then shows "Reversed by TXN-… on …".
- Export: Excel / PDF / Print.

### 5.8 Categories (purpose manager)
- Left column: the six root types (Income, Expense, Advance, Transfer, Adjustment, Opening Balance), each with a count.
- Right side: an expandable tree of categories and sub-categories.
- Each row: name, code, description, active toggle, lock icon, edit, delete.
- Delete is disabled when the category is locked or used by transactions.
- Create/Edit modal fields: name, type, parent, description.

### 5.9 Chart of Accounts
- Tree grouped by **Asset · Liability · Equity · Income · Expense**. Each group has an optional Bangla subtitle: সম্পদ, দায়, মূলধন, আয়, ব্যয়.
- Columns: code, name, kind (Cash/Bank/MFS/Wallet/Ledger), sub-type, balance, active, locked.
- Create/Edit fields: code, name, group, kind, sub-type, parent, active, allowed references (what this account can be linked to: project, supplier, employee…).

### 5.10 Reports (index)
- A card grid linking to each report:
  - Daily Statement
  - Statement
  - Cash Book
  - Bank Book
  - Account Ledger
  - Customer Ledger
  - Collection (money in)
  - Payment (money out)
  - Expense
  - Project-wise Expense
  - Daily Summary
  - Trial Balance
  - Profit & Loss
  - Balance Sheet
  - Assets
  - Liability
- Every report has a date range, filters, and Excel / PDF / Print buttons.

---

## 6. States to show
- Empty state for each list, with a primary call to action (e.g. "No money in recorded today — Record Money In").
- Loading skeletons for KPI tiles and tables.
- Validation errors under fields. Examples: "Amount must be greater than 0", "From and To accounts must differ", "This bank account is not linked to a ledger account".
- Permission-restricted: hide action buttons, but keep the view.
- Insufficient balance warning on Money Out and Transfer (warn only, don't block).
- Success and error toasts, top-right.
- A locked category or account shows a lock icon and disabled edit/delete.

---

## 7. Sample data for mockups

Accounts:
- **City Bank – Gulshan** (Bank) `•••• 4821` — BDT 24,85,300.00
- **Dutch-Bangla – Uttara** (Bank) `•••• 0937` — BDT 9,12,450.00
- **Head Office Cash** (Cash) — BDT 1,48,200.00
- **Site Petty Cash – Bashundhara** (Cash) — BDT 32,750.00
- **bKash Merchant** (MFS) `017•••••219` — BDT 68,900.00

Transactions:
| Date | No | Type | Purpose | Party | Method | Account | In | Out |
|---|---|---|---|---|---|---|---|---|
| 28 Oct 2026 10:42 | TXN-001284 | Customer Receipt | Installment Receive | Rahim Uddin (Flat B-4) | Bank Transfer | City Bank | 1,50,000.00 | |
| 28 Oct 2026 11:15 | TXN-001285 | Labor Bill Payment | Project Expense → Labor Cost | Karim Contractor | Cash | Site Petty Cash | | 18,500.00 |
| 28 Oct 2026 12:30 | TXN-001286 | Transfer | Cash Transfer | — | Cash | Head Office → Site Petty Cash | ⇄ 25,000.00 | |
| 27 Oct 2026 16:05 | TXN-001281 | Supplier Payment | Supplier Bill → Material Supply | Bashundhara Cement Ltd | Cheque | Dutch-Bangla | | 3,20,000.00 |
| 27 Oct 2026 14:20 | TXN-001280 | Income | Booking Money | Nasrin Akter (Plot 12) | bKash | bKash Merchant | 50,000.00 | |
| 27 Oct 2026 09:10 | TXN-001279 | Salary Payment | Payroll → Staff Salary | October payroll | Bank | City Bank | | 4,85,000.00 |

Payment requests:
- `BPR-261028-00042` — Office Expense → Office Rent — BDT 85,000 — **Pending**
- `BPR-261028-00041` — Employee Advance — Jamal Hossain — BDT 20,000 — **Approved**
- `BPR-261027-00039` — Utility Bill Payment → Utility Bills — BDT 12,340 — **Completed** (TXN-001276)
- `BPR-261027-00038` — Marketing → Advertisement — BDT 40,000 — **Rejected** ("Budget not approved for October")

---

## 8. Deliverables
1. Overview dashboard
2. Cash & Banks: card grid, plus the detail drawer
3. Money In form with double-entry preview
4. Money Out form with double-entry preview and low-balance warning
5. Transfer modal
6. Payment Requests: list, detail drawer (pending and completed versions), reject modal, complete confirmation
7. Transactions: list, plus the detail drawer (normal and reversed versions), reverse confirmation
8. Categories manager
9. Chart of Accounts tree
10. Reports index
11. Empty, loading and error state examples for one list
