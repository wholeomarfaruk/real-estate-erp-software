<?php

namespace App\Services\Accounts;

use App\Enums\Accounts\AccountType;
use App\Models\Account;
use App\Models\AccountCollection;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Supplier;
use App\Models\SupplierLedger;
use App\Models\Transaction;
use App\Models\TransactionLine;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AccountReportService
{
    /**
     * @var \Illuminate\Support\Collection<int, \App\Models\Account>|null
     */
    protected ?Collection $accounts = null;

    /**
     * @var array<string, array<int, int>>
     */
    protected array $groupAccountIds = [];

    /**
     * @return array<string, array<string, mixed>>
     */
    public function definitions(): array
    {
        return [
            'asset' => [
                'title' => 'Assets Report',
                'filters' => ['account' => true, 'project' => false, 'supplier' => false, 'customer_name' => false],
            ],
            'payment' => [
                'title' => 'Payment Report',
                'filters' => ['account' => true, 'project' => true, 'supplier' => true, 'customer_name' => false],
            ],
            'collection' => [
                'title' => 'Collection Report',
                'filters' => ['account' => true, 'project' => true, 'supplier' => false, 'customer_name' => false],
            ],
            'expense' => [
                'title' => 'Expense Report',
                'filters' => ['account' => true, 'project' => true, 'supplier' => true, 'customer_name' => false],
            ],
            'cash-book' => [
                'title' => 'Cash Book',
                'filters' => ['account' => true, 'project' => false, 'supplier' => false, 'customer_name' => false],
            ],
            'bank-book' => [
                'title' => 'Bank Book',
                'filters' => ['account' => true, 'project' => false, 'supplier' => false, 'customer_name' => false],
            ],
            'supplier-ledger' => [
                'title' => 'Supplier Ledger',
                'filters' => ['account' => false, 'project' => false, 'supplier' => true, 'customer_name' => false],
            ],
            'customer-ledger' => [
                'title' => 'Customer Ledger',
                'filters' => ['account' => true, 'project' => false, 'supplier' => false, 'customer_name' => true],
            ],
            'trial-balance' => [
                'title' => 'Trial Balance',
                'filters' => ['account' => true, 'project' => false, 'supplier' => false, 'customer_name' => false],
            ],
            'profit-loss' => [
                'title' => 'Profit & Loss',
                'filters' => ['account' => true, 'project' => false, 'supplier' => false, 'customer_name' => false],
            ],
            'balance-sheet' => [
                'title' => 'Balance Sheet',
                'filters' => ['account' => true, 'project' => false, 'supplier' => false, 'customer_name' => false],
            ],
            'daily-summary' => [
                'title' => 'Daily Summary',
                'filters' => ['account' => true, 'project' => true, 'supplier' => true, 'customer_name' => false],
            ],
            'account-ledger' => [
                'title' => 'Account Ledger',
                'filters' => ['account' => true, 'project' => false, 'supplier' => false, 'customer_name' => false],
            ],
            'project-wise-expense' => [
                'title' => 'Project Wise Expense',
                'filters' => ['account' => true, 'project' => true, 'supplier' => false, 'customer_name' => false],
            ],
            'liability' => [
                'title' => 'Liability Report',
                'filters' => ['account' => true, 'project' => false, 'supplier' => false, 'customer_name' => false],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function definition(string $report): array
    {
        $definition = $this->definitions()[$report] ?? null;

        abort_unless($definition, 404, 'Report not found.');

        return $definition;
    }

    public function hasReport(string $report): bool
    {
        return array_key_exists($report, $this->definitions());
    }

    public function supportsPdfExport(): bool
    {
        return class_exists(\Barryvdh\DomPDF\Facade\Pdf::class);
    }

    public function supportsExcelPackage(): bool
    {
        return class_exists(\Maatwebsite\Excel\Facades\Excel::class);
    }

    /**
     * @return \Illuminate\Support\Collection<int, \App\Models\Account>
     */
    public function getAccounts(): Collection
    {
        return Account::query()
            ->where('is_active', true)
            ->orderBy('type')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'type']);
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
     * @return \Illuminate\Support\Collection<int, \App\Models\Supplier>
     */
    public function getSuppliers(): Collection
    {
        if (! class_exists(Supplier::class) || ! Schema::hasTable('suppliers')) {
            return collect();
        }

        return Supplier::query()
            ->active()
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * @return \Illuminate\Support\Collection<int, string>
     */
    public function getCustomerNames(): Collection
    {
        $collectionNames = AccountCollection::query()
            ->whereNotNull('payer_name')
            ->pluck('payer_name');

        $paymentNames = Payment::query()
            ->where('reference_type', 'customer')
            ->whereNotNull('payee_name')
            ->pluck('payee_name');

        return $collectionNames
            ->merge($paymentNames)
            ->filter(fn (mixed $name): bool => filled($name))
            ->map(fn (mixed $name): string => trim((string) $name))
            ->filter()
            ->unique()
            ->sort()
            ->values();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function build(string $report, array $filters): array
    {
        $definition = $this->definition($report);
        $normalized = $this->normalizeFilters($filters);

        return match ($report) {
            'asset' => $this->buildAssetReport($definition['title'], $normalized),
            'payment' => $this->buildPaymentReport($definition['title'], $normalized),
            'collection' => $this->buildCollectionReport($definition['title'], $normalized),
            'expense' => $this->buildExpenseReport($definition['title'], $normalized),
            'cash-book' => $this->buildCashBookReport($definition['title'], $normalized),
            'bank-book' => $this->buildBankBookReport($definition['title'], $normalized),
            'supplier-ledger' => $this->buildSupplierLedgerReport($definition['title'], $normalized),
            'customer-ledger' => $this->buildCustomerLedgerReport($definition['title'], $normalized),
            'trial-balance' => $this->buildTrialBalanceReport($definition['title'], $normalized),
            'profit-loss' => $this->buildProfitLossReport($definition['title'], $normalized),
            'balance-sheet' => $this->buildBalanceSheetReport($definition['title'], $normalized),
            'daily-summary' => $this->buildDailySummaryReport($definition['title'], $normalized),
            'account-ledger' => $this->buildAccountLedgerReport($definition['title'], $normalized),
            'project-wise-expense' => $this->buildProjectWiseExpenseReport($definition['title'], $normalized),
            'liability' => $this->buildLiabilityReport($definition['title'], $normalized),
            default => abort(404, 'Report not found.'),
        };
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{from:\Carbon\Carbon,to:\Carbon\Carbon,account_id:?int,project_id:?int,supplier_id:?int,customer_name:?string}
     */
    protected function normalizeFilters(array $filters): array
    {
        $today = now();
        $from = $this->parseDate($filters['from_date'] ?? null, $today->copy()->startOfMonth()->toDateString());
        $to = $this->parseDate($filters['to_date'] ?? null, $today->copy()->toDateString());

        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        $customerName = trim((string) ($filters['customer_name'] ?? ''));

        return [
            'from' => $from->startOfDay(),
            'to' => $to->startOfDay(),
            'account_id' => $this->nullableInt($filters['account_id'] ?? null),
            'project_id' => $this->nullableInt($filters['project_id'] ?? null),
            'supplier_id' => $this->nullableInt($filters['supplier_id'] ?? null),
            'customer_name' => $customerName !== '' ? $customerName : null,
        ];
    }

    /**
     * @param  array{from:\Carbon\Carbon,to:\Carbon\Carbon,account_id:?int,project_id:?int,supplier_id:?int,customer_name:?string}  $filters
     * @return array<string, mixed>
     */
    protected function buildExpenseReport(string $title, array $filters): array
    {
        $expenses = Expense::query()
            ->with([
                'expenseAccount:id,name,code',
                'paymentAccount:id,name,code',
            ])
            ->when($filters['account_id'], function (Builder $query) use ($filters): void {
                $query->where(function (Builder $subQuery) use ($filters): void {
                    $subQuery->where('expense_account_id', $filters['account_id'])
                        ->orWhere('payment_account_id', $filters['account_id']);
                });
            })
            ->tap(fn (Builder $query) => $this->applyDateBetween($query, 'date', $filters))
            ->tap(fn (Builder $query) => $this->applyReferenceFilter($query, 'project', $filters['project_id']))
            ->tap(fn (Builder $query) => $this->applyReferenceFilter($query, 'supplier', $filters['supplier_id']))
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        $rows = $expenses->map(function (Expense $expense): array {
            $note = collect([
                $expense->title,
                $expense->notes,
            ])->filter()->implode(' - ');

            return [
                'date' => optional($expense->date)->format('d M Y') ?: '-',
                'account' => $expense->expenseAccount?->name ?? '-',
                'amount' => $this->money((float) $expense->amount),
                'note' => $note !== '' ? $note : '-',
            ];
        })->all();

        return $this->reportPayload(
            title: $title,
            filters: $filters,
            columns: [
                ['key' => 'date', 'label' => 'Date'],
                ['key' => 'account', 'label' => 'Account'],
                ['key' => 'amount', 'label' => 'Amount', 'align' => 'right'],
                ['key' => 'note', 'label' => 'Note'],
            ],
            rows: $rows,
            footer: [
                'date' => '',
                'account' => 'Total',
                'amount' => $this->money((float) $expenses->sum('amount')),
                'note' => '',
            ]
        );
    }

    /**
     * @param  array{from:\Carbon\Carbon,to:\Carbon\Carbon,account_id:?int,project_id:?int,supplier_id:?int,customer_name:?string}  $filters
     * @return array<string, mixed>
     */
    protected function buildPaymentReport(string $title, array $filters): array
    {
        $payments = Payment::query()
            ->with([
                'purposeAccount:id,name,code',
                'paymentAccount:id,name,code',
            ])
            ->when($filters['account_id'], function (Builder $query) use ($filters): void {
                $query->where(function (Builder $subQuery) use ($filters): void {
                    $subQuery->where('purpose_account_id', $filters['account_id'])
                        ->orWhere('payment_account_id', $filters['account_id']);
                });
            })
            ->tap(fn (Builder $query) => $this->applyDateBetween($query, 'date', $filters))
            ->tap(fn (Builder $query) => $this->applyReferenceFilter($query, 'project', $filters['project_id']))
            ->tap(fn (Builder $query) => $this->applyReferenceFilter($query, 'supplier', $filters['supplier_id']))
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        $rows = $payments->map(function (Payment $payment): array {
            return [
                'date' => optional($payment->date)->format('d M Y') ?: '-',
                'pay_to' => $payment->payee_name ?: '-',
                'account' => $payment->purposeAccount?->name ?? '-',
                'amount' => $this->money((float) $payment->amount),
                'reference' => $this->entryReference(
                    documentNo: $payment->payment_no,
                    referenceType: $payment->reference_type,
                    referenceId: $payment->reference_id
                ),
            ];
        })->all();

        return $this->reportPayload(
            title: $title,
            filters: $filters,
            columns: [
                ['key' => 'date', 'label' => 'Date'],
                ['key' => 'pay_to', 'label' => 'Pay To'],
                ['key' => 'account', 'label' => 'Account'],
                ['key' => 'amount', 'label' => 'Amount', 'align' => 'right'],
                ['key' => 'reference', 'label' => 'Reference'],
            ],
            rows: $rows,
            footer: [
                'date' => '',
                'pay_to' => '',
                'account' => 'Total',
                'amount' => $this->money((float) $payments->sum('amount')),
                'reference' => '',
            ]
        );
    }

    /**
     * @param  array{from:\Carbon\Carbon,to:\Carbon\Carbon,account_id:?int,project_id:?int,supplier_id:?int,customer_name:?string}  $filters
     * @return array<string, mixed>
     */
    protected function buildCollectionReport(string $title, array $filters): array
    {
        $collections = AccountCollection::query()
            ->with([
                'targetAccount:id,name,code',
                'collectionAccount:id,name,code',
            ])
            ->when($filters['account_id'], function (Builder $query) use ($filters): void {
                $query->where(function (Builder $subQuery) use ($filters): void {
                    $subQuery->where('target_account_id', $filters['account_id'])
                        ->orWhere('collection_account_id', $filters['account_id']);
                });
            })
            ->tap(fn (Builder $query) => $this->applyDateBetween($query, 'date', $filters))
            ->tap(fn (Builder $query) => $this->applyReferenceFilter($query, 'project', $filters['project_id']))
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        $rows = $collections->map(function (AccountCollection $collection): array {
            return [
                'date' => optional($collection->date)->format('d M Y') ?: '-',
                'received_from' => $collection->payer_name ?: '-',
                'account' => $collection->targetAccount?->name ?? '-',
                'amount' => $this->money((float) $collection->amount),
                'reference' => $this->entryReference(
                    documentNo: $collection->collection_no,
                    referenceType: $collection->reference_type,
                    referenceId: $collection->reference_id
                ),
            ];
        })->all();

        return $this->reportPayload(
            title: $title,
            filters: $filters,
            columns: [
                ['key' => 'date', 'label' => 'Date'],
                ['key' => 'received_from', 'label' => 'Received From'],
                ['key' => 'account', 'label' => 'Account'],
                ['key' => 'amount', 'label' => 'Amount', 'align' => 'right'],
                ['key' => 'reference', 'label' => 'Reference'],
            ],
            rows: $rows,
            footer: [
                'date' => '',
                'received_from' => '',
                'account' => 'Total',
                'amount' => $this->money((float) $collections->sum('amount')),
                'reference' => '',
            ]
        );
    }

    /**
     * @param  array{from:\Carbon\Carbon,to:\Carbon\Carbon,account_id:?int,project_id:?int,supplier_id:?int,customer_name:?string}  $filters
     * @return array<string, mixed>
     */
    protected function buildAssetReport(string $title, array $filters): array
    {
        $balances = $this->accountBalanceRows($filters, [AccountType::ASSET->value], 'range');

        $rows = $balances->map(function (object $row): array {
            return [
                'account' => $this->accountLabel($row->code, $row->name),
                'debit' => $this->money((float) $row->total_debit),
                'credit' => $this->money((float) $row->total_credit),
                'balance' => $this->money(max(0, (float) $row->total_debit - (float) $row->total_credit)),
            ];
        })->all();

        return $this->reportPayload(
            title: $title,
            filters: $filters,
            columns: [
                ['key' => 'account', 'label' => 'Account'],
                ['key' => 'debit', 'label' => 'Debit', 'align' => 'right'],
                ['key' => 'credit', 'label' => 'Credit', 'align' => 'right'],
                ['key' => 'balance', 'label' => 'Balance', 'align' => 'right'],
            ],
            rows: $rows,
            footer: [
                'account' => 'Total',
                'debit' => $this->money((float) $balances->sum('total_debit')),
                'credit' => $this->money((float) $balances->sum('total_credit')),
                'balance' => $this->money((float) $balances->sum(fn (object $row): float => max(0, (float) $row->total_debit - (float) $row->total_credit))),
            ]
        );
    }

    /**
     * @param  array{from:\Carbon\Carbon,to:\Carbon\Carbon,account_id:?int,project_id:?int,supplier_id:?int,customer_name:?string}  $filters
     * @return array<string, mixed>
     */
    protected function buildLiabilityReport(string $title, array $filters): array
    {
        $balances = $this->accountBalanceRows($filters, [AccountType::LIABILITY->value], 'range');

        $rows = $balances->map(function (object $row): array {
            return [
                'account' => $this->accountLabel($row->code, $row->name),
                'debit' => $this->money((float) $row->total_debit),
                'credit' => $this->money((float) $row->total_credit),
                'balance' => $this->money(max(0, (float) $row->total_credit - (float) $row->total_debit)),
            ];
        })->all();

        return $this->reportPayload(
            title: $title,
            filters: $filters,
            columns: [
                ['key' => 'account', 'label' => 'Account'],
                ['key' => 'debit', 'label' => 'Debit', 'align' => 'right'],
                ['key' => 'credit', 'label' => 'Credit', 'align' => 'right'],
                ['key' => 'balance', 'label' => 'Balance', 'align' => 'right'],
            ],
            rows: $rows,
            footer: [
                'account' => 'Total',
                'debit' => $this->money((float) $balances->sum('total_debit')),
                'credit' => $this->money((float) $balances->sum('total_credit')),
                'balance' => $this->money((float) $balances->sum(fn (object $row): float => max(0, (float) $row->total_credit - (float) $row->total_debit))),
            ]
        );
    }

    /**
     * @param  array{from:\Carbon\Carbon,to:\Carbon\Carbon,account_id:?int,project_id:?int,supplier_id:?int,customer_name:?string}  $filters
     * @return array<string, mixed>
     */
    protected function buildCashBookReport(string $title, array $filters): array
    {
        $selectedIds = $this->selectedGroupedAccountIds('cash', $filters['account_id']);
        $lines = $this->bookLines($selectedIds, $filters);
        $runningBalance = $this->openingBalanceForAccounts($selectedIds, $filters);

        $rows = [];

        foreach ($lines as $line) {
            $runningBalance += (float) $line->debit - (float) $line->credit;

            $rows[] = [
                'date' => optional($line->transaction?->date)->format('d M Y') ?: '-',
                'account' => $line->account?->name ?? '-',
                'reference' => $this->transactionReference($line->transaction),
                'particulars' => $this->lineParticulars($line, $selectedIds),
                'debit' => $this->money((float) $line->debit),
                'credit' => $this->money((float) $line->credit),
                'balance' => $this->money($runningBalance),
            ];
        }

        return $this->reportPayload(
            title: $title,
            filters: $filters,
            columns: [
                ['key' => 'date', 'label' => 'Date'],
                ['key' => 'account', 'label' => 'Account'],
                ['key' => 'reference', 'label' => 'Reference'],
                ['key' => 'particulars', 'label' => 'Particulars'],
                ['key' => 'debit', 'label' => 'Debit', 'align' => 'right'],
                ['key' => 'credit', 'label' => 'Credit', 'align' => 'right'],
                ['key' => 'balance', 'label' => 'Running Balance', 'align' => 'right'],
            ],
            rows: $rows,
            footer: [
                'date' => '',
                'account' => '',
                'reference' => '',
                'particulars' => 'Closing Balance',
                'debit' => '',
                'credit' => '',
                'balance' => $this->money($runningBalance),
            ]
        );
    }

    /**
     * @param  array{from:\Carbon\Carbon,to:\Carbon\Carbon,account_id:?int,project_id:?int,supplier_id:?int,customer_name:?string}  $filters
     * @return array<string, mixed>
     */
    protected function buildBankBookReport(string $title, array $filters): array
    {
        $selectedIds = $this->selectedGroupedAccountIds('bank', $filters['account_id']);
        $lines = $this->bookLines($selectedIds, $filters);
        $runningBalance = $this->openingBalanceForAccounts($selectedIds, $filters);

        $rows = [];

        foreach ($lines as $line) {
            $runningBalance += (float) $line->debit - (float) $line->credit;

            $rows[] = [
                'date' => optional($line->transaction?->date)->format('d M Y') ?: '-',
                'account' => $line->account?->name ?? '-',
                'reference' => $this->transactionReference($line->transaction),
                'particulars' => $this->lineParticulars($line, $selectedIds),
                'debit' => $this->money((float) $line->debit),
                'credit' => $this->money((float) $line->credit),
                'balance' => $this->money($runningBalance),
            ];
        }

        return $this->reportPayload(
            title: $title,
            filters: $filters,
            columns: [
                ['key' => 'date', 'label' => 'Date'],
                ['key' => 'account', 'label' => 'Account'],
                ['key' => 'reference', 'label' => 'Reference'],
                ['key' => 'particulars', 'label' => 'Particulars'],
                ['key' => 'debit', 'label' => 'Debit', 'align' => 'right'],
                ['key' => 'credit', 'label' => 'Credit', 'align' => 'right'],
                ['key' => 'balance', 'label' => 'Running Balance', 'align' => 'right'],
            ],
            rows: $rows,
            footer: [
                'date' => '',
                'account' => '',
                'reference' => '',
                'particulars' => 'Closing Balance',
                'debit' => '',
                'credit' => '',
                'balance' => $this->money($runningBalance),
            ]
        );
    }

    /**
     * @param  array{from:\Carbon\Carbon,to:\Carbon\Carbon,account_id:?int,project_id:?int,supplier_id:?int,customer_name:?string}  $filters
     * @return array<string, mixed>
     */
    protected function buildSupplierLedgerReport(string $title, array $filters): array
    {
        $ledgerEntries = SupplierLedger::query()
            ->with('supplier:id,name')
            ->when($filters['supplier_id'], fn (Builder $query): Builder => $query->where('supplier_id', $filters['supplier_id']))
            ->when($filters['from'], fn (Builder $query): Builder => $query->whereDate('transaction_date', '>=', $filters['from']->toDateString()))
            ->when($filters['to'], fn (Builder $query): Builder => $query->whereDate('transaction_date', '<=', $filters['to']->toDateString()))
            ->orderBy('supplier_id')
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();

        $rows = $ledgerEntries->map(function (SupplierLedger $entry): array {
            return [
                'date' => optional($entry->transaction_date)->format('d M Y') ?: '-',
                'supplier' => $entry->supplier?->name ?? '-',
                'reference' => $entry->reference_no ?: $this->referenceLabel($entry->reference_type, $entry->reference_id),
                'description' => $entry->description ?: '-',
                'debit' => $this->money((float) $entry->debit),
                'credit' => $this->money((float) $entry->credit),
                'balance' => $this->money((float) $entry->balance),
            ];
        })->all();

        return $this->reportPayload(
            title: $title,
            filters: $filters,
            columns: [
                ['key' => 'date', 'label' => 'Date'],
                ['key' => 'supplier', 'label' => 'Supplier'],
                ['key' => 'reference', 'label' => 'Reference'],
                ['key' => 'description', 'label' => 'Description'],
                ['key' => 'debit', 'label' => 'Debit', 'align' => 'right'],
                ['key' => 'credit', 'label' => 'Credit', 'align' => 'right'],
                ['key' => 'balance', 'label' => 'Balance', 'align' => 'right'],
            ],
            rows: $rows,
            footer: [
                'date' => '',
                'supplier' => '',
                'reference' => '',
                'description' => 'Total',
                'debit' => $this->money((float) $ledgerEntries->sum('debit')),
                'credit' => $this->money((float) $ledgerEntries->sum('credit')),
                'balance' => $this->money((float) ($ledgerEntries->last()?->balance ?? 0)),
            ]
        );
    }

    /**
     * @param  array{from:\Carbon\Carbon,to:\Carbon\Carbon,account_id:?int,project_id:?int,supplier_id:?int,customer_name:?string}  $filters
     * @return array<string, mixed>
     */
    protected function buildCustomerLedgerReport(string $title, array $filters): array
    {
        $collectionEntries = AccountCollection::query()
            ->with(['targetAccount:id,name,code'])
            ->when($filters['account_id'], function (Builder $query) use ($filters): void {
                $query->where(function (Builder $subQuery) use ($filters): void {
                    $subQuery->where('target_account_id', $filters['account_id'])
                        ->orWhere('collection_account_id', $filters['account_id']);
                });
            })
            ->tap(fn (Builder $query) => $this->applyDateBetween($query, 'date', $filters))
            ->when($filters['customer_name'], fn (Builder $query): Builder => $query->where('payer_name', $filters['customer_name']))
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        $paymentEntries = Payment::query()
            ->with(['purposeAccount:id,name,code'])
            ->where('reference_type', 'customer')
            ->when($filters['account_id'], function (Builder $query) use ($filters): void {
                $query->where(function (Builder $subQuery) use ($filters): void {
                    $subQuery->where('purpose_account_id', $filters['account_id'])
                        ->orWhere('payment_account_id', $filters['account_id']);
                });
            })
            ->tap(fn (Builder $query) => $this->applyDateBetween($query, 'date', $filters))
            ->when($filters['customer_name'], fn (Builder $query): Builder => $query->where('payee_name', $filters['customer_name']))
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        $entries = collect();

        foreach ($collectionEntries as $collection) {
            $entries->push([
                'date' => optional($collection->date)->toDateString(),
                'customer' => $collection->payer_name ?: 'Customer',
                'reference' => $this->entryReference($collection->collection_no, $collection->reference_type, $collection->reference_id),
                'debit_raw' => 0.0,
                'credit_raw' => (float) $collection->amount,
                'description' => $collection->targetAccount?->name ?? 'Collection',
                'sequence' => 'collection-'.(int) $collection->id,
            ]);
        }

        foreach ($paymentEntries as $payment) {
            $entries->push([
                'date' => optional($payment->date)->toDateString(),
                'customer' => $payment->payee_name ?: 'Customer',
                'reference' => $this->entryReference($payment->payment_no, $payment->reference_type, $payment->reference_id),
                'debit_raw' => (float) $payment->amount,
                'credit_raw' => 0.0,
                'description' => $payment->purposeAccount?->name ?? 'Payment',
                'sequence' => 'payment-'.(int) $payment->id,
            ]);
        }

        $sorted = $entries
            ->sortBy([
                ['customer', 'asc'],
                ['date', 'asc'],
                ['sequence', 'asc'],
            ])
            ->values();

        $runningBalances = [];
        $rows = [];

        foreach ($sorted as $entry) {
            $customer = (string) $entry['customer'];
            $runningBalances[$customer] = ($runningBalances[$customer] ?? 0) + (float) $entry['debit_raw'] - (float) $entry['credit_raw'];

            $rows[] = [
                'date' => $entry['date'] ? Carbon::parse($entry['date'])->format('d M Y') : '-',
                'customer' => $customer,
                'reference' => (string) $entry['reference'],
                'description' => (string) $entry['description'],
                'debit' => $this->money((float) $entry['debit_raw']),
                'credit' => $this->money((float) $entry['credit_raw']),
                'balance' => $this->money((float) $runningBalances[$customer]),
            ];
        }

        return $this->reportPayload(
            title: $title,
            filters: $filters,
            columns: [
                ['key' => 'date', 'label' => 'Date'],
                ['key' => 'customer', 'label' => 'Customer'],
                ['key' => 'reference', 'label' => 'Reference'],
                ['key' => 'description', 'label' => 'Description'],
                ['key' => 'debit', 'label' => 'Debit', 'align' => 'right'],
                ['key' => 'credit', 'label' => 'Credit', 'align' => 'right'],
                ['key' => 'balance', 'label' => 'Balance', 'align' => 'right'],
            ],
            rows: $rows,
            footer: [
                'date' => '',
                'customer' => '',
                'reference' => '',
                'description' => 'Total',
                'debit' => $this->money((float) $paymentEntries->sum('amount')),
                'credit' => $this->money((float) $collectionEntries->sum('amount')),
                'balance' => '',
            ]
        );
    }

    /**
     * @param  array{from:\Carbon\Carbon,to:\Carbon\Carbon,account_id:?int,project_id:?int,supplier_id:?int,customer_name:?string}  $filters
     * @return array<string, mixed>
     */
    protected function buildTrialBalanceReport(string $title, array $filters): array
    {
        $balances = $this->accountBalanceRows($filters, null, 'range');

        $rows = $balances->map(function (object $row): array {
            $typeLabel = $row->type instanceof AccountType
                ? $row->type->label()
                : Str::headline((string) $row->type);

            return [
                'account' => $this->accountLabel($row->code, $row->name),
                'type' => $typeLabel,
                'debit' => $this->money((float) $row->total_debit),
                'credit' => $this->money((float) $row->total_credit),
            ];
        })->all();

        return $this->reportPayload(
            title: $title,
            filters: $filters,
            columns: [
                ['key' => 'account', 'label' => 'Account'],
                ['key' => 'type', 'label' => 'Type'],
                ['key' => 'debit', 'label' => 'Debit', 'align' => 'right'],
                ['key' => 'credit', 'label' => 'Credit', 'align' => 'right'],
            ],
            rows: $rows,
            footer: [
                'account' => '',
                'type' => 'Total',
                'debit' => $this->money((float) $balances->sum('total_debit')),
                'credit' => $this->money((float) $balances->sum('total_credit')),
            ]
        );
    }

    /**
     * @param  array{from:\Carbon\Carbon,to:\Carbon\Carbon,account_id:?int,project_id:?int,supplier_id:?int,customer_name:?string}  $filters
     * @return array<string, mixed>
     */
    protected function buildProfitLossReport(string $title, array $filters): array
    {
        $balances = $this->accountBalanceRows($filters, [AccountType::INCOME->value, AccountType::EXPENSE->value], 'range');

        $incomeRows = $balances->where('type', AccountType::INCOME->value);
        $expenseRows = $balances->where('type', AccountType::EXPENSE->value);

        $rows = [];

        foreach ($incomeRows as $row) {
            $rows[] = [
                'section' => 'Income',
                'account' => $this->accountLabel($row->code, $row->name),
                'amount' => $this->money(max(0, (float) $row->total_credit - (float) $row->total_debit)),
            ];
        }

        $totalIncome = (float) $incomeRows->sum(fn (object $row): float => max(0, (float) $row->total_credit - (float) $row->total_debit));
        $rows[] = [
            'section' => 'Income',
            'account' => 'Total Income',
            'amount' => $this->money($totalIncome),
            '__row_class' => 'bg-gray-50 font-semibold',
        ];

        foreach ($expenseRows as $row) {
            $rows[] = [
                'section' => 'Expense',
                'account' => $this->accountLabel($row->code, $row->name),
                'amount' => $this->money(max(0, (float) $row->total_debit - (float) $row->total_credit)),
            ];
        }

        $totalExpense = (float) $expenseRows->sum(fn (object $row): float => max(0, (float) $row->total_debit - (float) $row->total_credit));
        $netAmount = $totalIncome - $totalExpense;

        $rows[] = [
            'section' => 'Expense',
            'account' => 'Total Expense',
            'amount' => $this->money($totalExpense),
            '__row_class' => 'bg-gray-50 font-semibold',
        ];
        $rows[] = [
            'section' => 'Result',
            'account' => $netAmount >= 0 ? 'Net Profit' : 'Net Loss',
            'amount' => $this->money(abs($netAmount)),
            '__row_class' => 'bg-gray-100 font-semibold',
        ];

        return $this->reportPayload(
            title: $title,
            filters: $filters,
            columns: [
                ['key' => 'section', 'label' => 'Section'],
                ['key' => 'account', 'label' => 'Account'],
                ['key' => 'amount', 'label' => 'Amount', 'align' => 'right'],
            ],
            rows: $rows,
        );
    }

    /**
     * @param  array{from:\Carbon\Carbon,to:\Carbon\Carbon,account_id:?int,project_id:?int,supplier_id:?int,customer_name:?string}  $filters
     * @return array<string, mixed>
     */
    protected function buildBalanceSheetReport(string $title, array $filters): array
    {
        $balances = $this->accountBalanceRows($filters, [AccountType::ASSET->value, AccountType::LIABILITY->value, AccountType::EQUITY->value], 'through');

        $assets = $balances->where('type', AccountType::ASSET->value);
        $liabilities = $balances->where('type', AccountType::LIABILITY->value);
        $equity = $balances->where('type', AccountType::EQUITY->value);

        $rows = [];

        foreach ($assets as $row) {
            $rows[] = [
                'section' => 'Assets',
                'account' => $this->accountLabel($row->code, $row->name),
                'amount' => $this->money(max(0, (float) $row->total_debit - (float) $row->total_credit)),
            ];
        }

        $totalAssets = (float) $assets->sum(fn (object $row): float => max(0, (float) $row->total_debit - (float) $row->total_credit));
        $rows[] = [
            'section' => 'Assets',
            'account' => 'Total Assets',
            'amount' => $this->money($totalAssets),
            '__row_class' => 'bg-gray-50 font-semibold',
        ];

        foreach ($liabilities as $row) {
            $rows[] = [
                'section' => 'Liabilities',
                'account' => $this->accountLabel($row->code, $row->name),
                'amount' => $this->money(max(0, (float) $row->total_credit - (float) $row->total_debit)),
            ];
        }

        $totalLiabilities = (float) $liabilities->sum(fn (object $row): float => max(0, (float) $row->total_credit - (float) $row->total_debit));
        $rows[] = [
            'section' => 'Liabilities',
            'account' => 'Total Liabilities',
            'amount' => $this->money($totalLiabilities),
            '__row_class' => 'bg-gray-50 font-semibold',
        ];

        foreach ($equity as $row) {
            $rows[] = [
                'section' => 'Equity',
                'account' => $this->accountLabel($row->code, $row->name),
                'amount' => $this->money(max(0, (float) $row->total_credit - (float) $row->total_debit)),
            ];
        }

        $totalEquity = (float) $equity->sum(fn (object $row): float => max(0, (float) $row->total_credit - (float) $row->total_debit));
        $rows[] = [
            'section' => 'Equity',
            'account' => 'Total Equity',
            'amount' => $this->money($totalEquity),
            '__row_class' => 'bg-gray-50 font-semibold',
        ];
        $rows[] = [
            'section' => 'Equation',
            'account' => 'Liabilities + Equity',
            'amount' => $this->money($totalLiabilities + $totalEquity),
            '__row_class' => 'bg-gray-100 font-semibold',
        ];

        return $this->reportPayload(
            title: $title,
            filters: $filters,
            columns: [
                ['key' => 'section', 'label' => 'Section'],
                ['key' => 'account', 'label' => 'Account'],
                ['key' => 'amount', 'label' => 'Amount', 'align' => 'right'],
            ],
            rows: $rows,
        );
    }

    /**
     * @param  array{from:\Carbon\Carbon,to:\Carbon\Carbon,account_id:?int,project_id:?int,supplier_id:?int,customer_name:?string}  $filters
     * @return array<string, mixed>
     */
    protected function buildDailySummaryReport(string $title, array $filters): array
    {
        $collections = AccountCollection::query()
            ->selectRaw('date, COALESCE(SUM(amount), 0) as total_amount')
            ->when($filters['account_id'], function (Builder $query) use ($filters): void {
                $query->where(function (Builder $subQuery) use ($filters): void {
                    $subQuery->where('target_account_id', $filters['account_id'])
                        ->orWhere('collection_account_id', $filters['account_id']);
                });
            })
            ->tap(fn (Builder $query) => $this->applyDateBetween($query, 'date', $filters))
            ->tap(fn (Builder $query) => $this->applyReferenceFilter($query, 'project', $filters['project_id']))
            ->groupBy('date')
            ->pluck('total_amount', 'date');

        $payments = Payment::query()
            ->selectRaw('date, COALESCE(SUM(amount), 0) as total_amount')
            ->when($filters['account_id'], function (Builder $query) use ($filters): void {
                $query->where(function (Builder $subQuery) use ($filters): void {
                    $subQuery->where('purpose_account_id', $filters['account_id'])
                        ->orWhere('payment_account_id', $filters['account_id']);
                });
            })
            ->tap(fn (Builder $query) => $this->applyDateBetween($query, 'date', $filters))
            ->tap(fn (Builder $query) => $this->applyReferenceFilter($query, 'project', $filters['project_id']))
            ->tap(fn (Builder $query) => $this->applyReferenceFilter($query, 'supplier', $filters['supplier_id']))
            ->groupBy('date')
            ->pluck('total_amount', 'date');

        $expenses = Expense::query()
            ->selectRaw('date, COALESCE(SUM(amount), 0) as total_amount')
            ->when($filters['account_id'], function (Builder $query) use ($filters): void {
                $query->where(function (Builder $subQuery) use ($filters): void {
                    $subQuery->where('expense_account_id', $filters['account_id'])
                        ->orWhere('payment_account_id', $filters['account_id']);
                });
            })
            ->tap(fn (Builder $query) => $this->applyDateBetween($query, 'date', $filters))
            ->tap(fn (Builder $query) => $this->applyReferenceFilter($query, 'project', $filters['project_id']))
            ->tap(fn (Builder $query) => $this->applyReferenceFilter($query, 'supplier', $filters['supplier_id']))
            ->groupBy('date')
            ->pluck('total_amount', 'date');

        $dates = collect($collections->keys())
            ->merge($payments->keys())
            ->merge($expenses->keys())
            ->unique()
            ->sort()
            ->values();

        $rows = $dates->map(function (string $date) use ($collections, $payments, $expenses): array {
            $collectionTotal = (float) ($collections[$date] ?? 0);
            $paymentTotal = (float) ($payments[$date] ?? 0);
            $expenseTotal = (float) ($expenses[$date] ?? 0);

            return [
                'date' => Carbon::parse($date)->format('d M Y'),
                'collection' => $this->money($collectionTotal),
                'payment' => $this->money($paymentTotal),
                'expense' => $this->money($expenseTotal),
                'net' => $this->money($collectionTotal - $paymentTotal - $expenseTotal),
            ];
        })->all();

        return $this->reportPayload(
            title: $title,
            filters: $filters,
            columns: [
                ['key' => 'date', 'label' => 'Date'],
                ['key' => 'collection', 'label' => 'Total Collection', 'align' => 'right'],
                ['key' => 'payment', 'label' => 'Total Payment', 'align' => 'right'],
                ['key' => 'expense', 'label' => 'Total Expense', 'align' => 'right'],
                ['key' => 'net', 'label' => 'Net Movement', 'align' => 'right'],
            ],
            rows: $rows,
            footer: [
                'date' => 'Total',
                'collection' => $this->money((float) $collections->sum()),
                'payment' => $this->money((float) $payments->sum()),
                'expense' => $this->money((float) $expenses->sum()),
                'net' => $this->money((float) $collections->sum() - (float) $payments->sum() - (float) $expenses->sum()),
            ]
        );
    }

    /**
     * @param  array{from:\Carbon\Carbon,to:\Carbon\Carbon,account_id:?int,project_id:?int,supplier_id:?int,customer_name:?string}  $filters
     * @return array<string, mixed>
     */
    protected function buildAccountLedgerReport(string $title, array $filters): array
    {
        $query = TransactionLine::query()
            ->select('transaction_lines.*')
            ->join('transactions', 'transactions.id', '=', 'transaction_lines.transaction_id')
            ->with([
                'account:id,name,code',
                'transaction:id,date,type,reference_type,reference_id,notes',
                'transaction.payment:id,transaction_id,payment_no,payee_name,reference_type,reference_id',
                'transaction.collection:id,transaction_id,collection_no,payer_name,reference_type,reference_id',
                'transaction.expense:id,transaction_id,expense_no,title,reference_type,reference_id',
                'transaction.lines:id,transaction_id,account_id,debit,credit,description',
                'transaction.lines.account:id,name,code',
            ])
            ->when($filters['account_id'], fn (Builder $builder): Builder => $builder->where('transaction_lines.account_id', $filters['account_id']))
            ->whereDate('transactions.date', '>=', $filters['from']->toDateString())
            ->whereDate('transactions.date', '<=', $filters['to']->toDateString())
            ->orderBy('transaction_lines.account_id')
            ->orderBy('transactions.date')
            ->orderBy('transaction_lines.id');

        $lines = $query->get();
        $openingBalances = $this->openingBalanceMapForLedger($filters);
        $runningBalances = $openingBalances;
        $selectedIds = $filters['account_id'] ? [(int) $filters['account_id']] : [];

        $rows = [];

        foreach ($lines as $line) {
            $accountId = (int) $line->account_id;
            $runningBalances[$accountId] = ($runningBalances[$accountId] ?? 0) + (float) $line->debit - (float) $line->credit;

            $rows[] = [
                'date' => optional($line->transaction?->date)->format('d M Y') ?: '-',
                'account' => $this->accountLabel($line->account?->code, $line->account?->name),
                'reference' => $this->transactionReference($line->transaction),
                'type' => $line->transaction?->type?->label() ?? Str::headline((string) $line->transaction?->type),
                'description' => $this->lineParticulars($line, $selectedIds),
                'debit' => $this->money((float) $line->debit),
                'credit' => $this->money((float) $line->credit),
                'balance' => $this->money((float) $runningBalances[$accountId]),
            ];
        }

        return $this->reportPayload(
            title: $title,
            filters: $filters,
            columns: [
                ['key' => 'date', 'label' => 'Date'],
                ['key' => 'account', 'label' => 'Account'],
                ['key' => 'reference', 'label' => 'Reference'],
                ['key' => 'type', 'label' => 'Type'],
                ['key' => 'description', 'label' => 'Description'],
                ['key' => 'debit', 'label' => 'Debit', 'align' => 'right'],
                ['key' => 'credit', 'label' => 'Credit', 'align' => 'right'],
                ['key' => 'balance', 'label' => 'Balance', 'align' => 'right'],
            ],
            rows: $rows,
            footer: [
                'date' => '',
                'account' => '',
                'reference' => '',
                'type' => '',
                'description' => 'Total',
                'debit' => $this->money((float) $lines->sum('debit')),
                'credit' => $this->money((float) $lines->sum('credit')),
                'balance' => '',
            ]
        );
    }

    /**
     * @param  array{from:\Carbon\Carbon,to:\Carbon\Carbon,account_id:?int,project_id:?int,supplier_id:?int,customer_name:?string}  $filters
     * @return array<string, mixed>
     */
    protected function buildProjectWiseExpenseReport(string $title, array $filters): array
    {
        if (! class_exists(Project::class) || ! Schema::hasTable('projects')) {
            return $this->reportPayload(
                title: $title,
                filters: $filters,
                columns: [
                    ['key' => 'project', 'label' => 'Project'],
                    ['key' => 'entries', 'label' => 'Entries', 'align' => 'right'],
                    ['key' => 'amount', 'label' => 'Total Expense', 'align' => 'right'],
                ],
                rows: []
            );
        }

        $rows = DB::table('expenses as e')
            ->join('projects as p', 'p.id', '=', 'e.reference_id')
            ->where('e.reference_type', 'project')
            ->when($filters['project_id'], fn (QueryBuilder $query): QueryBuilder => $query->where('e.reference_id', $filters['project_id']))
            ->when($filters['account_id'], function (QueryBuilder $query) use ($filters): void {
                $query->where(function (QueryBuilder $subQuery) use ($filters): void {
                    $subQuery->where('e.expense_account_id', $filters['account_id'])
                        ->orWhere('e.payment_account_id', $filters['account_id']);
                });
            })
            ->whereDate('e.date', '>=', $filters['from']->toDateString())
            ->whereDate('e.date', '<=', $filters['to']->toDateString())
            ->selectRaw('p.name as project_name')
            ->selectRaw('COUNT(*) as entry_count')
            ->selectRaw('COALESCE(SUM(e.amount), 0) as total_amount')
            ->groupBy('p.id', 'p.name')
            ->orderBy('p.name')
            ->get();

        $formattedRows = $rows->map(function (object $row): array {
            return [
                'project' => $row->project_name,
                'entries' => number_format((float) $row->entry_count, 0),
                'amount' => $this->money((float) $row->total_amount),
            ];
        })->all();

        return $this->reportPayload(
            title: $title,
            filters: $filters,
            columns: [
                ['key' => 'project', 'label' => 'Project'],
                ['key' => 'entries', 'label' => 'Entries', 'align' => 'right'],
                ['key' => 'amount', 'label' => 'Total Expense', 'align' => 'right'],
            ],
            rows: $formattedRows,
            footer: [
                'project' => 'Total',
                'entries' => number_format((float) $rows->sum('entry_count'), 0),
                'amount' => $this->money((float) $rows->sum('total_amount')),
            ]
        );
    }

    /**
     * @param  array{from:\Carbon\Carbon,to:\Carbon\Carbon,account_id:?int,project_id:?int,supplier_id:?int,customer_name:?string}  $filters
     * @return array<string, mixed>
     */
    protected function accountBalanceRows(array $filters, ?array $types, string $mode): Collection
    {
        $aggregate = DB::table('transaction_lines as tl')
            ->join('transactions as t', 't.id', '=', 'tl.transaction_id')
            ->selectRaw('tl.account_id')
            ->selectRaw('COALESCE(SUM(tl.debit), 0) as total_debit')
            ->selectRaw('COALESCE(SUM(tl.credit), 0) as total_credit');

        if ($mode === 'through') {
            $aggregate->whereDate('t.date', '<=', $filters['to']->toDateString());
        } else {
            $aggregate->whereDate('t.date', '>=', $filters['from']->toDateString())
                ->whereDate('t.date', '<=', $filters['to']->toDateString());
        }

        $aggregate->groupBy('tl.account_id');

        return Account::query()
            ->leftJoinSub($aggregate, 'ledger_agg', function ($join): void {
                $join->on('ledger_agg.account_id', '=', 'accounts.id');
            })
            ->where('accounts.is_active', true)
            ->when($types, fn (Builder $query): Builder => $query->whereIn('accounts.type', $types))
            ->when($filters['account_id'], fn (Builder $query): Builder => $query->where('accounts.id', $filters['account_id']))
            ->orderBy('accounts.type')
            ->orderBy('accounts.name')
            ->get([
                'accounts.id',
                'accounts.code',
                'accounts.name',
                'accounts.type',
                DB::raw('COALESCE(ledger_agg.total_debit, 0) as total_debit'),
                DB::raw('COALESCE(ledger_agg.total_credit, 0) as total_credit'),
            ]);
    }

    /**
     * @param  array<int, int>  $accountIds
     * @param  array{from:\Carbon\Carbon,to:\Carbon\Carbon,account_id:?int,project_id:?int,supplier_id:?int,customer_name:?string}  $filters
     * @return \Illuminate\Support\Collection<int, \App\Models\TransactionLine>
     */
    protected function bookLines(array $accountIds, array $filters): Collection
    {
        if ($accountIds === []) {
            return collect();
        }

        return TransactionLine::query()
            ->select('transaction_lines.*')
            ->join('transactions', 'transactions.id', '=', 'transaction_lines.transaction_id')
            ->with([
                'account:id,name,code',
                'transaction:id,date,type,reference_type,reference_id,notes',
                'transaction.payment:id,transaction_id,payment_no,payee_name,reference_type,reference_id',
                'transaction.collection:id,transaction_id,collection_no,payer_name,reference_type,reference_id',
                'transaction.expense:id,transaction_id,expense_no,title,reference_type,reference_id',
                'transaction.lines:id,transaction_id,account_id,debit,credit,description',
                'transaction.lines.account:id,name,code',
            ])
            ->whereIn('transaction_lines.account_id', $accountIds)
            ->whereDate('transactions.date', '>=', $filters['from']->toDateString())
            ->whereDate('transactions.date', '<=', $filters['to']->toDateString())
            ->orderBy('transactions.date')
            ->orderBy('transaction_lines.id')
            ->get();
    }

    /**
     * @param  array<int, int>  $accountIds
     * @param  array{from:\Carbon\Carbon,to:\Carbon\Carbon,account_id:?int,project_id:?int,supplier_id:?int,customer_name:?string}  $filters
     */
    protected function openingBalanceForAccounts(array $accountIds, array $filters): float
    {
        if ($accountIds === [] || ! $filters['from']) {
            return 0.0;
        }

        return round((float) (DB::table('transaction_lines as tl')
            ->join('transactions as t', 't.id', '=', 'tl.transaction_id')
            ->whereIn('tl.account_id', $accountIds)
            ->whereDate('t.date', '<', $filters['from']->toDateString())
            ->selectRaw('COALESCE(SUM(tl.debit - tl.credit), 0) as balance')
            ->value('balance') ?? 0), 2);
    }

    /**
     * @param  array{from:\Carbon\Carbon,to:\Carbon\Carbon,account_id:?int,project_id:?int,supplier_id:?int,customer_name:?string}  $filters
     * @return array<int, float>
     */
    protected function openingBalanceMapForLedger(array $filters): array
    {
        $query = DB::table('transaction_lines as tl')
            ->join('transactions as t', 't.id', '=', 'tl.transaction_id')
            ->selectRaw('tl.account_id, COALESCE(SUM(tl.debit - tl.credit), 0) as balance')
            ->whereDate('t.date', '<', $filters['from']->toDateString());

        if ($filters['account_id']) {
            $query->where('tl.account_id', $filters['account_id']);
        }

        return $query->groupBy('tl.account_id')
            ->pluck('balance', 'tl.account_id')
            ->map(fn (mixed $balance): float => round((float) $balance, 2))
            ->all();
    }

    /**
     * @param  array<int, int>  $selectedAccountIds
     */
    protected function lineParticulars(TransactionLine $line, array $selectedAccountIds): string
    {
        $excludedAccountIds = $selectedAccountIds !== []
            ? $selectedAccountIds
            : [(int) $line->account_id];

        $counterparts = $line->transaction?->lines
            ?->reject(fn (TransactionLine $other): bool => in_array((int) $other->account_id, $excludedAccountIds, true))
            ->map(fn (TransactionLine $other): ?string => $other->account?->name)
            ->filter()
            ->unique()
            ->values()
            ->all() ?? [];

        if (filled($line->description)) {
            $suffix = $counterparts !== [] ? ' - '.implode(', ', $counterparts) : '';

            return $line->description.$suffix;
        }

        if ($counterparts !== []) {
            return implode(', ', $counterparts);
        }

        return $line->description ?: ($line->transaction?->notes ?: '-');
    }

    protected function transactionReference(?Transaction $transaction): string
    {
        if (! $transaction) {
            return '-';
        }

        if ($transaction->payment) {
            return $this->entryReference($transaction->payment->payment_no, $transaction->payment->reference_type ?? null, $transaction->payment->reference_id ?? null);
        }

        if ($transaction->collection) {
            return $this->entryReference($transaction->collection->collection_no, $transaction->collection->reference_type ?? null, $transaction->collection->reference_id ?? null);
        }

        if ($transaction->expense) {
            return $this->entryReference($transaction->expense->expense_no, $transaction->expense->reference_type ?? null, $transaction->expense->reference_id ?? null);
        }

        return $this->referenceLabel($transaction->reference_type, $transaction->reference_id);
    }

    protected function entryReference(?string $documentNo, ?string $referenceType, mixed $referenceId): string
    {
        $parts = collect([
            $documentNo ?: null,
            $this->referenceLabel($referenceType, $referenceId, false),
        ])->filter();

        return $parts->isEmpty() ? '-' : $parts->implode(' / ');
    }

    protected function referenceLabel(?string $referenceType, mixed $referenceId, bool $fallbackDash = true): string
    {
        if (! $referenceType && ! $referenceId) {
            return $fallbackDash ? '-' : '';
        }

        $label = $referenceType ? Str::headline(str_replace('_', ' ', $referenceType)) : 'Ref';
        $idPart = $referenceId ? ' #'.$referenceId : '';

        return trim($label.$idPart);
    }

    protected function applyReferenceFilter(Builder $query, string $referenceType, ?int $referenceId): void
    {
        if (! $referenceId) {
            return;
        }

        $query->where('reference_type', $referenceType)
            ->where('reference_id', $referenceId);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder  $query
     * @param  array{from:\Carbon\Carbon,to:\Carbon\Carbon,account_id:?int,project_id:?int,supplier_id:?int,customer_name:?string}  $filters
     */
    protected function applyDateBetween(object $query, string $column, array $filters): void
    {
        $query->whereDate($column, '>=', $filters['from']->toDateString())
            ->whereDate($column, '<=', $filters['to']->toDateString());
    }

    /**
     * @param  array<int, array<string, mixed>>  $columns
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<string, mixed>  $filters
     * @param  array<string, mixed>|null  $footer
     * @return array<string, mixed>
     */
    protected function reportPayload(string $title, array $filters, array $columns, array $rows, ?array $footer = null): array
    {
        return [
            'title' => $title,
            'columns' => $columns,
            'rows' => $rows,
            'footer' => $footer,
            'meta' => [
                'company_name' => config('app.name'),
                'from_date' => $filters['from']->toDateString(),
                'to_date' => $filters['to']->toDateString(),
                'period_label' => $filters['from']->isSameDay($filters['to'])
                    ? $filters['from']->format('d M Y')
                    : $filters['from']->format('d M Y').' to '.$filters['to']->format('d M Y'),
                'file_name' => Str::slug($title).'-'.$filters['from']->format('Ymd').'-'.$filters['to']->format('Ymd'),
            ],
        ];
    }

    protected function money(float $value): string
    {
        return number_format($value, 2, '.', '');
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

    protected function accountLabel(mixed $code, mixed $name): string
    {
        $resolvedName = trim((string) ($name ?? ''));
        $resolvedCode = trim((string) ($code ?? ''));

        if ($resolvedCode === '') {
            return $resolvedName !== '' ? $resolvedName : '-';
        }

        return trim($resolvedCode.' - '.$resolvedName);
    }

    /**
     * @return array<int, int>
     */
    protected function selectedGroupedAccountIds(string $group, ?int $selectedAccountId): array
    {
        $groupIds = $this->accountIdsForGroup($group);

        if (! $selectedAccountId) {
            return $groupIds;
        }

        return in_array($selectedAccountId, $groupIds, true) ? [$selectedAccountId] : [];
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
            default => [],
        };

        $configuredCodes = match ($group) {
            'cash' => [(string) config('hrm.accounts.cash.code', '')],
            'bank' => [(string) config('hrm.accounts.bank.code', '')],
            default => [],
        };

        $keywords = match ($group) {
            'cash' => ['cash'],
            'bank' => ['bank'],
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
}
