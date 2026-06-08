# Supplier Payment & Advance Bill Settlement - Flow Plan

**Created:** 2026-06-07  
**Mode:** Planning (No Code Implementation)  
**Status:** Analysis & Design Phase

---

## EXECUTIVE SUMMARY

This plan designs a comprehensive supplier payment settlement system that:
- Handles **advance fund releases** before materials arrive
- Supports **multiple partial payments** for outstanding bills
- Creates **banking payment requests** for approval on each transaction
- Tracks **supplier ledger** for financial reconciliation
- Aligns with existing **PropertySale payment schedule** pattern for consistency

---

## PART 1: CURRENT PURCHASING FLOW ANALYSIS

### 1.1 Current Purchase Order → Invoice → Payment Flow

```
┌─────────────────────────────────────────────────────────────┐
│ STAGE 1: PURCHASE ORDER CREATION & APPROVAL                │
└─────────────────────────────────────────────────────────────┘
    ↓
    Model: PurchaseOrder
    Fields:
      • po_no (auto-generated)
      • order_date, requested_by, store_id, supplier_id
      • fund_request_amount (requested advance/total)
      • approved_amount (approved by chairman/accounts)
      • actual_purchase_amount (what was actually spent)
      • due_amount (remaining owed to supplier)
      • status: Enum[PurchaseOrderStatus]
    
    Approvals Required:
      • engineer_approved_by/at
      • chairman_approved_by/at
      • accounts_approved_by/at

    ├─ Has: PurchaseOrderItem (line items)
    ├─ Has: PurchaseOrderApproval (approval trail)
    └─ Has: PurchaseFund (advance payment records)

┌─────────────────────────────────────────────────────────────┐
│ STAGE 2: ADVANCE FUND RELEASE TO SUPPLIER/EMPLOYEE         │
└─────────────────────────────────────────────────────────────┘
    ↓
    Model: PurchaseFund
    Fields:
      • purchase_order_id (links to PO)
      • transaction_id (accounting transaction)
      • amount (advance given)
      • released_by (user who released)
      • release_date
      • payto (who receives the advance)
      • receiver_type/receiver_id (polymorphic: Supplier or Employee)
      • status
      • transaction_category_id (linking to COA)
      • bank_account_id (which bank account released from)
      • method (payment method: cash, check, transfer, etc.)
    
    Relationship: Has MorphOne → BankingPaymentRequest
      • Creates banking payment request for approval
      • Request goes through: REQUESTED → APPROVED → RELEASED → COMPLETED

┌─────────────────────────────────────────────────────────────┐
│ STAGE 3: STOCK RECEIVE & PURCHASE INVOICE CREATION         │
└─────────────────────────────────────────────────────────────┘
    ↓
    Model: PurchaseInvoice
    Fields:
      • supplier_id, purchase_order_id, stock_receive_id
      • invoice_no (supplier's invoice)
      • invoice_date, due_date
      • subtotal, discount_amount, shipping_amount, total_amount
      • paid_amount, due_amount
      • status: Enum[PurchaseInvoiceStatus]
      • advance_adjusted_amount (advance already paid, to be deducted)
      • advance_account_id, accounts_payable_account_id
      • purchase_payable_id (links to PurchasePayable for detailed accounting)
      • transaction_id (GL entry for the invoice)
    
    Current Logic:
      • Invoice created when stock is received
      • Advance (PurchaseFund) reduces the total invoice due
      • paid_amount tracks cash paid toward this invoice
      • due_amount = total_amount - paid_amount - advance_adjusted_amount

    ├─ Has: PurchaseInvoiceItem (line items with details)
    ├─ BelongsTo: StockReceive (when received)
    └─ BelongsTo: PurchasePayable (for GL entries)

┌─────────────────────────────────────────────────────────────┐
│ STAGE 4: PAYMENT TO SUPPLIER (Current Gaps Below)          │
└─────────────────────────────────────────────────────────────┘
    ↓
    Identified Issue: Supplier payment flow is partially designed
    
    Model: SupplierPayment
    Fields:
      • supplier_id
      • payment_no (auto-generated)
      • payment_date
      • payment_method: Enum[SupplierPaymentMethod]
      • total_amount (total paid in this payment transaction)
      • allocated_amount (amount allocated to bills)
      • unallocated_amount (retained for future allocation)
      • status: Enum[SupplierPaymentStatus]
      
    Status Flow:
      DRAFT → POSTED → PARTIAL_ALLOCATED/FULLY_ALLOCATED → CANCELLED?
    
    ├─ Has: SupplierPaymentAllocation (bills paid by this payment)
    │   └─ Bridges: SupplierPayment → SupplierBill (many-to-many)
    └─ Links to: BankingPaymentRequest (for approval)

┌─────────────────────────────────────────────────────────────┐
│ STAGE 5: SUPPLIER LEDGER (Tracking & Reconciliation)       │
└─────────────────────────────────────────────────────────────┘
    ↓
    Model: SupplierLedger
    Purpose: Maintains debit/credit history for each supplier
    
    Fields:
      • transaction_date, transaction_type (enum)
      • reference_type/reference_id (polymorphic)
      • reference_no (invoice#, PO#, payment#)
      • description
      • debit (amount owed by company to supplier increases)
      • credit (amount paid by company to supplier)
      • balance (running total: debit - credit)
    
    Usage:
      • Create entry when invoice created (debit)
      • Create entry when advance released (credit)
      • Create entry when payment made (credit)
      • Running balance shows total supplier receivable/payable
```

### 1.2 Current System Capabilities

✅ **WORKING:**
- PO creation with multi-level approvals
- Advance fund release with banking payment requests
- Purchase invoice generation after stock receipt
- Advance deduction from invoice totals
- Supplier ledger tracking

