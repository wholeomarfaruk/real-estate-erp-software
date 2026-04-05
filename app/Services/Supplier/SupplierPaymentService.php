<?php

namespace App\Services\Supplier;

use App\Enums\Supplier\SupplierBillStatus;
use App\Enums\Supplier\SupplierPaymentStatus;
use App\Models\SupplierBill;
use App\Models\SupplierPayment;
use App\Models\SupplierPaymentAllocation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class SupplierPaymentService
{
    public function generatePaymentNo(): string
    {
        $lastId = (int) SupplierPayment::query()->withTrashed()->max('id');

        return 'SPY-'.str_pad((string) ($lastId + 1), 6, '0', STR_PAD_LEFT);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, array{supplier_bill_id:int,allocated_amount:float,notes:?string}>  $allocations
     */
    public function savePayment(array $payload, array $allocations, ?SupplierPayment $payment = null, ?int $actorId = null): SupplierPayment
    {
        $resolvedActorId = $this->resolveActorId($actorId);

        return DB::transaction(function () use ($payload, $allocations, $payment, $resolvedActorId): SupplierPayment {
            $record = null;
            $oldBillIds = [];

            if ($payment) {
                $record = SupplierPayment::query()
                    ->with('allocations')
                    ->lockForUpdate()
                    ->findOrFail($payment->id);

                if (! $record->canEdit()) {
                    throw new \DomainException('Only draft payments can be edited.');
                }

                $oldBillIds = $record->allocations->pluck('supplier_bill_id')->map(fn ($id): int => (int) $id)->all();
            }

            $normalizedAllocations = $this->normalizeAllocations($allocations);
            $totalAmount = round(max(0, (float) ($payload['total_amount'] ?? 0)), 2);
            $allocatedAmount = round(collect($normalizedAllocations)->sum('allocated_amount'), 2);

            if ($totalAmount <= 0) {
                throw new \DomainException('Total amount must be greater than zero.');
            }

            if ($allocatedAmount - $totalAmount > 0.0001) {
                throw new \DomainException('Allocation total cannot exceed payment total amount.');
            }

            $this->validateAllocations(
                supplierId: (int) ($payload['supplier_id'] ?? 0),
                allocations: $normalizedAllocations
            );

            $record ??= new SupplierPayment();
            $record->fill($payload);
            $record->allocated_amount = $allocatedAmount;
            $record->unallocated_amount = round(max(0, $totalAmount - $allocatedAmount), 2);
            $record->updated_by = $resolvedActorId;

            if (! $record->exists) {
                $record->created_by = $resolvedActorId;
            }

            $preserveDraft = (($payload['status'] ?? SupplierPaymentStatus::DRAFT->value) === SupplierPaymentStatus::DRAFT->value);
            $record->syncAmountsAndStatus($preserveDraft);
            $record->save();

            $record->allocations()->delete();

            foreach ($normalizedAllocations as $allocation) {
                $record->allocations()->create([
                    'supplier_bill_id' => $allocation['supplier_bill_id'],
                    'allocated_amount' => $allocation['allocated_amount'],
                    'notes' => $allocation['notes'] ?? null,
                ]);
            }

            $newBillIds = collect($normalizedAllocations)->pluck('supplier_bill_id')->map(fn ($id): int => (int) $id)->all();
            $affectedBillIds = array_values(array_unique(array_merge($oldBillIds, $newBillIds)));

            $this->syncBillsByAllocations($affectedBillIds, $resolvedActorId);
            app(SupplierLedgerService::class)->postPayment($record, $resolvedActorId, false);

            return $record->refresh();
        });
    }

    public function cancelPayment(SupplierPayment $payment, ?int $actorId = null): SupplierPayment
    {
        $resolvedActorId = $this->resolveActorId($actorId);

        return DB::transaction(function () use ($payment, $resolvedActorId): SupplierPayment {
            $record = SupplierPayment::query()
                ->with('allocations')
                ->lockForUpdate()
                ->findOrFail($payment->id);

            if (! $record->canCancel()) {
                throw new \DomainException('This payment cannot be cancelled.');
            }

            $affectedBillIds = $record->allocations
                ->pluck('supplier_bill_id')
                ->map(fn ($id): int => (int) $id)
                ->unique()
                ->values()
                ->all();

            $record->update([
                'status' => SupplierPaymentStatus::CANCELLED->value,
                'updated_by' => $resolvedActorId,
            ]);

            $this->syncBillsByAllocations($affectedBillIds, $resolvedActorId);
            app(SupplierLedgerService::class)->postPayment($record, $resolvedActorId, false);

            return $record->refresh();
        });
    }

    /**
     * @param  array<int, array{supplier_bill_id:int,allocated_amount:float,notes:?string}>  $allocations
     * @return array<int, array{supplier_bill_id:int,allocated_amount:float,notes:?string}>
     */
    protected function normalizeAllocations(array $allocations): array
    {
        $bucket = [];

        foreach ($allocations as $allocation) {
            $billId = (int) ($allocation['supplier_bill_id'] ?? 0);
            $allocatedAmount = round(max(0, (float) ($allocation['allocated_amount'] ?? 0)), 2);
            $notes = isset($allocation['notes']) ? trim((string) $allocation['notes']) : null;

            if ($billId <= 0 || $allocatedAmount <= 0) {
                continue;
            }

            if (! isset($bucket[$billId])) {
                $bucket[$billId] = [
                    'supplier_bill_id' => $billId,
                    'allocated_amount' => 0.0,
                    'notes' => $notes ?: null,
                ];
            }

            $bucket[$billId]['allocated_amount'] = round($bucket[$billId]['allocated_amount'] + $allocatedAmount, 2);

            if ($notes && ! $bucket[$billId]['notes']) {
                $bucket[$billId]['notes'] = $notes;
            }
        }

        return array_values($bucket);
    }

    /**
     * @param  array<int, array{supplier_bill_id:int,allocated_amount:float,notes:?string}>  $allocations
     */
    protected function validateAllocations(int $supplierId, array $allocations): void
    {
        if ($allocations === []) {
            return;
        }

        if ($supplierId <= 0) {
            throw new \DomainException('Supplier is required for bill allocation.');
        }

        $billIds = collect($allocations)->pluck('supplier_bill_id')->unique()->values()->all();
        $bills = SupplierBill::query()
            ->whereIn('id', $billIds)
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($allocations as $allocation) {
            $bill = $bills->get($allocation['supplier_bill_id']);

            if (! $bill) {
                throw new \DomainException('One or more allocated bills do not exist.');
            }

            if ((int) $bill->supplier_id !== $supplierId) {
                throw new \DomainException('All allocated bills must belong to selected supplier.');
            }

            if (in_array($bill->status, [SupplierBillStatus::PAID, SupplierBillStatus::CANCELLED], true) || (float) $bill->due_amount <= 0) {
                throw new \DomainException('Only pending bills can receive payment allocation.');
            }

            if ((float) $allocation['allocated_amount'] - (float) $bill->due_amount > 0.0001) {
                throw new \DomainException('Allocation cannot exceed selected bill due amount.');
            }
        }
    }

    /**
     * @param  int[]  $billIds
     */
    protected function syncBillsByAllocations(array $billIds, int $actorId): void
    {
        $ids = collect($billIds)
            ->map(fn ($id): int => (int) $id)
            ->filter(fn ($id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($ids === []) {
            return;
        }

        $bills = SupplierBill::query()
            ->whereIn('id', $ids)
            ->lockForUpdate()
            ->get();

        foreach ($bills as $bill) {
            if ($bill->status === SupplierBillStatus::CANCELLED) {
                continue;
            }

            $allocatedAmount = (float) SupplierPaymentAllocation::query()
                ->where('supplier_bill_id', $bill->id)
                ->whereHas('payment', function (Builder $builder): void {
                    $builder->whereNotIn('status', [
                        SupplierPaymentStatus::DRAFT->value,
                        SupplierPaymentStatus::CANCELLED->value,
                    ]);
                })
                ->sum('allocated_amount');

            $paidAmount = round(min((float) $bill->total_amount, max(0, $allocatedAmount)), 2);
            $dueAmount = round(max(0, (float) $bill->total_amount - $paidAmount), 2);

            $status = $this->resolveBillStatus($bill, $paidAmount, $dueAmount);

            $bill->update([
                'paid_amount' => $paidAmount,
                'due_amount' => $dueAmount,
                'status' => $status->value,
                'updated_by' => $actorId,
            ]);
        }
    }

    protected function resolveBillStatus(SupplierBill $bill, float $paidAmount, float $dueAmount): SupplierBillStatus
    {
        if ($dueAmount <= 0) {
            return SupplierBillStatus::PAID;
        }

        if ($paidAmount > 0 && $dueAmount > 0) {
            return SupplierBillStatus::PARTIAL;
        }

        if ($dueAmount > 0 && $bill->due_date && $bill->due_date->lt(now()->startOfDay())) {
            return SupplierBillStatus::OVERDUE;
        }

        return SupplierBillStatus::OPEN;
    }

    protected function resolveActorId(?int $actorId = null): int
    {
        $resolved = $actorId ?? auth()->id();

        if (! $resolved) {
            throw new \DomainException('Unable to resolve authenticated user for this action.');
        }

        return (int) $resolved;
    }
}
