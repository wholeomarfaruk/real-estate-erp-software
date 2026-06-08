<?php

namespace App\Services\Inventory;

use App\Enums\Accounts\PaymentRequestSourceType;
use App\Enums\Accounts\TransactionRelationType;
use App\Enums\Accounts\TransactionType;
use App\Models\BankAccount;
use App\Models\BankingPaymentRequest;
use App\Models\PurchaseInvoice;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class PurchaseInvoicePaymentService
{
    /**
     * Create a BankingPaymentRequest for a purchase invoice payment.
     * Validates amount does not exceed due_amount.
     */
    public function requestPayment(PurchaseInvoice $invoice, array $payload, int $userId): BankingPaymentRequest
    {
        $due    = round((float) $invoice->due_amount, 3);
        $amount = round((float) ($payload['amount'] ?? 0), 3);

        if ($amount <= 0) {
            throw new \DomainException('Payment amount must be greater than zero.');
        }

        if ($amount > $due + 0.001) {
            throw new \DomainException("Payment amount (৳ {$amount}) exceeds due amount (৳ {$due}).");
        }

        $externalData = array_filter([
            'method'    => $payload['method'] ?? null,
            'reference' => $payload['reference'] ?? null,
        ]);

        return BankingPaymentRequest::create([
            'request_no'              => BankingPaymentRequest::generateRequestNo(),
            'source_type'             => PaymentRequestSourceType::SUPPLIER->value,
            'sourceable_type'         => PurchaseInvoice::class,
            'sourceable_id'           => $invoice->id,
            'amount'                  => $amount,
            'payment_date'            => $payload['payment_date'] ?? null,
            'transaction_category_id' => $payload['transaction_category_id'] ?? null,
            'description'             => 'Payment for Purchase Invoice #' . $invoice->invoice_no
                . ' — ' . $invoice->supplier->name,
            'bank_account_id'         => (int) $payload['bank_account_id'],
            'notes'                   => $payload['notes'] ?? null,
            'external_data'           => $externalData ?: null,
            'status'                  => 'pending',
            'requested_by'            => $userId,
        ]);
    }

    /**
     * Called by BankingManagement::markCompleted() when source_type = 'supplier'
     * and sourceable is a PurchaseInvoice.
     *
     * Creates:
     *   CR cash/bank account  [payment amount]   (money leaves)
     *
     * Then syncs invoice paid_amount + status via the existing syncPaymentStatus().
     */
    public function completePayment(BankingPaymentRequest $request, int $userId): void
    {
        DB::transaction(function () use ($request, $userId): void {
            $invoice = PurchaseInvoice::lockForUpdate()->findOrFail($request->sourceable_id);

            if (! $invoice->status->isPosted()) {
                throw new \DomainException('Invoice is not in a posted state.');
            }

            $bankAccount = BankAccount::findOrFail($request->bank_account_id);

            if (! $bankAccount->account_id) {
                throw new \DomainException(
                    'Bank account "' . $bankAccount->bank_name . '" has no linked Chart of Accounts entry.'
                );
            }

            $amount   = round((float) $request->amount, 3);
            $notes    = 'Purchase Invoice #' . $invoice->invoice_no . ' – supplier payment';
            $datetime = now()->format('Y-m-d H:i:s');

            // CR cash/bank — money leaves
            $txn = Transaction::create([
                'account_id'             => (int) $bankAccount->account_id,
                'datetime'               => $datetime,
                'type'                   => TransactionType::PURCHASE_INVOICE->value,
                'reference_type'         => 'purchase_invoice',
                'reference_id'           => $invoice->id,
                'debit'                  => 0,
                'credit'                 => $amount,
                'notes'                  => $notes,
                'related_transaction_id' => $invoice->transaction_id,
                'relation_type'          => TransactionRelationType::PAIR->value,
                'created_by'             => $userId,
            ]);

            // Mark request completed
            $request->update([
                'transaction_id' => $txn->id,
                'status'         => 'completed',
                'completed_by'   => $userId,
                'completed_at'   => now(),
            ]);

            // Sync invoice paid/due/status from all CR transactions
            app(PurchaseInvoiceService::class)->syncPaymentStatus($invoice);
        });
    }
}
