<?php

namespace App\Services\Supplier;

use App\Enums\Supplier\SupplierBillStatus;
use App\Enums\Supplier\SupplierReturnStatus;
use App\Enums\Supplier\SupplierLedgerTransactionType;
use App\Enums\Supplier\SupplierPaymentStatus;
use App\Models\Supplier;
use App\Models\SupplierBill;
use App\Models\SupplierLedger as SupplierLedgerModel;
use App\Models\SupplierPayment;
use App\Models\SupplierReturn;
use Illuminate\Support\Facades\DB;

class SupplierLedgerService
{
    public const REFERENCE_TYPE_SUPPLIER = 'supplier';

    public const REFERENCE_TYPE_BILL = 'supplier_bill';

    public const REFERENCE_TYPE_PAYMENT = 'supplier_payment';

    public const REFERENCE_TYPE_RETURN = 'supplier_return';

    public function postOpeningBalance(Supplier $supplier, ?int $actorId = null, bool $wrapTransaction = true): void
    {
        if ($wrapTransaction) {
            DB::transaction(function () use ($supplier, $actorId): void {
                $this->postOpeningBalance($supplier, $actorId, false);
            });

            return;
        }

        $record = Supplier::query()->lockForUpdate()->find($supplier->id);

        if (! $record) {
            return;
        }

        $resolvedActorId = $this->resolveActorId($actorId);

        $this->syncOpeningBalanceEntry($record, $resolvedActorId);
        $this->rebuildSupplierBalances((int) $record->id, false);
    }

    public function postBill(SupplierBill $bill, ?int $actorId = null, bool $wrapTransaction = true): void
    {
        if ($wrapTransaction) {
            DB::transaction(function () use ($bill, $actorId): void {
                $this->postBill($bill, $actorId, false);
            });

            return;
        }

        $record = SupplierBill::query()->lockForUpdate()->find($bill->id);

        if (! $record) {
            return;
        }

        $resolvedActorId = $this->resolveActorId($actorId);

        $this->syncBillEntry($record, $resolvedActorId);
        $this->rebuildSupplierBalances((int) $record->supplier_id, false);
    }

    public function postPayment(SupplierPayment $payment, ?int $actorId = null, bool $wrapTransaction = true): void
    {
        if ($wrapTransaction) {
            DB::transaction(function () use ($payment, $actorId): void {
                $this->postPayment($payment, $actorId, false);
            });

            return;
        }

        $record = SupplierPayment::query()->lockForUpdate()->find($payment->id);

        if (! $record) {
            return;
        }

        $resolvedActorId = $this->resolveActorId($actorId);

        $this->syncPaymentEntry($record, $resolvedActorId);
        $this->rebuildSupplierBalances((int) $record->supplier_id, false);
    }

    public function postReturn(SupplierReturn $supplierReturn, ?int $actorId = null, bool $wrapTransaction = true): void
    {
        if ($wrapTransaction) {
            DB::transaction(function () use ($supplierReturn, $actorId): void {
                $this->postReturn($supplierReturn, $actorId, false);
            });

            return;
        }

        $record = SupplierReturn::query()->lockForUpdate()->find($supplierReturn->id);

        if (! $record) {
            return;
        }

        $resolvedActorId = $this->resolveActorId($actorId);

        $this->syncReturnEntry($record, $resolvedActorId);
        $this->rebuildSupplierBalances((int) $record->supplier_id, false);
    }

    public function postSupplierReturnPlaceholder(int $supplierId, ?int $actorId = null): void
    {
        // Backward compatibility shim for earlier placeholder call sites.
        $this->syncSupplierFromSource($supplierId, $actorId);
    }

    public function syncSupplierFromSource(int $supplierId, ?int $actorId = null, bool $wrapTransaction = true): void
    {
        if ($wrapTransaction) {
            DB::transaction(function () use ($supplierId, $actorId): void {
                $this->syncSupplierFromSource($supplierId, $actorId, false);
            });

            return;
        }

        SupplierBill::syncOverdueStatuses();

        $supplier = Supplier::query()->lockForUpdate()->find($supplierId);

        if (! $supplier) {
            return;
        }

        $resolvedActorId = $this->resolveActorId($actorId);

        $this->syncOpeningBalanceEntry($supplier, $resolvedActorId);
        $this->syncBillEntriesForSupplier($supplierId, $resolvedActorId);
        $this->syncPaymentEntriesForSupplier($supplierId, $resolvedActorId);
        $this->syncReturnEntriesForSupplier($supplierId, $resolvedActorId);
        $this->rebuildSupplierBalances($supplierId, false);
    }