⚠️ **PARTIAL/INCOMPLETE:**
- SupplierPayment → SupplierBill allocation (exists but may need refinement)
- Multiple partial payments against single invoice (model structure exists, logic unclear)
- Banking transaction creation for supplier payments (needs verification)
- Payment method support (defined but implementation unclear)

❌ **GAPS IDENTIFIED:**
- No clear "payment split" capability (e.g., pay 200 of 1000 due, then 500 later)
- Unclear how banking_payment_request flows for supplier payments
- No transaction validation/reversal on payment cancellation
- No payment installment scheduling for suppliers
- Missing: "Unallocated Amount" handling (what happens if partial payment?)

---

## PART 2: PROPERTY SALE/RENT PAYMENT REFERENCE ARCHITECTURE

### 2.1 How PropertySale Handles Multiple Payments ✅

```
┌────────────────────────────────────────────────────────────┐
│ MODEL: PropertySale (Master Transaction)                   │
├────────────────────────────────────────────────────────────┤
│ Fields:                                                    │
│  • sale_number, customer_id, property_unit_id             │
│  • sale_amount, discount_amount, net_amount               │
│  • payment_status (paid, partial, pending)                │
│  • down_payment_amount, down_payment_percentage           │
│  • payment_terms (defines schedule structure)             │
│  • is_scheduled (boolean: uses payment schedule)          │
│  • schedule_count, schedule_amount, schedule_type        │
└────────────────────────────────────────────────────────────┘
    ↓
    Trait: HasPaymentSchedules (methods)
      • totalScheduled() - sum all schedule items
      • totalPaid() - sum all paid amounts
      • totalDue() - sum all due amounts
      • overdueCount() - count unpaid past-due items
      • isFullyPaid() - check payment_status
      • hasOverdueSchedules() - check for overdue items

    ↓
┌────────────────────────────────────────────────────────────┐
│ MODEL: PaymentSchedule (Individual Installments)           │
├────────────────────────────────────────────────────────────┤
│ Fields:                                                    │
│  • property_sale_id (FK to PropertySale)                  │
│  • payment_category (enum):                               │
│    - down_payment                                         │
│    - installment                                          │
│    - monthly_rent                                         │
│    - security_deposit                                     │
│    - extra_charge                                         │
│    - manual_charge                                        │
│  • sequence_no (order in schedule)                        │
│  • due_date (when due)                                    │
│  • amount (total due)                                     │
│  • paid_amount (received so far)                          │
│  • due_amount (remaining: amount - paid_amount)           │
│  • status (pending, partial, paid, overdue)               │
│  • is_auto_generated (true if from schedule template)     │
│  • remarks                                                │
└────────────────────────────────────────────────────────────┘
    ↓
┌────────────────────────────────────────────────────────────┐
│ MODEL: PropertyPayment (Individual Deposit Transactions)   │
├────────────────────────────────────────────────────────────┤
│ Fields:                                                    │
│  • payment_no (auto-generated: PAY-0000001)               │
│  • property_sale_id (FK)                                  │
│  • payment_date                                           │
│  • total_amount (deposit amount from customer)            │
│  • payment_method (cash, check, transfer, card, etc.)    │
│  • reference_no (bank reference, check#, etc.)           │
│  • received_by (user who processed)                       │
│  • notes                                                  │
│  • created_by                                             │
└────────────────────────────────────────────────────────────┘
    ├─ Has Many: PropertyPaymentItem
    │   └─ allocation record
    │
    └─ MorphMany: Transaction (GL entry)
        └─ Creates accounting transaction

    ↓
┌────────────────────────────────────────────────────────────┐
│ MODEL: PropertyPaymentItem (Payment ← Schedule Allocation) │
├────────────────────────────────────────────────────────────┤
│ Fields:                                                    │
│  • property_payment_id (FK)                               │
│  • payment_schedule_id (FK)                               │
│  • amount (portion of PropertyPayment allocated)          │
│  • notes                                                  │
│                                                            │
│ Purpose: Maps PropertyPayment → PaymentSchedule           │
│  E.g., One PropertyPayment can pay multiple schedules:    │
│    PropertyPayment #1 ($2000)                            │
│      → PaymentScheduleItem 1 ($1000) - Down Payment      │
│      → PaymentScheduleItem 2 ($1000) - Installment #1    │
│                                                            │
│  Then PropertyPayment #2 ($500)                          │
│      → PaymentScheduleItem 2 ($500) - Installment #1     │
│           (completes the $1500 total)                     │
└────────────────────────────────────────────────────────────┘
```

### 2.2 Key Patterns from PropertyPayment (to apply to SupplierPayment)

| Concept | Property Sale | Supplier Payment | Application |
|---------|---------------|------------------|-------------|
| **Master Record** | PropertySale | ? (need to design) | One entity tracks total outstanding supplier liability |
| **Line Items** | PaymentSchedule | SupplierBill? | Individual bills/invoices that need payment |
| **Payment Transaction** | PropertyPayment | SupplierPayment | A single payment event (may split across multiple bills) |
| **Allocation** | PropertyPaymentItem | SupplierPaymentAllocation | Maps Payment → Bill(s) with amount per bill |
| **Tracking** | HasPaymentSchedules trait | SupplierLedger | Tracks totals (scheduled, paid, due) |
| **Status** | On Payment/Schedule | On Bill/Payment | Status updates as items get paid |

---

## PART 3: CURRENT SUPPLIER BILL & PAYMENT STRUCTURE

### 3.1 SupplierBill (Individual Invoice/Bill)

