<?php

namespace App\Services\Inventory;

use App\Accounting\PostingContext;
use App\Enums\Accounts\PostingLeg;
use App\Enums\Accounts\TransactionType;
use App\Models\Account;
use App\Models\AccountingEvent;
use App\Models\BankAccount;
use App\Models\BankingPaymentRequest;
use App\Models\Employee;
use App\Models\PurchaseFund;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Services\Accounts\PostingEngine;
use Illuminate\Support\Facades\DB;

class FundReleaseService
{
    /** Event key + advance account: a PO fund release is always a supplier advance. */
    private const ADVANCE_EVENT = 'purchase.supplier_advance';

    public function __construct(private readonly PostingEngine $engine) {}

    /**
     * Submit a fund advance request (pending banking approval).
     *
     * Creates one PurchaseFund (status=pending) and one BankingPaymentRequest.
     * No ledger Transaction is created at this point.
     *
     * @param  array{
     *   payment_account_id: int,  // chart-of-accounts money account (the Cr leg)
     *   account_type: string,     // cash | bank | mfs | wallet
     *   method: string,
     *   amount: float,
     *   release_date: string,
     *   receiver_mode: string,    // 'supplier_direct' | 'via_employee'
     *   receiver_id: int,
     *   reference_no?: ?string,
     *   remarks?: ?string,
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
            // 2. Parse payload — a PO fund release is always a supplier advance;
            //    the money is paid directly to the supplier or via an employee.
            // ------------------------------------------------------------------
            $amount        = round((float) ($payload['amount'] ?? 0), 2);
            $paymentAccountId = (int) ($payload['payment_account_id'] ?? 0);
            $method        = (string) ($payload['method'] ?? '');
            $releaseDate   = (string) ($payload['release_date'] ?? now()->toDateString());
            $receiverMode  = (string) ($payload['receiver_mode'] ?? 'supplier_direct');
            $referenceNo   = trim((string) ($payload['reference_no'] ?? '')) ?: null;

            if ($amount <= 0) {
                throw new \DomainException('Release amount must be greater than zero.');
            }
            if ($paymentAccountId <= 0) {
                throw new \DomainException('Source account is required.');
            }

            // The source is a chart-of-accounts money account; a BankAccount (when
            // one is attached to it) is just descriptive info for the banking screen.
            $paymentAccount = Account::query()->where('is_active', true)->findOrFail($paymentAccountId);
            $linkedBankAccountId = BankAccount::query()
                ->where('account_id', $paymentAccount->id)
                ->value('id');

            // Receiver: directly the PO supplier, or an employee intermediary.
            if ($receiverMode === 'via_employee') {
                $receiverId = (int) ($payload['receiver_id'] ?? 0);
                if ($receiverId <= 0) {
                    throw new \DomainException('Please select the employee receiving the advance.');
                }
                $payeeType     = 'employee';
                $receiverClass = Employee::class;
            } else {
                $receiverId = (int) ($locked->supplier_id ?? 0);
                if ($receiverId <= 0) {
                    throw new \DomainException('This purchase order has no supplier to advance to.');
                }
                $payeeType     = 'supplier';
                $receiverClass = Supplier::class;
            }

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
            // 4. Resolve receiver for description + the supplier-advance Dr account
            // ------------------------------------------------------------------
            $receiverName  = $this->resolveReceiverName($payeeType, $receiverId, $po);
            $receiverPhone = $this->resolveReceiverPhone($payeeType, $receiverId, $po);
            $advanceAccountId = $this->resolveSupplierAdvanceAccountId();

            // ------------------------------------------------------------------
            // 5. Create PurchaseFund (status = pending — no transaction yet)
            // ------------------------------------------------------------------
            $fund = PurchaseFund::query()->create([
                'purchase_order_id'  => (int) $locked->id,
                'transaction_id'     => null,
                'amount'             => $amount,
                'released_by'        => $userId,
                'release_date'       => $releaseDate,
                'payto'              => $payeeType,
                'receiver_type'      => $receiverClass,
                'receiver_id'        => $receiverId,
                'reference_no'       => $referenceNo,
                'remarks'            => $payload['remarks'] ?? null,
                'status'             => 'pending',
                'payment_account_id' => $paymentAccount->id,   // COA money account (Cr leg)
                'bank_account_id'    => $linkedBankAccountId,   // info, if one is attached
                'method'             => $method,
            ]);

            // ------------------------------------------------------------------
            // 6. Create BankingPaymentRequest — sourceable → Supplier.
            //    (The advance belongs to the supplier and can later be applied to
            //    any of that supplier's purchase invoices.) The specific
            //    PurchaseFund this request settles is carried in
            //    external_data['purchase_fund_id'] so completion can resolve it.
            // ------------------------------------------------------------------
            $supplierId = (int) ($locked->supplier_id ?? 0);
            if ($supplierId <= 0) {
                throw new \DomainException('This purchase order has no supplier to advance to.');
            }

            BankingPaymentRequest::query()->create([
                'request_no'      => BankingPaymentRequest::generateRequestNo(),
                'source_type'     => TransactionType::ADVANCE->value,
                'sourceable_type' => Supplier::class,
                'sourceable_id'   => $supplierId,
                'external_data'   => [
                    'purchase_fund_id'  => $fund->id,
                    'purchase_order_id' => $locked->id,
                ],
                'amount'          => $amount,
                'payment_date'    => $releaseDate,
                'description'     => sprintf(
                    'Supplier advance – PO# %s → %s',
                    $locked->po_no,
                    $receiverName ?? 'Unknown'
                ),
                'account_id'      => $paymentAccount->id,    // COA money account
                'bank_account_id' => $linkedBankAccountId,   // info, if attached
                // Pre-stored double-entry: Dr Supplier Advance / Cr the money account.
                'debit_account_id'  => $advanceAccountId,
                'debit_amount'      => $amount,
                'credit_account_id' => $paymentAccount->id,
                'credit_amount'     => $amount,
                'reference_no'    => $referenceNo,
                'name'            => $receiverName,
                'phone'           => $receiverPhone,
                'method'          => $method,
                'status'          => 'pending',
                'notes'           => $payload['remarks'] ?? null,
                'requested_by'    => $userId,
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

            $fundId = (int) ($bankingRequest->external_data['purchase_fund_id'] ?? 0);
            if ($bankingRequest->sourceable_type !== Supplier::class || $fundId <= 0) {
                throw new \DomainException('This payment request is not linked to a fund release.');
            }

            // Lock the PurchaseFund row
            $fund = PurchaseFund::query()
                ->lockForUpdate()
                ->where('id', $fundId)
                ->where('status', 'pending')
                ->firstOrFail();

            // The payment (Cr) leg is the chart-of-accounts money account stored on
            // the fund — resolved straight from `accounts`, no BankAccount indirection.
            $paymentAccountId = (int) ($fund->payment_account_id ?: $bankingRequest->account_id);
            if ($paymentAccountId <= 0) {
                throw new \DomainException('Fund release has no source account to pay from.');
            }

            $po           = $fund->purchaseOrder;
            $receiver     = $fund->receiver;   // Supplier or Employee (morph)
            $receiverName = $receiver?->name ?? $this->resolveReceiverName($fund->payto, (int) $fund->receiver_id, $po);
            $notes        = $fund->remarks ?? ('Supplier advance – PO# ' . $po->po_no);

            // ------------------------------------------------------------------
            // Auto-post the balanced double-entry via the configured event:
            //   Dr Supplier Advance (asset)  /  Cr the chosen money account.
            // Receiver details (name/phone) + reference_no ride on the header.
            // ------------------------------------------------------------------
            $transaction = $this->engine->record(self::ADVANCE_EVENT, new PostingContext(
                amount: (float) $fund->amount,
                datetime: $fund->release_date->format('Y-m-d') . ' 00:00:00',
                paymentAccountId: $paymentAccountId,
                referenceType: Supplier::class,
                referenceId: (int) $bankingRequest->sourceable_id,
                referenceNo: $fund->reference_no,
                method: $fund->method,
                name: $receiverName,
                phone: $receiver?->phone,
                notes: $notes,
                actorId: $userId,
            ));

            // Update PurchaseFund → completed
            $fund->update([
                'transaction_id' => $transaction->id,
                'status'         => 'completed',
            ]);

            // Update BankingPaymentRequest → completed
            $bankingRequest->update([
                'transaction_id' => $transaction->id,
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

    private function resolveReceiverPhone(string $payeeType, int $receiverId, PurchaseOrder $po): ?string
    {
        if ($receiverId <= 0) {
            return null;
        }

        if ($payeeType === 'employee') {
            return Employee::query()->find($receiverId)?->phone;
        }

        return $po->supplier?->phone ?? Supplier::query()->find($receiverId)?->phone;
    }

    /**
     * Fixed Supplier Advance (asset) account = the Dr leg, taken from the
     * configured `purchase.supplier_advance` accounting event so the pre-stored
     * double-entry matches what the posting engine would resolve at completion.
     */
    private function resolveSupplierAdvanceAccountId(): int
    {
        $event = AccountingEvent::query()
            ->active()
            ->forKey(self::ADVANCE_EVENT)
            ->with('rules')
            ->first();

        $debitRule = $event?->rules
            ->first(fn ($rule) => $rule->leg === PostingLeg::DEBIT && $rule->isFixed());

        $accountId = (int) ($debitRule?->account_id ?? 0);

        if ($accountId <= 0) {
            throw new \DomainException(
                "Accounting event '" . self::ADVANCE_EVENT . "' has no fixed Supplier Advance debit account configured."
            );
        }

        return $accountId;
    }
}
