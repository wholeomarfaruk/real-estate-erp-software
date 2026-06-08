# Invoice vs Bill - Clarification

## Quick Answer

| Aspect | Invoice | Bill |
|--------|---------|------|
| **Model** | `PurchaseInvoice` | `SupplierBill` |
| **When Created** | When stock received (auto) | Manual or linked to invoice |
| **Purpose** | Purchase transaction document | Payment tracking (generic) |
| **Scope** | Only from PO + Stock Receive | Can be from PO, Stock Receive, or Manual |
| **Payment Status** | Has status: PENDING → APPROVED → PARTIALLY_PAID → PAID | Has status: OPEN → PARTIAL → PAID |
| **Payment Tracking** | Has paid_amount, due_amount | Has paid_amount, due_amount |
| **Payment Method** | Direct (via PurchaseInvoice.transaction) | Via SupplierPaymentAllocation |

---

## Your Scenario - Which Model to Use?

```
You want:
  Invoice: 2000 BDT
  → Payment 1: 1000 BDT
  → Payment 2: 700 BDT
  → Due: 300 BDT
```

### OPTION A: Use PurchaseInvoice Directly ❌

```php
PurchaseInvoice:
  • paid_amount: 0 → 1000 → 1700
  • due_amount: 2000 → 1000 → 300
  • status: APPROVED → PARTIALLY_PAID → PARTIALLY_PAID

Problem:
  • SupplierPaymentAllocation links to SupplierBill, NOT PurchaseInvoice
  • Payment allocation system expects SupplierBill
  • Two separate payment systems = confusion
```

### OPTION B: Use SupplierBill (Recommended) ✅

```php
PurchaseInvoice (from stock receive):
  • paid_amount: 0 (unchanged)
  • due_amount: 2000 (unchanged)
  • status: APPROVED (accounting approval only)

SupplierBill (created from invoice):
  • reference_type: LINKED_STOCK_RECEIVE (or LINKED_PURCHASE_ORDER)
  • reference_id: PurchaseInvoice.id
  • paid_amount: 0 → 1000 → 1700
  • due_amount: 2000 → 1000 → 300
  • status: OPEN → PARTIAL → PARTIAL

SupplierPayment (payment event):
  • payment_no: SP-001, SP-002
  • allocated to SupplierBill
  • Creates Banking Request
  • Creates Transaction on completion

Benefits:
  ✅ Separates "invoice document" from "payment tracking"
  ✅ Uses existing SupplierPaymentAllocation system
  ✅ Aligns with generic bill payment flow
  ✅ Can handle standalone bills too (not just from PO)
```

---

## Current System Design

```
PurchaseInvoice (Accounting Document)
├─ Created when: Stock Receive triggered
├─ Purpose: Record liability to supplier
├─ Status: PENDING (accounts review) → APPROVED (posted to GL)
├─ GL Entry: Created when APPROVED
│  └─ DR COGS / CR Accounts Payable
└─ Has: transaction_id (points to GL entry)

         ↓↓↓ LINK ↓↓↓

SupplierBill (Payment Tracker)
├─ Created when: Manual action or auto-triggered
├─ reference_type: LINKED_STOCK_RECEIVE or LINKED_PURCHASE_ORDER
├─ reference_id: PurchaseInvoice.id
├─ Purpose: Manage payment allocations
├─ Status: OPEN (unpaid) → PARTIAL (partially paid) → PAID
├─ Has: paymentAllocations() → SupplierPaymentAllocation
└─ Updated when: Each payment allocated

         ↓↓↓ LINK ↓↓↓

SupplierPayment (Payment Event)
├─ Created when: User initiates payment
├─ Allocates to: SupplierBill (not invoice)
├─ Creates: BankingPaymentRequest (for approval)
└─ On completion: Creates Transaction (GL entry)
   └─ DR Accounts Payable / CR Bank
```

---

## Visual Flow