    public function rebuildSupplierBalances(int $supplierId, bool $wrapTransaction = true): void
    {
        if ($wrapTransaction) {
            DB::transaction(function () use ($supplierId): void {
                $this->rebuildSupplierBalances($supplierId, false);
            });

            return;
        }

        $entries = SupplierLedgerModel::query()
            ->where('supplier_id', $supplierId)
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $running = 0.0;

        foreach ($entries as $entry) {
            $running = round($running + (float) $entry->credit - (float) $entry->debit, 2);

            if (round((float) $entry->balance, 2) === $running) {
                continue;
            }

            $entry->balance = $running;
            $entry->save();
        }
    }

    protected function syncBillEntriesForSupplier(int $supplierId, ?int $actorId): void
    {
        $billStatuses = $this->postableBillStatuses();

        $bills = SupplierBill::query()
            ->where('supplier_id', $supplierId)
            ->whereIn('status', $billStatuses)
            ->get();

        $billIds = $bills->pluck('id')->map(fn ($id): int => (int) $id)->all();

        $staleQuery = SupplierLedgerModel::query()
            ->where('supplier_id', $supplierId)
            ->where('transaction_type', SupplierLedgerTransactionType::BILL->value);

        if ($billIds !== []) {
            $staleQuery->whereNotIn('reference_id', $billIds);
        }

        $staleQuery->delete();

        foreach ($bills as $bill) {
            $this->syncBillEntry($bill, $actorId);
        }
    }

    protected function syncPaymentEntriesForSupplier(int $supplierId, ?int $actorId): void
    {
        $paymentStatuses = $this->postablePaymentStatuses();

        $payments = SupplierPayment::query()
            ->where('supplier_id', $supplierId)
            ->whereIn('status', $paymentStatuses)
            ->get();

        $paymentIds = $payments->pluck('id')->map(fn ($id): int => (int) $id)->all();

        $staleQuery = SupplierLedgerModel::query()
            ->where('supplier_id', $supplierId)
            ->where('transaction_type', SupplierLedgerTransactionType::PAYMENT->value);

        if ($paymentIds !== []) {
            $staleQuery->whereNotIn('reference_id', $paymentIds);
        }

        $staleQuery->delete();

        foreach ($payments as $payment) {
            $this->syncPaymentEntry($payment, $actorId);
        }
    }

    protected function syncReturnEntriesForSupplier(int $supplierId, ?int $actorId): void
    {
        $returnStatuses = $this->postableReturnStatuses();

        $returns = SupplierReturn::query()
            ->where('supplier_id', $supplierId)
            ->whereIn('status', $returnStatuses)
            ->get();

        $returnIds = $returns->pluck('id')->map(fn ($id): int => (int) $id)->all();

        $staleQuery = SupplierLedgerModel::query()
            ->where('supplier_id', $supplierId)
            ->where('transaction_type', SupplierLedgerTransactionType::RETURN_TXN->value);

        if ($returnIds !== []) {
            $staleQuery->whereNotIn('reference_id', $returnIds);
        }

        $staleQuery->delete();

        foreach ($returns as $supplierReturn) {
            $this->syncReturnEntry($supplierReturn, $actorId);
        }
    }

    protected function syncOpeningBalanceEntry(Supplier $supplier, ?int $actorId): void
    {
        $amount = round(max(0, (float) $supplier->opening_balance), 2);

        $debit = 0.0;
        $credit = 0.0;

        if ($amount > 0) {
            if ($supplier->opening_balance_type === Supplier::OPENING_BALANCE_TYPE_ADVANCE) {
                $debit = $amount;
            } else {
                $credit = $amount;
            }
        }

        $openingDate = optional($supplier->created_at)->toDateString() ?: now()->toDateString();
        $description = $supplier->opening_balance_type === Supplier::OPENING_BALANCE_TYPE_ADVANCE
            ? 'Supplier opening advance balance'
            : 'Supplier opening payable balance';

        $this->upsertSystemEntry(
            supplierId: (int) $supplier->id,
            transactionType: SupplierLedgerTransactionType::OPENING_BALANCE,
            referenceType: self::REFERENCE_TYPE_SUPPLIER,
            referenceId: (int) $supplier->id,
            referenceNo: $supplier->code,
            description: $description,
            transactionDate: $openingDate,
            debit: $debit,
            credit: $credit,
            status: 'posted',
            shouldExist: $amount > 0,
            actorId: $actorId
        );
    }

    protected function syncBillEntry(SupplierBill $bill, ?int $actorId): void
    {
        $statusValue = $bill->status?->value ?? (string) $bill->status;
        $totalAmount = round(max(0, (float) $bill->total_amount), 2);

        $shouldExist = in_array($statusValue, $this->postableBillStatuses(), true)
            && $totalAmount > 0;

        $this->upsertSystemEntry(
            supplierId: (int) $bill->supplier_id,
            transactionType: SupplierLedgerTransactionType::BILL,
            referenceType: self::REFERENCE_TYPE_BILL,
            referenceId: (int) $bill->id,
            referenceNo: $bill->bill_no,
            description: 'Supplier bill posted',
            transactionDate: optional($bill->bill_date)->toDateString() ?: now()->toDateString(),
            debit: 0,
            credit: $totalAmount,
            status: $statusValue ?: 'posted',
            shouldExist: $shouldExist,
            actorId: $actorId
        );
    }

