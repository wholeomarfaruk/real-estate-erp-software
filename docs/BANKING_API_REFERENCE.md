# Banking Transaction Service - API Reference

## BankingTransactionService

**Namespace:** `App\Services\Accounts\BankingTransactionService`

### Constructor

```php
public function __construct(
    private readonly PostingEngine $engine,
    private readonly LedgerService $ledger,
)
```

### Public Methods

#### completePaymentRequest()

Complete a banking payment request by posting the appropriate double-entry transaction.

```php
public function completePaymentRequest(
    BankingPaymentRequest $request,
    int $userId
): Transaction
```

**Parameters:**
- `$request` - The banking payment request to complete
- `$userId` - The user ID completing the payment (for audit trail)

**Returns:**
- `Transaction` - The created ledger transaction

**Throws:**
- `\DomainException` - If validation fails or posting error occurs

**Usage:**
```php
$service = app(BankingTransactionService::class);
$transaction = $service->completePaymentRequest($request, Auth::id());
```

**Example with All Payment Types:**

```php
// Expense payment
$expenseRequest = BankingPaymentRequest::where('source_type', 'expense')
    ->where('status', 'released')
    ->first();
$transaction = $service->completePaymentRequest($expenseRequest, Auth::id());

// Payroll payment
$payrollRequest = BankingPaymentRequest::where('source_type', 'payroll')
    ->where('status', 'released')
    ->first();
$transaction = $service->completePaymentRequest($payrollRequest, Auth::id());

// Supplier invoice payment
$supplierRequest = BankingPaymentRequest::where('source_type', 'supplier')
    ->where('status', 'released')
    ->first();
$transaction = $service->completePaymentRequest($supplierRequest, Auth::id());

// Advance fund
$advanceRequest = BankingPaymentRequest::where('source_type', 'advance')
    ->where('status', 'released')
    ->first();
$transaction = $service->completePaymentRequest($advanceRequest, Auth::id());

// Income/opening balance
$incomeRequest = BankingPaymentRequest::where('source_type', 'income')
    ->where('status', 'released')
    ->first();
$transaction = $service->completePaymentRequest($incomeRequest, Auth::id());
```

---

## BankingPaymentRequest Model

**Namespace:** `App\Models\BankingPaymentRequest`

### Properties

| Property | Type | Description |
|----------|------|-------------|
| `id` | int | Primary key |
| `request_no` | string | Unique request number (e.g., BPR-260622-00001) |
| `source_type` | string | Transaction type or payment source |
| `sourceable_type` | string | Polymorphic source class name |
| `sourceable_id` | int | Polymorphic source record ID |
| `transaction_category_id` | int | Transaction category (nullable) |
| `transaction_id` | int | Linked ledger transaction (set on completion) |
| `amount` | decimal | Payment amount |
| `payment_date` | date | Payment date (nullable) |
| `description` | string | Payment description |
| `bank_account_id` | int | Bank account reference |
| `account_id` | int | Chart of Accounts payment account |
| `status` | string | pending, approved, released, completed, rejected |
| `notes` | text | Additional notes |
| `rejection_reason` | text | Reason if rejected |
| `requested_by` | int | User who created request |
| `approved_by` | int | User who approved (nullable) |
| `approved_at` | datetime | Approval timestamp |
| `released_by` | int | User who released (nullable) |
| `released_at` | datetime | Release timestamp |
| `completed_by` | int | User who completed (nullable) |
| `completed_at` | datetime | Completion timestamp |
| `rejected_by` | int | User who rejected (nullable) |
| `rejected_at` | datetime | Rejection timestamp |
| `external_data` | array | Additional metadata |
| `created_at` | datetime | Creation timestamp |
| `updated_at` | datetime | Last update timestamp |

### Relationships

#### bankAccount()
```php
public function bankAccount(): BelongsTo
```
Returns the associated BankAccount.

#### account()
```php
public function account(): BelongsTo
```
Returns the Chart of Accounts entry (payment account).

#### sourceable()
```php
public function sourceable(): MorphTo
```
Returns the polymorphic source (Expense, PayrollPayment, PurchaseInvoice, PurchaseFund, etc.)

#### transaction()
```php
public function transaction(): BelongsTo
```
Returns the linked Transaction (after completion).

#### requestedBy()
```php
public function requestedBy(): BelongsTo
```
Returns the User who created the request.

#### approvedBy()
```php
public function approvedBy(): BelongsTo
```
Returns the User who approved the request.

#### releasedBy()
```php
public function releasedBy(): BelongsTo
```
Returns the User who released the request.

#### completedBy()
```php
public function completedBy(): BelongsTo
```
Returns the User who completed the request.

#### rejectedBy()
```php
public function rejectedBy(): BelongsTo
```
Returns the User who rejected the request.

### Query Methods

#### generateRequestNo()
```php
public static function generateRequestNo(): string
```
Generates a unique request number (e.g., BPR-260622-00001).

**Returns:** Unique request number string

### Helper Methods

#### getPaymentAccount()
```php
public function getPaymentAccount(): ?Account
```
Resolves the payment account (Chart of Accounts entry) from either:
1. Direct `account_id` field
2. Bank account's linked account (`bankAccount->account_id`)

**Returns:** Account model or null if not found

