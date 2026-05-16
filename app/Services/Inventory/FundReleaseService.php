<?php

namespace App\Services\Inventory;

use App\Enums\Accounts\TransactionType;
use App\Enums\Inventory\FundReleaseType;
use App\Models\Employee;
use App\Models\Payment;
use App\Models\PurchaseFund;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Transaction;
use App\Services\NumberSequenceService;
use Illuminate\Support\Facades\DB;

class FundReleaseService
{
    public function __construct(private readonly NumberSequenceService $sequences) {}

    /**
     * Release an advance fund against a purchase order.
     *
     * Accounting entries posted:
     *   CASE employee_advance:  DR Employee Advance Account  /  CR Cash/Bank
     *   CASE supplier_advance:  DR Supplier Advance Account  /  CR Cash/Bank
     *
     * @param  array{
     *   advance_type: string,
     *   advance_account_id: int,
     *   payment_account_id: int,
     *   payment_method: string,
     *   amount: float,
     *   release_date: string,
     *   payee_type: string,
     *   receiver_id: int,
     *   remarks: ?string,
     * } $payload
     */
    public function release(PurchaseOrder $po, array $payload, int $userId): PurchaseFund
    {
        return DB::transaction(function () use ($po, $payload, $userId): PurchaseFund {

            // ------------------------------------------------------------------
            // 1. Lock and validate purchase order
            // ------------------------------------------------------------------
            $locked = PurchaseOrder::query()->lockForUpdate()->findOrFail($po->id);

            $allowedStatuses = ['approved', 'partially_received', 'received'];
            if (! in_array($locked->status?->value, $allowedStatuses, true)) {
                throw new \DomainException('Fund can only be released for approved purchase orders.');
            }

            // ------------------------------------------------------------------
            // 2. Parse and validate amounts
            // ------------------------------------------------------------------
            $amount           = round((float) ($payload['amount'] ?? 0), 2);
            $advanceAccountId = (int) ($payload['advance_account_id'] ?? 0);
            $paymentAccountId = (int) ($payload['payment_account_id'] ?? 0);
            $advanceType      = (string) ($payload['advance_type'] ?? '');
            $paymentMethod    = (string) ($payload['payment_method'] ?? '');
            $releaseDate      = (string) ($payload['release_date'] ?? now()->toDateString());

            if ($amount <= 0) {
                throw new \DomainException('Release amount must be greater than zero.');
            }
            if ($advanceAccountId <= 0) {
                throw new \DomainException('Advance account (DR) is required.');
            }
            if ($paymentAccountId <= 0) {
                throw new \DomainException('Cash / bank account (CR) is required.');
            }
            if (! FundReleaseType::tryFrom($advanceType)) {
                throw new \DomainException('Invalid advance type.');
            }

            // ------------------------------------------------------------------
            // 2b. Cap check — total releases must not exceed approved amount.
            //     Runs inside the PO lockForUpdate so concurrent releases for the
            //     same PO are serialised: Transaction B blocks here until A commits,
            //     then reads A's newly inserted fund row before deciding.
            // ------------------------------------------------------------------
            $approvedAmount  = round((float) ($locked->approved_amount ?? 0), 2);
            $alreadyReleased = round(
                (float) PurchaseFund::query()
                    ->where('purchase_order_id', $locked->id)
                    ->whereNotNull('transaction_id')
                    ->sum('amount'),
                2
            );
            $remaining = round(max(0, $approvedAmount - $alreadyReleased), 2);

            if ($approvedAmount <= 0) {
                throw new \DomainException(
                    'No approved amount is set for this purchase order. Contact your manager before releasing funds.'
                );
            }

            if ($amount > $remaining) {
                throw new \DomainException(sprintf(
                    'Fund release exceeds approved purchase order limit. '
                    . 'Approved: %s | Already released: %s | Remaining: %s | Requested: %s.',
                    number_format($approvedAmount, 2),
                    number_format($alreadyReleased, 2),
                    number_format($remaining, 2),
                    number_format($amount, 2)
                ));
            }

            // ------------------------------------------------------------------
            // 3. Build journal entry lines
            //    DR Advance Account (asset — increases advance balance owed)
            //    CR Cash / Bank Account (asset — decreases cash balance)
            // ------------------------------------------------------------------
            $advanceTypeEnum = FundReleaseType::from($advanceType);
            $lines = [
                [
                    'account_id'  => $advanceAccountId,
                    'debit'       => $amount,
                    'credit'      => 0,
                    'description' => $advanceTypeEnum->drDescription() . ' – PO# ' . $locked->po_no,
                ],
                [
                    'account_id'  => $paymentAccountId,
                    'debit'       => 0,
                    'credit'      => $amount,
                    'description' => 'Cash/bank payment – PO# ' . $locked->po_no,
                ],
            ];

            // ------------------------------------------------------------------
            // 4. Create journal transaction
            // ------------------------------------------------------------------
            $transaction = Transaction::query()->create([
                'date'           => $releaseDate,
                'type'           => TransactionType::FUND_RELEASE->value,
                'reference_type' => 'purchase_fund',
                'reference_id'   => null, // updated after fund record created
                'notes'          => 'Fund release – PO# ' . $locked->po_no,
                'created_by'     => $userId,
            ]);

            $transaction->lines()->createMany($lines);

            // ------------------------------------------------------------------
            // 5. Create payment record
            // ------------------------------------------------------------------
            $payment = Payment::query()->create([
                'transaction_id'    => (int) $transaction->id,
                'payment_no'        => $this->sequences->next('FR'),
                'date'              => $releaseDate,
                'method'            => $paymentMethod,
                'payment_type'      => 'fund_release',
                'release_type'      => $advanceType,
                'payment_account_id' => $paymentAccountId,
                'purpose_account_id' => $advanceAccountId,
                'amount'            => $amount,
                'payee_name'        => $this->resolvePayeeName($payload),
                'reference_type'    => 'purchase_order',
                'reference_id'      => (int) $locked->id,
                'notes'             => $payload['remarks'] ?? null,
                'created_by'        => $userId,
            ]);

            // ------------------------------------------------------------------
            // 6. Create PurchaseFund record
            // ------------------------------------------------------------------
            $receiverClass = $advanceType === FundReleaseType::EMPLOYEE_ADVANCE->value
                ? Employee::class
                : Supplier::class;

            $fund = PurchaseFund::query()->create([
                'purchase_order_id'  => (int) $locked->id,
                'release_type'       => $paymentMethod,
                'advance_type'       => $advanceType,
                'advance_account_id' => $advanceAccountId,
                'payment_account_id' => $paymentAccountId,
                'transaction_id'     => (int) $transaction->id,
                'payment_id'         => (int) $payment->id,
                'amount'             => $amount,
                'released_by'        => $userId,
                'release_date'       => $releaseDate,
                'payto'              => $advanceType,
                'receiver_type'      => $receiverClass,
                'receiver_id'        => (int) ($payload['receiver_id'] ?? 0),
                'remarks'            => $payload['remarks'] ?? null,
            ]);

            // 7. Back-fill the transaction reference
            $transaction->update(['reference_id' => (int) $fund->id]);

            return $fund;
        });
    }

    /**
     * Return the total advance amount released for a PO that has not yet
     * been offset against an invoice.
     */
    public function unreconciled(PurchaseOrder $po): float
    {
        // Total released
        $released = (float) PurchaseFund::query()
            ->where('purchase_order_id', $po->id)
            ->whereNotNull('transaction_id')
            ->sum('amount');

        // Total already adjusted in approved invoices
        $adjusted = (float) \App\Models\PurchaseInvoice::query()
            ->where('purchase_order_id', $po->id)
            ->whereNotNull('transaction_id') // approved
            ->sum('advance_adjusted_amount');

        return round(max(0, $released - $adjusted), 2);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function resolvePayeeName(array $payload): ?string
    {
        if (! empty($payload['payee_name'])) {
            return $payload['payee_name'];
        }

        $type = $payload['advance_type'] ?? '';
        $id   = (int) ($payload['receiver_id'] ?? 0);

        if ($id <= 0) {
            return null;
        }

        return $type === FundReleaseType::EMPLOYEE_ADVANCE->value
            ? Employee::query()->find($id)?->name
            : Supplier::query()->find($id)?->name;
    }
}
