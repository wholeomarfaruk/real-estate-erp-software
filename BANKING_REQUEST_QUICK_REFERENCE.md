# Banking Payment Request - Quick Reference

## Column Groups by Purpose

### 📋 Request Identity
- `request_no` → Unique ID (BPR-yymmdd-xxxxx)
- `source_type` → Type (employee_advance, payroll, expense, supplier)
- `status` → Lifecycle (pending → approved → released → completed)

### 🔗 Sourceable (Polymorphic Link)
- `sourceable_type` → Class name (Employee, PayrollPayment, PurchaseInvoice, etc)
- `sourceable_id` → ID within that class
- `external_data` → JSON with extra context (advance_id, payment_account_id, etc)

### 💰 Amount & Accounts
- `amount` → Total payment amount
- `debit_account_id` → Account debited (required for pre-stored sources)
- `debit_amount` → Amount debited
- `credit_account_id` → Account credited (required for pre-stored sources)
- `credit_amount` → Amount credited

### 🏦 Banking Details
- `bank_account_id` → FK to bank_accounts (optional)
- `account_id` → FK to accounts (legacy/unused)
- `payment_date` → When payment was made (if applicable)
- `method` → Payment method (cash, bank, cheque, mfs)

### 📝 Reference Info
- `description` → What the payment is for
- `reference_no` → Invoice#, PO#, etc
- `name` → Payee/Payer name
- `phone` → Contact number
- `transaction_category_id` → Category (optional)
- `notes` → Internal notes
- `rejection_reason` → If rejected, why?

### 🔐 Audit Trail
- `requested_by` → Who requested
- `approved_by` → Who approved (if applicable)
- `approved_at` → When approved
- `released_by` → Who released for payment
- `released_at` → When released
- `completed_by` → Who completed
- `completed_at` → When completed
- `rejected_by` → Who rejected (if applicable)
- `rejected_at` → When rejected

### 🔗 Transaction Link
- `transaction_id` → FK to transactions (populated after completion)

---

## What to Fill By Source Type

### ✅ EMPLOYEE_ADVANCE (Minimal)
```
source_type             ← 'employee_advance'
sourceable_type         ← Employee::class
sourceable_id           ← employee.id
amount                  ← advance amount
name                    ← employee.name
status                  ← 'pending'
external_data           ← {employee_advance_id, payment_account_id, employee_id}
requested_by            ← auth()->id()

❌ Skip:
  debit_account_id      (PostingEngine resolves)
  credit_account_id     (PostingEngine resolves)
```

### ✅ PAYROLL (Complete)
```
source_type             ← 'payroll'
sourceable_type         ← PayrollPayment::class
sourceable_id           ← payment.id
amount                  ← payment.amount
debit_account_id        ← salary_payable.id
debit_amount            ← payment.amount
credit_account_id       ← payment_account.id
credit_amount           ← payment.amount
name                    ← employee.name
method                  ← payment_method
status                  ← 'pending'
requested_by            ← auth()->id()
```

### ✅ EXPENSE (Complete)
```
source_type             ← 'expense'
amount                  ← expense.amount
debit_account_id        ← expense_account.id
debit_amount            ← expense.amount
credit_account_id       ← payment_account.id
credit_amount           ← expense.amount
name                    ← 'Expense Description'
reference_no            ← 'EXP-001'
method                  ← 'cash'
status                  ← 'pending'
requested_by            ← auth()->id()
```

### ✅ SUPPLIER (Complete)
```
source_type             ← 'supplier'
sourceable_type         ← PurchaseInvoice::class
sourceable_id           ← invoice.id
amount                  ← invoice.amount
debit_account_id        ← ap_account.id
debit_amount            ← invoice.amount
credit_account_id       ← payment_account.id
credit_amount           ← invoice.amount
name                    ← supplier.name
reference_no            ← invoice.invoice_no
status                  ← 'pending'
requested_by            ← auth()->id()
```

---

## Status Transitions

```
pending
  ↓ (Manager approves)
approved
  ↓ (Finance releases)
released
  ↓ (Complete payment - creates Transaction)
completed ✓
  
OR

[pending/approved/released] → rejected (with reason)
```

---

## Common Issues & Fixes

| Issue | Cause | Fix |
|-------|-------|-----|
| "Debit account not found (ID: )" | Validation checking debit/credit for PostingEngine sources | PostingEngine sources (employee_advance, payroll) skip this check |
| debit_amount/credit_amount not saving | Not in model casts | Added `'debit_amount' => 'decimal:3'` to `$casts` |
| Banking request won't complete | Missing required fields for pre-stored sources | Ensure debit_account_id, credit_account_id, and amounts are set |
| sourceable not found | Morph not set correctly | Verify sourceable_type and sourceable_id match entity |

---

## Validation Rules

### All Sources
- ✓ amount > 0
- ✓ status in [pending, approved, released, completed, rejected]
- ✓ requested_by must be valid user

### PostingEngine Sources (employee_advance, payroll)
- ✓ sourceable_type & sourceable_id must be set
- ✗ debit_account_id & credit_account_id NOT required
- ~ external_data may contain resolution hints

### Pre-stored Sources (expense, supplier)
- ✓ debit_account_id & credit_account_id REQUIRED
- ✓ debit_amount & credit_amount REQUIRED
- ✓ debit_amount == credit_amount == request amount
- ✓ Both accounts must be active

---

## Completion Process

1. **Request Status**: pending → approved → released
2. **Completion**: When status === 'released', call `BankingTransactionService::completePaymentRequest()`
3. **Routing**: Service routes to appropriate handler based on source_type
4. **Journal Entry**: Handler posts Transaction with appropriate debit/credit lines
5. **Update**: Banking request status → completed, transaction_id populated

---

## Testing Checklist

- [ ] Employee advance created with external_data
- [ ] Payroll request has debit/credit accounts populated
- [ ] Validation skips D/C check for PostingEngine sources
- [ ] Validation requires D/C for pre-stored sources
- [ ] Status workflow: pending → approved → released → completed
- [ ] Transaction created with correct journal entries
- [ ] Amounts match across all fields

---

✅ **See BANKING_PAYMENT_REQUEST_SCHEMA.md for detailed column documentation**
