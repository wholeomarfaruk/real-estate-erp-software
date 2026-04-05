<?php

namespace App\Services\Supplier;

use App\Enums\Supplier\SupplierBillStatus;
use App\Models\SupplierBill;
use App\Models\SupplierLedger;
use App\Models\SupplierPayment;
use Illuminate\Database\Eloquent\Builder;

class SupplierStatementService
{
    /**
     * @return array{opening_balance:float,total_debit:float,total_credit:float,closing_balance:float}
     */
    public function buildSummary(?int $supplierId = null, ?string $fromDate = null, ?string $toDate = null, ?string $transactionType = null): array
    {
        $baseQuery = SupplierLedger::query()->forSupplier($supplierId);

        $openingBalance = 0.0;

        if ($fromDate) {
            $openingCredits = (float) (clone $baseQuery)
                ->whereDate('transaction_date', '<', $fromDate)
                ->sum('credit');

            $openingDebits = (float) (clone $baseQuery)
                ->whereDate('transaction_date', '<', $fromDate)
                ->sum('debit');

            $openingBalance = round($openingCredits - $openingDebits, 2);
        }

        $periodQuery = $this->transactionQuery(
            supplierId: $supplierId,
            fromDate: $fromDate,
            toDate: $toDate,
            transactionType: $transactionType
        );

        $totalDebit = round((float) (clone $periodQuery)->sum('debit'), 2);
        $totalCredit = round((float) (clone $periodQuery)->sum('credit'), 2);
        $closingBalance = round($openingBalance + $totalCredit - $totalDebit, 2);

        return [
            'opening_balance' => $openingBalance,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'closing_balance' => $closingBalance,
        ];
    }

    public function transactionQuery(?int $supplierId = null, ?string $fromDate = null, ?string $toDate = null, ?string $transactionType = null): Builder
    {
        return SupplierLedger::query()
            ->with(['supplier:id,name,code'])
            ->forSupplier($supplierId)
            ->when($fromDate, fn (Builder $builder): Builder => $builder->whereDate('transaction_date', '>=', $fromDate))
            ->when($toDate, fn (Builder $builder): Builder => $builder->whereDate('transaction_date', '<=', $toDate))
            ->when($transactionType, fn (Builder $builder): Builder => $builder->where('transaction_type', $transactionType));
    }

    /**
     * @return array{pending_count:int,pending_amount:float,overdue_count:int,overdue_amount:float}
     */
    public function pendingBillsSummary(int $supplierId): array
    {
        $query = SupplierBill::query()
            ->pending()
            ->where('supplier_id', $supplierId);

        $pendingCount = (clone $query)->count();
        $pendingAmount = (float) (clone $query)->sum('due_amount');
        $overdueCount = (clone $query)->where('status', SupplierBillStatus::OVERDUE->value)->count();
        $overdueAmount = (float) (clone $query)->where('status', SupplierBillStatus::OVERDUE->value)->sum('due_amount');

        return [
            'pending_count' => (int) $pendingCount,
            'pending_amount' => round($pendingAmount, 2),
            'overdue_count' => (int) $overdueCount,
            'overdue_amount' => round($overdueAmount, 2),
        ];
    }

    /**
     * @return array{unallocated_count:int,unallocated_amount:float}
     */
    public function unallocatedPaymentSummary(int $supplierId, ?string $toDate = null): array
    {
        $query = SupplierPayment::query()
            ->active()
            ->where('supplier_id', $supplierId)
            ->where('unallocated_amount', '>', 0)
            ->when($toDate, fn (Builder $builder): Builder => $builder->whereDate('payment_date', '<=', $toDate));

        return [
            'unallocated_count' => (int) (clone $query)->count(),
            'unallocated_amount' => round((float) (clone $query)->sum('unallocated_amount'), 2),
        ];
    }
}
