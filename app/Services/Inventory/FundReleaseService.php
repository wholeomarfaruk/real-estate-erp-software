<?php

namespace App\Services\Inventory;

use App\Enums\Accounts\TransactionRelationType;
use App\Enums\Accounts\TransactionType;
use App\Models\Account;
use App\Models\BankAccount;
use App\Models\BankingPaymentRequest;
use App\Models\Employee;
use App\Models\PurchaseFund;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use Illuminate\Support\Facades\DB;

class FundReleaseService
{
    /**
     * Submit a fund advance request (pending banking approval).
     *
     * Creates one PurchaseFund (status=pending) and one BankingPaymentRequest.
     * No ledger Transaction is created at this point.
     *
     * @param  array{
     *   transaction_category_id: int,
     *   bank_account_id: int,
     *   method: string,
     *   amount: float,
     *   release_date: string,
     *   payee_type: string,
     *   receiver_id: int,
     *   remarks: ?string,
     * } $payload
     */
    public function requestRelease(PurchaseOrder $po, array $payload, int $userId): PurchaseFund
    {
        return DB::transaction(function () use ($po, $payload, $userId): PurchaseFund {

            // ------------------------------------------------------------------
            // 1. Lock and validate purchase order
            // ------------------------------------------------------------------
            $locked = PurchaseOrder::query()->lockForUpdate()->findOrFail($po->id);

            $allowedStatuses = ['approved', 'partially_received', 'received'];
            if (! in_array($locked->status?->value, $allowedStatuses, true)) {
                throw new \DomainException('Fund can only be requested for approved purchase orders.');
            }

            // ------------------------------------------------------------------
            // 2. Parse payload
            // ------------------------------------------------------------------
            $amount        = round((float) ($payload['amount'] ?? 0), 2);
            $bankAccountId = (int) ($payload['bank_account_id'] ?? 0);
            $categoryId    = (int) ($payload['transaction_category_id'] ?? 0);
            $method        = (string) ($payload['method'] ?? '');
            $releaseDate   = (string) ($payload['release_date'] ?? now()->toDateString());
            $payeeType     = (string) ($payload['payee_type'] ?? '');
            $receiverId    = (int) ($payload['receiver_id'] ?? 0);

            if ($amount <= 0) {
                throw new \DomainException('Release amount must be greater than zero.');
            }
            if ($bankAccountId <= 0) {
                throw new \DomainException('Source bank account is required.');
            }
            if ($categoryId <= 0) {
                throw new \DomainException('Advance category is required.');
            }

            $category = TransactionCategory::query()
                ->where('id', $categoryId)
                ->where('type', 'advance')
                ->first();

            if (! $category) {
                throw new \DomainException('Invalid advance category selected.');
            }

            $bankAccount = BankAccount::query()->findOrFail($bankAccountId);

            // ------------------------------------------------------------------
            // 3. Cap check — pending + completed both count against the limit
            // ------------------------------------------------------------------
            $approvedAmount  = round((float) ($locked->approved_amount ?? 0), 2);
            $alreadyCommitted = round(
                (float) PurchaseFund::query()
                    ->where('purchase_order_id', $locked->id)
                    ->whereIn('status', ['pending', 'completed'])
                    ->sum('amount'),
                2
            );
            $remaining = round(max(0, $approvedAmount - $alreadyCommitted), 2);

            if ($approvedAmount <= 0) {
                throw new \DomainException('No approved amount is set for this purchase order.');
            }

            if ($amount > $remaining) {
                throw new \DomainException(sprintf(
                    'Fund request exceeds approved limit. Approved: %s | Committed: %s | Remaining: %s | Requested: %s.',
                    number_format($approvedAmount, 2),
                    number_format($alreadyCommitted, 2),
                    number_format($remaining, 2),
                    number_format($amount, 2)
                ));
            }

            // ------------------------------------------------------------------
            // 4. Resolve receiver for description
            // ------------------------------------------------------------------
            $receiverName  = $this->resolveReceiverName($payeeType, $receiverId, $po);
            $receiverClass = $payeeType === 'employee' ? Employee::class : Supplier::class;

            // ------------------------------------------------------------------
            // 5. Create PurchaseFund (status = pending — no transaction yet)
            // ------------------------------------------------------------------
            $fund = PurchaseFund::query()->create([
                'purchase_order_id'       => (int) $locked->id,
                'transaction_id'          => null,
                'amount'                  => $amount,
                'released_by'             => $userId,
                'release_date'            => $releaseDate,
                'payto'                   => $payeeType,
                'receiver_type'           => $receiverClass,
                'receiver_id'             => $receiverId,
                'remarks'                 => $payload['remarks'] ?? null,
                'status'                  => 'pending',
                'transaction_category_id' => $categoryId,
                'bank_account_id'         => $bankAccountId,
                'method'                  => $method,
            ]);

            // ------------------------------------------------------------------
            // 6. Create BankingPaymentRequest — sourceable → PurchaseFund
            // ------------------------------------------------------------------
            BankingPaymentRequest::query()->create([
                'request_no'              => BankingPaymentRequest::generateRequestNo(),
                'source_type'             => TransactionType::ADVANCE->value,
                'sourceable_type'         => PurchaseFund::class,
                'sourceable_id'           => $fund->id,
                'transaction_category_id' => $categoryId,
                'amount'                  => $amount,
                'description'             => sprintf(
                    'Fund advance – PO# %s → %s',
                    $locked->po_no,
                    $receiverName ?? 'Unknown'
                ),
                'bank_account_id'         => $bankAccountId,
                'status'                  => 'pending',
                'notes'                   => $payload['remarks'] ?? null,
                'requested_by'            => $userId,
            ]);

            return $fund;
        });
    }