```
┌────────────────────────────────────────────────────────────┐
│ MODEL: SupplierBill                                        │
├────────────────────────────────────────────────────────────┤
│ Fields:                                                    │
│  • supplier_id, bill_no, bill_date, due_date             │
│  • reference_type/reference_id (can link to PO or other) │
│  • purchase_order_id (FK)                                │
│  • stock_receive_id (FK)                                 │
│  • subtotal, discount_amount, tax_amount, other_charge  │
│  • total_amount (invoice total)                          │
│  • paid_amount (amount received from supplier so far)    │
│  • due_amount (remaining: total - paid)                  │
│  • status: Enum[SupplierBillStatus]                     │
│    - OPEN (unpaid)                                       │
│    - PARTIAL (partially paid)                            │
│    - PAID (fully paid)                                   │
│    - CANCELLED (rejected/reversed)                       │
│  • notes, created_by, updated_by                        │
└────────────────────────────────────────────────────────────┘
    ↓
    Scope: pending() → returns OPEN and PARTIAL status
    
    Has: SupplierBillItem (line items)
    Has: SupplierPaymentAllocation (payments applied)
    Has: SupplierReturn (return items to deduct)
```

### 3.2 SupplierPayment (Payment Event)

```
┌────────────────────────────────────────────────────────────┐
│ MODEL: SupplierPayment                                     │
├────────────────────────────────────────────────────────────┤
│ Fields:                                                    │
│  • supplier_id                                            │
│  • payment_no (auto: e.g., SP-0000001)                   │
│  • payment_date                                           │
│  • payment_method: Enum[SupplierPaymentMethod]          │
│    - cash, check, bank_transfer, card, draft            │
│  • account_name, account_reference, reference_no        │
│  • transaction_no, cheque_no                            │
│  • remarks                                               │
│  • total_amount (total paid)                            │
│  • allocated_amount (amount applied to bills)           │
│  • unallocated_amount (retained for future use)         │
│  • status: Enum[SupplierPaymentStatus]                 │
│    - DRAFT (not committed yet)                          │
│    - POSTED (created, but allocation pending)           │
│    - PARTIAL_ALLOCATED (some bills paid)               │
│    - FULLY_ALLOCATED (all amount applied to bills)      │
│    - CANCELLED (reversal/rejection)                     │
│  • created_by, updated_by                              │
│                                                          │
│ Key Logic: resolveStatus()                              │
│  • If cancelled → CANCELLED                             │
│  • If allocated <= 0 → POSTED (no bills paid yet)      │
│  • If allocated < total → PARTIAL_ALLOCATED            │
│  • If allocated = total → FULLY_ALLOCATED              │
└────────────────────────────────────────────────────────────┘
    ↓
    canEdit() → true if status = DRAFT
    canCancel() → true if status != CANCELLED
    
    Has: SupplierPaymentAllocation (bills paid)
    Scope: active() → excludes DRAFT and CANCELLED
```

### 3.3 SupplierPaymentAllocation (Bridge: Payment → Bill)

```
┌────────────────────────────────────────────────────────────┐
│ MODEL: SupplierPaymentAllocation                           │
├────────────────────────────────────────────────────────────┤
│ Fields:                                                    │
│  • supplier_payment_id (FK)                              │
│  • supplier_bill_id (FK)                                 │
│  • allocated_amount (how much of payment → this bill)    │
│  • notes                                                  │
│                                                            │
│ Purpose: Many-to-Many bridge between Payment & Bill      │
│  E.g.,                                                    │
│    SupplierPayment (1000 BDT)                           │
│      → Bill #1 (Invoice-001, total: 800): alloc 200     │
│      → Bill #2 (Invoice-002, total: 1500): alloc 800    │
│                                                            │
│  Then SupplierPayment #2 (500 BDT)                      │
│      → Bill #1 (Invoice-001, remaining 600): alloc 500  │
│      (Bill #1 is now fully paid: 200 + 500 = 800)       │
│                                                            │
│      → Bill #2 (Invoice-002, remaining 700): alloc 0    │
│      (still partially paid)                               │
└────────────────────────────────────────────────────────────┘
```

---

## PART 4: PROPOSED SUPPLIER ADVANCE/DUE BILL SETTLEMENT FLOW

### 4.1 Design Philosophy

**Core Principle:** Apply the PropertyPayment pattern to SupplierPayment

**Key Features:**
1. **One Payment Event** = Multiple Bills Paid (split payment)
2. **Each Bill** can be paid in **multiple transactions** (accumulating)
3. **Every payment** creates a **BankingPaymentRequest** for approval
4. **Ledger tracking** for financial reconciliation
5. **Support for multiple payment methods** (cash, check, bank transfer, etc.)

### 4.2 Proposed Flow: Partial/Multiple Payment Settlement

