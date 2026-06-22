<?php

namespace App\Services\Accounts;

use App\Enums\Accounts\PaymentRequestSourceType;
use App\Enums\Accounts\TransactionType;
use App\Models\Account;
use App\Models\BankAccount;
use App\Models\BankingPaymentRequest;
use App\Models\PayrollPayment;
use App\Models\PurchaseFund;
use App\Models\PurchaseInvoice;

/**
 * Builds double-entry account details for banking payment requests.
 * Stores debit/credit accounts and amounts on the request before transaction creation.
 */
class BankingDoubleEntryBuilderService
{
    /**
     * Build and store double-entry structure for a payment request based on source type.
     * Determines which accounts should be debited/credited and populates the request.
     */
    public function buildDoubleEntry(
        BankingPaymentRequest $request,
        ?string $sourceType = null
    ): void {
        $sourceType = $sourceType ?? $request->source_type;

        // Route to appropriate builder
        match ($sourceType) {
            TransactionType::EXPENSE->value => $this->buildExpenseEntry($request),
            TransactionType::INCOME->value => $this->buildIncomeEntry($request),
            TransactionType::ADVANCE->value => $this->buildAdvanceEntry($request),
            PaymentRequestSourceType::PAYROLL->value => $this->buildPayrollEntry($request),
            PaymentRequestSourceType::SUPPLIER->value => $this->buildSupplierEntry($request),
            default => throw new \DomainException("Unsupported source type: {$sourceType}"),
        };
    }

    /**
     * Expense Payment:
     *   DR Expense Account (from settings/accounting event)
     *   CR Payment Account (bank/cash)
     */
    private function buildExpenseEntry(BankingPaymentRequest $request): void
    {
        // For now, we'll use a default expense account
        // In production, this would come from settings or accounting event configuration
        $expenseAccount = Account::where('name', 'Expenses')
            ->orWhere('code', 'like', 'EXP%')
            ->where('is_active', true)
            ->first();

        if (!$expenseAccount) {
            // Create placeholder if doesn't exist
            $expenseAccount = Account::create([
                'name' => 'Expenses',
                'code' => 'EXP-001',
                'type' => 'ledger',
                'is_active' => true,
            ]);
        }

        $paymentAccount = $this->resolvePaymentAccount($request);

        $request->update([
            'debit_account_id' => $expenseAccount->id,
            'debit_amount' => $request->amount,
            'credit_account_id' => $paymentAccount->id,
            'credit_amount' => $request->amount,
        ]);
    }

    /**
     * Income/Opening Balance:
     *   DR Payment Account (bank/cash)
     *   CR Opening Balance / Income Account
     */
    private function buildIncomeEntry(BankingPaymentRequest $request): void
    {
        $paymentAccount = $this->resolvePaymentAccount($request);

        $incomeAccount = Account::where('name', 'Opening Balance / Income')
            ->orWhere('code', 'OBI%')
            ->where('is_active', true)
            ->first();

        if (!$incomeAccount) {
            $incomeAccount = Account::create([
                'name' => 'Opening Balance / Income',
                'code' => 'OBI-001',
                'type' => 'ledger',
                'is_active' => true,
            ]);
        }

        $request->update([
            'debit_account_id' => $paymentAccount->id,
            'debit_amount' => $request->amount,
            'credit_account_id' => $incomeAccount->id,
            'credit_amount' => $request->amount,
        ]);
    }

    /**
     * Advance Fund:
     *   DR Supplier Advance (asset)
     *   CR Payment Account (bank/cash)
     */
    private function buildAdvanceEntry(BankingPaymentRequest $request): void
    {
        $advanceAccount = Account::where('name', 'like', '%Advance%')
            ->orWhere('code', 'like', 'ADV%')
            ->where('is_active', true)
            ->first();

        if (!$advanceAccount) {
            $advanceAccount = Account::create([
                'name' => 'Supplier Advance',
                'code' => 'ADV-001',
                'type' => 'ledger',
                'is_active' => true,
            ]);
        }

        $paymentAccount = $this->resolvePaymentAccount($request);

        $request->update([
            'debit_account_id' => $advanceAccount->id,
            'debit_amount' => $request->amount,
            'credit_account_id' => $paymentAccount->id,
            'credit_amount' => $request->amount,
        ]);
    }

    /**
     * Payroll Payment:
     *   DR Salary Payable
     *   CR Payment Account (bank/cash)
     */
    private function buildPayrollEntry(BankingPaymentRequest $request): void
    {
        $salaryAccount = Account::where('name', 'like', '%Salary%')
            ->orWhere('code', 'like', 'SAL%')
            ->where('is_active', true)
            ->first();

        if (!$salaryAccount) {
            $salaryAccount = Account::create([
                'name' => 'Salary Payable',
                'code' => 'SAL-001',
                'type' => 'ledger',
                'is_active' => true,
            ]);
        }

        $paymentAccount = $this->resolvePaymentAccount($request);

        $request->update([
            'debit_account_id' => $salaryAccount->id,
            'debit_amount' => $request->amount,
            'credit_account_id' => $paymentAccount->id,
            'credit_amount' => $request->amount,
        ]);
    }

    /**
     * Supplier Payment:
     *   DR Accounts Payable
     *   CR Payment Account (bank/cash)
     */
    private function buildSupplierEntry(BankingPaymentRequest $request): void
    {
        $apAccount = Account::where('name', 'like', '%Payable%')
            ->orWhere('code', 'like', 'AP%')
            ->where('is_active', true)
            ->first();

        if (!$apAccount) {
            $apAccount = Account::create([
                'name' => 'Accounts Payable',
                'code' => 'AP-001',
                'type' => 'ledger',
                'is_active' => true,
            ]);
        }

        $paymentAccount = $this->resolvePaymentAccount($request);

        $request->update([
            'debit_account_id' => $apAccount->id,
            'debit_amount' => $request->amount,
            'credit_account_id' => $paymentAccount->id,
            'credit_amount' => $request->amount,
        ]);
    }

    /**
     * Resolve the payment account (COA entry) from the request.
     * Prefers direct account_id, falls back to bank account's linked account.
     */
    private function resolvePaymentAccount(BankingPaymentRequest $request): Account
    {
        if ($request->account_id) {
            $account = Account::find($request->account_id);
            if ($account && $account->is_active) {
                return $account;
            }
        }

        if ($request->bank_account_id) {
            $bankAccount = BankAccount::find($request->bank_account_id);
            if ($bankAccount && $bankAccount->account_id) {
                $account = Account::find($bankAccount->account_id);
                if ($account && $account->is_active) {
                    return $account;
                }
            }
        }

        throw new \DomainException('No valid payment account found for this request.');
    }
}
