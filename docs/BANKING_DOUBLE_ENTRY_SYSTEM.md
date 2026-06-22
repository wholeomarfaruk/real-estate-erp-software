# Banking Double-Entry Transaction System

## Overview

The Banking Double-Entry System ensures all payment completions through the banking module create properly balanced double-entry journal entries in the Chart of Accounts. This document describes the architecture, workflows, and integration points.

## System Components

### Core Service: BankingTransactionService

**File:** `app/Services/Accounts/BankingTransactionService.php`

Central orchestrator for completing banking payment requests with double-entry transactions.

**Primary Method:**
```php
public function completePaymentRequest(
    BankingPaymentRequest $request,
    int $userId
): Transaction
```

Routes payment completion to the appropriate handler based on `source_type`.

### Models

#### BankingPaymentRequest
- Represents a banking payment workflow (pending → approved → released → completed)
- Links to polymorphic sourceable records (Expense, PayrollPayment, PurchaseInvoice, PurchaseFund)
- Fields:
  - `account_id`: Chart of Accounts entry (payment source/destination)
  - `bank_account_id`: Bank account information (linked to account via BankAccount.account_id)
  - `transaction_id`: Linked ledger transaction (set on completion)
  - `status`: Workflow state
  - `amount`: Payment amount

#### BankAccount
- Information storage only (bank name, account number, branch, etc.)
- Linked to Chart of Accounts via `account_id`
- **No accounting logic directly in this model**

#### Account (Chart of Accounts)
- Holds ledger account master data
- Linked to bank/cash accounts via one-to-one relationship
- Balance computed from TransactionLine entries

#### Transaction & TransactionLine
- Double-entry ledger entries
- Each transaction has multiple lines (per-account debit/credit)
- Transaction must balance (sum of debits = sum of credits)

## Payment Completion Workflows

### 1. Expense Payment

**Source Type:** `TransactionType::EXPENSE`

**Double-Entry:**
- **Debit:** Expense Account (from `expense.payment` accounting event)
- **Credit:** Payment Account (bank/cash)

**Accounting Event:** `expense.payment` (must be configured)

**Example:**
```
DR Expenses - Office Supplies    $1,000.00
  CR Cash on Hand                           $1,000.00
```

### 2. Payroll Payment

**Source Type:** `PaymentRequestSourceType::PAYROLL`

**Double-Entry:**
- **Debit:** Salary Payable (from `payroll.payment` accounting event)
- **Credit:** Payment Account (bank/cash)

**Accounting Event:** `payroll.payment` (must be configured)

**Example:**
```
DR Salary Payable                 $5,000.00
  CR Bank - Checking Account                $5,000.00
```

### 3. Supplier Invoice Payment

**Source Type:** `PaymentRequestSourceType::SUPPLIER`

**Double-Entry:**
- **Debit:** Accounts Payable (from `purchase.supplier_payment` accounting event)
- **Credit:** Payment Account (bank/cash)

**Accounting Event:** `purchase.supplier_payment` (must be configured)

**Example:**
```
DR Accounts Payable               $25,000.00
  CR Bank - Checking Account                $25,000.00
```

### 4. Advance Fund (Purchase Order)

**Source Type:** `TransactionType::ADVANCE`

**Double-Entry:**
- **Debit:** Supplier Advance (asset, from `purchase.supplier_advance` accounting event)
- **Credit:** Payment Account (bank/cash)

**Accounting Event:** `purchase.supplier_advance` (must be configured)

**Example:**
```
DR Supplier Advance               $50,000.00
  CR Bank - Checking Account                $50,000.00
```

### 5. Income / Opening Balance

**Source Type:** `TransactionType::INCOME`

**Double-Entry:**
- **Debit:** Payment Account (bank/cash)
- **Credit:** Opening Balance / Income Account (auto-created if needed)

**Note:** This type does NOT use accounting events; creates entry directly via LedgerService.

**Example:**
```
DR Cash on Hand                  $10,000.00
  CR Opening Balance / Income                $10,000.00
```

## Accounting Events Configuration

For expense, payroll, supplier, and advance payments, configure the required accounting events:

### Expense Payment Event

```
Event Key: expense.payment
Transaction Type: EXPENSE

Posting Rules:
  1. Leg: DEBIT    | Account: [Expense Account]      | Description: Expense
  2. Leg: CREDIT   | Slot: payment_account            | Description: Payment Account
```

### Payroll Payment Event

```
Event Key: payroll.payment
Transaction Type: EXPENSE

Posting Rules:
  1. Leg: DEBIT    | Account: [Salary Payable]      | Description: Salary Payable
  2. Leg: CREDIT   | Slot: payment_account           | Description: Payment Account
```

### Supplier Payment Event

```
Event Key: purchase.supplier_payment
Transaction Type: EXPENSE

Posting Rules:
  1. Leg: DEBIT    | Account: [Accounts Payable]    | Description: Accounts Payable
  2. Leg: CREDIT   | Slot: payment_account           | Description: Payment Account
```

### Supplier Advance Event

```
Event Key: purchase.supplier_advance
Transaction Type: ADVANCE

Posting Rules:
  1. Leg: DEBIT    | Account: [Supplier Advance]    | Description: Supplier Advance
  2. Leg: CREDIT   | Slot: payment_account           | Description: Payment Account
```

## Validation Rules

When completing a banking payment request, the service validates:

1. **Status:** Request must be in `released` state
2. **Amount:** Must be greater than zero
3. **Payment Account:** Must exist and be active
4. **Not Already Completed:** Request must not have `transaction_id` set
5. **Source Validation:** Polymorphic sourceable record must exist (for expense, payroll, supplier, advance)