    /**
     * Finalise a fund advance after Banking marks the request as completed.
     *
     * Creates the ledger Transaction and links it back to both the
     * PurchaseFund and the BankingPaymentRequest.
     */
    public function completeRelease(BankingPaymentRequest $bankingRequest, int $userId): PurchaseFund
    {
        return DB::transaction(function () use ($bankingRequest, $userId): PurchaseFund {

            if ($bankingRequest->status !== 'released') {
                throw new \DomainException('Only a released payment request can be completed.');
            }

            if ($bankingRequest->sourceable_type !== PurchaseFund::class || ! $bankingRequest->sourceable_id) {
                throw new \DomainException('This payment request is not linked to a fund release.');
            }

            // Lock the PurchaseFund row
            $fund = PurchaseFund::query()
                ->lockForUpdate()
                ->where('id', $bankingRequest->sourceable_id)
                ->where('status', 'pending')
                ->firstOrFail();

            $bankAccount = BankAccount::query()->findOrFail($bankingRequest->bank_account_id);
            $accountId   = (int) $bankAccount->account_id;

            $po           = $fund->purchaseOrder;
            $receiverName = $this->resolveReceiverName($fund->payto, (int) $fund->receiver_id, $po);

            $datetime    = $fund->release_date->format('Y-m-d') . ' 00:00:00';
            $categoryId  = $bankingRequest->transaction_category_id ?? $fund->transaction_category_id;
            $amount      = (float) $fund->amount;
            $notes       = $fund->remarks ?? ('Fund release – PO# ' . $po->po_no);

            // ------------------------------------------------------------------
            // TXN-CASH: CR bank/cash — money physically leaves the account
            // ------------------------------------------------------------------
            $txnCash = Transaction::query()->create([
                'account_id'              => $accountId,
                'datetime'                => $datetime,
                'type'                    => TransactionType::ADVANCE->value,
                'transaction_category_id' => $categoryId,
                'reference_type'          => 'purchase_order',
                'reference_id'            => (int) $po->id,
                'debit'                   => 0,
                'credit'                  => $amount,
                'name'                    => $receiverName,
                'method'                  => $fund->method,
                'notes'                   => $notes,
                'created_by'              => $userId,
            ]);

            // ------------------------------------------------------------------
            // TXN-ADVANCE: DR advance account — receivable/advance created
            // This is the transaction tracked in PurchaseFund.transaction_id
            // so remainingAdvance() works correctly.
            // ------------------------------------------------------------------
            $advanceAccount = $this->resolveAdvanceAccount();

            $txnAdvance = Transaction::query()->create([
                'account_id'              => $advanceAccount->id,
                'datetime'                => $datetime,
                'type'                    => TransactionType::ADVANCE->value,
                'transaction_category_id' => $categoryId,
                'reference_type'          => 'purchase_order',
                'reference_id'            => (int) $po->id,
                'debit'                   => $amount,
                'credit'                  => 0,
                'name'                    => $receiverName,
                'method'                  => $fund->method,
                'notes'                   => $notes,
                'related_transaction_id'  => $txnCash->id,
                'relation_type'           => TransactionRelationType::PAIR->value,
                'created_by'              => $userId,
            ]);

            // Update PurchaseFund → completed (tracks the ADVANCE side)
            $fund->update([
                'transaction_id' => $txnAdvance->id,
                'status'         => 'completed',
            ]);

            // Update BankingPaymentRequest → completed (tracks the CASH side)
            $bankingRequest->update([
                'transaction_id' => $txnCash->id,
                'status'         => 'completed',
                'completed_by'   => $userId,
                'completed_at'   => now(),
            ]);

            return $fund->fresh();
        });
    }

    /**
     * Total advance released for a PO that has not yet been offset against an invoice.
     * Only counts completed funds (transaction created and confirmed).
     */
    public function unreconciled(PurchaseOrder $po): float
    {
        $released = (float) PurchaseFund::query()
            ->where('purchase_order_id', $po->id)
            ->where('status', 'completed')
            ->sum('amount');

        $adjusted = (float) PurchaseInvoice::query()
            ->where('purchase_order_id', $po->id)
            ->whereNotNull('transaction_id')
            ->sum('advance_adjusted_amount');

        return round(max(0, $released - $adjusted), 2);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function resolveAdvanceAccount(): Account
    {
        $account = Account::query()
            ->whereRaw('LOWER(name) = ?', ['advance'])
            ->first();

        if (! $account) {
            throw new \DomainException('No account named "advance" found. Please create a Ledger account named "Advance".');
        }

        return $account;
    }

    private function resolveReceiverName(string $payeeType, int $receiverId, PurchaseOrder $po): ?string
    {
        if ($receiverId <= 0) {
            return null;
        }

        if ($payeeType === 'employee') {
            return Employee::query()->find($receiverId)?->name;
        }

        return $po->supplier?->name ?? Supplier::query()->find($receiverId)?->name;
    }
}