```
SCENARIO: Supplier has 3 outstanding bills
  Bill-001: 1000 BDT (Invoice for materials)
  Bill-002: 1500 BDT (Another invoice)
  Bill-003: 500 BDT (Return adjustment, negative)
  
  Total Due to Supplier: 2000 BDT

PAYMENT SETTLEMENT PROCESS:
─────────────────────────────────────────────────────────────

STEP 1: Create SupplierPayment #1 (First Payment)
  ├─ Create: SupplierPayment
  │   • payment_no: SP-0000001
  │   • supplier_id: 5
  │   • payment_date: 2026-06-07
  │   • payment_method: BANK_TRANSFER
  │   • total_amount: 1000 BDT
  │   • allocated_amount: 0 (initially)
  │   • unallocated_amount: 1000
  │   • status: DRAFT
  │
  ├─ Create Allocations (DRAFT mode):
  │   • SP-0000001 → Bill-001: 700 BDT
  │   • SP-0000001 → Bill-002: 300 BDT
  │
  └─ Result:
     • allocated_amount: 1000 → status changes to POSTED
     • Bill-001: paid_amount becomes 700 (was 0)
     • Bill-002: paid_amount becomes 300 (was 0)
     • Bill-001.status: PARTIAL (700/1000)
     • Bill-002.status: PARTIAL (300/1500)

STEP 2: Create BankingPaymentRequest (for approval)
  ├─ sourceable: SupplierPayment (SP-0000001)
  ├─ amount: 1000 BDT
  ├─ description: "Payment to Supplier-XYZ for Bill-001 (700), Bill-002 (300)"
  ├─ status: REQUESTED → APPROVED → RELEASED → COMPLETED
  │
  └─ On COMPLETED:
     • Create Transaction (GL entry: Bank → Accounts Payable)
     • Update PurchaseInvoice.paid_amount (if linked via Bill)
     • Update SupplierBill.paid_amount
     • Update SupplierLedger (credit entry)

STEP 3: Update Supplier Ledger
  ├─ Create SupplierLedger entry:
  │   • transaction_type: PAYMENT
  │   • reference_type: SupplierPayment
  │   • reference_id: SP-0000001
  │   • reference_no: "SP-0000001"
  │   • debit: 0
  │   • credit: 1000 (company paid this amount)
  │   • balance: (old balance - 1000)
  │
  └─ Result: Supplier balance now shows reduced liability

STEP 4: Create SupplierPayment #2 (Second Payment - Additional)
  ├─ Create: SupplierPayment
  │   • payment_no: SP-0000002
  │   • supplier_id: 5
  │   • payment_date: 2026-06-09
  │   • payment_method: CHECK
  │   • total_amount: 500 BDT
  │   • allocated_amount: 0 (initially)
  │   • unallocated_amount: 500
  │   • status: DRAFT
  │
  ├─ Create Allocations (DRAFT mode):
  │   • SP-0000002 → Bill-001: 300 BDT (completes Bill-001: 700+300=1000)
  │   • SP-0000002 → Bill-002: 200 BDT
  │
  └─ Result:
     • allocated_amount: 500 → status changes to FULLY_ALLOCATED
     • Bill-001: paid_amount becomes 1000 (700+300) → status: PAID ✅
     • Bill-002: paid_amount becomes 500 (300+200) → status: PARTIAL (500/1500)

STEP 5: Create BankingPaymentRequest #2 (for approval)
  ├─ Same approval workflow as Payment #1
  ├─ On COMPLETED:
  │   • Update Bill-001 to PAID
  │   • Update SupplierLedger (credit: 500)
  │   • Total company paid to supplier: 1000 + 500 = 1500

STEP 6: Create SupplierPayment #3 (Final Payment)
  ├─ Create: SupplierPayment
  │   • payment_no: SP-0000003
  │   • payment_date: 2026-06-10
  │   • total_amount: 700 BDT
  │   • Allocations:
  │     - Bill-002: 700 BDT (completes: 300+200+700=1500) → PAID ✅
  │
  └─ Result:
     • All bills PAID
     • SupplierLedger balance: 0 (fully settled)
     • Total payments: 1000 + 500 + 700 = 2200... 
     
     WAIT: This is more than 2000 due. Need UNALLOCATED logic!

UNALLOCATED AMOUNT HANDLING:
─────────────────────────────────────────────────────────────
  If Payment total > allocated total, unallocated_amount > 0:
  
  • Status becomes PARTIAL_ALLOCATED (not FULLY_ALLOCATED)
  • unallocated_amount holds the excess
  • Options:
    A) Carry forward to next period (retained amount on account)
    B) Credit memo (create negative bill/advance)
    C) Reversal (reduce payment or cancel it)
    D) Employee reimbursement (if advance to employee)

EXAMPLE:
  Payment #3: 1000 BDT
    → Bill-002: allocated 700 (fully paid)
    → unallocated_amount: 300 (carried forward)
  
  Then create SupplierPayment #4 (next month):
    → Use unallocated_amount from #3 (300) + new cash (700)
    → Total pool: 1000 to allocate to new bills

REVERSAL/CANCELLATION FLOW:
─────────────────────────────────────────────────────────────
  If SupplierPayment status = POSTED/PARTIAL_ALLOCATED:
    1. Can cancel if BankingPaymentRequest hasn't been COMPLETED
    2. On cancel:
       • Revert Bill.paid_amount (subtract allocation)
       • Revert Bill.status (back to OPEN or PARTIAL)
       • Create reversal SupplierLedger entry (debit instead of credit)
       • Create reversal BankingPaymentRequest (if banking occurred)
```

### 4.3 System State After Multi-Payment Scenario