## Error Handling

### Transaction Posting Failures

If posting fails, the BankingPaymentRequest remains in `released` state:
- Payment request is NOT marked completed
- No `transaction_id` is set
- User can retry completion without recreating the payment request

### Missing Accounting Events

If a required accounting event is not configured:
```
Cannot post expense payment: No active accounting event configured for: expense.payment
(ensure 'expense.payment' event is configured)
```

### Missing Bank Account Link

If a bank account is not linked to the Chart of Accounts:
```
Bank account has no linked Chart of Accounts entry.
```

## Model Helper Methods

### BankingPaymentRequest

```php
// Resolve the payment account (COA entry)
public function getPaymentAccount(): ?Account

// Check if payment has been completed with transaction
public function isCompleted(): bool

// Check if payment can be completed now
public function canBeCompleted(): bool
```

## UI Integration

### Banking Management Component

**File:** `app/Livewire/Admin/Accounts/Banking/BankingManagement.php`

- Refactored `markCompleted()` to use centralized BankingTransactionService
- Single try-catch block handles all payment types
- Uniform error messaging to user

**Detail Drawer Enhancement:**
- Shows linked transaction ID when payment is completed
- Displays transaction type and creation timestamp
- Shows double-entry summary (debit/credit amounts)

## Data Migration

### Backfill Command

Migrate previously completed requests without transaction records:

```bash
# Show what would be processed (dry run)
php artisan banking:backfill-transactions --dry-run

# Process all completed requests without transaction_id
php artisan banking:backfill-transactions

# Only process previously failed requests
php artisan banking:backfill-transactions --only-failed
```

## Testing

### Test File
`tests/Feature/Accounts/BankingTransactionServiceTest.php`

### Test Coverage

- ✅ Complete expense payment creates balanced transaction
- ✅ Complete income payment creates balanced transaction
- ✅ Reject completion if not released
- ✅ Reject completion if already completed
- ✅ Reject completion with zero amount
- ✅ Reject completion without valid payment account
- ✅ Transaction references banking request
- ✅ Transaction uses correct creator (user_id)
- ✅ Payroll payment requires valid sourceable
- ✅ Supplier payment requires valid sourceable
- ✅ Advance fund requires valid sourceable

## Workflow Diagram

```
BankingPaymentRequest (pending)
    ↓
    [Approve]
BankingPaymentRequest (approved)
    ↓
    [Release]
BankingPaymentRequest (released)
    ↓
    [Mark Completed] → BankingTransactionService.completePaymentRequest()
    ↓
    Route by source_type:
    ├─ EXPENSE       → PostingEngine('expense.payment')
    ├─ PAYROLL       → PostingEngine('payroll.payment')
    ├─ SUPPLIER      → PostingEngine('purchase.supplier_payment')
    ├─ ADVANCE       → PostingEngine('purchase.supplier_advance')
    └─ INCOME        → LedgerService.post()
    ↓
    Create Transaction with TransactionLines
    ↓
    Update BankingPaymentRequest:
    ├─ status = 'completed'
    ├─ transaction_id = [created transaction ID]
    ├─ completed_by = [user ID]
    └─ completed_at = now()
    ↓
BankingPaymentRequest (completed)
```

## Integration with Existing Services

### Previously Used Services

The following services had payment completion logic that is now delegated to BankingTransactionService:

1. **ExpenseService::completeExpense()** — Now uses BankingTransactionService
2. **PayrollService::completePayrollPayment()** — Can delegate to BankingTransactionService
3. **FundReleaseService::completeRelease()** — Can delegate to BankingTransactionService
4. **PurchaseInvoicePaymentService::completePayment()** — Can delegate to BankingTransactionService

### Deprecation Path

Original methods should remain for backward compatibility but can eventually route through BankingTransactionService internally.

## Best Practices

### For Developers

1. **Always complete payments via BankingManagement UI** — Ensures consistent workflow
2. **Do not call service methods directly** — Use BankingManagement as entry point
3. **Validate accounting events are configured** — Check PostingEngine before completing payments
4. **Test with all payment types** — Ensure changes work across expense, payroll, supplier, advance, income

### For Accountants

1. **Ensure accounting events are configured** before allowing users to complete payments
2. **Link all bank accounts to Chart of Accounts** before processing payments
3. **Review double-entry entries after payment completion** via transaction detail view
4. **Monitor for posting failures** in application logs

## Troubleshooting

### Transaction Posting Fails

1. Check if accounting event is configured: `AccountingEvent::active()->forKey('expense.payment')`
2. Verify account is active: `Account::find($id)->is_active`
3. Verify posting rules exist: `$event->rules->count() > 0`
4. Ensure transaction lines balance: sum(debit) = sum(credit)

### Payment Request Won't Complete

1. Verify status is 'released': `$request->status`
2. Verify amount > 0: `$request->amount > 0`
3. Verify payment account exists: `$request->getPaymentAccount()`
4. Check for missing `transaction_id`: indicates already completed

### Bank Account Not Linked

1. Ensure BankAccount has `account_id` set: `$bankAccount->account_id`
2. Verify linked account exists and is active: `$bankAccount->account->is_active`
3. Update bank account: `$bankAccount->update(['account_id' => $coaAccountId])`

## Future Enhancements

1. **Event-based notifications** — Notify accountants when payments complete
2. **Automatic reversals** — Generate reversal transactions for rejected payments
3. **Multi-currency support** — Handle currency conversion in entries
4. **Audit trail report** — Track who completed each payment and resulting transaction
5. **Batch processing** — Complete multiple payments in one operation
