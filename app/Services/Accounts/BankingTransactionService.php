<?php

namespace App\Services\Accounts;

use App\Accounting\PostingContext;
use App\Enums\Accounts\PaymentRequestSourceType;
use App\Enums\Accounts\TransactionType;
use App\Models\Account;
use App\Models\BankAccount;
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
     * Post a payroll payment:
     *   Dr Salary Payable / Cr Payment Account
     *
     * Uses the payroll.payment accounting event.
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

        $bankAccount = BankAccount::query()->findOrFail($request->bank_account_id);
        $paymentAccountId = (int) $bankAccount->account_id;

        if ($paymentAccountId <= 0) {
            throw new \DomainException('Bank account has no linked Chart of Accounts entry.');
        }

        $payment = PayrollPayment::query()
            ->with(['payroll.employee:id,name'])
            ->findOrFail($request->sourceable_id);

        try {
            return $this->engine->record(
                'payroll.payment',
                new PostingContext(
                    amount: (float) $request->amount,
                    datetime: now()->format('Y-m-d H:i:s'),
                    paymentAccountId: $paymentAccountId,
                    referenceType: 'payroll_payment',
                    referenceId: $payment->id,
                    method: $payment->payment_method,
                    name: $payment->payroll?->employee?->name,
                    notes: $request->notes ?? $request->description,
                    actorId: $userId,
                ),
            );
        } catch (\DomainException $e) {
            throw new \DomainException(
                "Cannot post payroll payment: {$e->getMessage()} (ensure 'payroll.payment' event is configured)"
            );
        }
    }

    /**
     * Post a supplier invoice payment:
     *   Dr Accounts Payable / Cr Payment Account
     *
     * Uses the purchase.supplier_payment accounting event.
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

        $bankAccount = BankAccount::query()->findOrFail($request->bank_account_id);
        $paymentAccountId = (int) $bankAccount->account_id;

        if ($paymentAccountId <= 0) {
            throw new \DomainException('Bank account has no linked Chart of Accounts entry.');
        }

        $invoice = PurchaseInvoice::query()
            ->with(['supplier:id,name'])
            ->findOrFail($request->sourceable_id);

        try {
            return $this->engine->record(
                'purchase.supplier_payment',
                new PostingContext(
                    amount: (float) $request->amount,
                    datetime: now()->format('Y-m-d H:i:s'),
                    paymentAccountId: $paymentAccountId,
                    referenceType: 'purchase_invoice',
                    referenceId: $invoice->id,
                    referenceNo: $invoice->invoice_no,
                    name: $invoice->supplier?->name,
                    notes: $request->notes ?? $request->description,
                    actorId: $userId,
                ),
            );
        } catch (\DomainException $e) {
            throw new \DomainException(
                "Cannot post supplier payment: {$e->getMessage()} (ensure 'purchase.supplier_payment' event is configured)"
            );
        }
    }

    /**
     * Post an advance fund (purchase order):
     *   Dr Supplier Advance (asset) / Cr Payment Account
     *
     * Uses the purchase.supplier_advance accounting event.
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

        $fund = PurchaseFund::query()
            ->with(['receiver', 'purchaseOrder:id,po_no'])
            ->findOrFail($request->sourceable_id);

        // Resolve payment account: prefer from fund, fallback to request
        $paymentAccountId = (int) ($fund->payment_account_id ?: $request->account_id);

        if ($paymentAccountId <= 0) {
            throw new \DomainException('Fund release has no source account to pay from.');
        }

        try {
            return $this->engine->record(
                'purchase.supplier_advance',
                new PostingContext(
                    amount: (float) $request->amount,
                    datetime: now()->format('Y-m-d H:i:s'),
                    paymentAccountId: $paymentAccountId,
                    referenceType: 'purchase_order',
                    referenceId: (int) $fund->purchase_order_id,
                    referenceNo: $fund->reference_no,
                    method: $fund->method,
                    name: $fund->receiver?->name,
                    phone: $fund->receiver?->phone,
                    notes: $request->notes ?? $request->description,
                    actorId: $userId,
                ),
            );
        } catch (\DomainException $e) {
            throw new \DomainException(
                "Cannot post advance fund: {$e->getMessage()} (ensure 'purchase.supplier_advance' event is configured)"
            );
        }
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

        // Ensure either account_id or (bank_account_id linked to account) is set
        $hasPaymentAccount = false;

        if ($request->account_id) {
            $account = Account::query()->find($request->account_id);
            if ($account && $account->is_active) {
                $hasPaymentAccount = true;
            }
        } elseif ($request->bank_account_id) {
            $bankAccount = BankAccount::query()->find($request->bank_account_id);
            if ($bankAccount && $bankAccount->account_id) {
                $account = Account::query()->find($bankAccount->account_id);
                if ($account && $account->is_active) {
                    $hasPaymentAccount = true;
                }
            }
        }

        if (! $hasPaymentAccount) {
            throw new \DomainException(
                'No valid payment account found. Bank account must be linked to Chart of Accounts.'
            );
        }
    }
}
