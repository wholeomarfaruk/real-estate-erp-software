<?php

namespace App\Services\Accounts;

use App\Accounting\PostingContext;
use App\Enums\Accounts\PaymentRequestSourceType;
use App\Enums\Accounts\TransactionType;
use App\Models\Account;
use App\Models\BankingPaymentRequest;
use App\Models\PayrollPayment;
use App\Models\PurchaseFund;
use App\Models\PurchaseInvoice;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class BankingTransactionService
{
    public function __construct(
        private readonly PostingEngine $engine,
        private readonly LedgerService $ledger,
    ) {}

    /**
     * Complete a banking payment request by posting the appropriate double-entry
     * transaction. Routes to the correct handler based on source type.
     *
     * @throws \DomainException
     */
    public function completePaymentRequest(
        BankingPaymentRequest $request,
        int $userId
    ): Transaction {
        return DB::transaction(function () use ($request, $userId): Transaction {
            $this->validatePaymentRequest($request);

            $sourceType = $request->source_type;

            // Expense payment
            if ($sourceType === TransactionType::EXPENSE->value) {
                return $this->postExpensePayment($request, $userId);
            }

            // Payroll payment
            if ($sourceType === PaymentRequestSourceType::PAYROLL->value) {
                return $this->postPayrollPayment($request, $userId);
            }

            // Supplier invoice payment
            if ($sourceType === PaymentRequestSourceType::SUPPLIER->value) {
                return $this->postSupplierPayment($request, $userId);
            }

            // Advance fund (purchase order)
            if ($sourceType === TransactionType::ADVANCE->value) {
                return $this->postAdvanceFund($request, $userId);
            }

            // Income / deposit / opening balance
            if ($sourceType === TransactionType::INCOME->value) {
                return $this->postIncome($request, $userId);
            }

            throw new \DomainException("Unsupported payment source type: {$sourceType}");
        });
    }

    /**
     * Post an expense payment using stored double-entry data:
     *   Dr Expense Account / Cr Payment Account
     *
     * Uses debit/credit accounts and amounts pre-stored on the request.
     */
    private function postExpensePayment(
        BankingPaymentRequest $request,
        int $userId
    ): Transaction {
        // Use stored double-entry data
        if (!$request->debit_account_id || !$request->credit_account_id) {
            throw new \DomainException('Double-entry accounts not configured for this expense request.');
        }

        // Validate accounts exist and are active
        $debitAccount = Account::findOrFail($request->debit_account_id);
        $creditAccount = Account::findOrFail($request->credit_account_id);

        if (!$debitAccount->is_active || !$creditAccount->is_active) {
            throw new \DomainException('One or more double-entry accounts are inactive.');
        }

        // Create balanced double-entry using stored amounts
        $transaction = $this->ledger->post(
            [
                'datetime' => now()->format('Y-m-d H:i:s'),
                'type' => $request->source_type,
                'reference_type' => 'banking_payment_request',
                'reference_id' => $request->id,
                'reference_no' => $request->reference_no,
                'name' => $request->name,
                'phone' => $request->phone,
                'method' => $request->method ?? 'bank',
                'notes' => $request->notes ?? $request->description,
                'created_by' => $userId,
            ],
            [
                [
                    'account_id' => (int) $debitAccount->id,
                    'debit' => (float) $request->debit_amount,
                    'credit' => 0,
                    'notes' => $debitAccount->name,
                ],
                [
                    'account_id' => (int) $creditAccount->id,
                    'debit' => 0,
                    'credit' => (float) $request->credit_amount,
                    'notes' => $creditAccount->name,
                ],
            ],
        );

        // Update banking request with transaction and completion info
        $request->update([
            'transaction_id' => $transaction->id,
            'status' => 'completed',
            'completed_by' => $userId,
            'completed_at' => now(),
        ]);

        return $transaction;
    }

    /**
     * Post a payroll payment using stored double-entry data:
     *   Dr Salary Payable / Cr Payment Account
     *
     * Uses debit/credit accounts and amounts pre-stored on the request.
     */
    private function postPayrollPayment(
        BankingPaymentRequest $request,
        int $userId
    ): Transaction {
        if (
            $request->sourceable_type !== PayrollPayment::class
            || ! $request->sourceable_id
        ) {
            throw new \DomainException('Payroll request must be linked to a PayrollPayment.');
        }

        // Use stored double-entry data
        if (!$request->debit_account_id || !$request->credit_account_id) {
            throw new \DomainException('Double-entry accounts not configured for this payroll request.');
        }

        // Validate accounts exist and are active
        $debitAccount = Account::findOrFail($request->debit_account_id);
        $creditAccount = Account::findOrFail($request->credit_account_id);

        if (!$debitAccount->is_active || !$creditAccount->is_active) {
            throw new \DomainException('One or more double-entry accounts are inactive.');
        }

        $payment = PayrollPayment::query()
            ->with(['payroll.employee:id,name'])
            ->findOrFail($request->sourceable_id);

        // Create balanced double-entry using stored amounts
        $transaction = $this->ledger->post(
            [
                'datetime' => now()->format('Y-m-d H:i:s'),
                'type' => TransactionType::PURCHASE->value,
                'reference_type' => 'payroll_payment',
                'reference_id' => $payment->id,
                'reference_no' => $request->reference_no,
                'name' => $request->name ?? $payment->payroll?->employee?->name,
                'phone' => $request->phone,
                'method' => $request->method ?? $payment->payment_method ?? 'bank',
                'notes' => $request->notes ?? $request->description,
                'created_by' => $userId,
            ],
            [
                [
                    'account_id' => (int) $debitAccount->id,
                    'debit' => (float) $request->debit_amount,
                    'credit' => 0,
                    'notes' => $debitAccount->name,
                ],
                [
                    'account_id' => (int) $creditAccount->id,
                    'debit' => 0,
                    'credit' => (float) $request->credit_amount,
                    'notes' => $creditAccount->name,
                ],
            ],
        );

        // Update banking request with transaction and completion info
        $request->update([
            'transaction_id' => $transaction->id,
            'status' => 'completed',
            'completed_by' => $userId,
            'completed_at' => now(),
        ]);

        return $transaction;
    }

    /**
     * Post a supplier invoice payment using stored double-entry data:
     *   Dr Accounts Payable / Cr Payment Account
     *
     * Uses debit/credit accounts and amounts pre-stored on the request.
     */
    private function postSupplierPayment(
        BankingPaymentRequest $request,
        int $userId
    ): Transaction {
        if (
            $request->sourceable_type !== PurchaseInvoice::class
            || ! $request->sourceable_id
        ) {
            throw new \DomainException('Supplier payment must be linked to a PurchaseInvoice.');
        }

        // Use stored double-entry data
        if (!$request->debit_account_id || !$request->credit_account_id) {
            throw new \DomainException('Double-entry accounts not configured for this supplier payment request.');
        }

        // Validate accounts exist and are active
        $debitAccount = Account::findOrFail($request->debit_account_id);
        $creditAccount = Account::findOrFail($request->credit_account_id);

        if (!$debitAccount->is_active || !$creditAccount->is_active) {
            throw new \DomainException('One or more double-entry accounts are inactive.');
        }

        $invoice = PurchaseInvoice::query()
            ->with(['supplier:id,name'])
            ->findOrFail($request->sourceable_id);

        // Create balanced double-entry using stored amounts
        $transaction = $this->ledger->post(
            [
                'datetime' => now()->format('Y-m-d H:i:s'),
                'type' => TransactionType::SUPPLIER_PAYMENT->value,
                'reference_type' => 'purchase_invoice',
                'reference_id' => $invoice->id,
                'reference_no' => $request->reference_no ?? $invoice->invoice_no,
                'name' => $request->name ?? $invoice->supplier?->name,
                'phone' => $request->phone,
                'method' => $request->method ?? 'bank',
                'notes' => $request->notes ?? $request->description,
                'created_by' => $userId,
            ],
            [
                [
                    'account_id' => (int) $debitAccount->id,
                    'debit' => (float) $request->debit_amount,
                    'credit' => 0,
                    'notes' => $debitAccount->name,
                ],
                [
                    'account_id' => (int) $creditAccount->id,
                    'debit' => 0,
                    'credit' => (float) $request->credit_amount,
                    'notes' => $creditAccount->name,
                ],
            ],
        );

        // Update banking request with transaction and completion info
        $request->update([
            'transaction_id' => $transaction->id,
            'status' => 'completed',
            'completed_by' => $userId,
            'completed_at' => now(),
        ]);

        return $transaction;
    }

    /**
     * Post an advance fund (purchase order) using stored double-entry data:
     *   Dr Supplier Advance (asset) / Cr Payment Account
     *
     * Uses debit/credit accounts and amounts pre-stored on the request.
     */
    private function postAdvanceFund(
        BankingPaymentRequest $request,
        int $userId
    ): Transaction {
        if (
            $request->sourceable_type !== PurchaseFund::class
            || ! $request->sourceable_id
        ) {
            throw new \DomainException('Advance fund must be linked to a PurchaseFund.');
        }

        // Use stored double-entry data
        if (!$request->debit_account_id || !$request->credit_account_id) {
            throw new \DomainException('Double-entry accounts not configured for this advance fund request.');
        }

        // Validate accounts exist and are active
        $debitAccount = Account::findOrFail($request->debit_account_id);
        $creditAccount = Account::findOrFail($request->credit_account_id);

        if (!$debitAccount->is_active || !$creditAccount->is_active) {
            throw new \DomainException('One or more double-entry accounts are inactive.');
        }

        $fund = PurchaseFund::query()
            ->with(['receiver', 'purchaseOrder:id,po_no'])
            ->findOrFail($request->sourceable_id);

        // Create balanced double-entry using stored amounts
        $transaction = $this->ledger->post(
            [
                'datetime' => now()->format('Y-m-d H:i:s'),
                'type' => TransactionType::ADVANCE->value,
                'reference_type' => 'purchase_fund',
                'reference_id' => $fund->id,
                'reference_no' => $request->reference_no ?? $fund->reference_no,
                'name' => $request->name ?? $fund->receiver?->name,
                'phone' => $request->phone ?? $fund->receiver?->phone,
                'method' => $request->method ?? $fund->method ?? 'bank',
                'notes' => $request->notes ?? $request->description,
                'created_by' => $userId,
            ],
            [
                [
                    'account_id' => (int) $debitAccount->id,
                    'debit' => (float) $request->debit_amount,
                    'credit' => 0,
                    'notes' => $debitAccount->name,
                ],
                [
                    'account_id' => (int) $creditAccount->id,
                    'debit' => 0,
                    'credit' => (float) $request->credit_amount,
                    'notes' => $creditAccount->name,
                ],
            ],
        );

        // Update banking request with transaction and completion info
        $request->update([
            'transaction_id' => $transaction->id,
            'status' => 'completed',
            'completed_by' => $userId,
            'completed_at' => now(),
        ]);

        return $transaction;
    }

    /**
     * Post an income or opening balance deposit using stored double-entry data.
     *   Dr Payment Account / Cr Income/Opening Balance Account
     *
     * Uses debit/credit accounts and amounts pre-stored on the request.
     */
    private function postIncome(
        BankingPaymentRequest $request,
        int $userId
    ): Transaction {
        // Use stored double-entry data
        if (!$request->debit_account_id || !$request->credit_account_id) {
            throw new \DomainException('Double-entry accounts not configured for this payment request.');
        }

        // Validate accounts exist and are active
        $debitAccount = Account::findOrFail($request->debit_account_id);
        $creditAccount = Account::findOrFail($request->credit_account_id);

        if (!$debitAccount->is_active || !$creditAccount->is_active) {
            throw new \DomainException('One or more double-entry accounts are inactive.');
        }

        // Create balanced double-entry using stored amounts
        $transaction = $this->ledger->post(
            [
                'datetime' => now()->format('Y-m-d H:i:s'),
                'type' => TransactionType::INCOME->value,
                'reference_type' => 'banking_payment_request',
                'reference_id' => $request->id,
                'reference_no' => $request->reference_no,
                'name' => $request->name,
                'phone' => $request->phone,
                'method' => $request->method ?? 'bank',
                'notes' => $request->notes ?? $request->description,
                'created_by' => $userId,
            ],
            [
                [
                    'account_id' => (int) $debitAccount->id,
                    'debit' => (float) $request->debit_amount,
                    'credit' => 0,
                    'notes' => $debitAccount->name,
                ],
                [
                    'account_id' => (int) $creditAccount->id,
                    'debit' => 0,
                    'credit' => (float) $request->credit_amount,
                    'notes' => $creditAccount->name,
                ],
            ],
        );

        // Update banking request with transaction and completion info
        $request->update([
            'transaction_id' => $transaction->id,
            'status' => 'completed',
            'completed_by' => $userId,
            'completed_at' => now(),
        ]);

        return $transaction;
    }

    /**
     * Validate that the banking request is in a valid state for completion.
     *
     * @throws \DomainException
     */
    private function validatePaymentRequest(BankingPaymentRequest $request): void
    {
        if ($request->status !== 'released') {
            throw new \DomainException(
                "Payment request must be 'released' to complete. Current status: {$request->status}"
            );
        }

        if ($request->transaction_id) {
            throw new \DomainException(
                'This payment request has already been completed (transaction_id set).'
            );
        }

        if ((float) $request->amount <= 0) {
            throw new \DomainException('Payment amount must be greater than zero.');
        }

        // Ensure double-entry accounts are configured
        if (!$request->debit_account_id || !$request->credit_account_id) {
            throw new \DomainException(
                'Double-entry accounts not configured. Ensure debit_account_id and credit_account_id are set.'
            );
        }

        // Validate both accounts exist and are active
        $debitAccount = Account::query()->find($request->debit_account_id);
        $creditAccount = Account::query()->find($request->credit_account_id);

        if (!$debitAccount) {
            throw new \DomainException(
                'Debit account not found (ID: ' . $request->debit_account_id . ').'
            );
        }

        if (!$creditAccount) {
            throw new \DomainException(
                'Credit account not found (ID: ' . $request->credit_account_id . ').'
            );
        }

        if (!$debitAccount->is_active) {
            throw new \DomainException('Debit account is inactive.');
        }

        if (!$creditAccount->is_active) {
            throw new \DomainException('Credit account is inactive.');
        }
    }
}
