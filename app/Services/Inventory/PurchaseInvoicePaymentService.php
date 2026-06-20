<?php

namespace App\Services\Inventory;

use App\Enums\Accounts\PaymentRequestSourceType;
use App\Enums\Accounts\TransactionType;
use App\Models\BankAccount;
use App\Models\BankingPaymentRequest;
use App\Models\PurchaseInvoice;
use App\Services\Accounts\LedgerService;
use Illuminate\Support\Facades\DB;

class PurchaseInvoicePaymentService
{
    public function __construct(private readonly LedgerService $ledger) {}

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

        // Source is a chart-of-accounts money account; a BankAccount (if one is
        // attached to it) is just descriptive info for the banking screen.
        $accountId           = (int) ($payload['payment_account_id'] ?? 0);
        $linkedBankAccountId = $accountId > 0
            ? BankAccount::query()->where('account_id', $accountId)->value('id')
            : null;

        $attachmentIds = collect($payload['attachment_ids'] ?? [])
            ->map(fn ($id): int => (int) $id)->filter()->unique()->values()->all();

        $externalData = array_filter([
            'method'      => $payload['method'] ?? null,
            'reference'   => $payload['reference'] ?? null,
            'name'        => $payload['name'] ?? null,   // blank ⇒ paid directly to supplier
            'phone'       => $payload['phone'] ?? null,
            'attachments' => $attachmentIds ?: null,
        ], fn ($v) => $v !== null && $v !== '');

        return BankingPaymentRequest::create([
            'request_no'      => BankingPaymentRequest::generateRequestNo(),
            'source_type'     => PaymentRequestSourceType::SUPPLIER->value,
            'sourceable_type' => PurchaseInvoice::class,
            'sourceable_id'   => $invoice->id,
            'amount'          => $amount,
            'payment_date'    => $payload['payment_date'] ?? null,
            'description'     => 'Payment for Purchase Invoice #' . $invoice->invoice_no
                . ' — ' . $invoice->supplier->name,
            'account_id'      => $accountId ?: null,
            'bank_account_id' => $linkedBankAccountId,
            'notes'           => $payload['notes'] ?? null,
            'external_data'   => $externalData ?: null,
            'status'          => 'pending',
            'requested_by'    => $userId,
        ]);
    }

    /**
     * Called by BankingManagement::markCompleted() when source_type = 'supplier'
     * and sourceable is a PurchaseInvoice.
     *
     * Posts a balanced double-entry transaction:
     *   DR accounts payable  [payment amount]   (liability settled)
     *   CR cash/bank account [payment amount]   (money leaves)
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

            // The payment (Cr) account is the chart-of-accounts money account stored on
            // the request. Fall back to the linked BankAccount for legacy requests.
            $cashAccountId = (int) ($request->account_id ?: 0);
            if ($cashAccountId <= 0 && $request->bank_account_id) {
                $cashAccountId = (int) (BankAccount::find($request->bank_account_id)?->account_id ?? 0);
            }
            if ($cashAccountId <= 0) {
                throw new \DomainException('Payment request has no source account to pay from.');
            }

            $payableAccountId = (int) $invoice->accounts_payable_account_id;

            if ($payableAccountId <= 0) {
                throw new \DomainException('Invoice has no accounts payable account to settle against.');
            }

            $amount   = round((float) $request->amount, 3);
            $notes    = 'Purchase Invoice #' . $invoice->invoice_no . ' – supplier payment';
            $datetime = now()->format('Y-m-d H:i:s');

            // Receiver details — blank name/phone means paid directly to the supplier.
            $ext        = (array) ($request->external_data ?? []);
            $payeeName  = $ext['name'] ?? null ?: $invoice->supplier?->name;
            $payeePhone = $ext['phone'] ?? null;
            $method     = $ext['method'] ?? null;
            $reference  = $ext['reference'] ?? null;

            // DR payable (settle liability) / CR cash-bank (money leaves)
            $txn = $this->ledger->post(
                array_filter([
                    'datetime'       => $datetime,
                    'type'           => TransactionType::PURCHASE_INVOICE->value,
                    'reference_type' => 'purchase_invoice',
                    'reference_id'   => $invoice->id,
                    'reference_no'   => $reference,
                    'method'         => $method,
                    'name'           => $payeeName,
                    'phone'          => $payeePhone,
                    'notes'          => $notes,
                    'created_by'     => $userId,
                ], fn ($v) => $v !== null),
                [
                    ['account_id' => $payableAccountId, 'debit' => $amount, 'credit' => 0,       'notes' => 'Accounts payable'],
                    ['account_id' => $cashAccountId,    'debit' => 0,       'credit' => $amount, 'notes' => 'Bank/Cash'],
                ],
            );

            // Carry uploaded attachments onto the posted transaction.
            if (! empty($ext['attachments'])) {
                $txn->update(['attachments' => $ext['attachments']]);
            }

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
