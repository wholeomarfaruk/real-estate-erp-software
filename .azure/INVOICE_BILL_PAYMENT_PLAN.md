# Invoice → Bill → Payment → Transaction Flow Plan

**Created:** 2026-06-07  
**Mode:** Planning (No Code)  
**Scope:** Bill creation from invoice & multi-payment settlement

---

## QUICK OVERVIEW

```
CURRENT STATE (WORKING ✅):
  PO Create → Approve → Release Fund (Advance) → Stock Receive → Purchase Invoice Created

NEXT FLOW (TO BUILD):
  PurchaseInvoice → Create Bill when needed
  Bill: 2000 BDT (due)
    ├─ Payment 1: 1000 BDT → Creates Transaction 1 → Banking Request 1
    ├─ Payment 2: 700 BDT  → Creates Transaction 2 → Banking Request 2
    └─ Due: 300 BDT (remaining)
```

---

## PART 1: CURRENT STATE - WHAT EXISTS

### 1.1 PurchaseInvoice Model (Already Created)

```php
Fields:
  • invoice_no (supplier's invoice number)
  • invoice_date, due_date
  • total_amount (e.g., 2000 BDT)
  • paid_amount (cumulative: 0 initially)
  • due_amount (2000 - 0 = 2000 initially)
  • status: PurchaseInvoiceStatus enum
  • purchase_order_id, supplier_id, stock_receive_id
  • purchase_payable_id (links to GL)
  • transaction_id (GL entry for invoice)
```

**Current Flow:**
- Invoice created when stock received ✅
- Tracks paid_amount & due_amount ✅
- Links to PurchaseOrder & Supplier ✅
- Has GL transaction created ✅

### 1.2 Transaction Model (Already Exists)

```php
Fields:
  • account_id (GL account)
  • type: TransactionType enum (income, expense, etc.)
  • datetime
  • debit, credit (double-entry accounting)
  • reference_type/reference_id (polymorphic link)
  • reference_no (invoice#, bill#, payment#)
```

**Current State:**
- Creates GL entry when invoice posted ✅
- Can track reference (invoice, bill, payment) ✅

### 1.3 BankingPaymentRequest Model (Already Exists)

```php
Fields:
  • sourceable_type/sourceable_id (polymorphic: links to SupplierPayment, PurchaseFund, etc.)
  • amount
  • status: REQUESTED → APPROVED → RELEASED → COMPLETED
  • transaction_id (GL entry created on completion)
```

**Current State:**
- Creates banking requests for approval ✅
- Morphable to different sources ✅
- Workflow: REQUESTED → APPROVED → RELEASED → COMPLETED ✅

---

## PART 2: GAP IDENTIFIED - WHAT'S MISSING

### 2.1 The Missing Piece: Bill vs Invoice

| Concept | Current | Needed | Gap |
|---------|---------|--------|-----|
| **Invoice** | PurchaseInvoice created when stock received | ✅ | None |
| **Bill** | Separate model? Or same as Invoice? | Need to clarify | **HERE** |
| **Payment** | SupplierPayment exists | ✅ | None |
| **Transaction Creation** | On invoice posting | On payment completion? | **Timing?** |

**Question to Answer:**
- Is `SupplierBill` = `PurchaseInvoice`? 
  - **Answer:** YES, they appear to be same financial document
  - SupplierBill: Generic bill from supplier (reference_type could be invoice, receipt, etc.)
  - PurchaseInvoice: Specific to purchase order flow

**Decision:** We'll use **SupplierBill as master** for payment tracking, but it may reference a PurchaseInvoice

---

## PART 3: PROPOSED FLOW

### 3.1 Flow Diagram