```
SUPPLIER BILL SUMMARY (After all payments):
┌──────────────────────────────────────────────────────────┐
│ Bill-001 (Invoice-001)                                   │
│  • Total: 1000 BDT                                       │
│  • Paid: 1000 BDT (Payment #1: 700 + Payment #2: 300)   │
│  • Due: 0 BDT                                            │
│  • Status: PAID ✅                                       │
│  • Payments Applied:                                     │
│    - SupplierPaymentAllocation (SP-0000001 → 700)       │
│    - SupplierPaymentAllocation (SP-0000002 → 300)       │
└──────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────┐
│ Bill-002 (Invoice-002)                                   │
│  • Total: 1500 BDT                                       │
│  • Paid: 1000 BDT (Payment #1: 300 + Payment #2: 200    │
│            + Payment #3: 500)                           │
│  • Due: 500 BDT                                          │
│  • Status: PARTIAL                                       │
│  • Payments Applied:                                     │
│    - SupplierPaymentAllocation (SP-0000001 → 300)       │
│    - SupplierPaymentAllocation (SP-0000002 → 200)       │
│    - SupplierPaymentAllocation (SP-0000003 → 500)       │
└──────────────────────────────────────────────────────────┘

SUPPLIER LEDGER:
┌──────────────────────────────────────────────────────────┐
│ 2026-06-05: Invoice-001 received    │ Debit: 1000, Bal: 1000
│ 2026-06-05: Invoice-002 received    │ Debit: 1500, Bal: 2500
│ 2026-06-05: Return-003 received     │ Credit: 500, Bal: 2000
│ 2026-06-07: Payment #1 (SP-0001)    │ Credit: 1000, Bal: 1000
│ 2026-06-09: Payment #2 (SP-0002)    │ Credit: 500, Bal: 500
│ 2026-06-10: Payment #3 (SP-0003)    │ Credit: 500, Bal: 0
└──────────────────────────────────────────────────────────┘

BANKING PAYMENT REQUESTS:
┌──────────────────────────────────────────────────────────┐
│ Request #1: SP-0000001                                   │
│  • Amount: 1000 BDT                                      │
│  • Status: COMPLETED                                     │
│  • Created: 2026-06-07 → Approved → Released → Done    │
│                                                          │
│ Request #2: SP-0000002                                   │
│  • Amount: 500 BDT                                       │
│  • Status: COMPLETED                                     │
│  • Created: 2026-06-09 → Approved → Released → Done    │
│                                                          │
│ Request #3: SP-0000003                                   │
│  • Amount: 500 BDT                                       │
│  • Status: COMPLETED                                     │
│  • Created: 2026-06-10 → Approved → Released → Done    │
└──────────────────────────────────────────────────────────┘

GL ENTRIES (Auto-Created):
┌──────────────────────────────────────────────────────────┐
│ 2026-06-07: DR Accounts Payable 1000  CR Bank 1000      │
│ 2026-06-09: DR Accounts Payable 500   CR Bank 500       │
│ 2026-06-10: DR Accounts Payable 500   CR Bank 500       │
│            (or: DR Advance Payable if advance account) │
└──────────────────────────────────────────────────────────┘
```

---

## PART 5: FLOW IMPLEMENTATION REQUIREMENTS

### 5.1 Required Models & Fields (New or Extended)

| Model | Field/Method | Purpose | Status |
|-------|--------------|---------|--------|
| **SupplierPayment** | `unallocated_amount` | Track excess payment | ✅ Exists |
| **SupplierPayment** | `resolveStatus()` method | Auto-calculate status | ✅ Exists |
| **SupplierPaymentAllocation** | (existing) | Bridge Payment → Bill | ✅ Exists |
| **SupplierBill** | `pending()` scope | Query unpaid bills | ✅ Exists |
| **SupplierBill** | `paid_amount` | Cumulative from allocations | ✅ Exists |
| **SupplierBill** | `due_amount` | Derived: total - paid | ✅ Exists |
| **SupplierBill** | `status` enum | OPEN/PARTIAL/PAID | ✅ Exists |
| **SupplierLedger** | (existing) | Debit/credit tracking | ✅ Exists |
| **BankingPaymentRequest** | `sourceable` (morphTo) | Links to SupplierPayment | ✅ Exists |
| **PurchaseFund** | `bankingRequest()` | Links to BankingPaymentRequest | ✅ Exists |

**Status: Core models already in place!**

### 5.2 Required Business Logic (Needs Implementation)

| Logic | Location | Purpose | Priority |
|-------|----------|---------|----------|
| **A. Payment Creation & Draft Mode** | SupplierPayment controller | Allow creating DRAFT payment with allocations | HIGH |
| **B. Allocation Manager** | SupplierPaymentAllocation controller | Add/edit allocations to bills | HIGH |
| **C. Status Resolution** | SupplierPayment model | Call `resolveStatus()` on save/update | HIGH |
| **D. Bill Status Update** | SupplierBill observer | On allocation change, update bill status | HIGH |
| **E. Ledger Entry Creation** | SupplierPayment observer | Auto-create SupplierLedger on completion | HIGH |
| **F. Banking Request Creation** | SupplierPayment controller | Create BankingPaymentRequest on POST | HIGH |
| **G. Payment Reversal** | SupplierPayment observer | Handle cancellation & ledger reversal | MEDIUM |
| **H. Unallocated Amount Carryover** | SupplierPayment view | Display & manage unallocated balance | MEDIUM |
| **I. GL Entry Creation** | Transaction observer | Create debit/credit on BankingPaymentRequest completion | HIGH |
| **J. Validation Rules** | SupplierPayment request | Validate allocation ≤ pending bills | HIGH |

### 5.3 User Interface Flow (Pages/Actions)

