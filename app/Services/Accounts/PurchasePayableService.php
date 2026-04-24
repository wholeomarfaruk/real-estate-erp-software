<?php

namespace App\Services\Accounts;

use App\Enums\Accounts\PurchasePayableStatus;
use App\Models\Payment;
use App\Models\PurchaseOrder;
use App\Models\PurchasePayable;
use Illuminate\Support\Facades\DB;

class PurchasePayableService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function savePayable(array $payload, ?PurchasePayable $purchasePayable = null): PurchasePayable
    {
        return DB::transaction(function () use ($payload, $purchasePayable): PurchasePayable {
            $record = $purchasePayable
                ? PurchasePayable::query()->lockForUpdate()->findOrFail($purchasePayable->id)
                : new PurchasePayable();

            $record->fill($payload);
            $record->payable_amount = round(max(0, (float) ($payload['payable_amount'] ?? 0)), 2);
            $record->paid_amount = round(max(0, (float) ($payload['paid_amount'] ?? 0)), 2);
            $record->recalculateDueAndStatus();

            if ($record->payable_amount <= 0) {
                throw new \DomainException('Payable amount must be greater than zero.');
            }

            if ((float) $record->paid_amount > (float) $record->payable_amount) {
                throw new \DomainException('Paid amount cannot exceed payable amount.');
            }

            $this->validateSupplierConsistency(
                purchaseOrderId: (int) $record->purchase_order_id,
                supplierId: $record->supplier_id ? (int) $record->supplier_id : null,
            );

            $record->save();

            return $record->refresh();
        });
    }

    public function deletePayable(PurchasePayable $purchasePayable): void
    {
        DB::transaction(function () use ($purchasePayable): void {
            $record = PurchasePayable::query()->lockForUpdate()->findOrFail($purchasePayable->id);

            $hasSettlements = Payment::query()
                ->where('reference_type', AccountingEntryService::PAYABLE_REFERENCE_TYPE)
                ->where('reference_id', $record->id)
                ->exists();

            if ($hasSettlements) {
                throw new \DomainException('Payable has settlement entries and cannot be deleted.');
            }

            $record->delete();
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function settlePayable(PurchasePayable $purchasePayable, array $payload, ?int $actorId = null): Payment
    {
        return DB::transaction(function () use ($purchasePayable, $payload, $actorId): Payment {
            $record = PurchasePayable::query()->lockForUpdate()->findOrFail($purchasePayable->id);

            if ($record->status === PurchasePayableStatus::PAID || (float) $record->due_amount <= 0) {
                throw new \DomainException('Selected payable is already fully paid.');
            }

            $amount = round(max(0, (float) ($payload['amount'] ?? 0)), 2);

            if ($amount <= 0) {
                throw new \DomainException('Settlement amount must be greater than zero.');
            }

            if ($amount - (float) $record->due_amount > 0.0001) {
                throw new \DomainException('Settlement amount cannot exceed payable due amount.');
            }

            $payment = app(AccountingEntryService::class)->savePayment([
                'payment_no' => null,
                'date' => $payload['date'],
                'method' => $payload['method'],
                'payment_account_id' => $payload['payment_account_id'],
                'purpose_account_id' => $payload['payable_account_id'],
                'amount' => $amount,
                'payee_name' => $payload['payee_name'] ?? ($record->supplier?->name ?: null),
                'reference_type' => AccountingEntryService::PAYABLE_REFERENCE_TYPE,
                'reference_id' => (int) $record->id,
                'notes' => $payload['notes'] ?? null,
            ], null, $actorId);

            return $payment;
        });
    }

    protected function validateSupplierConsistency(int $purchaseOrderId, ?int $supplierId): void
    {
        if (! $supplierId) {
            return;
        }

        $purchaseOrder = PurchaseOrder::query()
            ->with('items:id,purchase_order_id,supplier_id')
            ->find($purchaseOrderId);

        if (! $purchaseOrder) {
            throw new \DomainException('Selected purchase order is invalid.');
        }

        $suppliersInOrder = $purchaseOrder->items
            ->pluck('supplier_id')
            ->filter()
            ->unique()
            ->values();

        // Current schema has supplier on purchase order items, not purchase_orders table.
        // Enforce supplier match only when the order resolves to a single supplier.
        if ($suppliersInOrder->count() === 1 && (int) $suppliersInOrder->first() !== $supplierId) {
            throw new \DomainException('Supplier does not match the purchase order supplier.');
        }
    }
}