```
┌─────────────────────────────────────────┐
│ STEP 1: PurchaseInvoice Created         │ (Already Done ✅)
├─────────────────────────────────────────┤
│ • Date: 2026-06-05                      │
│ • Invoice#: INV-2000                    │
│ • Supplier: XYZ Company                 │
│ • Amount: 2000 BDT                      │
│ • Due Date: 2026-06-20                  │
│ • paid_amount: 0, due_amount: 2000      │
│ • status: POSTED (or PENDING)           │
└─────────────────────────────────────────┘
           ↓
┌─────────────────────────────────────────┐
│ STEP 2: Create SupplierBill             │ (Manual or Auto)
├─────────────────────────────────────────┤
│ NEW: SupplierBill record created        │
│ • bill_no: BILL-001 (or same as invoice)
│ • reference_id: PurchaseInvoice#       │
│ • reference_type: PurchaseInvoice      │
│ • total_amount: 2000 BDT               │
│ • paid_amount: 0, due_amount: 2000     │
│ • status: OPEN                         │
│ • supplier_id: 5                        │
│                                         │
│ DECISION: Create auto-trigger when     │
│ PurchaseInvoice status = POSTED?       │
└─────────────────────────────────────────┘
           ↓
┌─────────────────────────────────────────┐
│ STEP 3: Payment 1 - 1000 BDT            │ (First Installment)
├─────────────────────────────────────────┤
│ Create SupplierPayment:                 │
│ • payment_no: SP-001                    │
│ • payment_date: 2026-06-10              │
│ • payment_method: BANK_TRANSFER        │
│ • total_amount: 1000 BDT               │
│ • supplier_id: 5                        │
│                                         │
│ Allocate to Bill:                       │
│ • SupplierPaymentAllocation             │
│   → supplier_bill_id: BILL-001         │
│   → allocated_amount: 1000 BDT         │
│ • status: POSTED → FULLY_ALLOCATED     │
│                                         │
│ Create BankingPaymentRequest:           │
│ • sourceable: SupplierPayment (SP-001) │
│ • amount: 1000 BDT                      │
│ • status: REQUESTED                    │
│   → APPROVED → RELEASED → COMPLETED    │
└─────────────────────────────────────────┘
           ↓
┌─────────────────────────────────────────┐
│ STEP 4: Create Transaction 1            │ (GL Entry on Banking Complete)
├─────────────────────────────────────────┤
│ When BankingPaymentRequest = COMPLETED │
│                                         │
│ Create Transaction (GL Entry):          │
│ • account_id: Accounts Payable (AP)    │
│ • type: EXPENSE (or PAYMENT)           │
│ • datetime: 2026-06-10                 │
│ • debit: 1000 BDT (DR Payable)        │
│ • credit: 0                             │
│ • reference_no: SP-001                 │
│ • reference_type: SupplierPayment      │
│ • reference_id: SP-001                 │
│                                         │
│ Create Transaction 2 (other side):      │
│ • account_id: Bank Account             │
│ • debit: 0                              │
│ • credit: 1000 BDT (CR Bank)           │
│                                         │
│ Update SupplierBill:                    │
│ • paid_amount: 0 + 1000 = 1000         │
│ • due_amount: 2000 - 1000 = 1000       │
│ • status: PARTIAL (paid 1000 of 2000) │
│                                         │
│ Update SupplierLedger:                  │
│ • credit: 1000 BDT                      │
│ • balance: (old - 1000)                 │
└─────────────────────────────────────────┘
           ↓
┌─────────────────────────────────────────┐
│ STEP 5: Payment 2 - 700 BDT             │ (Second Installment)
├─────────────────────────────────────────┤
│ Create SupplierPayment:                 │
│ • payment_no: SP-002                    │
│ • payment_date: 2026-06-15              │
│ • total_amount: 700 BDT                │
│                                         │
│ Allocate to Bill:                       │
│ • SupplierPaymentAllocation             │
│   → supplier_bill_id: BILL-001         │
│   → allocated_amount: 700 BDT          │
│ • status: FULLY_ALLOCATED              │
│                                         │
│ Create BankingPaymentRequest:           │
│ • sourceable: SupplierPayment (SP-002) │
│ • amount: 700 BDT                       │
└─────────────────────────────────────────┘
           ↓
┌─────────────────────────────────────────┐
│ STEP 6: Create Transaction 2            │ (GL Entry on Banking Complete)
├─────────────────────────────────────────┤
│ When BankingPaymentRequest SP-002 = COMPLETED
│                                         │
│ Create Transaction:                     │
│ • DR Accounts Payable: 700 BDT         │
│ • CR Bank: 700 BDT                      │
│ • reference_no: SP-002                 │
│                                         │
│ Update SupplierBill:                    │
│ • paid_amount: 1000 + 700 = 1700       │
│ • due_amount: 2000 - 1700 = 300        │
│ • status: PARTIAL (paid 1700 of 2000) │
│                                         │
│ Update SupplierLedger:                  │
│ • credit: 700 BDT                       │
│ • balance: (old - 700)                  │
└─────────────────────────────────────────┘
           ↓
┌─────────────────────────────────────────┐
│ FINAL STATE                             │
├─────────────────────────────────────────┤
│ SupplierBill BILL-001:                  │
│ • total_amount: 2000 BDT               │
│ • paid_amount: 1700 BDT (1000 + 700)  │
│ • due_amount: 300 BDT                  │
│ • status: PARTIAL                      │
│                                         │
│ Payments:                               │
│ • SP-001: 1000 BDT, completed ✅       │
│ • SP-002: 700 BDT, completed ✅        │
│ • SP-003 (pending): 300 BDT            │
│                                         │
│ Transactions (GL):                      │
│ • TRX-001: DR AP 1000 / CR Bank 1000  │
│ • TRX-002: DR AP 700 / CR Bank 700    │
│ • (Total GL: DR AP 1700 / CR Bank 1700) │
│                                         │
│ SupplierLedger:                         │
│ • Invoice received: Debit 2000, Bal 2000
│ • Payment 1: Credit 1000, Bal 1000     │
│ • Payment 2: Credit 700, Bal 300       │
└─────────────────────────────────────────┘
```

