<?php

namespace App\Services\Inventory;

use App\Enums\Accounts\PaymentRequestSourceType;
use App\Enums\Accounts\TransactionType;
use App\Models\Account;
use App\Models\AdvanceAdjustment;
use App\Models\BankingPaymentRequest;
use App\Models\PurchaseFund;
use App\Models\PurchaseInvoice;
use App\Models\Transaction;
use App\Services\Accounts\LedgerService;
use Illuminate\Support\Facades\DB;

class PurchaseInvoicePaymentService
{
    public function __construct(private readonly LedgerService $ledger) {}

    /**
     * Create a BankingPaymentRequest for a purchase invoice payment with pre-configured double-entry.
     *
     * Stores debit/credit accounts at creation time:
     * DR = Accounts Payable (from invoice)
     * CR = Payment Account (cash/bank)
     *
     * Also stores transaction details (reference_no, name, phone, method) for use at completion time.
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

        // Resolve payment account (CR account for payment)
        $paymentAccountId = (int) ($payload['payment_account_id'] ?? 0);
        if ($paymentAccountId <= 0) {
            throw new \DomainException('No payment account selected.');
        }

        $paymentAccount = Account::findOrFail($paymentAccountId);
        if (!$paymentAccount->is_active) {
            throw new \DomainException('Selected payment account is inactive.');
        }

        // Get debit account (AP account from invoice)
        $payableAccountId = (int) $invoice->accounts_payable_account_id;
        if ($payableAccountId <= 0) {
            throw new \DomainException('Invoice has no accounts payable account configured.');
        }

        $payableAccount = Account::findOrFail($payableAccountId);
        if (!$payableAccount->is_active) {
            throw new \DomainException('Invoice accounts payable account is inactive.');
        }

        $attachmentIds = collect($payload['attachment_ids'] ?? [])
            ->map(fn ($id): int => (int) $id)->filter()->unique()->values()->all();

        // Create request with pre-configured double-entry
        return BankingPaymentRequest::create([
            'request_no'              => BankingPaymentRequest::generateRequestNo(),
            'source_type'             => PaymentRequestSourceType::SUPPLIER->value,
            'sourceable_type'         => PurchaseInvoice::class,
            'sourceable_id'           => $invoice->id,
            'amount'                  => $amount,
            'payment_date'            => $payload['payment_date'] ?? null,
            'description'             => 'Payment for Purchase Invoice #' . $invoice->invoice_no
                . ' — ' . $invoice->supplier->name,
            'account_id'              => $paymentAccountId,
            'bank_account_id'         => null,
            'notes'                   => $payload['notes'] ?? null,
            // Double-entry configuration
            'debit_account_id'        => $payableAccountId,
            'debit_amount'            => $amount,
            'credit_account_id'       => $paymentAccountId,
            'credit_amount'           => $amount,
            // Transaction details (for use at completion)
            'reference_no'            => $payload['reference'] ?? null,
            'name'                    => trim($payload['name'] ?? '') ?: null,
            'phone'                   => trim($payload['phone'] ?? '') ?: null,
            'method'                  => $payload['method'] ?? 'bank',
            'status'                  => 'pending',
            'requested_by'            => $userId,
        ]);
    }

    /**
     * Settle part of a purchase invoice using an existing supplier advance.
     *
     * The advance money already left the company (the PurchaseFund was completed,
     * which posted Dr Supplier Advance / Cr cash). Applying it to an invoice is an
     * internal reclassification, so it posts immediately — no banking approval:
     *
     *   DR Accounts Payable   (invoice liability settled)
     *   CR Supplier Advance   (ASSET-SUP-ADV asset reduced)
     *
     * One invoice payment consumes exactly one advance (fund). The whole remaining
     * advance is applied, capped at the invoice's due amount so it never over-pays.
     */
    public function applyAdvance(PurchaseInvoice $invoice, int $fundId, int $userId): Transaction
    {
        return DB::transaction(function () use ($invoice, $fundId, $userId): Transaction {
            $locked = PurchaseInvoice::lockForUpdate()->findOrFail($invoice->id);

            if (! $locked->status->isPosted()) {
                throw new \DomainException('Invoice is not in a posted state.');
            }

            $due = round((float) $locked->due_amount, 3);
            if ($due <= 0) {
                throw new \DomainException('This invoice is already fully paid.');
            }

            // The fund must be a completed advance for one of this supplier's POs.
            $fund = PurchaseFund::query()
                ->where('id', $fundId)
                ->where('status', 'completed')
                ->whereNotNull('transaction_id')
                ->with('purchaseOrder:id,supplier_id,po_no')
                ->first();

            if (! $fund) {
                throw new \DomainException('Selected advance is not available.');
            }

            if ((int) $fund->purchaseOrder?->supplier_id !== (int) $locked->supplier_id) {
                throw new \DomainException('The advance belongs to a different supplier.');
            }

            $advanceTxn = Transaction::lockForUpdate()->findOrFail($fund->transaction_id);
            $remaining  = round($advanceTxn->remainingAdvance(), 3);

            if ($remaining <= 0) {
                throw new \DomainException('This advance has no remaining balance.');
            }

            // Apply the whole remaining advance, but never more than what is due.
            $amount = round(min($remaining, $due), 3);
            if ($amount <= 0) {
                throw new \DomainException('Nothing to apply from this advance.');
            }

            $payableAccountId = (int) $locked->accounts_payable_account_id;
            if ($payableAccountId <= 0) {
                throw new \DomainException('Invoice has no accounts payable account to settle against.');
            }

            $advanceAccountId = (int) Account::query()->where('code', 'ASSET-SUP-ADV')->value('id');
            if ($advanceAccountId <= 0) {
                throw new \DomainException('Supplier Advance account (ASSET-SUP-ADV) not found.');
            }

            $datetime = now()->format('Y-m-d H:i:s');
            $notes    = 'Purchase Invoice #' . $locked->invoice_no
                . ' – settled from advance (PO# ' . ($fund->purchaseOrder?->po_no ?? '—') . ')';

            // DR payable (settle liability) / CR supplier advance (asset reduces)
            $txn = $this->ledger->post(
                [
                    'datetime'       => $datetime,
                    'type'           => TransactionType::PURCHASE->value,
                    'reference_type' => 'purchase_invoice',
                    'reference_id'   => $locked->id,
                    'notes'          => $notes,
                    'created_by'     => $userId,
                ],
                [
                    ['account_id' => $payableAccountId, 'debit' => $amount, 'credit' => 0,       'notes' => 'Accounts payable'],
                    ['account_id' => $advanceAccountId, 'debit' => 0,       'credit' => $amount, 'notes' => 'Supplier advance applied'],
                ],
            );

            // Keep Transaction::remainingAdvance() accurate.
            AdvanceAdjustment::query()->create([
                'advance_transaction_id' => $advanceTxn->id,
                'adjust_transaction_id'  => $txn->id,
                'amount'                 => $amount,
                'notes'                  => 'Applied to Invoice #' . $locked->invoice_no,
                'created_by'             => $userId,
            ]);

            // Track total advance offset on the invoice.
            $locked->advance_adjusted_amount = round((float) $locked->advance_adjusted_amount + $amount, 3);
            $locked->save();

            // Re-sync paid/due/status — the CR above (≠ AP account) counts as paid.
            app(PurchaseInvoiceService::class)->syncPaymentStatus($locked);

            return $txn;
        });
    }

