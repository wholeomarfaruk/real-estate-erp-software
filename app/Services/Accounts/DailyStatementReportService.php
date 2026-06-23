<?php

namespace App\Services\Accounts;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\TransactionLine;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DailyStatementReportService
{
    public function supportsPdfExport(): bool
    {
        return class_exists(Pdf::class);
    }

    /**
     * @return \Illuminate\Support\Collection<int, \App\Models\Account>
     */
    public function getBankAccounts(): Collection
    {
        return Account::query()
            ->with('bankAccount:id,account_id,bank_name')
            ->where('is_active', true)
            ->where('sub_type', 'bank')
            ->orderBy('name')
            ->get(['id', 'name', 'sub_type']);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function build(array $filters): array
    {
        $reportDate = $this->parseDate($filters['report_date'] ?? $filters['date'] ?? null);
        $bankAccounts = $this->getBankAccounts();
        $cashAccounts = $this->getCashAccounts();

        $selectedBankId = $this->nullableInt($filters['bank_account_id'] ?? null);
        if ($selectedBankId && ! $bankAccounts->contains(fn (Account $account): bool => (int) $account->id === $selectedBankId)) {
            $selectedBankId = null;
        }

        $selectedBankAccounts = $selectedBankId
            ? $bankAccounts->where('id', $selectedBankId)->values()
            : $bankAccounts->values();

        $bankIds = $selectedBankAccounts->pluck('id')->map(static fn (mixed $id): int => (int) $id)->all();
        $cashIds = $cashAccounts->pluck('id')->map(static fn (mixed $id): int => (int) $id)->all();
        $trackedAccountIds = array_values(array_unique(array_merge($bankIds, $cashIds)));
        $advanceAccountIds = $this->getAdvanceAccountIds();

        $openingBalanceMap = $this->openingBalanceMap(
            array_values(array_unique(array_merge($trackedAccountIds, $advanceAccountIds))),
            $reportDate->copy()->startOfDay()
        );

        $transactions = $this->dailyTransactions($trackedAccountIds, $reportDate);

        $bankRows = $this->buildBankRows($selectedBankAccounts, $transactions, $openingBalanceMap);
        $cashRows = $this->buildCashRows($transactions, $cashIds, $openingBalanceMap);
        $expenseRows = $this->buildExpenseRows($transactions);
        $incomeRows = $this->buildIncomeRows($transactions);

        $bankTotals = [
            'total_opening' => $this->money(collect($bankRows)->sum('opening_balance')),
            'total_deposit' => $this->money(collect($bankRows)->sum('deposit')),
            'total_transfer_in' => $this->money(collect($bankRows)->sum('bank_transfer_in')),
            'total_withdrawn' => $this->money(collect($bankRows)->sum('withdrawn')),
            'total_transfer_out' => $this->money(collect($bankRows)->sum('bank_transfer_out')),
            'total_closing' => $this->money(collect($bankRows)->sum('closing_balance')),
        ];

        $cashOpeningBalance = $this->money(collect($cashIds)->sum(
            fn (int $accountId): float => (float) ($openingBalanceMap[$accountId] ?? 0)
        ));

        $cashClosingBalance = $cashRows === []
            ? $cashOpeningBalance
            : $this->money((float) data_get(last($cashRows), 'closing_balance', $cashOpeningBalance));

        $advanceClosingBalance = $this->closingAdvanceBalance($advanceAccountIds, $reportDate);

        $cashTotals = [
            'grand_total_opening' => $cashRows === []
                ? $cashOpeningBalance
                : $this->money((float) data_get($cashRows, '0.opening_balance', $cashOpeningBalance)),
            'grand_total_received' => $this->money(collect($cashRows)->sum('cash_received')),
            'grand_total_iou' => $this->money(collect($cashRows)->sum('iou_received')),
            'grand_total_transfer' => $this->money(collect($cashRows)->sum('bank_transfer')),
            'grand_total_expenses' => $this->money(collect($cashRows)->sum('expenses')),
            'grand_total_closing' => $cashClosingBalance,
            'ho_in_hand' => (object) ['closing_balance' => $cashClosingBalance],
            'ho_iou' => (object) ['closing_balance' => $advanceClosingBalance],
        ];

        $expenseTotals = [
            'iou_payment' => $this->money(
                collect($expenseRows)
                    ->where('category_type', 'advance')
                    ->sum(fn (array $row): float => (float) $row['amount'] + (float) $row['bank_transfer'])
            ),
            'closing_balance'    => $this->money($cashClosingBalance + $bankTotals['total_closing']),
            'total_cash'         => $this->money(collect($expenseRows)->sum('amount')),
            'total_bank_transfer'=> $this->money(collect($expenseRows)->sum('bank_transfer')),
        ];

        $incomeTotals = [
            'total_cash'         => $this->money(collect($incomeRows)->sum('cash')),
            'total_bank_transfer'=> $this->money(collect($incomeRows)->sum('bank_transfer')),
            'grand_total'        => $this->money(collect($incomeRows)->sum('cash') + collect($incomeRows)->sum('bank_transfer')),
        ];

        $statement = (object) [
            'statement_ref' => $this->statementRef($reportDate, $selectedBankId),
            'statement_date' => $reportDate,
            'prepared_by' => auth()->user()?->name,
            'checked_by' => null,
            'approved_by' => null,
            'bankDetails' => collect($bankRows)->map(function (array $row): object {
                return (object) [
                    'bankAccount' => (object) ['bank_name' => $row['bank_name']],
                    'cheque_no' => $row['cheque_no'],
                    'opening_balance' => $row['opening_balance'],
                    'deposit' => $row['deposit'],
                    'bank_transfer_in' => $row['bank_transfer_in'],
                    'total_taka' => $row['total_taka'],
                    'withdrawn' => $row['withdrawn'],
                    'bank_transfer_out' => $row['bank_transfer_out'],
                    'closing_balance' => $row['closing_balance'],
                ];
            })->values(),
            'cashDetails' => collect($cashRows)->map(fn (array $row): object => (object) $row)->values(),
            'expenses' => collect($expenseRows)->map(fn (array $row): object => (object) $row)->values(),
            'incomes'  => collect($incomeRows)->map(fn (array $row): object => (object) $row)->values(),
        ];

        return [
            'statement'    => $statement,
            'bankTotals'   => $bankTotals,
            'cashTotals'   => $cashTotals,
            'expenseTotals'=> $expenseTotals,
            'incomeTotals' => $incomeTotals,
            'meta' => [
                'report_date' => $reportDate->toDateString(),
                'bank_account_id' => $selectedBankId,
                'bank_account_name' => $selectedBankAccounts->first()?->name,
            ],
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<int, \App\Models\Account>
     */
    protected function getCashAccounts(): Collection
    {
        return Account::query()
            ->where('is_active', true)
            ->where('sub_type', 'cash')
            ->orderBy('name')
            ->get(['id', 'name', 'sub_type']);
    }

    /**
     * @return array<int, int>
     */
    protected function getAdvanceAccountIds(): array
    {
        $configuredNames = [
            'Advance',
            'Employee Advance',
            'Supplier Advance',
            'Customer Advance',
            (string) config('hrm.accounts.employee_advance.name'),
        ];

        return Account::query()
            ->where('is_active', true)
            ->where(function (Builder $query) use ($configuredNames): void {
                $query->whereIn('name', collect($configuredNames)->filter()->unique()->values())
                    ->orWhereRaw('LOWER(name) like ?', ['%advance%']);
            })
            ->pluck('id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->values()
            ->all();
    }

    protected function parseDate(mixed $value): Carbon
    {
        try {
            return Carbon::parse($value ?: now()->toDateString())->startOfDay();
        } catch (\Throwable) {
            return now()->startOfDay();
        }
    }

    protected function nullableInt(mixed $value): ?int
    {
        $normalized = (int) $value;

        return $normalized > 0 ? $normalized : null;
    }

    /**
     * @param  array<int, int>  $accountIds
     * @return array<int, float>
     */
    protected function openingBalanceMap(array $accountIds, Carbon $beforeDate): array
    {
        if ($accountIds === []) {
            return [];
        }

        return DB::table('transaction_lines as tl')
            ->join('transactions as t', 't.id', '=', 'tl.transaction_id')
            ->selectRaw('tl.account_id, COALESCE(SUM(tl.debit - tl.credit), 0) as balance')
            ->whereIn('tl.account_id', $accountIds)
            ->where('t.datetime', '<', $beforeDate->toDateTimeString())
            ->groupBy('tl.account_id')
            ->pluck('balance', 'account_id')
            ->map(fn (mixed $value): float => $this->money((float) $value))
            ->all();
    }

    protected function closingAdvanceBalance(array $advanceAccountIds, Carbon $throughDate): float
    {
        if ($advanceAccountIds === []) {
            return 0.0;
        }

        return $this->money((float) DB::table('transaction_lines as tl')
            ->join('transactions as t', 't.id', '=', 'tl.transaction_id')
            ->whereIn('tl.account_id', $advanceAccountIds)
            ->where('t.datetime', '<=', $throughDate->copy()->endOfDay()->toDateTimeString())
            ->selectRaw('COALESCE(SUM(tl.debit - tl.credit), 0) as balance')
            ->value('balance'));
    }

    /**
     * @param  array<int, int>  $trackedAccountIds
     * @return \Illuminate\Support\Collection<int, \App\Models\Transaction>
     */
    protected function dailyTransactions(array $trackedAccountIds, Carbon $reportDate): Collection
    {
        if ($trackedAccountIds === []) {
            return collect();
        }

        // Per-account daily movements come from transaction_lines. Each line carries
        // its own account + debit/credit; TransactionLine proxy accessors expose the
        // parent transaction's type/datetime/reference/expense so the row builders
        // below read $tx->account_id / debit / credit / reference unchanged.
        return TransactionLine::query()
            ->with([
                'account:id,name,sub_type',
                'account.bankAccount:id,account_id,bank_name',
                'transaction.expense:id,transaction_id,expense_no,title,reference_type,reference_id',
                'transaction.reference',
                'transaction.reference.propertySale.property:id,name',
                'transaction.reference.propertySale.propertyUnit:id,property_floor_id,code,unit_number,type',
                'transaction.reference.propertySale.propertyUnit.floor:id,label,code',
            ])
            ->whereIn('transaction_lines.account_id', $trackedAccountIds)
            ->whereHas('transaction', function (Builder $query) use ($reportDate): void {
                $query->whereIn('type', $this->supportedTransactionTypes())
                    ->whereBetween('datetime', [
                        $reportDate->copy()->startOfDay()->toDateTimeString(),
                        $reportDate->copy()->endOfDay()->toDateTimeString(),
                    ]);
            })
            ->join('transactions', 'transactions.id', '=', 'transaction_lines.transaction_id')
            ->orderBy('transactions.datetime')
            ->orderBy('transactions.id')
            ->select('transaction_lines.*')
            ->get();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, \App\Models\Account>  $accounts
     * @param  \Illuminate\Support\Collection<int, \App\Models\Transaction>  $transactions
     * @param  array<int, float>  $openingBalanceMap
     * @return array<int, array<string, mixed>>
     */
    protected function buildBankRows(Collection $accounts, Collection $transactions, array $openingBalanceMap): array
    {
        if ($accounts->isEmpty()) {
            return [];
        }

        $rows = [];

        foreach ($accounts as $account) {
            $rows[(int) $account->id] = [
                'bank_name' => $account->bankAccount?->bank_name ?: $account->name,
                'cheque_no' => '',
                'opening_balance' => $this->money((float) ($openingBalanceMap[$account->id] ?? 0)),
                'deposit' => 0.0,
                'bank_transfer_in' => 0.0,
                'total_taka' => 0.0,
                'withdrawn' => 0.0,
                'bank_transfer_out' => 0.0,
                'closing_balance' => 0.0,
            ];
        }

        foreach ($transactions as $transaction) {
            $accountId = (int) $transaction->account_id;

            if (! isset($rows[$accountId])) {
                continue;
            }

            $type = $this->classifyTransactionType($transaction);
            $debit = $this->money((float) $transaction->debit);
            $credit = $this->money((float) $transaction->credit);

            if ($debit > 0) {
                if ($type === 'transfer') {
                    $rows[$accountId]['bank_transfer_in'] += $debit;
                } else {
                    $rows[$accountId]['deposit'] += $debit;
                }
            }

            if ($credit > 0) {
                if ($type === 'transfer') {
                    $rows[$accountId]['bank_transfer_out'] += $credit;
                } else {
                    $rows[$accountId]['withdrawn'] += $credit;
                }

                if ($this->usesCheque($transaction) && $rows[$accountId]['cheque_no'] === '') {
                    $rows[$accountId]['cheque_no'] = $transaction->reference_no ?: 'TXN-'.$transaction->id;
                }
            }
        }

        return collect($rows)
            ->map(function (array $row): array {
                $row['deposit'] = $this->money((float) $row['deposit']);
                $row['bank_transfer_in'] = $this->money((float) $row['bank_transfer_in']);
                $row['withdrawn'] = $this->money((float) $row['withdrawn']);
                $row['bank_transfer_out'] = $this->money((float) $row['bank_transfer_out']);
                $row['total_taka'] = $this->money($row['opening_balance'] + $row['deposit'] + $row['bank_transfer_in']);
                $row['closing_balance'] = $this->money($row['total_taka'] - $row['withdrawn'] - $row['bank_transfer_out']);

                return $row;
            })
            ->filter(fn (array $row): bool => $this->hasAnyValue([
                $row['opening_balance'],
                $row['deposit'],
                $row['bank_transfer_in'],
                $row['withdrawn'],
                $row['bank_transfer_out'],
                $row['closing_balance'],
            ]))
            ->values()
            ->all();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, \App\Models\Transaction>  $transactions
     * @param  array<int, int>  $cashIds
     * @param  array<int, float>  $openingBalanceMap
     * @return array<int, array<string, mixed>>
     */
    protected function buildCashRows(Collection $transactions, array $cashIds, array $openingBalanceMap): array
    {
        if ($cashIds === []) {
            return [];
        }

        $rows = [];
        $runningBalance = $this->money(collect($cashIds)->sum(
            fn (int $accountId): float => (float) ($openingBalanceMap[$accountId] ?? 0)
        ));

        foreach ($transactions as $transaction) {
            if (! in_array((int) $transaction->account_id, $cashIds, true)) {
                continue;
            }

            $type = $this->classifyTransactionType($transaction);
            $debit = $this->money((float) $transaction->debit);
            $credit = $this->money((float) $transaction->credit);

            $cashReceived = 0.0;
            $iouReceived = 0.0;
            $bankTransfer = 0.0;
            $expenses = 0.0;

            if ($type === 'advance') {
                $iouReceived = $debit > 0 ? $debit : -$credit;
            } elseif ($type === 'transfer') {
                $bankTransfer = $debit > 0 ? $debit : -$credit;
            } elseif ($credit > 0) {
                $expenses = $credit;
            } elseif ($debit > 0) {
                $cashReceived = $debit;
            }

            if (! $this->hasAnyValue([$cashReceived, $iouReceived, $bankTransfer, $expenses])) {
                continue;
            }

            $totalTaka = $this->money($runningBalance + $cashReceived + $iouReceived + $bankTransfer);
            $closingBalance = $this->money($totalTaka - $expenses);
            $details = $this->resolveDetails($transaction);

            $rows[] = [
                'mr_no' => $this->referenceNo($transaction),
                'particulars' => $this->transactionLabel($transaction),
                'opening_balance' => $runningBalance,
                'cash_received' => $this->money($cashReceived),
                'iou_received' => $this->money($iouReceived),
                'bank_transfer' => $this->money($bankTransfer),
                'total_taka' => $totalTaka,
                'expenses' => $this->money($expenses),
                'closing_balance' => $closingBalance,
                'purpose'  => $details['purpose'],
                'property' => $details['property'],
                'unit'     => $details['unit'],
                'floor'    => $details['floor'],
            ];

            $runningBalance = $closingBalance;
        }

        return $rows;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, \App\Models\Transaction>  $transactions
     * @return array<int, array<string, mixed>>
     */
    protected function buildExpenseRows(Collection $transactions): array
    {
        return $transactions
            ->filter(function (Transaction|TransactionLine $transaction): bool {
                return (float) $transaction->credit > 0
                    && in_array($this->classifyTransactionType($transaction), ['expense', 'advance'], true);
            })
            ->map(function (Transaction|TransactionLine $transaction): array {
                $type = $this->classifyTransactionType($transaction);
                $isBank = $this->accountSubType($transaction) === 'bank';
                $amount = $this->money((float) $transaction->credit);
                $details = $this->resolveDetails($transaction);

                return [
                    'voucher_no' => $this->referenceNo($transaction),
                    'particulars' => $this->transactionLabel($transaction),
                    'req_no' => $transaction->transactionCategory?->name ?: Str::headline($type),
                    'amount' => $isBank ? 0.0 : $amount,
                    'bank_transfer' => $isBank ? $amount : 0.0,
                    'bank_name' => $isBank
                        ? ($transaction->account?->bankAccount?->bank_name ?: $transaction->account?->name ?: '-')
                        : '-',
                    'category_type' => $type,
                    'purpose'      => $details['purpose'],
                    'property'     => $details['property'],
                    'unit'         => $details['unit'],
                    'floor'        => $details['floor'],
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, \App\Models\Transaction>  $transactions
     * @return array<int, array<string, mixed>>
     */
    protected function buildIncomeRows(Collection $transactions): array
    {
        return $transactions
            ->filter(function (Transaction|TransactionLine $transaction): bool {
                return (float) $transaction->debit > 0
                    && in_array($this->classifyTransactionType($transaction), ['income', 'opening_balance', 'adjustment'], true);
            })
            ->map(function (Transaction|TransactionLine $transaction): array {
                $type  = $this->classifyTransactionType($transaction);
                $isBank = $this->accountSubType($transaction) === 'bank';
                $amount = $this->money((float) $transaction->debit);
                $details = $this->resolveDetails($transaction);

                return [
                    'ref_no'       => $this->referenceNo($transaction),
                    'particulars'  => $this->transactionLabel($transaction),
                    'category'     => $transaction->transactionCategory?->name ?: Str::headline($type),
                    'cash'         => $isBank ? 0.0 : $amount,
                    'bank_transfer'=> $isBank ? $amount : 0.0,
                    'bank_name'    => $isBank
                        ? ($transaction->account?->bankAccount?->bank_name ?: $transaction->account?->name ?: '-')
                        : '-',
                    'category_type'=> $type,
                    'purpose'      => $details['purpose'],
                    'property'     => $details['property'],
                    'unit'         => $details['unit'],
                    'floor'        => $details['floor'],
                ];
            })
            ->values()
            ->all();
    }

    protected function classifyTransactionType(Transaction|TransactionLine $transaction): string
    {
        // Categories were removed from transactions; classify purely from the
        // transaction's own type, falling back to the debit/credit sign.
        $type = $transaction->type;
        $rawType = strtolower((string) ($type instanceof \BackedEnum ? $type->value : $type));

        return in_array($rawType, ['income', 'expense', 'advance', 'transfer', 'adjustment', 'opening_balance'], true)
            ? $rawType
            : ($transaction->credit > 0 ? 'expense' : 'income');
    }

    protected function transactionLabel(Transaction|TransactionLine $transaction): string
    {
        $notes = trim((string) $transaction->notes);
        $name = trim((string) $transaction->name);
        $category = trim((string) optional($transaction->transactionCategory)->name);

        if ($notes !== '') {
            return $notes;
        }

        if ($name !== '') {
            return $name;
        }

        if ($category !== '') {
            return $category;
        }

        return Str::headline(str_replace('_', ' ', $this->classifyTransactionType($transaction)));
    }

    /**
     * Resolve the human "purpose" plus property / unit / floor behind a
     * transaction, so each daily-statement line can show what the money was for.
     *
     * @return array{purpose: string, property: ?string, unit: ?string, floor: ?string}
     */
    protected function resolveDetails(Transaction|TransactionLine $transaction): array
    {
        $reference = $transaction->reference;

        // Property-sale receipts reference a PaymentSchedule → sale → unit/property.
        $sale = $reference instanceof \App\Models\PaymentSchedule
            ? $reference->propertySale
            : null;

        $unit = $sale?->propertyUnit;

        // Purpose: schedule label for sale payments, else expense title, else
        // the transaction category. (transactionLabel() already covers notes/name.)
        $purpose = match (true) {
            $reference instanceof \App\Models\PaymentSchedule => $reference->label(),
            (bool) $transaction->expense => $transaction->expense->title ?: 'Expense',
            default => (string) ($transaction->transactionCategory?->name ?? ''),
        };

        $unitLabel = $unit
            ? (string) ($unit->code ?: $unit->unit_number ?: $unit->type ?: $unit->id)
            : null;

        $floor = $unit?->floor;
        $floorLabel = $floor
            ? (string) ($floor->label ?: $floor->code ?: '')
            : null;

        return [
            'purpose'  => trim($purpose) !== '' ? trim($purpose) : '',
            'property' => $sale?->property?->name,
            'unit'     => $unitLabel,
            'floor'    => $floorLabel !== '' ? $floorLabel : null,
        ];
    }

    protected function referenceNo(Transaction|TransactionLine $transaction): string
    {
        // Ref. No / V. No columns show the transaction id.
        return 'TXN-'.$transaction->id;
    }

    protected function statementRef(Carbon $reportDate, ?int $bankAccountId): string
    {
        return $bankAccountId
            ? 'DS-'.$reportDate->format('Ymd').'-B'.$bankAccountId
            : 'DS-'.$reportDate->format('Ymd');
    }

    protected function accountSubType(Transaction|TransactionLine $transaction): string
    {
        return strtolower((string) optional($transaction->account)->sub_type);
    }

    protected function usesCheque(Transaction|TransactionLine $transaction): bool
    {
        $method = $transaction->method ?? null;

        // method is an EntryMethod enum on Transaction; normalise to its string value.
        $methodValue = $method instanceof \App\Enums\Accounts\EntryMethod
            ? $method->value
            : (string) $method;

        return str_contains(strtolower($methodValue), 'cheque');
    }

    /**
     * @return array<int, string>
     */
    protected function supportedTransactionTypes(): array
    {
        return ['income', 'expense', 'advance', 'transfer', 'adjustment', 'opening_balance'];
    }

    /**
     * @param  array<int, float|int>  $values
     */
    protected function hasAnyValue(array $values): bool
    {
        foreach ($values as $value) {
            if (abs((float) $value) > 0.004) {
                return true;
            }
        }

        return false;
    }

    protected function money(float $value): float
    {
        return round($value, 2);
    }
}
