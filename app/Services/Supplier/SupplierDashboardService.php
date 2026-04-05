<?php

namespace App\Services\Supplier;

use App\Enums\Supplier\SupplierBillStatus;
use App\Enums\Supplier\SupplierPaymentStatus;
use App\Enums\Supplier\SupplierReturnStatus;
use App\Models\Supplier;
use App\Models\SupplierBill;
use App\Models\SupplierLedger;
use App\Models\SupplierPayment;
use App\Models\SupplierReturn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SupplierDashboardService
{
    /**
     * @return array{
     *  total_suppliers:int,
     *  active_suppliers:int,
     *  blocked_suppliers:int,
     *  total_payable:float,
     *  overdue_payable:float,
     *  unapplied_advance:float,
     *  this_month_billed:float,
     *  this_month_paid:float,
     *  this_month_return:float
     * }
     */
    public function summaryCards(): array
    {
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();

        $dueSummary = app(SupplierReportService::class)->supplierDueSummary();
        $balanceSnapshot = $this->payableBalanceSnapshot();

        $unallocatedAdvance = (float) SupplierPayment::query()
            ->whereIn('status', $this->activePaymentStatuses())
            ->sum('unallocated_amount');

        return [
            'total_suppliers' => (int) Supplier::query()->count(),
            'active_suppliers' => (int) Supplier::query()->active()->count(),
            'blocked_suppliers' => (int) Supplier::query()->blocked()->count(),
            'total_payable' => round(max((float) ($dueSummary['total_payable'] ?? 0), (float) ($balanceSnapshot['total_payable'] ?? 0)), 2),
            'overdue_payable' => round((float) ($dueSummary['total_overdue'] ?? 0), 2),
            'unapplied_advance' => round(max($unallocatedAdvance, (float) ($balanceSnapshot['total_advance'] ?? 0)), 2),
            'this_month_billed' => round((float) SupplierBill::query()
                ->whereIn('status', $this->postedBillStatuses())
                ->whereBetween('bill_date', [$monthStart, $monthEnd])
                ->sum('total_amount'), 2),
            'this_month_paid' => round((float) SupplierPayment::query()
                ->whereIn('status', $this->activePaymentStatuses())
                ->whereBetween('payment_date', [$monthStart, $monthEnd])
                ->sum('total_amount'), 2),
            'this_month_return' => round((float) SupplierReturn::query()
                ->where('status', SupplierReturnStatus::APPROVED->value)
                ->whereBetween('return_date', [$monthStart, $monthEnd])
                ->sum('total_amount'), 2),
        ];
    }

    /**
     * @return array{total_payable:float,total_advance:float}
     */
    protected function payableBalanceSnapshot(): array
    {
        $row = DB::query()
            ->fromSub($this->supplierBalanceQuery(), 'balance_rows')
            ->selectRaw('COALESCE(SUM(CASE WHEN COALESCE(current_balance, 0) > 0 THEN current_balance ELSE 0 END), 0) as total_payable')
            ->selectRaw('COALESCE(SUM(CASE WHEN COALESCE(current_balance, 0) < 0 THEN ABS(current_balance) ELSE 0 END), 0) as total_advance')
            ->first();

        return [
            'total_payable' => round((float) ($row->total_payable ?? 0), 2),
            'total_advance' => round((float) ($row->total_advance ?? 0), 2),
        ];
    }

    /**
     * @return array<int, array{
     *   key:string,
     *   title:string,
     *   count:int,
     *   amount:float,
     *   tone:string,
     *   meta:?string,
     *   route:?string
     * }>
     */
    public function alertWidgets(): array
    {
        $dueSummary = app(SupplierReportService::class)->supplierDueSummary();
        $overdueBillsCount = (int) SupplierBill::query()
            ->where('status', SupplierBillStatus::OVERDUE->value)
            ->where('due_amount', '>', 0)
            ->count();

        $overCredit = $this->overCreditLimitMetrics();
        $noRecentPurchase = $this->noRecentPurchaseMetrics();
        $highReturn = $this->highReturnMetrics();
        $draftReturnIssues = $this->draftReturnIssueMetrics();
        $negativeBalance = $this->negativeBalanceMetrics();

        return [
            [
                'key' => 'overdue_bills',
                'title' => 'Overdue Bills',
                'count' => $overdueBillsCount,
                'amount' => round((float) ($dueSummary['total_overdue'] ?? 0), 2),
                'tone' => 'rose',
                'meta' => 'Requires immediate follow-up',
                'route' => 'admin.supplier.bills.pending',
            ],
            [
                'key' => 'credit_limit',
                'title' => 'Suppliers Over Credit Limit',
                'count' => (int) ($overCredit['suppliers_count'] ?? 0),
                'amount' => round((float) ($overCredit['excess_amount'] ?? 0), 2),
                'tone' => 'amber',
                'meta' => 'Payable above configured limit',
                'route' => 'admin.supplier.reports.due',
            ],
            [
                'key' => 'no_recent_purchase',
                'title' => 'No Recent Purchase',
                'count' => (int) ($noRecentPurchase['suppliers_count'] ?? 0),
                'amount' => 0,
                'tone' => 'zinc',
                'meta' => 'No posted bill in last 90 days',
                'route' => 'admin.supplier.reports.supplier-wise',
            ],
            [
                'key' => 'high_return',
                'title' => 'High Return Suppliers',
                'count' => (int) ($highReturn['suppliers_count'] ?? 0),
                'amount' => round((float) ($highReturn['return_amount'] ?? 0), 2),
                'tone' => 'indigo',
                'meta' => 'Last 90 days return ratio is high',
                'route' => 'admin.supplier.returns.index',
            ],
            [
                'key' => 'return_draft_issue',
                'title' => 'Draft Return Queue',
                'count' => (int) ($draftReturnIssues['draft_count'] ?? 0),
                'amount' => round((float) ($draftReturnIssues['stale_count'] ?? 0), 2),
                'tone' => 'blue',
                'meta' => 'Stale drafts older than 7 days',
                'route' => 'admin.supplier.returns.index',
            ],
            [
                'key' => 'negative_balance',
                'title' => 'Suppliers With Advance',
                'count' => (int) ($negativeBalance['suppliers_count'] ?? 0),
                'amount' => round((float) ($negativeBalance['total_advance'] ?? 0), 2),
                'tone' => 'emerald',
                'meta' => 'Negative ledger balance (overpayment)',
                'route' => 'admin.supplier.statement.index',
            ],
        ];
    }

    /**
     * @return array{
     *  monthly_purchase_payment:array{labels:array<int,string>,purchase:array<int,float>,payment:array<int,float>},
     *  top_suppliers_by_purchase:array{labels:array<int,string>,values:array<int,float>},
     *  due_aging_distribution:array{labels:array<int,string>,values:array<int,float>},
     *  supplier_status_distribution:array{labels:array<int,string>,values:array<int,int>}
     * }
     */
    public function chartData(): array
    {
        return [
            'monthly_purchase_payment' => $this->monthlyPurchaseVsPaymentChart(),
            'top_suppliers_by_purchase' => $this->topSuppliersByPurchaseChart(),
            'due_aging_distribution' => $this->dueAgingDistributionChart(),
            'supplier_status_distribution' => $this->supplierStatusDistributionChart(),
        ];
    }

    /**
     * @return array{
     *  recent_bills:Collection<int, SupplierBill>,
     *  recent_payments:Collection<int, SupplierPayment>,
     *  recent_returns:Collection<int, SupplierReturn>,
     *  top_due_suppliers:Collection<int, object>
     * }
     */
    public function recentActivity(int $limit = 8): array
    {
        $recentBills = SupplierBill::query()
            ->with('supplier:id,name,code')
            ->whereIn('status', $this->postedBillStatuses())
            ->latest('bill_date')
            ->latest('id')
            ->limit($limit)
            ->get([
                'id',
                'supplier_id',
                'bill_no',
                'bill_date',
                'due_date',
                'due_amount',
                'status',
            ]);

        $recentPayments = SupplierPayment::query()
            ->with('supplier:id,name,code')
            ->whereIn('status', $this->activePaymentStatuses())
            ->latest('payment_date')
            ->latest('id')
            ->limit($limit)
            ->get([
                'id',
                'supplier_id',
                'payment_no',
                'payment_date',
                'payment_method',
                'total_amount',
                'status',
            ]);

        $recentReturns = SupplierReturn::query()
            ->with('supplier:id,name,code')
            ->latest('return_date')
            ->latest('id')
            ->limit($limit)
            ->get([
                'id',
                'supplier_id',
                'return_no',
                'return_date',
                'total_amount',
                'status',
            ]);

        $topDueSuppliers = app(SupplierReportService::class)
            ->supplierDueQuery(['due_only' => true])
            ->selectSub(
                SupplierPayment::query()
                    ->selectRaw('MAX(payment_date)')
                    ->whereColumn('supplier_payments.supplier_id', 'suppliers.id')
                    ->whereIn('status', $this->activePaymentStatuses()),
                'last_payment_date'
            )
            ->orderByDesc('net_payable')
            ->orderByDesc('overdue_amount')
            ->limit($limit)
            ->get();

        return [
            'recent_bills' => $recentBills,
            'recent_payments' => $recentPayments,
            'recent_returns' => $recentReturns,
            'top_due_suppliers' => $topDueSuppliers,
        ];
    }

    /**
     * @return array{labels:array<int,string>,purchase:array<int,float>,payment:array<int,float>}
     */
    protected function monthlyPurchaseVsPaymentChart(int $months = 12): array
    {
        $months = max(3, $months);

        $startDate = now()->startOfMonth()->subMonths($months - 1)->toDateString();
        $endDate = now()->endOfMonth()->toDateString();

        $billMonthExpression = $this->monthKeyExpression('bill_date');
        $paymentMonthExpression = $this->monthKeyExpression('payment_date');

        $billedMap = SupplierBill::query()
            ->selectRaw("{$billMonthExpression} as month_key")
            ->selectRaw('COALESCE(SUM(total_amount), 0) as amount')
            ->whereIn('status', $this->postedBillStatuses())
            ->whereBetween('bill_date', [$startDate, $endDate])
            ->groupByRaw($billMonthExpression)
            ->pluck('amount', 'month_key');

        $paidMap = SupplierPayment::query()
            ->selectRaw("{$paymentMonthExpression} as month_key")
            ->selectRaw('COALESCE(SUM(total_amount), 0) as amount')
            ->whereIn('status', $this->activePaymentStatuses())
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->groupByRaw($paymentMonthExpression)
            ->pluck('amount', 'month_key');

        $labels = [];
        $purchaseSeries = [];
        $paymentSeries = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $month = now()->startOfMonth()->subMonths($i);
            $key = $month->format('Y-m');
            $labels[] = $month->format('M Y');
            $purchaseSeries[] = round((float) ($billedMap[$key] ?? 0), 2);
            $paymentSeries[] = round((float) ($paidMap[$key] ?? 0), 2);
        }

        return [
            'labels' => $labels,
            'purchase' => $purchaseSeries,
            'payment' => $paymentSeries,
        ];
    }

    /**
     * @return array{labels:array<int,string>,values:array<int,float>}
     */
    protected function topSuppliersByPurchaseChart(int $limit = 10): array
    {
        $rows = SupplierBill::query()
            ->join('suppliers', 'suppliers.id', '=', 'supplier_bills.supplier_id')
            ->whereIn('supplier_bills.status', $this->postedBillStatuses())
            ->selectRaw('supplier_bills.supplier_id')
            ->selectRaw('MAX(suppliers.name) as supplier_name')
            ->selectRaw('COALESCE(SUM(supplier_bills.total_amount), 0) as purchase_total')
            ->groupBy('supplier_bills.supplier_id')
            ->orderByDesc('purchase_total')
            ->limit($limit)
            ->get();

        return [
            'labels' => $rows->pluck('supplier_name')->map(fn ($name): string => (string) $name)->all(),
            'values' => $rows->pluck('purchase_total')->map(fn ($amount): float => round((float) $amount, 2))->all(),
        ];
    }

    /**
     * @return array{labels:array<int,string>,values:array<int,float>}
     */
    protected function dueAgingDistributionChart(): array
    {
        $asOn = now()->toDateString();
        $minus30 = Carbon::parse($asOn)->subDays(30)->toDateString();
        $minus60 = Carbon::parse($asOn)->subDays(60)->toDateString();
        $minus90 = Carbon::parse($asOn)->subDays(90)->toDateString();

        $row = SupplierBill::query()
            ->whereIn('status', $this->pendingBillStatuses())
            ->where('due_amount', '>', 0)
            ->selectRaw('COALESCE(SUM(CASE WHEN due_date IS NULL OR due_date >= ? THEN due_amount ELSE 0 END), 0) as current_due', [$asOn])
            ->selectRaw('COALESCE(SUM(CASE WHEN due_date < ? AND due_date >= ? THEN due_amount ELSE 0 END), 0) as bucket_1_30', [$asOn, $minus30])
            ->selectRaw('COALESCE(SUM(CASE WHEN due_date < ? AND due_date >= ? THEN due_amount ELSE 0 END), 0) as bucket_31_60', [$minus30, $minus60])
            ->selectRaw('COALESCE(SUM(CASE WHEN due_date < ? AND due_date >= ? THEN due_amount ELSE 0 END), 0) as bucket_61_90', [$minus60, $minus90])
            ->selectRaw('COALESCE(SUM(CASE WHEN due_date < ? THEN due_amount ELSE 0 END), 0) as bucket_90_plus', [$minus90])
            ->first();

        return [
            'labels' => ['Current', '1-30 Days', '31-60 Days', '61-90 Days', '90+ Days'],
            'values' => [
                round((float) ($row->current_due ?? 0), 2),
                round((float) ($row->bucket_1_30 ?? 0), 2),
                round((float) ($row->bucket_31_60 ?? 0), 2),
                round((float) ($row->bucket_61_90 ?? 0), 2),
                round((float) ($row->bucket_90_plus ?? 0), 2),
            ],
        ];
    }

    /**
     * @return array{labels:array<int,string>,values:array<int,int>}
     */
    protected function supplierStatusDistributionChart(): array
    {
        $active = (int) Supplier::query()->active()->count();
        $inactive = (int) Supplier::query()->inactive()->count();
        $blocked = (int) Supplier::query()->blocked()->count();

        return [
            'labels' => ['Active', 'Inactive', 'Blocked'],
            'values' => [$active, $inactive, $blocked],
        ];
    }

    /**
     * @return array{suppliers_count:int, excess_amount:float}
     */
    protected function overCreditLimitMetrics(): array
    {
        $row = DB::query()
            ->fromSub($this->supplierBalanceQuery(), 'balance_rows')
            ->where('credit_limit', '>', 0)
            ->whereRaw('COALESCE(current_balance, 0) > credit_limit')
            ->selectRaw('COUNT(*) as suppliers_count')
            ->selectRaw('COALESCE(SUM(COALESCE(current_balance, 0) - credit_limit), 0) as excess_amount')
            ->first();

        return [
            'suppliers_count' => (int) ($row->suppliers_count ?? 0),
            'excess_amount' => round((float) ($row->excess_amount ?? 0), 2),
        ];
    }

    /**
     * @return array{suppliers_count:int}
     */
    protected function noRecentPurchaseMetrics(): array
    {
        $cutoffDate = now()->subDays(90)->toDateString();

        $count = Supplier::query()
            ->active()
            ->whereDoesntHave('supplierBills', function (Builder $builder) use ($cutoffDate): void {
                $builder
                    ->whereIn('status', $this->postedBillStatuses())
                    ->whereDate('bill_date', '>=', $cutoffDate);
            })
            ->count();

        return [
            'suppliers_count' => (int) $count,
        ];
    }

    /**
     * @return array{suppliers_count:int, return_amount:float}
     */
    protected function highReturnMetrics(): array
    {
        $sinceDate = now()->subDays(90)->toDateString();

        $returnsAgg = SupplierReturn::query()
            ->where('status', SupplierReturnStatus::APPROVED->value)
            ->whereDate('return_date', '>=', $sinceDate)
            ->selectRaw('supplier_id')
            ->selectRaw('COALESCE(SUM(total_amount), 0) as return_total')
            ->groupBy('supplier_id');

        $billsAgg = SupplierBill::query()
            ->whereIn('status', $this->postedBillStatuses())
            ->whereDate('bill_date', '>=', $sinceDate)
            ->selectRaw('supplier_id')
            ->selectRaw('COALESCE(SUM(total_amount), 0) as bill_total')
            ->groupBy('supplier_id');

        $row = Supplier::query()
            ->leftJoinSub($returnsAgg, 'returns_agg', function ($join): void {
                $join->on('returns_agg.supplier_id', '=', 'suppliers.id');
            })
            ->leftJoinSub($billsAgg, 'bills_agg', function ($join): void {
                $join->on('bills_agg.supplier_id', '=', 'suppliers.id');
            })
            ->whereRaw('COALESCE(returns_agg.return_total, 0) > 0')
            ->where(function (Builder $builder): void {
                $builder
                    ->whereRaw('COALESCE(bills_agg.bill_total, 0) <= 0')
                    ->orWhereRaw('COALESCE(returns_agg.return_total, 0) >= (COALESCE(bills_agg.bill_total, 0) * 0.20)');
            })
            ->selectRaw('COUNT(*) as suppliers_count')
            ->selectRaw('COALESCE(SUM(COALESCE(returns_agg.return_total, 0)), 0) as return_amount')
            ->first();

        return [
            'suppliers_count' => (int) ($row->suppliers_count ?? 0),
            'return_amount' => round((float) ($row->return_amount ?? 0), 2),
        ];
    }

    /**
     * @return array{draft_count:int, stale_count:int}
     */
    protected function draftReturnIssueMetrics(): array
    {
        $staleDate = now()->subDays(7)->toDateString();

        $draftCount = SupplierReturn::query()
            ->where('status', SupplierReturnStatus::DRAFT->value)
            ->count();

        $staleCount = SupplierReturn::query()
            ->where('status', SupplierReturnStatus::DRAFT->value)
            ->whereDate('return_date', '<=', $staleDate)
            ->count();

        return [
            'draft_count' => (int) $draftCount,
            'stale_count' => (int) $staleCount,
        ];
    }

    /**
     * @return array{suppliers_count:int, total_advance:float}
     */
    protected function negativeBalanceMetrics(): array
    {
        $row = DB::query()
            ->fromSub($this->supplierBalanceQuery(), 'balance_rows')
            ->whereRaw('COALESCE(current_balance, 0) < 0')
            ->selectRaw('COUNT(*) as suppliers_count')
            ->selectRaw('COALESCE(SUM(ABS(current_balance)), 0) as total_advance')
            ->first();

        return [
            'suppliers_count' => (int) ($row->suppliers_count ?? 0),
            'total_advance' => round((float) ($row->total_advance ?? 0), 2),
        ];
    }

    protected function supplierBalanceQuery(): Builder
    {
        return Supplier::query()
            ->select('suppliers.id', 'suppliers.credit_limit')
            ->selectSub(
                SupplierLedger::query()
                    ->select('balance')
                    ->whereColumn('supplier_ledgers.supplier_id', 'suppliers.id')
                    ->orderByDesc('transaction_date')
                    ->orderByDesc('id')
                    ->limit(1),
                'current_balance'
            );
    }

    protected function monthKeyExpression(string $column): string
    {
        return match (DB::connection()->getDriverName()) {
            'pgsql' => "to_char({$column}, 'YYYY-MM')",
            'sqlite' => "strftime('%Y-%m', {$column})",
            'sqlsrv' => "FORMAT({$column}, 'yyyy-MM')",
            default => "DATE_FORMAT({$column}, '%Y-%m')",
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