```
A. SUPPLIER BILL LIST (New Page: /suppliers/{id}/bills)
   ├─ Show all bills (pending + paid)
   ├─ Columns: Bill#, Date, Due Date, Total, Paid, Due, Status
   ├─ Status colors: OPEN (red), PARTIAL (yellow), PAID (green)
   └─ Actions: Pay Bill (button)

B. CREATE SUPPLIER PAYMENT (Modal/Page: /suppliers/{id}/payment/create)
   ├─ Auto-fill supplier_id
   ├─ Select payment_method (dropdown)
   ├─ Enter payment_date, total_amount
   ├─ Enter payment_method details (check#, transfer ref, etc.)
   ├─ Add Allocations:
   │   ├─ Table: Pending Bills
   │   ├─ Each row: Bill#, Total, Currently Paid, Remaining, Input: Allocate Amount
   │   ├─ Validation: Allocate ≤ Bill Remaining
   │   └─ Button: "Add Row" for new bills
   ├─ Summary:
   │   ├─ Total Payment: (read-only sum)
   │   ├─ Total Allocated: (sum of allocations)
   │   └─ Unallocated: (total - allocated)
   └─ Buttons: SAVE as DRAFT | POST (finalize)

C. PAYMENT DRAFT LIST (Page: /suppliers/{id}/payments?status=draft)
   ├─ Show DRAFT payments only
   ├─ Columns: Payment#, Date, Amount, Allocated, Unallocated, Actions
   └─ Actions: Edit, Delete, POST

D. CONFIRM PAYMENT POSTING (Modal/Page)
   ├─ Display all allocations
   ├─ Show GL impact preview
   ├─ Show banking request that will be created
   ├─ Buttons: CONFIRM POST | CANCEL

E. BANKING PAYMENT REQUEST WORKFLOW (Existing, Enhanced)
   ├─ BankingPaymentRequest created when Payment POSTed
   ├─ Requester → Approval → Release → Completion
   ├─ On COMPLETED: Ledger entries auto-created

F. PAYMENT HISTORY (Page: /suppliers/{id}/payment-history)
   ├─ Timeline of all payments (completed)
   ├─ Columns: Payment#, Date, Amount, Method, Bill Allocations, Status
   ├─ Drill-down: See allocations per payment
   └─ Link to banking request & GL entries

G. SUPPLIER LEDGER (Page: /suppliers/{id}/ledger)
   ├─ Running balance table
   ├─ Columns: Date, Type, Reference#, Description, Debit, Credit, Balance
   ├─ Color coding: Debit (red), Credit (green)
   └─ Export: CSV/PDF for reconciliation
```

---

## PART 6: DATA FLOW DIAGRAM

```
┌─────────────────────────────────────────────────────────────┐
│              SUPPLIER PAYMENT SETTLEMENT FLOW               │
└─────────────────────────────────────────────────────────────┘

1. INVOICE RECEIVED FROM SUPPLIER
   ├─ PurchaseInvoice created (after stock received)
   ├─ SupplierBill created (if not already from PO)
   │  └─ status: OPEN, paid_amount: 0, due_amount: total
   └─ SupplierLedger entry (debit)

2. ADVANCE PAYMENT TO SUPPLIER (Optional)
   ├─ PurchaseFund created (advance to supplier/employee)
   ├─ BankingPaymentRequest created → Approval workflow
   ├─ On completion:
   │  ├─ PurchaseInvoice.advance_adjusted_amount increased
   │  ├─ SupplierBill.paid_amount increased
   │  └─ SupplierLedger entry (credit)

3. SUPPLIER PAYMENT - CREATE PHASE
   ├─ User creates SupplierPayment (DRAFT)
   │  └─ Allocates amounts to pending SupplierBills
   ├─ Validation:
   │  ├─ Allocated amount ≤ Bill remaining due
   │  └─ Total allocated ≤ Payment total
   └─ Result: DRAFT status (editable)

4. SUPPLIER PAYMENT - POST PHASE
   ├─ User clicks POST
   ├─ resolveStatus() calculates final status:
   │  ├─ If allocated = total → FULLY_ALLOCATED
   │  ├─ If allocated < total → PARTIAL_ALLOCATED
   │  └─ If allocated = 0 → POSTED (no allocations)
   ├─ BankingPaymentRequest created (status: REQUESTED)
   │  └─ sourceable: SupplierPayment
   └─ Result: Payment ready for banking approval

5. BANKING APPROVAL WORKFLOW
   ├─ BankingPaymentRequest status: REQUESTED → APPROVED
   ├─ Approver adds notes/conditions (optional)
   └─ Status changes to APPROVED

6. BANKING RELEASE WORKFLOW
   ├─ Finance team: APPROVED → RELEASED
   ├─ Triggers fund release from bank
   └─ Status changes to RELEASED

7. BANKING COMPLETION & GL ENTRY
   ├─ On COMPLETED:
   │  ├─ For each allocation:
   │  │  ├─ SupplierBill.paid_amount += allocated
   │  │  ├─ SupplierBill.due_amount = total - paid
   │  │  ├─ If due = 0 → status: PAID
   │  │  └─ If due > 0 → status: PARTIAL
   │  │
   │  ├─ SupplierPayment.status finalized
   │  ├─ SupplierLedger entry created (credit)
   │  ├─ Transaction created:
   │  │  ├─ DR Accounts Payable (or advance account)
   │  │  └─ CR Bank Account
   │  └─ GL account balances updated

8. OPTIONAL: UNALLOCATED CARRYOVER
   ├─ If unallocated_amount > 0:
   │  ├─ Next month's payment can reference it
   │  └─ Or create credit memo / employee reimbursement

9. OPTIONAL: PAYMENT REVERSAL/CANCELLATION
   ├─ If BankingPaymentRequest not completed:
   │  ├─ Cancel SupplierPayment
   │  ├─ Revert allocations (SupplierBill.paid_amount decreases)
   │  ├─ Revert SupplierLedger (reversal entry)
   │  └─ Cancel BankingPaymentRequest
   └─ Result: Bills back to previous status
```

---

## PART 7: EDGE CASES & VALIDATIONS

### 7.1 Edge Cases to Handle

| Case | Scenario | Handling |
|------|----------|----------|
| **Overpayment** | Allocate amount > bill due | Reject or create credit memo |
| **Unallocated Balance** | Payment > allocations | Carry forward or allow reversal |
| **Partial Bill Payment** | Bill needs 1000, pay 200 | Update status to PARTIAL |
| **Multiple Bills in One Payment** | Pay 3 bills in 1 transaction | Use allocations table |
| **Partial Bill - Second Payment** | Complete a PARTIAL bill | Add to same bill's allocations |
| **Advance + Invoice** | Advance paid before invoice | Auto-deduct advance from invoice |
| **Bill Cancellation** | Supplier bill cancelled after partial payment | Reverse ledger, create credit memo |
| **Banking Request Rejection** | Payment request rejected after approval | Revert payment, notify user |
| **Ledger Reconciliation** | Supplier disputes amount | Query SupplierLedger + Transaction history |
| **Year-End Closing** | Freeze payments after period close | Lock SupplierPayment status |