```
┌──────────────────────────────────────────────────────────────┐
│ STEP 1: Stock Received                                       │
└──────────────────────────────────────────────────────────────┘
         ↓
    PurchaseInvoice created
    • amount: 2000 BDT
    • status: PENDING
    • GL: DR COGS 2000 / CR AP 2000
         ↓
┌──────────────────────────────────────────────────────────────┐
│ STEP 2: Accounts Approves Invoice                           │
└──────────────────────────────────────────────────────────────┘
         ↓
    PurchaseInvoice.status → APPROVED
    GL Entry already created (or updated)
         ↓
┌──────────────────────────────────────────────────────────────┐
│ STEP 3: Create SupplierBill from Invoice (Auto or Manual)   │
└──────────────────────────────────────────────────────────────┘
         ↓
    SupplierBill created:
    • reference_type: LINKED_STOCK_RECEIVE
    • reference_id: PurchaseInvoice.id
    • total_amount: 2000 (from invoice)
    • status: OPEN
         ↓
┌──────────────────────────────────────────────────────────────┐
│ STEP 4: Payment 1 (1000 BDT)                                │
└──────────────────────────────────────────────────────────────┘
         ↓
    SupplierPayment created:
    • amount: 1000
    • allocated to SupplierBill: 1000
    • status: FULLY_ALLOCATED
    ↓
    BankingPaymentRequest created:
    • status: REQUESTED
    • (approval workflow: APPROVED → RELEASED → COMPLETED)
    ↓
    On COMPLETED:
    • SupplierBill.paid_amount: 1000
    • SupplierBill.due_amount: 1000
    • SupplierBill.status: PARTIAL
    • Transaction created: DR AP 1000 / CR Bank 1000
         ↓
┌──────────────────────────────────────────────────────────────┐
│ STEP 5: Payment 2 (700 BDT)                                 │
└──────────────────────────────────────────────────────────────┘
         ↓
    SupplierPayment created:
    • amount: 700
    • allocated to SupplierBill: 700
    • status: FULLY_ALLOCATED
    ↓
    BankingPaymentRequest created:
    • status: REQUESTED
    ↓
    On COMPLETED:
    • SupplierBill.paid_amount: 1700
    • SupplierBill.due_amount: 300
    • SupplierBill.status: PARTIAL
    • Transaction created: DR AP 700 / CR Bank 700
         ↓
┌──────────────────────────────────────────────────────────────┐
│ FINAL STATE                                                  │
└──────────────────────────────────────────────────────────────┘
    
    PurchaseInvoice:
    • paid_amount: 0 (unchanged)
    • due_amount: 2000 (unchanged)
    • status: APPROVED (unchanged)
    • Note: This stays as accounting document
    
    SupplierBill:
    • paid_amount: 1700
    • due_amount: 300
    • status: PARTIAL
    • Tracks: All payments + allocations
    
    GL Accounts Payable:
    • Debit: 1700 (from both payments)
    • Balance: 2000 - 1700 = 300 (matches due_amount)
    
    Remaining Due: 300 BDT (Payment 3 or carry forward)
```

---

## Implementation Decision

**Use SupplierBill for Payment Flow:**

1. **When PurchaseInvoice is APPROVED:**
   - Observer checks if SupplierBill exists for this invoice
   - If not, create one automatically
   
2. **SupplierBill Creation:**
   ```php
   SupplierBill.create({
       reference_type: SupplierBillReferenceType::LINKED_STOCK_RECEIVE,
       reference_id: invoice.id,
       supplier_id: invoice.supplier_id,
       bill_no: "BILL-" . invoice.invoice_no,
       total_amount: invoice.total_amount,
       status: SupplierBillStatus::OPEN,
       // ... other fields
   })
   ```

3. **All Payments Against SupplierBill:**
   - SupplierPayment → SupplierPaymentAllocation → SupplierBill
   - Not directly against PurchaseInvoice

4. **Transaction Creation on Payment Completion:**
   - When BankingPaymentRequest = COMPLETED
   - Create GL entry: DR AP / CR Bank
   - Update SupplierBill.paid_amount & status

---

## Summary Table

| Action | Uses | Status Flow |
|--------|------|------------|
| Stock received → Invoice | PurchaseInvoice | PENDING → APPROVED |
| Invoice approved → Bill | SupplierBill (auto) | OPEN |
| Payment allocated → Bill | SupplierPaymentAllocation | OPEN → PARTIAL |
| Payment completed → GL | Transaction | Payment recorded |

**Result:** Clean separation of concerns
- PurchaseInvoice = Accounting document (what was ordered/received)
- SupplierBill = Payment tracker (how much paid/due)
- SupplierPayment = Payment event (bank transaction)
- Transaction = GL record (financial posting)

