<?php

namespace App\Services\Supplier;

use App\Enums\Supplier\SupplierBillStatus;
use App\Enums\Supplier\SupplierPaymentStatus;
use App\Enums\Supplier\SupplierReturnStatus;
use App\Models\Supplier;
use App\Models\SupplierBill;
use App\Models\SupplierBillItem;
use App\Models\SupplierLedger;
use App\Models\SupplierPayment;
use App\Models\SupplierReturn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SupplierReportService
{
    /**
     * @param  array{supplier_id?:int|null,from_date?:string|null,to_date?:string|null,status?:string|null,due_only?:bool|null}  $filters
     */
    public function supplierWiseQuery(array $filters = []): Builder
    {
        $supplierId = $filters['supplier_id'] ?? null;
        $fromDate = $filters['from_date'] ?? null;
        $toDate = $filters['to_date'] ?? null;
        $status = (string) ($filters['status'] ?? '');
        $dueOnly = (bool) ($filters['due_only'] ?? false);

        $billAgg = SupplierBill::query()
            ->selectRaw('supplier_id')
            ->selectRaw('COUNT(*) as total_bills')
            ->selectRaw('COALESCE(SUM(total_amount), 0) as total_billed_amount')
            ->selectRaw('COALESCE(SUM(paid_amount), 0) as total_paid_amount')
            ->selectRaw('COALESCE(SUM(due_amount), 0) as total_due_amount')
            ->selectRaw('MAX(bill_date) as last_bill_date')
            ->whereIn('status', $this->postedBillStatuses())
            ->when($fromDate, fn (Builder $builder): Builder => $builder->whereDate('bill_date', '>=', $fromDate))
            ->when($toDate, fn (Builder $builder): Builder => $builder->whereDate('bill_date', '<=', $toDate))
            ->groupBy('supplier_id');

        $paymentAgg = SupplierPayment::query()
            ->selectRaw('supplier_id')
            ->selectRaw('COALESCE(SUM(unallocated_amount), 0) as total_advance_amount')
            ->selectRaw('MAX(payment_date) as last_payment_date')
            ->whereIn('status', $this->activePaymentStatuses())
            ->when($fromDate, fn (Builder $builder): Builder => $builder->whereDate('payment_date', '>=', $fromDate))
            ->when($toDate, fn (Builder $builder): Builder => $builder->whereDate('payment_date', '<=', $toDate))
            ->groupBy('supplier_id');

        $returnAgg = SupplierReturn::query()
            ->selectRaw('supplier_id')
            ->selectRaw('COALESCE(SUM(total_amount), 0) as total_return_amount')
            ->where('status', SupplierReturnStatus::APPROVED->value)
            ->when($fromDate, fn (Builder $builder): Builder => $builder->whereDate('return_date', '>=', $fromDate))
            ->when($toDate, fn (Builder $builder): Builder => $builder->whereDate('return_date', '<=', $toDate))
            ->groupBy('supplier_id');

        $ledgerTxnAgg = SupplierLedger::query()
            ->selectRaw('supplier_id')
            ->selectRaw('COUNT(*) as total_transactions')
            ->when($fromDate, fn (Builder $builder): Builder => $builder->whereDate('transaction_date', '>=', $fromDate))
            ->when($toDate, fn (Builder $builder): Builder => $builder->whereDate('transaction_date', '<=', $toDate))
            ->groupBy('supplier_id');

        $query = Supplier::query()
            ->select('suppliers.id', 'suppliers.code', 'suppliers.name', 'suppliers.status', 'suppliers.is_blocked')
            ->leftJoinSub($billAgg, 'bill_agg', function ($join): void {
                $join->on('bill_agg.supplier_id', '=', 'suppliers.id');
            })
            ->leftJoinSub($paymentAgg, 'payment_agg', function ($join): void {
                $join->on('payment_agg.supplier_id', '=', 'suppliers.id');
            })
            ->leftJoinSub($returnAgg, 'return_agg', function ($join): void {
                $join->on('return_agg.supplier_id', '=', 'suppliers.id');
            })
            ->leftJoinSub($ledgerTxnAgg, 'ledger_agg', function ($join): void {
                $join->on('ledger_agg.supplier_id', '=', 'suppliers.id');
            })
            ->selectRaw('COALESCE(bill_agg.total_bills, 0) as total_bills')
            ->selectRaw('COALESCE(bill_agg.total_billed_amount, 0) as total_billed_amount')
            ->selectRaw('COALESCE(bill_agg.total_paid_amount, 0) as total_paid_amount')
            ->selectRaw(
                'CASE
                    WHEN COALESCE(bill_agg.total_due_amount, 0) - COALESCE(return_agg.total_return_amount, 0) > 0
                    THEN COALESCE(bill_agg.total_due_amount, 0) - COALESCE(return_agg.total_return_amount, 0)
                    ELSE 0
                END as total_due_amount'
            )
            ->selectRaw(
                'COALESCE(payment_agg.total_advance_amount, 0)
                 + CASE
                    WHEN COALESCE(return_agg.total_return_amount, 0) - COALESCE(bill_agg.total_due_amount, 0) > 0
                    THEN COALESCE(return_agg.total_return_amount, 0) - COALESCE(bill_agg.total_due_amount, 0)
                    ELSE 0
                 END as total_advance_amount'
            )
            ->selectRaw('COALESCE(return_agg.total_return_amount, 0) as total_return_amount')
            ->selectRaw('COALESCE(ledger_agg.total_transactions, 0) as total_transactions')
            ->selectRaw('bill_agg.last_bill_date as last_bill_date')
            ->selectRaw('payment_agg.last_payment_date as last_payment_date')
            ->selectSub(
                SupplierLedger::query()
                    ->select('balance')
                    ->whereColumn('supplier_ledgers.supplier_id', 'suppliers.id')
                    ->when($toDate, fn (Builder $builder): Builder => $builder->whereDate('transaction_date', '<=', $toDate))
                    ->orderByDesc('transaction_date')
                    ->orderByDesc('id')
                    ->limit(1),
                'current_ledger_balance'
            )
            ->when($supplierId, fn (Builder $builder): Builder => $builder->where('suppliers.id', $supplierId))
            ->when($status !== '', fn (Builder $builder): Builder => $this->applySupplierStatusFilter($builder, $status))
            ->when($dueOnly, fn (Builder $builder): Builder => $builder->whereRaw('CASE WHEN COALESCE(bill_agg.total_due_amount, 0) - COALESCE(return_agg.total_return_amount, 0) > 0 THEN COALESCE(bill_agg.total_due_amount, 0) - COALESCE(return_agg.total_return_amount, 0) ELSE 0 END > 0'));

        return $query;
    }

    /**
     * @param  array{supplier_id?:int|null,from_date?:string|null,to_date?:string|null,status?:string|null,due_only?:bool|null}  $filters
     * @return array{total_billed:float,total_paid:float,total_due:float,suppliers_count:int}
     */
    public function supplierWiseSummary(array $filters = []): array
    {
        $sub = $this->queryToSub($this->supplierWiseQuery($filters));

        return [
            'total_billed' => round((float) (clone $sub)->sum('total_billed_amount'), 2),
            'total_paid' => round((float) (clone $sub)->sum('total_paid_amount'), 2),
            'total_due' => round((float) (clone $sub)->sum('total_due_amount'), 2),
            'suppliers_count' => (int) (clone $sub)->count(),
        ];
    }

    /**
     * @param  array{product_id?:int|null,supplier_id?:int|null,from_date?:string|null,to_date?:string|null}  $filters
     */
    public function productWiseSupplierQuery(array $filters = []): Builder
    {
        $productId = $filters['product_id'] ?? null;
        $supplierId = $filters['supplier_id'] ?? null;
        $fromDate = $filters['from_date'] ?? null;
        $toDate = $filters['to_date'] ?? null;

        $query = SupplierBillItem::query()
            ->from('supplier_bill_items as sbi')
            ->join('supplier_bills as sb', 'sb.id', '=', 'sbi.supplier_bill_id')
            ->join('products as p', 'p.id', '=', 'sbi.product_id')
            ->join('suppliers as s', 's.id', '=', 'sb.supplier_id')
            ->whereNotNull('sbi.product_id')
            ->whereIn('sb.status', $this->postedBillStatuses())
            ->when($productId, fn (Builder $builder): Builder => $builder->where('sbi.product_id', $productId))
            ->when($supplierId, fn (Builder $builder): Builder => $builder->where('sb.supplier_id', $supplierId))
            ->when($fromDate, fn (Builder $builder): Builder => $builder->whereDate('sb.bill_date', '>=', $fromDate))
            ->when($toDate, fn (Builder $builder): Builder => $builder->whereDate('sb.bill_date', '<=', $toDate))
            ->selectRaw('sbi.product_id')
            ->selectRaw('sb.supplier_id')
            ->selectRaw('MAX(p.name) as product_name')
            ->selectRaw('MAX(s.name) as supplier_name')
            ->selectRaw('COALESCE(SUM(sbi.qty), 0) as total_billed_qty')
            ->selectRaw('CASE WHEN SUM(sbi.qty) > 0 THEN SUM(sbi.line_total) / SUM(sbi.qty) ELSE 0 END as average_rate')
            ->selectRaw('COALESCE(MIN(sbi.rate), 0) as min_rate')
            ->selectRaw('COALESCE(MAX(sbi.rate), 0) as max_rate')
            ->selectRaw('COALESCE(SUM(sbi.line_total), 0) as total_purchase_amount')
            ->selectRaw('MAX(sb.bill_date) as last_purchase_date')
            ->selectSub(
                SupplierBillItem::query()
                    ->from('supplier_bill_items as lsbi')
                    ->join('supplier_bills as lsb', 'lsb.id', '=', 'lsbi.supplier_bill_id')
                    ->select('lsbi.rate')
                    ->whereColumn('lsbi.product_id', 'sbi.product_id')
                    ->whereColumn('lsb.supplier_id', 'sb.supplier_id')
                    ->whereIn('lsb.status', $this->postedBillStatuses())
                    ->when($fromDate, fn (Builder $builder): Builder => $builder->whereDate('lsb.bill_date', '>=', $fromDate))
                    ->when($toDate, fn (Builder $builder): Builder => $builder->whereDate('lsb.bill_date', '<=', $toDate))
                    ->orderByDesc('lsb.bill_date')
                    ->orderByDesc('lsb.id')
                    ->orderByDesc('lsbi.id')
                    ->limit(1),
                'last_rate'
            )
            ->groupBy('sbi.product_id', 'sb.supplier_id');

        return $query;
    }

    /**
     * @param  array{product_id?:int|null,supplier_id?:int|null,from_date?:string|null,to_date?:string|null}  $filters
     * @return array{total_products:int,total_suppliers:int,total_qty:float,total_purchase_value:float}
     */
    public function productWiseSupplierSummary(array $filters = []): array
    {
        $sub = $this->queryToSub($this->productWiseSupplierQuery($filters));

        $distinct = (clone $sub)
            ->selectRaw('COUNT(DISTINCT product_id) as total_products')
            ->selectRaw('COUNT(DISTINCT supplier_id) as total_suppliers')
            ->first();

        return [
            'total_products' => (int) ($distinct->total_products ?? 0),
            'total_suppliers' => (int) ($distinct->total_suppliers ?? 0),
            'total_qty' => round((float) (clone $sub)->sum('total_billed_qty'), 3),
            'total_purchase_value' => round((float) (clone $sub)->sum('total_purchase_amount'), 2),
        ];
    }

    /**
     * @param  array{supplier_id?:int|null,due_only?:bool|null,overdue_only?:bool|null,from_date?:string|null,to_date?:string|null}  $filters
     */
    public function supplierDueQuery(array $filters = []): Builder
    {
        $supplierId = $filters['supplier_id'] ?? null;
        $dueOnly = (bool) ($filters['due_only'] ?? false);
        $overdueOnly = (bool) ($filters['overdue_only'] ?? false);
        $fromDate = $filters['from_date'] ?? null;
        $toDate = $filters['to_date'] ?? null;
        $asOnDate = $toDate ?: now()->toDateString();

        $billAgg = SupplierBill::query()
            ->selectRaw('supplier_id')
            ->selectRaw('COALESCE(SUM(total_amount), 0) as total_bill_amount')
            ->selectRaw('COALESCE(SUM(paid_amount), 0) as total_paid_amount')
            ->selectRaw('COALESCE(SUM(due_amount), 0) as total_due_amount')
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN due_amount > 0 AND due_date IS NOT NULL AND due_date < ? THEN due_amount ELSE 0 END), 0) as overdue_amount',
                [$asOnDate]
            )
            ->whereIn('status', $this->postedBillStatuses())
            ->when($fromDate, fn (Builder $builder): Builder => $builder->whereDate('bill_date', '>=', $fromDate))
            ->when($toDate, fn (Builder $builder): Builder => $builder->whereDate('bill_date', '<=', $toDate))
            ->groupBy('supplier_id');

        $returnAgg = SupplierReturn::query()
            ->selectRaw('supplier_id')
            ->selectRaw('COALESCE(SUM(total_amount), 0) as approved_return_amount')
            ->where('status', SupplierReturnStatus::APPROVED->value)
            ->when($fromDate, fn (Builder $builder): Builder => $builder->whereDate('return_date', '>=', $fromDate))
            ->when($toDate, fn (Builder $builder): Builder => $builder->whereDate('return_date', '<=', $toDate))
            ->groupBy('supplier_id');

        $advanceAgg = SupplierPayment::query()
            ->selectRaw('supplier_id')
            ->selectRaw('COALESCE(SUM(unallocated_amount), 0) as unapplied_advance')
            ->whereIn('status', $this->activePaymentStatuses())
            ->when($fromDate, fn (Builder $builder): Builder => $builder->whereDate('payment_date', '>=', $fromDate))
            ->when($toDate, fn (Builder $builder): Builder => $builder->whereDate('payment_date', '<=', $toDate))
            ->groupBy('supplier_id');

        $query = Supplier::query()
            ->select('suppliers.id', 'suppliers.code', 'suppliers.name')
            ->leftJoinSub($billAgg, 'bill_agg', function ($join): void {
                $join->on('bill_agg.supplier_id', '=', 'suppliers.id');
            })
            ->leftJoinSub($returnAgg, 'return_agg', function ($join): void {
                $join->on('return_agg.supplier_id', '=', 'suppliers.id');
            })
            ->leftJoinSub($advanceAgg, 'advance_agg', function ($join): void {
                $join->on('advance_agg.supplier_id', '=', 'suppliers.id');
            })
            ->selectRaw('COALESCE(bill_agg.total_bill_amount, 0) as total_bill_amount')
            ->selectRaw('COALESCE(bill_agg.total_paid_amount, 0) as total_paid_amount')
            ->selectRaw(
                'CASE
                    WHEN COALESCE(bill_agg.total_due_amount, 0) - COALESCE(return_agg.approved_return_amount, 0) > 0
                    THEN COALESCE(bill_agg.total_due_amount, 0) - COALESCE(return_agg.approved_return_amount, 0)
                    ELSE 0
                END as total_due_amount'
            )
            ->selectRaw(
                'CASE
                    WHEN COALESCE(bill_agg.overdue_amount, 0) - COALESCE(return_agg.approved_return_amount, 0) > 0
                    THEN COALESCE(bill_agg.overdue_amount, 0) - COALESCE(return_agg.approved_return_amount, 0)
                    ELSE 0
                END as overdue_amount'
            )
            ->selectRaw('COALESCE(return_agg.approved_return_amount, 0) as approved_return_amount')
            ->selectRaw('COALESCE(advance_agg.unapplied_advance, 0) as unapplied_advance')
            ->selectRaw(
                'CASE
                    WHEN COALESCE(bill_agg.total_due_amount, 0) - COALESCE(return_agg.approved_return_amount, 0) > 0
                    THEN COALESCE(bill_agg.total_due_amount, 0) - COALESCE(return_agg.approved_return_amount, 0)
                    ELSE 0
                END - COALESCE(advance_agg.unapplied_advance, 0) as net_payable'
            )
            ->when($supplierId, fn (Builder $builder): Builder => $builder->where('suppliers.id', $supplierId))
            ->when($dueOnly, fn (Builder $builder): Builder => $builder->whereRaw('CASE WHEN COALESCE(bill_agg.total_due_amount, 0) - COALESCE(return_agg.approved_return_amount, 0) > 0 THEN COALESCE(bill_agg.total_due_amount, 0) - COALESCE(return_agg.approved_return_amount, 0) ELSE 0 END > 0'))
            ->when($overdueOnly, fn (Builder $builder): Builder => $builder->whereRaw('COALESCE(bill_agg.overdue_amount, 0) > 0'));

        return $query;
    }

    /**
     * @param  array{supplier_id?:int|null,due_only?:bool|null,overdue_only?:bool|null,from_date?:string|null,to_date?:string|null}  $filters
     * @return array{total_payable:float,total_overdue:float,suppliers_with_due:int,suppliers_with_advance:int}
     */
    public function supplierDueSummary(array $filters = []): array
    {
        $sub = $this->queryToSub($this->supplierDueQuery($filters));

        $row = (clone $sub)->selectRaw(
            'COALESCE(SUM(CASE WHEN net_payable > 0 THEN net_payable ELSE 0 END), 0) as total_payable,
             COALESCE(SUM(overdue_amount), 0) as total_overdue,
             COALESCE(SUM(CASE WHEN total_due_amount > 0 THEN 1 ELSE 0 END), 0) as suppliers_with_due,
             COALESCE(SUM(CASE WHEN net_payable < 0 THEN 1 ELSE 0 END), 0) as suppliers_with_advance'
        )->first();

        return [
            'total_payable' => round((float) ($row->total_payable ?? 0), 2),
            'total_overdue' => round((float) ($row->total_overdue ?? 0), 2),
            'suppliers_with_due' => (int) ($row->suppliers_with_due ?? 0),
            'suppliers_with_advance' => (int) ($row->suppliers_with_advance ?? 0),
        ];
    }

    /**
     * @param  array{supplier_id?:int|null,as_on_date?:string|null}  $filters
     */
    public function supplierAgingQuery(array $filters = []): Builder
    {
        $supplierId = $filters['supplier_id'] ?? null;
        $asOnDateInput = $filters['as_on_date'] ?? null;
        $asOnDate = $asOnDateInput ?: now()->toDateString();
        $asOn = Carbon::parse($asOnDate)->toDateString();
        $minus30 = Carbon::parse($asOn)->subDays(30)->toDateString();
        $minus60 = Carbon::parse($asOn)->subDays(60)->toDateString();
        $minus90 = Carbon::parse($asOn)->subDays(90)->toDateString();

        $agingAgg = SupplierBill::query()
            ->selectRaw('supplier_id')
            ->selectRaw('COALESCE(SUM(CASE WHEN due_date IS NULL OR due_date >= ? THEN due_amount ELSE 0 END), 0) as current_due', [$asOn])
            ->selectRaw('COALESCE(SUM(CASE WHEN due_date < ? AND due_date >= ? THEN due_amount ELSE 0 END), 0) as bucket_1_30', [$asOn, $minus30])
            ->selectRaw('COALESCE(SUM(CASE WHEN due_date < ? AND due_date >= ? THEN due_amount ELSE 0 END), 0) as bucket_31_60', [$minus30, $minus60])
            ->selectRaw('COALESCE(SUM(CASE WHEN due_date < ? AND due_date >= ? THEN due_amount ELSE 0 END), 0) as bucket_61_90', [$minus60, $minus90])
            ->selectRaw('COALESCE(SUM(CASE WHEN due_date < ? THEN due_amount ELSE 0 END), 0) as bucket_90_plus', [$minus90])
            ->selectRaw('COALESCE(SUM(due_amount), 0) as total_due')
            ->whereIn('status', $this->pendingBillStatuses())
            ->where('due_amount', '>', 0)
            ->whereDate('bill_date', '<=', $asOn)
            ->groupBy('supplier_id');

        $query = Supplier::query()
            ->select('suppliers.id', 'suppliers.code', 'suppliers.name')
            ->leftJoinSub($agingAgg, 'aging_agg', function ($join): void {
                $join->on('aging_agg.supplier_id', '=', 'suppliers.id');
            })
            ->selectRaw('COALESCE(aging_agg.current_due, 0) as current_due')
            ->selectRaw('COALESCE(aging_agg.bucket_1_30, 0) as bucket_1_30')
            ->selectRaw('COALESCE(aging_agg.bucket_31_60, 0) as bucket_31_60')
            ->selectRaw('COALESCE(aging_agg.bucket_61_90, 0) as bucket_61_90')
            ->selectRaw('COALESCE(aging_agg.bucket_90_plus, 0) as bucket_90_plus')
            ->selectRaw('COALESCE(aging_agg.total_due, 0) as total_due')
            ->when($supplierId, fn (Builder $builder): Builder => $builder->where('suppliers.id', $supplierId))
            ->whereRaw('COALESCE(aging_agg.total_due, 0) > 0');

        return $query;
    }

    /**
     * @param  array{supplier_id?:int|null,as_on_date?:string|null}  $filters
     * @return array{total_current:float,total_overdue:float,due_90_plus_total:float,supplier_count:int}
     */
    public function supplierAgingSummary(array $filters = []): array
    {
        $sub = $this->queryToSub($this->supplierAgingQuery($filters));

        $row = (clone $sub)->selectRaw(
            'COALESCE(SUM(current_due), 0) as total_current,
             COALESCE(SUM(bucket_1_30 + bucket_31_60 + bucket_61_90 + bucket_90_plus), 0) as total_overdue,
             COALESCE(SUM(bucket_90_plus), 0) as due_90_plus_total,
             COUNT(*) as supplier_count'
        )->first();

        return [
            'total_current' => round((float) ($row->total_current ?? 0), 2),
            'total_overdue' => round((float) ($row->total_overdue ?? 0), 2),
            'due_90_plus_total' => round((float) ($row->due_90_plus_total ?? 0), 2),
            'supplier_count' => (int) ($row->supplier_count ?? 0),
        ];
    }

    protected function queryToSub(Builder $query, string $alias = 'rows'): QueryBuilder
    {
        return DB::query()->fromSub($query, $alias);
    }

    protected function applySupplierStatusFilter(Builder $query, string $status): Builder
    {
        return match ($status) {
            'active' => $query->where('suppliers.status', true)->where('suppliers.is_blocked', false),
            'inactive' => $query->where('suppliers.status', false)->where('suppliers.is_blocked', false),
            'blocked' => $query->where('suppliers.is_blocked', true),
            default => $query,
        };
    }

    /**
     * @return string[]
     */
    protected function postedBillStatuses(): array
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
    protected function pendingBillStatuses(): array
    {
        return [
            SupplierBillStatus::OPEN->value,
            SupplierBillStatus::PARTIAL->value,
            SupplierBillStatus::OVERDUE->value,
        ];
    }

    /**
     * @return string[]
     */
    protected function activePaymentStatuses(): array
    {
        return [
            SupplierPaymentStatus::POSTED->value,
            SupplierPaymentStatus::PARTIAL_ALLOCATED->value,
            SupplierPaymentStatus::FULLY_ALLOCATED->value,
        ];
    }
}