### 7.2 Validation Rules

```
WHEN CREATING SUPPLER PAYMENT (DRAFT):
├─ supplier_id must exist & active
├─ payment_date cannot be future (or allow with approval)
└─ payment_method must be in enum

WHEN ADDING ALLOCATIONS:
├─ supplier_bill_id must exist & status = OPEN or PARTIAL
├─ allocated_amount > 0
├─ allocated_amount ≤ bill.due_amount
├─ allocated_amount ≤ remaining unallocated pool
└─ No duplicate allocation rows (payment → bill only once per row)

WHEN POSTING (DRAFT → POSTED/ALLOCATED):
├─ At least 1 allocation must exist (if posting, not just saving)
├─ Total allocated ≤ payment.total_amount
├─ All referenced bills must exist
└─ Payment method must be filled if required

WHEN BANKING REQUEST COMPLETES:
├─ SupplierPayment.status must be POSTED/PARTIAL_ALLOCATED
├─ BankingPaymentRequest.status must be COMPLETED
├─ All GL accounts must exist in Chart of Accounts
└─ Bank account balance must be sufficient (optional, depends on audit rules)

WHEN CANCELLING:
├─ SupplierPayment.status must not be CANCELLED already
├─ If allocations exist, must revert bill status
└─ SupplierLedger reversal must be created
```

---

## PART 8: COMPARISON: ADVANCE PAYABLE vs PURCHASE PAYABLE

### 8.1 Account Mapping

```
ADVANCE PAYABLE (when advance paid before invoice):
  PurchaseFund created → SupplierLedger credit → GL: DR Bank, CR Advance Payable

PURCHASE PAYABLE (when invoice received):
  PurchaseInvoice created → SupplierLedger debit → GL: DR COGS, CR Accounts Payable

SETTLEMENT (when payment made):
  SupplierPayment created & allocated
  ├─ If to ADVANCE: DR Advance Payable, CR Bank
  └─ If to PURCHASE: DR Accounts Payable, CR Bank

COMBINATION (advance + invoice):
  1. Advance paid: DR Bank 1000 → CR Advance Payable 1000
  2. Invoice received: DR COGS 1500 → CR Accounts Payable 1500
  3. Payment made:
     ├─ DR Advance Payable 1000 (clear advance)
     ├─ DR Accounts Payable 500 (partial payment)
     └─ CR Bank 1500 (total paid)
```

### 8.2 Current System Handling

From code review:
- **PurchaseInvoice** has fields: `advance_adjusted_amount`, `advance_account_id`
- **PurchaseInvoice** has field: `accounts_payable_account_id`
- Suggests: Invoice knows how to deduct advance from its total
- Suggests: Separate GL accounts for advance vs purchase payable

---

## PART 9: PROPERTY PAYMENT PATTERN APPLICATION

### 9.1 Similarities to Apply

| Property Payment | Supplier Payment | Benefit |
|-----------------|-----------------|---------|
| PropertyPayment (single transaction) | SupplierPayment (single transaction) | Consistency |
| PropertyPaymentItem (splits across schedule items) | SupplierPaymentAllocation (splits across bills) | Flexible allocation |
| PaymentSchedule (multiple due dates) | SupplierBill + Payment Schedule? | Recurring bills/contracts |
| HasPaymentSchedules trait (total helper methods) | SupplierLedger (cumulative balance) | Quick summary queries |
| Payment can pay multiple schedule items | Payment can pay multiple bills | Multi-bill handling |

### 9.2 Differences to Note

| Property Payment | Supplier Payment | Reason |
|-----------------|------------------|--------|
| Customer (many payments) | Supplier (bills received) | Opposite direction (customer pays company vs company pays supplier) |
| Scheduled items auto-generated | Bills manually created or from PO | Different creation trigger |
| Down payment often upfront | Advance may be withheld until invoice | Different payment timing |

---

## PART 10: IMPLEMENTATION ROADMAP (WITHOUT CODE)

### Phase 1: Data Integrity (Weeks 1-2)
- [ ] Verify SupplierBill model fields match design
- [ ] Verify SupplierPayment model fields match design
- [ ] Verify SupplierPaymentAllocation structure
- [ ] Verify BankingPaymentRequest morphTo relationship
- [ ] Review SupplierLedger transaction types (ensure PAYMENT type exists)
- [ ] Validate GL account setup for Accounts Payable & Advance Payable

### Phase 2: Business Logic Layer (Weeks 3-4)
- [ ] Create SupplierPaymentService class
  - [ ] Method: createDraftPayment(supplier, amount, method, date)
  - [ ] Method: addAllocation(payment, bill, amount)
  - [ ] Method: removeAllocation(allocation)
  - [ ] Method: postPayment(payment) - triggers status resolution
  - [ ] Method: reversePayment(payment) - handles cancellation
- [ ] Create SupplierBillService class
  - [ ] Method: updateStatusFromAllocations(bill)
  - [ ] Method: getPendingBills(supplier)
  - [ ] Method: getPaymentHistory(bill)
- [ ] Create SupplierLedgerService class
  - [ ] Method: recordDebit(supplier, bill, amount, date)
  - [ ] Method: recordCredit(supplier, payment, amount, date)
  - [ ] Method: getBalance(supplier, asOf_date)

