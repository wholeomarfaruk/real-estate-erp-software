<?php

namespace Tests\Feature\Accounts;

use App\Enums\Accounts\AccountType;
use App\Enums\Accounts\PaymentRequestSourceType;
use App\Enums\Accounts\TransactionType;
use App\Models\Account;
use App\Models\AccountingEvent;
use App\Models\BankAccount;
use App\Models\BankingPaymentRequest;
use App\Models\PayrollPayment;
use App\Models\PostingRule;
use App\Models\PurchaseFund;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Accounts\BankingTransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BankingTransactionServiceTest extends TestCase
{
    use RefreshDatabase;

    private BankingTransactionService $service;
    private User $user;
    private BankAccount $bankAccount;
    private Account $paymentAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(BankingTransactionService::class);
        $this->user = User::factory()->create();

        // Setup payment account (bank/cash)
        $this->paymentAccount = Account::factory()->create([
            'name' => 'Cash',
            'type' => AccountType::LEDGER->value,
            'is_active' => true,
        ]);

        // Setup bank account linked to chart of accounts
        $this->bankAccount = BankAccount::factory()->create([
            'account_id' => $this->paymentAccount->id,
            'bank_name' => 'Test Bank',
            'status' => 'active',
        ]);

        // Ensure accounting events are configured
        $this->setupAccountingEvents();
    }

    private function setupAccountingEvents(): void
    {
        // Expense payment event
        $expenseEvent = AccountingEvent::firstOrCreate(
            ['key' => 'expense.payment', 'is_active' => true],
            [
                'name' => 'Expense Payment',
                'transaction_type' => TransactionType::EXPENSE->value,
            ]
        );

        $expenseAccount = Account::factory()->create([
            'name' => 'Expense Account',
            'type' => AccountType::LEDGER->value,
        ]);

        PostingRule::firstOrCreate(
            ['accounting_event_id' => $expenseEvent->id, 'leg' => 'debit'],
            [
                'account_id' => $expenseAccount->id,
                'description' => 'Expense',
            ]
        );

        PostingRule::firstOrCreate(
            ['accounting_event_id' => $expenseEvent->id, 'leg' => 'credit'],
            [
                'leg' => 'credit',
                'slot' => 'payment_account',
                'description' => 'Payment Account',
            ]
        );

        // Payroll payment event
        $payrollEvent = AccountingEvent::firstOrCreate(
            ['key' => 'payroll.payment', 'is_active' => true],
            [
                'name' => 'Payroll Payment',
                'transaction_type' => TransactionType::EXPENSE->value,
            ]
        );

        $salaryPayableAccount = Account::factory()->create([
            'name' => 'Salary Payable',
            'type' => AccountType::LEDGER->value,
        ]);

        PostingRule::firstOrCreate(
            ['accounting_event_id' => $payrollEvent->id, 'leg' => 'debit'],
            [
                'account_id' => $salaryPayableAccount->id,
                'description' => 'Salary Payable',
            ]
        );

        PostingRule::firstOrCreate(
            ['accounting_event_id' => $payrollEvent->id, 'leg' => 'credit'],
            [
                'leg' => 'credit',
                'slot' => 'payment_account',
                'description' => 'Payment Account',
            ]
        );

        // Supplier payment event
        $supplierEvent = AccountingEvent::firstOrCreate(
            ['key' => 'purchase.supplier_payment', 'is_active' => true],
            [
                'name' => 'Supplier Payment',
                'transaction_type' => TransactionType::EXPENSE->value,
            ]
        );

        $apAccount = Account::factory()->create([
            'name' => 'Accounts Payable',
            'type' => AccountType::LEDGER->value,
        ]);

        PostingRule::firstOrCreate(
            ['accounting_event_id' => $supplierEvent->id, 'leg' => 'debit'],
            [
                'account_id' => $apAccount->id,
                'description' => 'Accounts Payable',
            ]
        );

        PostingRule::firstOrCreate(
            ['accounting_event_id' => $supplierEvent->id, 'leg' => 'credit'],
            [
                'leg' => 'credit',
                'slot' => 'payment_account',
                'description' => 'Payment Account',
            ]
        );

        // Supplier advance event
        $advanceEvent = AccountingEvent::firstOrCreate(
            ['key' => 'purchase.supplier_advance', 'is_active' => true],
            [
                'name' => 'Supplier Advance',
                'transaction_type' => TransactionType::ADVANCE->value,
            ]
        );

        $advanceAccount = Account::factory()->create([
            'name' => 'Supplier Advance',
            'type' => AccountType::LEDGER->value,
        ]);

        PostingRule::firstOrCreate(
            ['accounting_event_id' => $advanceEvent->id, 'leg' => 'debit'],
            [
                'account_id' => $advanceAccount->id,
                'description' => 'Supplier Advance',
            ]
        );

        PostingRule::firstOrCreate(
            ['accounting_event_id' => $advanceEvent->id, 'leg' => 'credit'],
            [
                'leg' => 'credit',
                'slot' => 'payment_account',
                'description' => 'Payment Account',
            ]
        );
    }

    public function test_complete_expense_payment_creates_balanced_transaction(): void
    {
        $request = BankingPaymentRequest::factory()->create([
            'source_type' => TransactionType::EXPENSE->value,
            'status' => 'released',
            'bank_account_id' => $this->bankAccount->id,
            'account_id' => $this->paymentAccount->id,
            'amount' => 1000.00,
        ]);

        $transaction = $this->service->completePaymentRequest($request, $this->user->id);

        $this->assertNotNull($transaction->id);
        $this->assertEquals(TransactionType::EXPENSE->value, $transaction->type);

        // Verify lines are balanced
        $totalDebit = $transaction->lines()->sum('debit');
        $totalCredit = $transaction->lines()->sum('credit');
        $this->assertEquals($totalDebit, $totalCredit);

        // Verify request was updated
        $request->refresh();
        $this->assertEquals('completed', $request->status);
        $this->assertEquals($transaction->id, $request->transaction_id);
        $this->assertEquals($this->user->id, $request->completed_by);
    }

    public function test_complete_income_payment_creates_balanced_transaction(): void
    {
        $request = BankingPaymentRequest::factory()->create([
            'source_type' => TransactionType::INCOME->value,
            'status' => 'released',
            'bank_account_id' => $this->bankAccount->id,
            'account_id' => $this->paymentAccount->id,
            'amount' => 5000.00,
        ]);

        $transaction = $this->service->completePaymentRequest($request, $this->user->id);

        $this->assertNotNull($transaction->id);
        $this->assertEquals(TransactionType::INCOME->value, $transaction->type);

        // Verify double-entry: DR payment account, CR income account
        $debitLines = $transaction->lines()->where('debit', '>', 0)->get();
        $creditLines = $transaction->lines()->where('credit', '>', 0)->get();

        $this->assertCount(1, $debitLines);
        $this->assertCount(1, $creditLines);

        $this->assertEquals(5000.00, $debitLines[0]->debit);
        $this->assertEquals(5000.00, $creditLines[0]->credit);

        // Verify payment account is the debit line
        $this->assertEquals($this->paymentAccount->id, $debitLines[0]->account_id);
    }

    public function test_reject_completion_if_not_released_status(): void
    {
        $request = BankingPaymentRequest::factory()->create([
            'status' => 'pending',
            'bank_account_id' => $this->bankAccount->id,
            'account_id' => $this->paymentAccount->id,
            'amount' => 1000.00,
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage("Payment request must be 'released' to complete");

        $this->service->completePaymentRequest($request, $this->user->id);
    }

    public function test_reject_completion_if_already_completed(): void
    {
        $transaction = Transaction::factory()->create();

        $request = BankingPaymentRequest::factory()->create([
            'status' => 'released',
            'bank_account_id' => $this->bankAccount->id,
            'account_id' => $this->paymentAccount->id,
            'amount' => 1000.00,
            'transaction_id' => $transaction->id,
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('already been completed');

        $this->service->completePaymentRequest($request, $this->user->id);
    }

    public function test_reject_completion_with_zero_amount(): void
    {
        $request = BankingPaymentRequest::factory()->create([
            'status' => 'released',
            'bank_account_id' => $this->bankAccount->id,
            'account_id' => $this->paymentAccount->id,
            'amount' => 0.00,
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Payment amount must be greater than zero');

        $this->service->completePaymentRequest($request, $this->user->id);
    }

    public function test_reject_completion_without_valid_payment_account(): void
    {
        $request = BankingPaymentRequest::factory()->create([
            'status' => 'released',
            'bank_account_id' => $this->bankAccount->id,
            'account_id' => null,
        ]);

        // Unlink the bank account from COA
        $this->bankAccount->update(['account_id' => null]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('No valid payment account found');

        $this->service->completePaymentRequest($request, $this->user->id);
    }

    public function test_transaction_references_banking_request(): void
    {
        $request = BankingPaymentRequest::factory()->create([
            'source_type' => TransactionType::INCOME->value,
            'status' => 'released',
            'bank_account_id' => $this->bankAccount->id,
            'account_id' => $this->paymentAccount->id,
            'amount' => 2000.00,
        ]);

        $transaction = $this->service->completePaymentRequest($request, $this->user->id);

        $this->assertEquals('banking_payment_request', $transaction->reference_type);
        $this->assertEquals($request->id, $transaction->reference_id);
    }

    public function test_transaction_uses_correct_creator(): void
    {
        $request = BankingPaymentRequest::factory()->create([
            'source_type' => TransactionType::INCOME->value,
            'status' => 'released',
            'bank_account_id' => $this->bankAccount->id,
            'account_id' => $this->paymentAccount->id,
            'amount' => 1500.00,
        ]);

        $transaction = $this->service->completePaymentRequest($request, $this->user->id);

        $this->assertEquals($this->user->id, $transaction->created_by);
    }

    public function test_payroll_payment_requires_valid_sourceable(): void
    {
        $request = BankingPaymentRequest::factory()->create([
            'source_type' => PaymentRequestSourceType::PAYROLL->value,
            'status' => 'released',
            'bank_account_id' => $this->bankAccount->id,
            'account_id' => $this->paymentAccount->id,
            'sourceable_type' => PayrollPayment::class,
            'sourceable_id' => null,
            'amount' => 1000.00,
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Payroll request must be linked to a PayrollPayment');

        $this->service->completePaymentRequest($request, $this->user->id);
    }

    public function test_supplier_payment_requires_valid_sourceable(): void
    {
        $request = BankingPaymentRequest::factory()->create([
            'source_type' => PaymentRequestSourceType::SUPPLIER->value,
            'status' => 'released',
            'bank_account_id' => $this->bankAccount->id,
            'account_id' => $this->paymentAccount->id,
            'sourceable_type' => PurchaseInvoice::class,
            'sourceable_id' => null,
            'amount' => 1000.00,
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Supplier payment must be linked to a PurchaseInvoice');

        $this->service->completePaymentRequest($request, $this->user->id);
    }

    public function test_advance_fund_requires_valid_sourceable(): void
    {
        $request = BankingPaymentRequest::factory()->create([
            'source_type' => TransactionType::ADVANCE->value,
            'status' => 'released',
            'bank_account_id' => $this->bankAccount->id,
            'account_id' => $this->paymentAccount->id,
            // Sourced to a Supplier but missing the external_data fund link.
            'sourceable_type' => Supplier::class,
            'sourceable_id' => null,
            'amount' => 1000.00,
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Advance fund must be linked to a Supplier and a PurchaseFund');

        $this->service->completePaymentRequest($request, $this->user->id);
    }
}