    /**
     * Complete supplier payment using stored double-entry configuration.
     *
     * Uses debit/credit accounts and amounts pre-stored on the request.
     * Also uses pre-stored transaction details (reference_no, name, phone, method).
     *
     * Posts a balanced transaction:
     *   DR accounts payable  [from request->debit_account_id]
     *   CR cash/bank account [from request->credit_account_id]
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

            // Use stored double-entry accounts
            if (!$request->debit_account_id || !$request->credit_account_id) {
                throw new \DomainException('Double-entry accounts not configured for this supplier payment request.');
            }

            // Validate both accounts exist and are active
            $debitAccount = Account::findOrFail($request->debit_account_id);
            $creditAccount = Account::findOrFail($request->credit_account_id);

            if (!$debitAccount->is_active || !$creditAccount->is_active) {
                throw new \DomainException('One or more double-entry accounts are inactive.');
            }

            $amount   = round((float) $request->amount, 3);
            $notes    = 'Purchase Invoice #' . $invoice->invoice_no . ' – supplier payment';
            $datetime = now()->format('Y-m-d H:i:s');

            // Use stored transaction details; blank name means paid directly to supplier
            $payeeName  = $request->name ?: $invoice->supplier?->name;
            $payeePhone = $request->phone;
            $method     = $request->method ?? 'bank';
            $reference  = $request->reference_no;

            // DR payable (settle liability) / CR cash-bank (money leaves)
            $txn = $this->ledger->post(
                array_filter([
                    'datetime'       => $datetime,
                    'type'           => TransactionType::PURCHASE->value,
                    'reference_type' => 'banking_payment_request',
                    'reference_id'   => $request->id,
                    'reference_no'   => $reference,
                    'method'         => $method,
                    'name'           => $payeeName,
                    'phone'          => $payeePhone,
                    'notes'          => $notes,
                    'created_by'     => $userId,
                ], fn ($v) => $v !== null),
                [
                    ['account_id' => (int) $debitAccount->id, 'debit' => $amount, 'credit' => 0, 'notes' => $debitAccount->name],
                    ['account_id' => (int) $creditAccount->id, 'debit' => 0, 'credit' => $amount, 'notes' => $creditAccount->name],
                ],
            );

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