**Usage:**
```php
$paymentAccount = $request->getPaymentAccount();
if ($paymentAccount) {
    echo $paymentAccount->name;  // e.g., "Cash on Hand"
}
```

#### isCompleted()
```php
public function isCompleted(): bool
```
Checks if the payment request has been fully completed with a transaction.

**Returns:** True if status is 'completed' AND transaction_id is set

**Usage:**
```php
if ($request->isCompleted()) {
    echo "This payment has been recorded in ledger.";
}
```

#### canBeCompleted()
```php
public function canBeCompleted(): bool
```
Validates if the payment request is ready to be completed.

**Checks:**
- Status must be 'released'
- Amount must be greater than zero
- Payment account must exist and be active

**Returns:** True if all conditions are met

**Usage:**
```php
if ($request->canBeCompleted()) {
    $service->completePaymentRequest($request, Auth::id());
} else {
    echo "Payment cannot be completed at this time.";
}
```

### Accessors

#### sourceTypeEnum()
```php
public function sourceTypeEnum(): TransactionType|PaymentRequestSourceType|null
```
Returns the enum representation of the source type.

**Returns:** TransactionType or PaymentRequestSourceType enum value, or null

**Usage:**
```php
$enum = $request->sourceTypeEnum();
echo $enum->label();  // e.g., "Expense"
```

#### statusBadgeClass()
```php
public function statusBadgeClass(): string
```
Returns Tailwind CSS classes for status badge styling.

**Returns:** CSS class string

**Usage:**
```php
<span class="{{ $request->statusBadgeClass() }}">
    {{ ucfirst($request->status) }}
</span>
```

---

## Transaction Model

**Namespace:** `App\Models\Transaction`

### Properties Related to Banking

| Property | Type | Description |
|----------|------|-------------|
| `id` | int | Transaction ID |
| `type` | TransactionType | expense, income, advance, etc. |
| `reference_type` | string | banking_payment_request, hrm_payroll, etc. |
| `reference_id` | int | Reference record ID |
| `reference_no` | string | Reference number |
| `datetime` | datetime | Transaction date/time |
| `created_by` | int | User who created transaction |
| `created_at` | datetime | Creation timestamp |

### Relationships

#### lines()
```php
public function lines(): HasMany
```
Returns all transaction lines (per-account debit/credit entries).

**Usage:**
```php
$totalDebit = $transaction->lines()->sum('debit');
$totalCredit = $transaction->lines()->sum('credit');
```

---

## TransactionLine Model

**Namespace:** `App\Models\TransactionLine`

### Properties

| Property | Type | Description |
|----------|------|-------------|
| `id` | int | Line ID |
| `transaction_id` | int | Parent transaction ID |
| `account_id` | int | Chart of Accounts entry |
| `debit` | decimal | Debit amount (0 if credit) |
| `credit` | decimal | Credit amount (0 if debit) |
| `notes` | text | Line description |

### Relationships

#### transaction()
```php
public function transaction(): BelongsTo
```
Returns the parent Transaction.

#### account()
```php
public function account(): BelongsTo
```
Returns the Chart of Accounts entry.

---

## Enums

### TransactionType

```php
enum TransactionType: string {
    case EXPENSE = 'expense';
    case INCOME = 'income';
    case ADVANCE = 'advance';
}
```

### PaymentRequestSourceType

```php
enum PaymentRequestSourceType: string {
    case PAYROLL = 'payroll';
    case SUPPLIER = 'supplier';
}
```

---

## Commands

### banking:backfill-transactions

Migrate previously completed requests without transaction records.

```bash
# Show what would be processed (dry run)
php artisan banking:backfill-transactions --dry-run

# Process all completed requests without transaction_id
php artisan banking:backfill-transactions

# Only process previously failed requests
php artisan banking:backfill-transactions --only-failed
```

---

## Error Examples

### Invalid Status

```php
try {
    $request = BankingPaymentRequest::where('status', 'pending')->first();
    $service->completePaymentRequest($request, Auth::id());
} catch (\DomainException $e) {
    // Output: "Payment request must be 'released' to complete. Current status: pending"
}
```

### Missing Payment Account

```php
try {
    $request = BankingPaymentRequest::factory()->create([
        'status' => 'released',
        'account_id' => null,
        'bank_account_id' => null,
    ]);
    $service->completePaymentRequest($request, Auth::id());
} catch (\DomainException $e) {
    // Output: "No valid payment account found..."
}
```

### Accounting Event Not Configured

```php
try {
    $request = BankingPaymentRequest::factory()->create([
        'source_type' => 'expense',
        'status' => 'released',
    ]);
    $service->completePaymentRequest($request, Auth::id());
} catch (\DomainException $e) {
    // Output: "Cannot post expense payment: No active accounting event configured for: expense.payment..."
}
```

---

## Testing Examples

```php
use App\Services\Accounts\BankingTransactionService;
use App\Models\BankingPaymentRequest;

class BankingTest extends TestCase {
    public function test_complete_expense_payment() {
        $request = BankingPaymentRequest::factory()->create([
            'source_type' => 'expense',
            'status' => 'released',
            'amount' => 1000.00,
        ]);

        $service = app(BankingTransactionService::class);
        $transaction = $service->completePaymentRequest($request, auth()->id());

        $this->assertTrue($request->isCompleted());
        $this->assertEquals($transaction->id, $request->transaction_id);
    }
}
```