---

## PART 4: DETAILED SPECIFICATIONS

### 4.1 When to Create SupplierBill from PurchaseInvoice

**Option A: Manual (User clicks "Create Bill")**
```
Flow: PurchaseInvoice Detail Page
  └─ Button: "Create Bill from Invoice"
     └─ Creates SupplierBill record
        └─ Copies: supplier_id, amount, due_date, invoice details
        └─ Sets: reference_type = PurchaseInvoice, reference_id = invoice.id
        └─ Status: OPEN
```

**Option B: Automatic (Observer)**
```
Flow: When PurchaseInvoice status = POSTED
  └─ Observer triggers: PurchaseInvoiceObserver::created()
     └─ Auto-creates SupplierBill
     └─ Or checks if already exists
```

**Recommendation:** Use **Option B (Automatic)** to ensure every invoice has a bill

### 4.2 When to Create Transaction from Payment

**Trigger:** When BankingPaymentRequest status = COMPLETED

```
Flow:
1. User creates SupplierPayment (DRAFT)
2. User adds allocation to SupplierBill
3. User POSTs payment → BankingPaymentRequest created (REQUESTED)
4. Manager approves → BankingPaymentRequest.status = APPROVED
5. Finance releases → BankingPaymentRequest.status = RELEASED
6. Payment cleared → BankingPaymentRequest.status = COMPLETED

On COMPLETED trigger:
├─ Update SupplierBill:
│  ├─ paid_amount += allocation
│  ├─ due_amount = total - paid
│  ├─ status = OPEN | PARTIAL | PAID
│  └─ Save
│
├─ Create Transaction (GL Entry):
│  ├─ Transaction 1: DR Accounts Payable, CR Bank
│  ├─ reference_no: payment number
│  ├─ reference_type: SupplierPayment
│  └─ Save
│
└─ Update SupplierLedger:
   ├─ transaction_type: PAYMENT
   ├─ credit: payment amount
   ├─ balance: old_balance - credit
   └─ Save
```

### 4.3 Account Mapping

```
GL STRUCTURE:

Accounts Payable (AP) Account
  • Code: 2010 (liability)
  • When Bill created: Debit to COGS/Expense, Credit to AP
    └─ Transaction on invoice creation (already done?)
  
  • When Payment made: Debit to AP, Credit to Bank
    └─ Transaction on payment completion

Bank Account
  • Code: 1010 (asset)
  • When Payment made: Credit from Bank
    └─ Credit side of payment transaction

Example (Payment 1: 1000 BDT):
  Journal Entry:
    DR  Accounts Payable (2010)     1000
    CR  Bank - Primary Account       1000
    
  Posting Reference: SP-001 (Payment Number)
```

### 4.4 Validation Rules

**When Creating SupplierBill from Invoice:**
- [ ] PurchaseInvoice must exist
- [ ] PurchaseInvoice.status must be POSTED or CONFIRMED
- [ ] Supplier must be active
- [ ] Bill with same reference doesn't already exist

