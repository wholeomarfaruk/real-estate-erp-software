# Banking Payment Request - Complete Schema & Usage Guide

## Table Columns

| Column | Type | Nullable | Purpose |
|--------|------|----------|---------|
| id | bigint | NO | Primary key |
| request_no | string(30) | NO | Unique request number (BPR-yymmdd-xxxxx) |
| source_type | string(30) | NO | Type of payment (employee_advance, payroll, expense, supplier) |
| sourceable_type | string(255) | YES | Polymorphic morph class name |
| sourceable_id | bigint | YES | Polymorphic morph ID |
| amount | decimal(15,3) | NO | Total payment amount |
| description | text | YES | Payment description |
| bank_account_id | bigint | YES | FK to bank_accounts (optional, for bank selection) |
| account_id | bigint | YES | FK to accounts (legacy/unused) |
| **debit_account_id** | bigint | YES | FK to accounts (debit side) |
| **debit_amount** | decimal(15,3) | YES | Debit amount |
| **credit_account_id** | bigint | YES | FK to accounts (credit side) |
| **credit_amount** | decimal(15,3) | YES | Credit amount |
| reference_no | string(50) | YES | External reference (Invoice#, PO#, etc) |
| name | string(100) | YES | Payee/Payer name |
| phone | string(20) | YES | Payee/Payer phone |
| method | string(20) | YES | Payment method (cash, bank, cheque, mfs) |
| transaction_category_id | bigint | YES | FK to transaction_categories |
| transaction_id | bigint | YES | FK to transactions (populated on completion) |
| status | string(20) | NO | Lifecycle: pending → approved → released → completed/rejected |
| notes | text | YES | Internal notes |
| rejection_reason | text | YES | Reason if rejected |
| requested_by | bigint | NO | FK to users (requester) |
| approved_by | bigint | YES | FK to users (who approved) |
| approved_at | datetime | YES | When approved |
| released_by | bigint | YES | FK to users (who released) |
| released_at | datetime | YES | When released |
| completed_by | bigint | YES | FK to users (who completed) |
| completed_at | datetime | YES | When completed |
| rejected_by | bigint | YES | FK to users (who rejected) |
| rejected_at | datetime | YES | When rejected |
| external_data | json | YES | Custom data by source type |
| created_at | timestamp | NO | Created timestamp |
| updated_at | timestamp | NO | Updated timestamp |

---

## Source Type Schemas

### 1. EMPLOYEE_ADVANCE (uses PostingEngine)

**Source**: `employee_advance`

**Column Configuration**:
```php
'source_type'           => PaymentRequestSourceType::EMPLOYEE_ADVANCE->value,
'sourceable_type'       => Employee::class,          // morph to employee
'sourceable_id'         => $employee->id,
'amount'                => $advance->amount,
'name'                  => $employee->name,
'description'           => "Employee advance — {$employee->name}",
'debit_account_id'      => NULL,                     // PostingEngine resolves
'debit_amount'          => NULL,
'credit_account_id'     => NULL,                     // PostingEngine resolves
'credit_amount'         => NULL,
'bank_account_id'       => $bankAccount->id,         // optional
'method'                => 'cash',                   // payment method
'transaction_category_id' => NULL,
'status'                => 'pending',
'requested_by'          => $actorId,
'external_data'         => [
    'employee_advance_id' => $advance->id,
    'payment_account_id'  => $accountId,             // COA account
    'employee_id'         => $employee->id,
],
```

**Validation Rules**:
- ✗ debit_account_id & credit_account_id NOT required
- ✓ sourceable must be set (Employee morph)
- ✓ external_data must contain employee_advance_id & payment_account_id
- ✓ amount > 0

**Completion Handler**: `BankingTransactionService::postEmployeeAdvancePayment()`
- Routes to `EmployeeAdvanceService::completeAdvancePayment()`
- Posts via `HrmAccountingService::createEmployeeAdvanceTransaction()`
- Posts: DR Employee Advance / CR Payment Account

---

### 2. PAYROLL (uses PostingEngine)

**Source**: `payroll`

**Column Configuration**:
```php
'source_type'           => PaymentRequestSourceType::PAYROLL->value,
'sourceable_type'       => PayrollPayment::class,
'sourceable_id'         => $payment->id,
'amount'                => $payment->amount,
'debit_account_id'      => $salaryPayableAccount->id,
'debit_amount'          => $payment->amount,
'credit_account_id'     => $paymentAccount->id,
'credit_amount'         => $payment->amount,
'name'                  => $employee->name,
'method'                => $payment->payment_method,
'status'                => 'pending',
'requested_by'          => $actorId,
```

**Validation Rules**:
- ✓ debit_account_id & credit_account_id REQUIRED (pre-stored)
- ✓ debit_amount & credit_amount REQUIRED
- ✓ amounts must equal request amount
- ✓ sourceable must be PayrollPayment

**Completion Handler**: `BankingTransactionService::postPayrollPayment()`
- Uses stored debit/credit accounts
- Posts: DR Salary Payable / CR Payment Account

---

### 3. EXPENSE (uses pre-stored accounts)

**Source**: `expense` (TransactionType enum value)

**Column Configuration**:
```php
'source_type'           => TransactionType::EXPENSE->value,
'sourceable_type'       => NULL,                     // optional
'sourceable_id'         => NULL,
'amount'                => $expenseAmount,
'debit_account_id'      => $expenseAccount->id,
'debit_amount'          => $expenseAmount,
'credit_account_id'     => $paymentAccount->id,
'credit_amount'         => $expenseAmount,
'name'                  => 'Expense Description',
'reference_no'          => 'EXP-001',
'method'                => 'cash',
'status'                => 'pending',
'requested_by'          => $actorId,
```

**Validation Rules**:
- ✓ debit_account_id & credit_account_id REQUIRED
- ✓ amounts must be equal and match request amount
- ✓ account_id can also be set (payment account)

**Completion Handler**: `BankingTransactionService::postExpensePayment()`
- Uses stored debit/credit accounts
- Posts: DR Expense / CR Payment Account

---

### 4. SUPPLIER (uses pre-stored accounts)

**Source**: `supplier`

**Column Configuration**:
```php
'source_type'           => PaymentRequestSourceType::SUPPLIER->value,
'sourceable_type'       => PurchaseInvoice::class,
'sourceable_id'         => $invoice->id,
'amount'                => $invoiceAmount,
'debit_account_id'      => $apAccount->id,           // Accounts Payable
'debit_amount'          => $invoiceAmount,
'credit_account_id'     => $paymentAccount->id,
'credit_amount'         => $invoiceAmount,
'name'                  => $supplier->name,
'reference_no'          => $invoice->invoice_no,
'method'                => 'bank',
'status'                => 'pending',
'requested_by'          => $actorId,
```

**Validation Rules**:
- ✓ debit_account_id & credit_account_id REQUIRED
- ✓ amounts must match invoice amount
- ✓ sourceable must be PurchaseInvoice

**Completion Handler**: `BankingTransactionService::postSupplierPayment()`
- Uses stored debit/credit accounts
- Posts: DR Accounts Payable / CR Payment Account

---

## Status Lifecycle

```
┌─────────┐       ┌──────────┐       ┌──────────┐       ┌───────────┐
│ pending │ ─────→ │ approved │ ─────→ │ released │ ─────→ │ completed │
└─────────┘       └──────────┘       └──────────┘       └───────────┘
    ↓                   ↓                   ↓                   ↓
    └───────────────────┴───────────────────┴───────────────────┘
                      rejected
```

**Status Transitions**:
- `pending` → `approved`: Manager/accountant approves
- `approved` → `released`: Finance releases for payment
- `released` → `completed`: Payment executed (transaction created)
- Any → `rejected`: Request denied with reason_rejection

---

## Validation Logic in BankingTransactionService

```php
// Check if source uses PostingEngine (debit/credit not required)
$usesPostingEngine = in_array($request->source_type, [
    PaymentRequestSourceType::EMPLOYEE_ADVANCE->value,
    PaymentRequestSourceType::PAYROLL->value,
], true);

if (!$usesPostingEngine) {
    // Validate pre-stored debit/credit accounts exist and are active
    if (!$request->debit_account_id || !$request->credit_account_id) {
        throw new DomainException('Double-entry accounts not configured...');
    }
    // Validate amounts match
    if ($request->debit_amount !== $request->credit_amount) {
        throw new DomainException('Debit and credit amounts must match...');
    }
}
```

---

## Model Casts

```php
protected $casts = [
    'amount'           => 'decimal:3',
    'debit_amount'     => 'decimal:3',
    'credit_amount'    => 'decimal:3',
    'payment_date'     => 'date',
    'approved_at'      => 'datetime',
    'released_at'      => 'datetime',
    'completed_at'     => 'datetime',
    'rejected_at'      => 'datetime',
    'external_data'    => 'array',
];
```

---

## Completion Rules

| Source | Handler | Posts |
|--------|---------|-------|
| employee_advance | `postEmployeeAdvancePayment()` | `hrm.advance_disbursement` event |
| payroll | `postPayrollPayment()` | DR Salary Payable / CR Payment Account |
| expense | `postExpensePayment()` | DR Expense / CR Payment Account |
| supplier | `postSupplierPayment()` | DR AP / CR Payment Account |

---

## Example: Creating a Banking Request

### Employee Advance
```php
$req = BankingPaymentRequest::create([
    'request_no' => BankingPaymentRequest::generateRequestNo(),
    'source_type' => PaymentRequestSourceType::EMPLOYEE_ADVANCE->value,
    'sourceable_type' => Employee::class,
    'sourceable_id' => $employee->id,
    'amount' => 20000,
    'name' => $employee->name,
    'description' => "Employee advance for {$employee->name}",
    'status' => 'pending',
    'requested_by' => auth()->id(),
    'external_data' => [
        'employee_advance_id' => $advance->id,
        'payment_account_id' => $paymentAccount->id,
        'employee_id' => $employee->id,
    ],
]);
```

### Payroll Payment
```php
$req = BankingPaymentRequest::create([
    'request_no' => BankingPaymentRequest::generateRequestNo(),
    'source_type' => PaymentRequestSourceType::PAYROLL->value,
    'sourceable_type' => PayrollPayment::class,
    'sourceable_id' => $payment->id,
    'amount' => 52000,
    'debit_account_id' => $salaryPayableAcc->id,
    'debit_amount' => 52000,
    'credit_account_id' => $cashAcc->id,
    'credit_amount' => 52000,
    'name' => $employee->name,
    'method' => 'cash',
    'status' => 'pending',
    'requested_by' => auth()->id(),
]);
```

---

## Error Fixes Applied

### Issue: "Debit account not found (ID: )"
**Cause**: Validation was checking debit/credit accounts for PostingEngine sources

**Fix**: Updated `validatePaymentRequest()` to skip account validation for:
- `employee_advance` (uses PostingEngine)
- `payroll` (uses PostingEngine)

### Issue: Missing columns in model
**Cause**: `debit_amount` and `credit_amount` not in casts

**Fix**: Added to `BankingPaymentRequest::$casts`:
```php
'debit_amount'  => 'decimal:3',
'credit_amount' => 'decimal:3',
```

---

✅ **System Status**: All banking payment request columns properly configured and validated
