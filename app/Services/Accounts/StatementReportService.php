<?php

namespace App\Services\Accounts;

use App\Enums\Accounts\AccountType;
use App\Models\Account;
use App\Models\Expense;
use App\Models\Project;
use App\Models\Property;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class StatementReportService
{
    /**
     * @var \Illuminate\Support\Collection<int, \App\Models\Account>|null
     */
    protected ?Collection $accounts = null;

    /**
     * @var array<string, array<int, int>>
     */
    protected array $groupAccountIds = [];

    public function supportsProjectFilter(): bool
    {
        return Schema::hasTable('transactions') && Schema::hasColumn('transactions', 'project_id');
    }

    public function supportsPropertyFilter(): bool
    {
        return Schema::hasTable('transactions') && Schema::hasColumn('transactions', 'property_id');
    }

    public function supportsPdfExport(): bool
    {
        return class_exists(\Barryvdh\DomPDF\Facade\Pdf::class);
    }

    /**
     * @return \Illuminate\Support\Collection<int, \App\Models\Account>
     */
    public function getBankAccounts(): Collection
    {
        $bankIds = $this->accountIdsForGroup('bank');

        return $this->allAccounts()
            ->whereIn('id', $bankIds)
            ->sortBy('name')
            ->values();
    }

    /**
     * @return \Illuminate\Support\Collection<int, \App\Models\Project>
     */
    public function getProjects(): Collection
    {
        if (! class_exists(Project::class) || ! Schema::hasTable('projects')) {
            return collect();
        }

        return Project::query()
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * @return \Illuminate\Support\Collection<int, \App\Models\Property>
     */
    public function getProperties(): Collection
    {
        if (! class_exists(Property::class) || ! Schema::hasTable('properties')) {
            return collect();
        }

        return Property::query()
            ->orderBy('name')
            ->get(['id', 'name', 'project_id']);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function build(array $filters): array
    {
        $normalized = $this->normalizeFilters($filters);
        $from = $normalized['from'];
        $to = $normalized['to'];

        $allBankIds = $this->accountIdsForGroup('bank');
        $cashIds = $this->accountIdsForGroup('cash');
        $iouIds = $this->accountIdsForGroup('iou');

        $bankAccounts = $this->getBankAccounts();

        $selectedBankId = $normalized['bank_account_id'];
        if ($selectedBankId && ! $bankAccounts->contains(fn (Account $account): bool => (int) $account->id === $selectedBankId)) {
            $selectedBankId = null;
        }

        $selectedBankIds = $selectedBankId ? [$selectedBankId] : $allBankIds;

        $trackedTransactionAccountIds = array_values(array_unique(array_merge($allBankIds, $cashIds, $iouIds)));

        $openingBalanceMap = $this->openingBalanceMap(
            array_values(array_unique(array_merge($selectedBankIds, $cashIds, $iouIds))),
            $from,
            $normalized
        );

        $transactions = $this->rangeTransactions($from, $to, $normalized, $trackedTransactionAccountIds);

        $bankRows = $this->buildBankRows(
            accounts: $bankAccounts->when($selectedBankId, fn (Collection $items): Collection => $items->where('id', $selectedBankId)->values()),
            openingBalanceMap: $openingBalanceMap,
            transactions: $transactions,
            selectedBankIds: $selectedBankIds,
            allBankIds: $allBankIds,
            cashIds: $cashIds
        );

        $cashOpeningBalance = $this->roundMoney(
            collect($cashIds)->sum(fn (int $accountId): float => (float) ($openingBalanceMap[$accountId] ?? 0))
        );

        $cashRows = $this->buildCashRows(
            openingBalance: $cashOpeningBalance,
            transactions: $transactions,
            cashIds: $cashIds,
            bankIds: $allBankIds,
            iouIds: $iouIds
        );

        $expenseRows = $this->buildExpenseRows(
            from: $from,
            to: $to,
            filters: $normalized,
            selectedBankId: $selectedBankId,
            cashIds: $cashIds,
            bankIds: $allBankIds
        );

        $closingBankBalance = $this->roundMoney(collect($bankRows)->sum('closing_balance'));
        $closingCashBalance = $cashRows === []
            ? $cashOpeningBalance
            : $this->roundMoney((float) data_get(last($cashRows), 'closing_balance', $cashOpeningBalance));
        $closingIouBalance = $this->balanceThroughDate($iouIds, $to, $normalized);
        $totalClosingAmount = $this->roundMoney($closingBankBalance + $closingCashBalance + $closingIouBalance);

        $statementType = $this->statementType($from, $to);

        return [
            'filters' => [
                'from_date' => $from->toDateString(),
                'to_date' => $to->toDateString(),
                'bank_account_id' => $selectedBankId,
                'project_id' => $normalized['project_id'],
                'property_id' => $normalized['property_id'],
            ],
            'meta' => [
                'statement_type' => $statementType,
                'statement_title' => match ($statementType) {
                    'daily' => 'Daily Statement',
                    'monthly' => 'Monthly Statement',
                    'yearly' => 'Yearly Statement',
                    default => 'Statement Sheet',
                },
                'period_label' => $this->periodLabel($from, $to, $statementType),
                'file_label' => $this->fileLabel($from, $to, $statementType),
                'bank_account_name' => $selectedBankId
                    ? $bankAccounts->firstWhere('id', $selectedBankId)?->name
                    : null,
                'has_transactions' => $this->hasTransactions($bankRows, $cashRows, $expenseRows),
                'supports_project_filter' => $this->supportsProjectFilter(),
                'supports_property_filter' => $this->supportsPropertyFilter(),
            ],
            'bank_rows' => $bankRows,
            'cash_rows' => $cashRows,
            'expense_rows' => $expenseRows,
            'totals' => [
                'closing_bank' => $closingBankBalance,
                'closing_cash' => $closingCashBalance,
                'closing_iou' => $closingIouBalance,
                'total_amount' => $totalClosingAmount,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{from:\Carbon\Carbon,to:\Carbon\Carbon,bank_account_id:?int,project_id:?int,property_id:?int}
     */
    protected function normalizeFilters(array $filters): array
    {
        $today = now();
        $from = $this->parseDate($filters['from_date'] ?? null, $today->copy()->toDateString());
        $to = $this->parseDate($filters['to_date'] ?? null, $today->copy()->toDateString());

        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        return [
            'from' => $from->startOfDay(),
            'to' => $to->startOfDay(),
            'bank_account_id' => $this->nullableInt($filters['bank_account_id'] ?? null),
            'project_id' => $this->supportsProjectFilter() ? $this->nullableInt($filters['project_id'] ?? null) : null,
            'property_id' => $this->supportsPropertyFilter() ? $this->nullableInt($filters['property_id'] ?? null) : null,
        ];
    }

    protected function parseDate(mixed $value, string $fallback): Carbon
    {
        try {
            return Carbon::parse($value ?: $fallback);
        } catch (\Throwable) {
            return Carbon::parse($fallback);
        }
    }

    protected function nullableInt(mixed $value): ?int
    {
        $normalized = (int) $value;

        return $normalized > 0 ? $normalized : null;
    }

    /**
     * @param  array<int, int>  $accountIds
     * @param  array<string, mixed>  $filters
     * @return array<int, float>
     */
    protected function openingBalanceMap(array $accountIds, Carbon $from, array $filters): array
    {
        if ($accountIds === []) {
            return [];
        }

        $rows = DB::table('transaction_lines')
            ->join('transactions', 'transactions.id', '=', 'transaction_lines.transaction_id')
            ->selectRaw('transaction_lines.account_id, COALESCE(SUM(transaction_lines.debit - transaction_lines.credit), 0) AS balance')
            ->whereIn('transaction_lines.account_id', $accountIds)
            ->where('transactions.date', '<', $from->toDateString());

        $this->applyTransactionDimensionFilters($rows, $filters);

        return $rows
            ->groupBy('transaction_lines.account_id')
            ->pluck('balance', 'transaction_lines.account_id')
            ->map(fn (mixed $value): float => $this->roundMoney((float) $value))
            ->all();
    }

    /**
     * @param  array<int, int>  $accountIds
     * @param  array<string, mixed>  $filters
     */
    protected function balanceThroughDate(array $accountIds, Carbon $to, array $filters): float
    {
        if ($accountIds === []) {
            return 0.0;
        }

        $query = DB::table('transaction_lines')
            ->join('transactions', 'transactions.id', '=', 'transaction_lines.transaction_id')
            ->selectRaw('COALESCE(SUM(transaction_lines.debit - transaction_lines.credit), 0) AS balance')
            ->whereIn('transaction_lines.account_id', $accountIds)
            ->where('transactions.date', '<=', $to->toDateString());

        $this->applyTransactionDimensionFilters($query, $filters);

        return $this->roundMoney((float) ($query->value('balance') ?? 0));
    }

    /**
     * @param  array<int, int>  $trackedAccountIds
     * @param  array<string, mixed>  $filters
     * @return \Illuminate\Support\Collection<int, \App\Models\Transaction>
     */
    protected function rangeTransactions(Carbon $from, Carbon $to, array $filters, array $trackedAccountIds): Collection
    {
        if ($trackedAccountIds === []) {
            return collect();
        }

        return Transaction::query()
            ->with([
                'lines:id,transaction_id,account_id,debit,credit,description',
                'lines.account:id,parent_id,name,code,type',
                'payment:id,transaction_id,payment_no,payee_name,purpose_account_id,notes',
                'payment.purposeAccount:id,name,code',
                'collection:id,transaction_id,collection_no,payer_name,target_account_id,notes',
                'collection.targetAccount:id,name,code',
                'expense:id,transaction_id,expense_no,title,expense_account_id,payment_account_id,notes',
                'expense.expenseAccount:id,name,code',
                'expense.paymentAccount:id,name,code',
            ])
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->whereHas('lines', function (Builder $query) use ($trackedAccountIds): void {
                $query->whereIn('account_id', $trackedAccountIds);
            })
            ->tap(fn (Builder $query) => $this->applyTransactionDimensionFilters($query, $filters))
            ->orderBy('date')
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, \App\Models\Account>  $accounts
     * @param  array<int, float>  $openingBalanceMap
     * @param  \Illuminate\Support\Collection<int, \App\Models\Transaction>  $transactions
     * @param  array<int, int>  $selectedBankIds
     * @param  array<int, int>  $allBankIds
     * @param  array<int, int>  $cashIds
     * @return array<int, array<string, mixed>>
     */
    protected function buildBankRows(
        Collection $accounts,
        array $openingBalanceMap,
        Collection $transactions,
        array $selectedBankIds,
        array $allBankIds,
        array $cashIds
    ): array {
        if ($selectedBankIds === []) {
            return [];
        }

        $rows = [];

        foreach ($accounts as $account) {
            if (! in_array((int) $account->id, $selectedBankIds, true)) {
                continue;
            }

            $rows[(int) $account->id] = [
                'account_id' => (int) $account->id,
                'bank_name' => $account->name,
                'opening_balance' => $this->roundMoney((float) ($openingBalanceMap[$account->id] ?? 0)),
                'deposit' => 0.0,
                'bank_transfer_in' => 0.0,
                'total_taka' => 0.0,
                'withdrawn' => 0.0,
                'bank_transfer_out' => 0.0,
                'closing_balance' => 0.0,
            ];
        }

        $liquidIds = array_values(array_unique(array_merge($allBankIds, $cashIds)));

        foreach ($transactions as $transaction) {
            foreach ($transaction->lines as $line) {
                $accountId = (int) $line->account_id;

                if (! isset($rows[$accountId])) {
                    continue;
                }

                $isTransfer = $transaction->lines->contains(function ($otherLine) use ($accountId, $liquidIds): bool {
                    return (int) $otherLine->account_id !== $accountId
                        && in_array((int) $otherLine->account_id, $liquidIds, true);
                });

                $debit = $this->roundMoney((float) $line->debit);
                $credit = $this->roundMoney((float) $line->credit);

                if ($debit > 0) {
                    $rows[$accountId][$isTransfer ? 'bank_transfer_in' : 'deposit'] += $debit;
                }

                if ($credit > 0) {
                    $rows[$accountId][$isTransfer ? 'bank_transfer_out' : 'withdrawn'] += $credit;
                }
            }
        }

        $preparedRows = [];
        $sl = 1;

        foreach ($rows as $row) {
            $row['deposit'] = $this->roundMoney((float) $row['deposit']);
            $row['bank_transfer_in'] = $this->roundMoney((float) $row['bank_transfer_in']);
            $row['withdrawn'] = $this->roundMoney((float) $row['withdrawn']);
            $row['bank_transfer_out'] = $this->roundMoney((float) $row['bank_transfer_out']);
            $row['total_taka'] = $this->roundMoney($row['opening_balance'] + $row['deposit'] + $row['bank_transfer_in']);
            $row['closing_balance'] = $this->roundMoney($row['total_taka'] - $row['withdrawn'] - $row['bank_transfer_out']);

            if (
                abs($row['opening_balance']) < 0.005
                && abs($row['deposit']) < 0.005
                && abs($row['bank_transfer_in']) < 0.005
                && abs($row['withdrawn']) < 0.005
                && abs($row['bank_transfer_out']) < 0.005
            ) {
                continue;
            }

            $row['sl'] = $sl++;
            $preparedRows[] = $row;
        }

        return $preparedRows;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, \App\Models\Transaction>  $transactions
     * @param  array<int, int>  $cashIds
     * @param  array<int, int>  $bankIds
     * @param  array<int, int>  $iouIds
     * @return array<int, array<string, mixed>>
     */
    protected function buildCashRows(
        float $openingBalance,
        Collection $transactions,
        array $cashIds,
        array $bankIds,
        array $iouIds
    ): array {
        if ($cashIds === []) {
            return [];
        }

        $rows = [];
        $runningBalance = $this->roundMoney($openingBalance);

        foreach ($transactions as $transaction) {
            $cashLines = $transaction->lines->filter(fn ($line): bool => in_array((int) $line->account_id, $cashIds, true));

            if ($cashLines->isEmpty()) {
                continue;
            }

            $cashDebit = $this->roundMoney((float) $cashLines->sum('debit'));
            $cashCredit = $this->roundMoney((float) $cashLines->sum('credit'));

            $hasBankCounterpart = $transaction->lines->contains(fn ($line): bool => in_array((int) $line->account_id, $bankIds, true));
            $hasIouCounterpart = $transaction->lines->contains(fn ($line): bool => in_array((int) $line->account_id, $iouIds, true));

            $cashReceived = 0.0;
            $iouAdjustment = 0.0;
            $bankTransfer = 0.0;
            $expenses = 0.0;

            if ($cashDebit > 0) {
                if ($hasBankCounterpart) {
                    $bankTransfer += $cashDebit;
                } elseif ($hasIouCounterpart) {
                    $iouAdjustment += $cashDebit;
                } else {
                    $cashReceived += $cashDebit;
                }
            }

            if ($cashCredit > 0) {
                if ($hasBankCounterpart) {
                    $bankTransfer -= $cashCredit;
                } elseif ($hasIouCounterpart) {
                    $iouAdjustment -= $cashCredit;
                } else {
                    $expenses += $cashCredit;
                }
            }

            if (
                abs($cashReceived) < 0.005
                && abs($iouAdjustment) < 0.005
                && abs($bankTransfer) < 0.005
                && abs($expenses) < 0.005
            ) {
                continue;
            }

            $counterparts = $transaction->lines
                ->reject(fn ($line): bool => in_array((int) $line->account_id, $cashIds, true))
                ->map(fn ($line): ?string => $line->account?->name)
                ->filter()
                ->unique()
                ->values()
                ->all();

            $totalTaka = $this->roundMoney($runningBalance + $cashReceived + $iouAdjustment + $bankTransfer);
            $closingBalance = $this->roundMoney($totalTaka - $expenses);

            $rows[] = [
                'mr_no' => $this->cashReferenceNo($transaction),
                'particulars' => $this->cashParticulars($transaction, $counterparts, $bankTransfer, $iouAdjustment, $expenses),
                'date_label' => optional($transaction->date)->format('d M Y'),
                'opening_balance' => $runningBalance,
                'cash_received' => $this->roundMoney($cashReceived),
                'iou_adjustment' => $this->roundMoney($iouAdjustment),
                'bank_transfer' => $this->roundMoney($bankTransfer),
                'total_taka' => $totalTaka,
                'expenses' => $this->roundMoney($expenses),
                'closing_balance' => $closingBalance,
            ];

            $runningBalance = $closingBalance;
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  array<int, int>  $cashIds
     * @param  array<int, int>  $bankIds
     * @return array<int, array<string, mixed>>
     */
    protected function buildExpenseRows(
        Carbon $from,
        Carbon $to,
        array $filters,
        ?int $selectedBankId,
        array $cashIds,
        array $bankIds
    ): array {
        $expenses = Expense::query()
            ->with([
                'paymentAccount:id,name,code,parent_id,type',
                'expenseAccount:id,name,code',
                'transaction:id,date',
            ])
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->when($selectedBankId, function (Builder $query) use ($selectedBankId, $cashIds): void {
                $query->where(function (Builder $subQuery) use ($selectedBankId, $cashIds): void {
                    $subQuery->where('payment_account_id', $selectedBankId);

                    if ($cashIds !== []) {
                        $subQuery->orWhereIn('payment_account_id', $cashIds);
                    }
                });
            })
            ->when($this->supportsProjectFilter() && $filters['project_id'], function (Builder $query) use ($filters): void {
                $query->whereHas('transaction', fn (Builder $transactionQuery): Builder => $transactionQuery->where('project_id', $filters['project_id']));
            })
            ->when($this->supportsPropertyFilter() && $filters['property_id'], function (Builder $query) use ($filters): void {
                $query->whereHas('transaction', fn (Builder $transactionQuery): Builder => $transactionQuery->where('property_id', $filters['property_id']));
            })
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        return $expenses->map(function (Expense $expense) use ($bankIds): array {
            $paymentAccountId = (int) $expense->payment_account_id;
            $isBankTransfer = in_array($paymentAccountId, $bankIds, true);

            return [
                'voucher_no' => $expense->expense_no ?: 'EXP-'.$expense->id,
                'particulars' => $expense->title ?: ($expense->expenseAccount?->name ?? 'Expense'),
                'date_label' => optional($expense->date)->format('d M Y'),
                'req_no' => $this->referenceLabel($expense->reference_type, $expense->reference_id),
                'taka' => $this->roundMoney($isBankTransfer ? 0 : (float) $expense->amount),
                'bank_transfer' => $this->roundMoney($isBankTransfer ? (float) $expense->amount : 0),
                'bank_name' => $isBankTransfer ? ($expense->paymentAccount?->name ?? '-') : '-',
                'notes' => $expense->notes,
            ];
        })->values()->all();
    }

    /**
     * @param  \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder  $query
     * @param  array<string, mixed>  $filters
     */
    protected function applyTransactionDimensionFilters(object $query, array $filters): void
    {
        if ($this->supportsProjectFilter() && ! empty($filters['project_id'])) {
            $query->where('transactions.project_id', $filters['project_id']);
        }

        if ($this->supportsPropertyFilter() && ! empty($filters['property_id'])) {
            $query->where('transactions.property_id', $filters['property_id']);
        }
    }

    protected function statementType(Carbon $from, Carbon $to): string
    {
        if ($from->isSameDay($to)) {
            return 'daily';
        }

        if (
            $from->isSameMonth($to)
            && $from->copy()->startOfMonth()->isSameDay($from)
            && $to->copy()->endOfMonth()->isSameDay($to)
        ) {
            return 'monthly';
        }

        if (
            $from->year === $to->year
            && $from->copy()->startOfYear()->isSameDay($from)
            && $to->copy()->endOfYear()->isSameDay($to)
        ) {
            return 'yearly';
        }

        return 'custom';
    }

    protected function periodLabel(Carbon $from, Carbon $to, string $statementType): string
    {
        return match ($statementType) {
            'daily' => $from->format('d M Y'),
            'monthly' => $from->format('F Y'),
            'yearly' => $from->format('Y'),
            default => $from->format('d M Y').' to '.$to->format('d M Y'),
        };
    }

    protected function fileLabel(Carbon $from, Carbon $to, string $statementType): string
    {
        return match ($statementType) {
            'daily' => 'daily-'.$from->format('Y-m-d'),
            'monthly' => 'monthly-'.$from->format('Y-m'),
            'yearly' => 'yearly-'.$from->format('Y'),
            default => $from->format('Y-m-d').'-to-'.$to->format('Y-m-d'),
        };
    }

    /**
     * @param  array<int, array<string, mixed>>  $bankRows
     * @param  array<int, array<string, mixed>>  $cashRows
     * @param  array<int, array<string, mixed>>  $expenseRows
     */
    protected function hasTransactions(array $bankRows, array $cashRows, array $expenseRows): bool
    {
        $bankActivity = collect($bankRows)->contains(function (array $row): bool {
            return abs((float) $row['deposit']) > 0.005
                || abs((float) $row['bank_transfer_in']) > 0.005
                || abs((float) $row['withdrawn']) > 0.005
                || abs((float) $row['bank_transfer_out']) > 0.005;
        });

        return $bankActivity || $cashRows !== [] || $expenseRows !== [];
    }

    protected function cashReferenceNo(Transaction $transaction): string
    {
        if ($transaction->collection) {
            return $transaction->collection->collection_no ?: 'COL-'.$transaction->id;
        }

        if ($transaction->expense) {
            return $transaction->expense->expense_no ?: 'EXP-'.$transaction->id;
        }

        if ($transaction->payment) {
            return $transaction->payment->payment_no ?: 'PAY-'.$transaction->id;
        }

        return 'TXN-'.$transaction->id;
    }

    /**
     * @param  array<int, string>  $counterparts
     */
    protected function cashParticulars(
        Transaction $transaction,
        array $counterparts,
        float $bankTransfer,
        float $iouAdjustment,
        float $expenseAmount
    ): string {
        $base = match (true) {
            (bool) $transaction->collection => $transaction->collection?->payer_name ?: 'Cash collection',
            (bool) $transaction->expense => $transaction->expense?->title ?: 'Cash expense',
            (bool) $transaction->payment => $transaction->payment?->payee_name ?: 'Cash payment',
            default => Str::headline((string) ($transaction->type?->value ?? 'journal')),
        };

        $descriptor = collect($counterparts)->filter()->implode(', ');

        if ($bankTransfer > 0) {
            $base .= ' (Bank transfer in)';
        } elseif ($bankTransfer < 0) {
            $base .= ' (Bank transfer out)';
        } elseif ($iouAdjustment > 0) {
            $base .= ' (IOU decrease)';
        } elseif ($iouAdjustment < 0) {
            $base .= ' (IOU increase)';
        } elseif ($expenseAmount > 0) {
            $base .= ' (Cash out)';
        }

        if ($descriptor !== '') {
            $base .= ' - '.$descriptor;
        }

        return $base;
    }

    protected function referenceLabel(?string $referenceType, mixed $referenceId): string
    {
        if (! $referenceType && ! $referenceId) {
            return '-';
        }

        $label = $referenceType ? Str::headline(str_replace('_', ' ', $referenceType)) : 'Ref';
        $idPart = $referenceId ? ' #'.$referenceId : '';

        return trim($label.$idPart);
    }

    /**
     * @return array<int, int>
     */
    protected function accountIdsForGroup(string $group): array
    {
        if (isset($this->groupAccountIds[$group])) {
            return $this->groupAccountIds[$group];
        }

        $accounts = $this->allAccounts()->filter(fn (Account $account): bool => $account->type === AccountType::ASSET);
        $accountsById = $this->allAccounts()->keyBy('id');

        $configuredNames = match ($group) {
            'cash' => [(string) config('hrm.accounts.cash.name', 'Cash')],
            'bank' => [(string) config('hrm.accounts.bank.name', 'Bank')],
            'iou' => [(string) config('hrm.accounts.employee_advance.name', 'Employee Advance')],
            default => [],
        };

        $configuredCodes = match ($group) {
            'cash' => [(string) config('hrm.accounts.cash.code', '')],
            'bank' => [(string) config('hrm.accounts.bank.code', '')],
            'iou' => [(string) config('hrm.accounts.employee_advance.code', '')],
            default => [],
        };

        $keywords = match ($group) {
            'cash' => ['cash'],
            'bank' => ['bank'],
            'iou' => ['iou'],
            default => [],
        };

        $ids = $accounts
            ->filter(function (Account $account) use ($accountsById, $configuredNames, $configuredCodes, $keywords): bool {
                $cursor = $account;
                $depth = 0;

                while ($cursor && $depth < 20) {
                    $name = Str::lower(trim((string) $cursor->name));
                    $code = Str::lower(trim((string) ($cursor->code ?? '')));
                    $haystack = Str::lower(trim($cursor->name.' '.$cursor->code));

                    foreach ($configuredNames as $configuredName) {
                        $normalized = Str::lower(trim($configuredName));

                        if ($normalized !== '' && $name === $normalized) {
                            return true;
                        }
                    }

                    foreach ($configuredCodes as $configuredCode) {
                        $normalized = Str::lower(trim($configuredCode));

                        if ($normalized !== '' && $code === $normalized) {
                            return true;
                        }
                    }

                    foreach ($keywords as $keyword) {
                        $normalized = Str::lower(trim($keyword));

                        if ($normalized !== '' && Str::contains($haystack, $normalized)) {
                            return true;
                        }
                    }

                    $cursor = $cursor->parent_id ? $accountsById->get($cursor->parent_id) : null;
                    $depth++;
                }

                return false;
            })
            ->pluck('id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->values()
            ->all();

        $this->groupAccountIds[$group] = $ids;

        return $ids;
    }

    /**
     * @return \Illuminate\Support\Collection<int, \App\Models\Account>
     */
    protected function allAccounts(): Collection
    {
        if ($this->accounts instanceof Collection) {
            return $this->accounts;
        }

        $this->accounts = Account::query()
            ->orderBy('name')
            ->get(['id', 'parent_id', 'name', 'code', 'type', 'is_active']);

        return $this->accounts;
    }

    protected function roundMoney(float $value): float
    {
        return round($value, 2);
    }
}