### Phase 3: Observer/Event Handling (Weeks 5-6)
- [ ] SupplierPayment Observer
  - [ ] On saving: call resolveStatus()
  - [ ] On posted: create BankingPaymentRequest
  - [ ] On cancelling: revert allocations & ledger
- [ ] SupplierPaymentAllocation Observer
  - [ ] On creating/updating: trigger SupplierBill status update
- [ ] BankingPaymentRequest Observer
  - [ ] On completed: create GL entries & finalize payment
  - [ ] On rejected: revert payment status

### Phase 4: Controller Actions (Weeks 7-8)
- [ ] SupplierPaymentController
  - [ ] create() - show draft form
  - [ ] store() - save draft
  - [ ] edit() - edit draft
  - [ ] update() - update draft
  - [ ] post() - finalize to POSTED
  - [ ] cancel() - cancellation
  - [ ] index() - list (pending/completed)
  - [ ] show() - view payment details
- [ ] SupplierPaymentAllocationController
  - [ ] addRow() - add allocation line
  - [ ] updateRow() - update allocation amount
  - [ ] removeRow() - delete allocation line
- [ ] SupplierBillController
  - [ ] Enhance show() to display payment history
  - [ ] Add pending() scope action

### Phase 5: Views & UI (Weeks 9-10)
- [ ] Supplier Payment Draft Form
  - [ ] Payment header (date, method, amount)
  - [ ] Allocations table (dynamic rows)
  - [ ] Summary (total, allocated, unallocated)
  - [ ] Save Draft & Post buttons
- [ ] Supplier Payment List
  - [ ] Filter by status
  - [ ] Search by payment# or supplier
  - [ ] Actions: View, Edit (if draft), Cancel
- [ ] Supplier Bill List
  - [ ] Status badges
  - [ ] Payment history link
  - [ ] Remaining due highlight
- [ ] Supplier Ledger View
  - [ ] Running balance table
  - [ ] Drill-down to transactions

### Phase 6: Reporting & Export (Weeks 11-12)
- [ ] Supplier Payment Report (all payments, date range)
- [ ] Supplier Aging Report (unpaid bills by supplier)
- [ ] Supplier Ledger Export (PDF/Excel)
- [ ] Reconciliation Report (payment vs GL)

### Phase 7: Testing & Validation (Weeks 13-14)
- [ ] Unit tests: Services, Models, Observers
- [ ] Integration tests: Multi-payment scenarios
- [ ] Banking request workflow tests
- [ ] Ledger reconciliation tests
- [ ] Edge case testing (overpayment, reversal, etc.)

### Phase 8: Documentation & Training (Week 15)
- [ ] User guide (how to create payments)
- [ ] Process flowchart
- [ ] Troubleshooting guide
- [ ] Admin/Accountant training

---

## PART 11: RISK & MITIGATION

| Risk | Impact | Mitigation |
|------|--------|-----------|
| **Ledger mismatch** | Financial discrepancy | Automated reconciliation report, audit trail |
| **Payment reversal complexity** | Accounting errors | Comprehensive reversal logic, GL reversal entries |
| **Multi-bill allocation confusion** | User error | Clear UI, validation, preview before posting |
| **Banking request failure** | Payment not released | Queue system, retry logic, manual override |
| **Unallocated amount orphans** | Floating funds | Monthly review, automatic carryover, alerts |
| **Bill overpayment** | Excess payment | Reject allocations > bill due, credit memo flow |
| **Concurrent payments** | Double-payment risk | Pessimistic locking on bill during allocation |
| **GL account misconfiguration** | Incorrect posting | Validation, mandatory account selection |

---

## PART 12: SUCCESS CRITERIA

**A payment settlement flow is successful when:**

1. ✅ User can create multi-bill payments without manual GL entries
2. ✅ Each payment creates banking request for approval
3. ✅ Bills transition from OPEN → PARTIAL → PAID as payments accumulate
4. ✅ SupplierLedger reflects accurate balance (debit - credit)
5. ✅ GL entries auto-generated match hand-calculated entries
6. ✅ Payment reversal accurately reverts all associated state changes
7. ✅ Unallocated amounts are trackable and managed
8. ✅ Reconciliation report shows 100% match: GL ↔ SupplierLedger ↔ Supplier statements
9. ✅ User can view full payment history per bill/supplier
10. ✅ System handles edge cases (overpayment, partial bills, advance scenarios)

---

## SUMMARY TABLE: Current vs Proposed State

| Aspect | Current State | Proposed State | Gap |
|--------|--------------|----------------|-----|
| **Payment Creation** | SupplierPayment model exists | DRAFT mode with allocations | Build form + controller |
| **Bill Allocation** | SupplierPaymentAllocation model exists | Multi-bill per payment | Build allocation UI |
| **Status Tracking** | Models have status fields | Auto-resolve on post | Build observer logic |
| **Banking Integration** | BankingPaymentRequest exists | Polymorphic sourceable | Already exists |
| **Ledger Tracking** | SupplierLedger model exists | Auto-populated on completion | Build observer logic |
| **GL Entries** | Transaction model exists | Auto-created on completion | Build transaction observer |
| **UI Support** | Partial (may need enhancement) | Full payment workflow | Build pages 1-6 in section 5.3 |
| **Validation Rules** | Basic | Comprehensive (section 7.2) | Build request validators |
| **Payment Reversal** | Handled manually? | Automated with ledger reversal | Build reversal service |
| **Reporting** | Basic query capability | Payment history + aging reports | Build reports |

---

**Status: READY FOR IMPLEMENTATION PHASE**

This plan provides a complete blueprint for handling supplier advance and due bill payments with multiple payment means, aligned with the existing property payment pattern and real-world ERP scenarios.