    protected function syncPaymentEntry(SupplierPayment $payment, ?int $actorId): void
    {
        $statusValue = $payment->status?->value ?? (string) $payment->status;
        $totalAmount = round(max(0, (float) $payment->total_amount), 2);

        $shouldExist = in_array($statusValue, $this->postablePaymentStatuses(), true)
            && $totalAmount > 0;

        $this->upsertSystemEntry(
            supplierId: (int) $payment->supplier_id,
            transactionType: SupplierLedgerTransactionType::PAYMENT,
            referenceType: self::REFERENCE_TYPE_PAYMENT,
            referenceId: (int) $payment->id,
            referenceNo: $payment->payment_no,
            description: 'Supplier payment posted',
            transactionDate: optional($payment->payment_date)->toDateString() ?: now()->toDateString(),
            debit: $totalAmount,
            credit: 0,
            status: $statusValue ?: 'posted',
            shouldExist: $shouldExist,
            actorId: $actorId
        );
    }

    protected function syncReturnEntry(SupplierReturn $supplierReturn, ?int $actorId): void
    {
        $statusValue = $supplierReturn->status?->value ?? (string) $supplierReturn->status;
        $totalAmount = round(max(0, (float) $supplierReturn->total_amount), 2);

        $shouldExist = in_array($statusValue, $this->postableReturnStatuses(), true)
            && $totalAmount > 0;

        $this->upsertSystemEntry(
            supplierId: (int) $supplierReturn->supplier_id,
            transactionType: SupplierLedgerTransactionType::RETURN_TXN,
            referenceType: self::REFERENCE_TYPE_RETURN,
            referenceId: (int) $supplierReturn->id,
            referenceNo: $supplierReturn->return_no,
            description: 'Supplier return approved',
            transactionDate: optional($supplierReturn->return_date)->toDateString() ?: now()->toDateString(),
            debit: $totalAmount,
            credit: 0,
            status: $statusValue ?: 'posted',
            shouldExist: $shouldExist,
            actorId: $actorId
        );
    }

    protected function upsertSystemEntry(
        int $supplierId,
        SupplierLedgerTransactionType $transactionType,
        ?string $referenceType,
        ?int $referenceId,
        ?string $referenceNo,
        ?string $description,
        string $transactionDate,
        float $debit,
        float $credit,
        ?string $status,
        bool $shouldExist,
        ?int $actorId
    ): void {
        $query = SupplierLedgerModel::query()
            ->where('supplier_id', $supplierId)
            ->where('transaction_type', $transactionType->value)
            ->where('reference_type', $referenceType);

        if ($referenceId === null) {
            $query->whereNull('reference_id');
        } else {
            $query->where('reference_id', $referenceId);
        }

        $entry = $query->lockForUpdate()->first();

        if (! $shouldExist) {
            if ($entry) {
                $entry->delete();
            }

            return;
        }

        $payload = [
            'supplier_id' => $supplierId,
            'transaction_date' => $transactionDate,
            'transaction_type' => $transactionType->value,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'reference_no' => $referenceNo,
            'description' => $description,
            'debit' => round(max(0, $debit), 2),
            'credit' => round(max(0, $credit), 2),
            'status' => $status,
        ];

        if ($entry) {
            $entry->fill($payload);
            $entry->save();

            return;
        }

        SupplierLedgerModel::query()->create([
            ...$payload,
            'created_by' => $actorId,
            'balance' => 0,
        ]);
    }

    /**
     * @return string[]
     */
    protected function postableBillStatuses(): array
    {
        return [
            SupplierBillStatus::OPEN->value,
            SupplierBillStatus::PARTIAL->value,
            SupplierBillStatus::PAID->value,
            SupplierBillStatus::OVERDUE->value,
        ];
    }

    /**
     * @return string[]
     */
    protected function postablePaymentStatuses(): array
    {
        return [
            SupplierPaymentStatus::POSTED->value,
            SupplierPaymentStatus::PARTIAL_ALLOCATED->value,
            SupplierPaymentStatus::FULLY_ALLOCATED->value,
        ];
    }

    /**
     * @return string[]
     */
    protected function postableReturnStatuses(): array
    {
        return [
            SupplierReturnStatus::APPROVED->value,
        ];
    }

    protected function resolveActorId(?int $actorId = null): ?int
    {
        $resolved = $actorId ?? auth()->id();

        return $resolved ? (int) $resolved : null;
    }
}