**When Creating SupplierPayment:**
- [ ] Supplier must be active
- [ ] Payment amount > 0
- [ ] Payment method must be valid
- [ ] Payment date cannot be in future (or configurable)

**When Adding Allocation:**
- [ ] SupplierBill must exist & status = OPEN or PARTIAL
- [ ] Allocated amount > 0
- [ ] Allocated amount ≤ Bill.due_amount (cannot overpay)
- [ ] Total allocations ≤ Payment.total_amount

**When Posting Payment:**
- [ ] At least 1 allocation must exist
- [ ] All allocations must pass validation above
- [ ] Status resolves correctly:
  - If allocated = total → FULLY_ALLOCATED
  - If allocated < total → PARTIAL_ALLOCATED
  - If allocated = 0 → POSTED

**When Completing Banking Request:**
- [ ] BankingPaymentRequest.status = RELEASED
- [ ] AP account must exist in COA
- [ ] Bank account must exist & active
- [ ] Transaction creation succeeds

---

## PART 5: DATA MODEL SUMMARY

### 5.1 Entity Relationships

```
PurchaseInvoice (Master)
  ├─ 1→1: SupplierBill (created from invoice)
  ├─ 1→M: Transaction (created on invoice post + each payment)
  └─ 1→M: SupplierPayment (via Bill)

SupplierBill (Payment Master)
  ├─ 1→M: SupplierPaymentAllocation (payments applied)
  ├─ 1→M: Transaction (one per payment)
  ├─ 1→1: Supplier
  ├─ 1→1: PurchaseInvoice (reference)
  └─ Tracked in: SupplierLedger

SupplierPayment (Payment Event)
  ├─ 1→1: BankingPaymentRequest (for approval)
  ├─ 1→M: SupplierPaymentAllocation (bills paid)
  ├─ 1→1: Supplier
  ├─ 1→1: Transaction (created on completion)
  └─ Tracked in: SupplierLedger

BankingPaymentRequest (Approval Workflow)
  ├─ morphTo: SupplierPayment
  ├─ 1→1: Transaction (created on completion)
  └─ Status: REQUESTED → APPROVED → RELEASED → COMPLETED

SupplierLedger (Audit Trail)
  ├─ reference morphTo: (SupplierPayment, Bill, Invoice)
  └─ Tracks: All debits/credits per supplier
```

### 5.2 Key Fields to Track

| Field | Model | Purpose |
|-------|-------|---------|
| `paid_amount` | SupplierBill | Cumulative payments received |
| `due_amount` | SupplierBill | Remaining: total - paid |
| `status` | SupplierBill | OPEN → PARTIAL → PAID |
| `allocated_amount` | SupplierPayment | Sum of allocations |
| `unallocated_amount` | SupplierPayment | Excess: total - allocated |
| `reference_type/id` | SupplierBill | Links to PurchaseInvoice |
| `sourceable_type/id` | BankingPaymentRequest | Links to SupplierPayment |
| `balance` | SupplierLedger | Running total: debit - credit |

---

## PART 6: IMPLEMENTATION CHECKLIST

### Phase 1: Model & Relationship Verification
- [ ] Verify SupplierBill model has: reference_type/id, paid_amount, due_amount, status
- [ ] Verify SupplierPayment model has: unallocated_amount, status enum
- [ ] Verify SupplierPaymentAllocation links Payment → Bill
- [ ] Verify BankingPaymentRequest has morphTo relationship
- [ ] Verify Transaction model can store debit/credit
- [ ] Verify SupplierLedger has balance & transaction_type

### Phase 2: Observer/Event Logic
- [ ] Create PurchaseInvoiceObserver → auto-create SupplierBill on POST
- [ ] Create SupplierPaymentObserver → resolve status on save
- [ ] Create SupplierPaymentAllocationObserver → update Bill on allocation change
- [ ] Create BankingPaymentRequestObserver → create Transaction & Ledger on COMPLETED
- [ ] Test all observers in correct sequence

