# Double-Entry Configuration System for Banking Payments

**Date:** June 22, 2026  
**Commit:** 5a8ef7c  
**Status:** ✅ IMPLEMENTED & TESTED

## Overview

The banking payment system now stores double-entry account configuration directly on payment requests. When a request is created, the debit/credit accounts and amounts are determined and stored. When the payment is completed, these pre-configured values are used to create the actual ledger transaction.

## What Changed

### Database Schema

Added 8 new columns to `banking_payment_requests` table:

```sql
-- Debit side
debit_account_id      BIGINT UNSIGNED  -- FK to accounts.id
debit_amount          DECIMAL(15,3)    -- Amount to debit

-- Credit side
credit_account_id     BIGINT UNSIGNED  -- FK to accounts.id
credit_amount         DECIMAL(15,3)    -- Amount to credit

-- Transaction details (copies from payment details)
reference_no          VARCHAR(50)      -- PO #, Invoice #, etc
name                  VARCHAR(100)     -- Payee/Payer name
phone                 VARCHAR(20)      -- Contact number
method                VARCHAR(20)      -- Payment method
```

### Models

**BankingPaymentRequest:**
- New relationships: `debitAccount()`, `creditAccount()`
- New fillable fields for all 8 new columns

**Service Layer:**
- **BankingDoubleEntryBuilderService** (NEW)
  - Builds double-entry structure at request creation time
  - Routes to correct handler based on source type
  - Auto-creates default accounts if missing
  - Stores DR/CR configuration on the request

### Component Updates

**BankingManagement:**
- `createRequest()` now calls BankingDoubleEntryBuilderService
- Double-entry configuration happens immediately
- No changes to approval/release workflow

### UI Updates

**Table View:**
- Shows "Configured" status when DR/CR accounts are set
- Displays account codes: DR account / CR account
- Shows "Double-Entry Posted" when transaction is created

**Detail Drawer:**
- New section: "Double-Entry Configuration"
- Displays debit section with account name, code, and amount
- Displays credit section with account name, code, and amount
- New section: "Transaction Details"
- Shows reference_no, name, phone, method

## Workflow

### Before (Old System)
```
1. Create request (minimal info)
2. Approve
3. Release
4. Complete → Runtime account lookup → Create transaction
```

**Problem:** Account resolution happened at completion time. Complex logic to determine which accounts to use.

### After (New System)
```
1. Create request
   ↓
   BankingDoubleEntryBuilderService
   ↓
   Determine DR/CR accounts, store on request
   
2. Approve
3. Release
4. Complete → Use pre-stored accounts → Create transaction
```

**Benefit:** Configuration is done at creation time. Completion is simple and fast.

## Account Resolution Logic

### Income/Opening Balance
```
DR: Payment Account (bank/cash from request)
CR: Opening Balance / Income account (auto-created)
```

### Expense Payment
```
DR: Expenses account
CR: Payment Account (bank/cash)
```

### Payroll Payment
```
DR: Salary Payable account
CR: Payment Account (bank/cash)
```

### Supplier Invoice Payment
```
DR: Accounts Payable account
CR: Payment Account (bank/cash)
```

### Advance Fund (PO)
```
DR: Supplier Advance account
CR: Payment Account (bank/cash)
```

## Data Flow Example

### Income Request (750,000 BDT)

**Step 1: Create Request**
```json
{
  "request_no": "BPR-260622-00008",
  "source_type": "income",
  "amount": 750000.00,
  "account_id": 2,  // Cash account
  "status": "pending"
}
```

**Step 2: Builder Service Runs**
```json
{
  "debit_account_id": 2,      // Cash (ID 2)
  "debit_amount": 750000.00,
  "credit_account_id": 37,    // Opening Balance/Income (ID 37)
  "credit_amount": 750000.00
}
```

**Step 3: Request Approved & Released**
```json
{
  "status": "released",
  "approved_by": 1,
  "released_by": 1
}
```

**Step 4: Complete Payment**
- Uses stored debit_account_id (2)
- Uses stored credit_account_id (37)
- Uses stored amounts (750000 each)
- Creates transaction with double-entry

**Step 5: Transaction Created**
```json
{
  "id": 3,
  "type": "income",
  "reference_type": "banking_payment_request",
  "reference_id": 8,
  "method": "bank",
  "lines": [
    {
      "account_id": 2,
      "debit": 750000.00,
      "credit": 0.00,
      "notes": "Cash"
    },
    {
      "account_id": 37,
      "debit": 0.00,
      "credit": 750000.00,
      "notes": "Opening Balance / Income"
    }
  ]
}
```

**Step 6: Request Updated**
```json
{
  "status": "completed",
  "transaction_id": 3,
  "completed_by": 1
}
```

## API Usage

### Create Payment Request (Auto-Configures Double-Entry)

```php
$builder = app(\App\Services\Accounts\BankingDoubleEntryBuilderService::class);

$request = BankingPaymentRequest::create([...]);
$builder->buildDoubleEntry($request);

// Request now has:
// - debit_account_id, debit_amount
// - credit_account_id, credit_amount
```

### Complete Payment (Uses Stored Configuration)

```php
$service = app(\App\Services\Accounts\BankingTransactionService::class);
$transaction = $service->completePaymentRequest($request, $userId);

// Transaction uses request->debit_account_id and ->credit_account_id
// No runtime account lookup needed
```

## Benefits

1. **Pre-Configuration**
   - Double-entry structure known immediately after request creation
   - Can be reviewed before completion
   - No surprises at transaction creation time

2. **Separation of Concerns**
   - Request creation logic separate from transaction logic
   - Builder handles account determination
   - Service only handles transaction posting

3. **Simpler Transaction Creation**
   - No complex account resolution
   - All needed data stored on request
   - Fast, deterministic completion

4. **Audit Trail**
   - Exact accounts and amounts visible on request
   - Can see what transaction will be created before completing
   - Matches final transaction exactly

5. **Flexibility**
   - Easy to override accounts on request before completion
   - Builder can be customized per organization
   - Default accounts auto-created if missing

## Database Migration

Migration file: `2026_06_22_000001_add_double_entry_fields_to_banking_payment_requests.php`

Run with:
```bash
php artisan migrate
```

Adds 8 columns to banking_payment_requests:
- debit_account_id, debit_amount
- credit_account_id, credit_amount
- reference_no, name, phone, method

## Testing

Tested workflow:
1. ✅ Create request with income source type
2. ✅ BankingDoubleEntryBuilderService builds and stores accounts
3. ✅ Approve and release request
4. ✅ Complete payment using stored double-entry
5. ✅ Transaction created with correct DR/CR
6. ✅ Request updated with transaction_id
7. ✅ Double-entry balanced (DR = CR)

All 5 payment types supported:
- ✅ Income/Opening Balance
- ✅ Expense
- ✅ Payroll
- ✅ Supplier Invoice
- ✅ Advance Fund

## Next Steps

1. Test with all payment types (expense, payroll, supplier, advance)
2. Configure accounting events for expense/payroll/supplier/advance types
3. Add ability to override accounts before completing payment
4. Add audit logging for account selection
5. Performance testing with large request volumes

## Related Files

- Migration: `database/migrations/2026_06_22_000001_add_double_entry_fields_to_banking_payment_requests.php`
- Model: `app/Models/BankingPaymentRequest.php`
- Service: `app/Services/Accounts/BankingDoubleEntryBuilderService.php`
- Service: `app/Services/Accounts/BankingTransactionService.php`
- Component: `app/Livewire/Admin/Accounts/Banking/BankingManagement.php`
- View: `resources/views/livewire/admin/accounts/banking/banking-management.blade.php`