### Phase 3: Service/Controller Logic
- [ ] Create SupplierBillService: createFromInvoice(), updateStatusFromAllocations()
- [ ] Create SupplierPaymentService: createDraft(), postPayment(), resolveStatus()
- [ ] Create SupplierLedgerService: recordDebit(), recordCredit(), getBalance()
- [ ] Create TransactionService: createPaymentTransaction()

### Phase 4: User Interface
- [ ] Bill list page (show paid_amount, due_amount, status)
- [ ] Payment creation form (allocate to bills)
- [ ] Payment history per bill
- [ ] Supplier ledger view

### Phase 5: Testing
- [ ] Multi-payment scenario (1000 + 700 + 300 = 2000)
- [ ] Bill status transitions (OPEN → PARTIAL → PAID)
- [ ] GL entry verification (total debits = total credits)
- [ ] Ledger reconciliation (sum = bill total)

---

## PART 7: QUICK REFERENCE - THE SCENARIO

```
SCENARIO IN CODE TERMS:

1. PurchaseInvoice created
   invoice = PurchaseInvoice.create({
       invoice_no: 'INV-2000',
       supplier_id: 5,
       total_amount: 2000,
       paid_amount: 0,
       due_amount: 2000,
       status: 'POSTED'
   })

2. SupplierBill auto-created (observer)
   bill = SupplierBill.create({
       bill_no: 'BILL-001',
       reference_type: 'PurchaseInvoice',
       reference_id: invoice.id,
       supplier_id: 5,
       total_amount: 2000,
       paid_amount: 0,
       due_amount: 2000,
       status: 'OPEN'
   })

3. First Payment
   payment1 = SupplierPayment.create({
       payment_no: 'SP-001',
       supplier_id: 5,
       total_amount: 1000,
       status: 'DRAFT'
   })
   
   allocation1 = SupplierPaymentAllocation.create({
       supplier_payment_id: payment1.id,
       supplier_bill_id: bill.id,
       allocated_amount: 1000
   })
   
   payment1.post()  // Status → FULLY_ALLOCATED
   
   Request = BankingPaymentRequest.create({
       sourceable_type: 'SupplierPayment',
       sourceable_id: payment1.id,
       amount: 1000,
       status: 'REQUESTED'
   })
   
   On complete:
   → Transaction 1: DR AP 1000 / CR Bank 1000
   → Bill: paid_amount = 1000, due_amount = 1000, status = PARTIAL
   → Ledger: credit 1000, balance = 1000

4. Second Payment
   payment2 = SupplierPayment.create({
       payment_no: 'SP-002',
       supplier_id: 5,
       total_amount: 700,
       status: 'DRAFT'
   })
   
   allocation2 = SupplierPaymentAllocation.create({
       supplier_payment_id: payment2.id,
       supplier_bill_id: bill.id,
       allocated_amount: 700
   })
   
   On complete:
   → Transaction 2: DR AP 700 / CR Bank 700
   → Bill: paid_amount = 1700, due_amount = 300, status = PARTIAL
   → Ledger: credit 700, balance = 300

RESULT:
   bill.paid_amount = 1700
   bill.due_amount = 300
   bill.status = 'PARTIAL'
   
   Total Payments: 1000 + 700 = 1700
   GL Balance: DR AP 1700 = CR Bank 1700 ✅
   Ledger: Debit 2000 - Credit 1700 = 300 balance ✅
```

---

## PART 8: SUCCESS CRITERIA

When implemented, the system should:

1. ✅ Auto-create SupplierBill from PurchaseInvoice
2. ✅ Allow partial payments (1000 + 700 + 300)
3. ✅ Update Bill status: OPEN → PARTIAL → PAID
4. ✅ Create one Transaction per payment completion
5. ✅ GL entries balance: DR AP = CR Bank
6. ✅ SupplierLedger tracks running balance
7. ✅ BankingPaymentRequest workflow for each payment
8. ✅ Invoice due_amount matches Bill due_amount at all times
9. ✅ Can reverse payment (cancel & revert state)
10. ✅ Reconciliation: Bill total = GL entries total

---

**Status: READY TO BUILD**

Focus areas in order:
1. Create observer: PurchaseInvoice → SupplierBill
2. Create observer: BankingPaymentRequest COMPLETED → Transaction + SupplierBill update + Ledger
3. Build UI: Payment creation with allocations
4. Test: Multi-payment scenario

