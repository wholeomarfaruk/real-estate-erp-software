<?php

namespace App\Livewire\Admin\Accounts\Transaction;

use App\Enums\Accounts\EntryMethod;
use App\Enums\Accounts\TransactionType;
use App\Helpers\TransactionReferenceFormatter;
use App\Livewire\Admin\Accounts\Concerns\InteractsWithAccountsAccess;
use App\Models\Account;
use App\Models\File;
use App\Models\Transaction;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class TransactionList extends Component
{
    use InteractsWithAccountsAccess;
    use WithPagination;

    public string $search = '';
    public string $typeFilter = '';
    public string $methodFilter = '';
    public string $accountFilter = '';
    public ?string $dateFrom = null;
    public ?string $dateTo = null;

    public bool $showDrawer = false;
    public ?int $viewingId = null;

    protected string $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->authorizePermission('accounts.transaction.list');
    }

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedTypeFilter(): void { $this->resetPage(); }
    public function updatedMethodFilter(): void { $this->resetPage(); }
    public function updatedAccountFilter(): void { $this->resetPage(); }
    public function updatedDateFrom(): void { $this->resetPage(); }
    public function updatedDateTo(): void { $this->resetPage(); }

    public function openDrawer(int $id): void
    {
        $this->authorizePermission('accounts.transaction.view');

        if (! Transaction::query()->whereKey($id)->exists()) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Transaction not found.']);
            return;
        }

        $this->viewingId = $id;
        $this->showDrawer = true;
    }

    public function closeDrawer(): void
    {
        $this->showDrawer = false;
        $this->viewingId = null;
    }

    public function render(): View
    {
        $this->authorizePermission('accounts.transaction.list');

        $isAdjustedTab = $this->typeFilter === 'adjusted';
        $typeValue     = $isAdjustedTab ? '' : $this->typeFilter;

        $transactions = Transaction::query()
            ->with([
                'creator:id,name',
                'lines:id,transaction_id,account_id,debit,credit,notes',
                'lines.account:id,name,code,type',
                'lines.account.bankAccount:id,account_id,bank_name,type,code',
            ])
            ->when($this->search !== '', function (Builder $query): void {
                $search = '%'.$this->search.'%';
                $query->where(function (Builder $subQuery) use ($search): void {
                    $subQuery->where('notes', 'like', $search)
                        ->orWhere('name', 'like', $search)
                        ->orWhere('reference_no', 'like', $search)
                        ->orWhere('reference_type', 'like', $search)
                        ->orWhereRaw('CAST(reference_id as CHAR) like ?', [$search]);
                });
            })
            ->when($typeValue !== '', fn (Builder $q) => $q->where('type', $typeValue))
            ->when($isAdjustedTab, fn (Builder $q) => $q->whereNotNull('adjusted_at'))
            ->when($this->methodFilter !== '', fn (Builder $q) => $q->where('method', $this->methodFilter))
            ->when($this->accountFilter !== '', fn (Builder $q) => $q->whereHas(
                'lines',
                fn (Builder $l) => $l->where('account_id', $this->accountFilter)
            ))
            ->when($this->dateFrom, fn (Builder $q) => $q->whereDate('datetime', '>=', $this->dateFrom))
            ->when($this->dateTo,   fn (Builder $q) => $q->whereDate('datetime', '<=', $this->dateTo))
            ->latest('datetime')
            ->latest('id')
            ->paginate(20);

        // Drawer detail
        $viewTransaction      = null;
        $viewTransactionFiles = collect();

        if ($this->showDrawer && $this->viewingId) {
            $viewTransaction = Transaction::query()
                ->with([
                    'creator:id,name',
                    'adjustedByUser:id,name',
                    'lines:id,transaction_id,account_id,debit,credit,notes',
                    'lines.account:id,name,code,type',
                    'lines.account.bankAccount:id,account_id,bank_name,type,code,ac_number',
                ])
                ->find($this->viewingId);

            if (! $viewTransaction) {
                $this->showDrawer = false;
                $this->viewingId  = null;
            } else {
                $viewTransactionFiles = File::query()
                    ->whereIn('id', $viewTransaction->attachments ?? [])
                    ->get(['id', 'name', 'type', 'extension']);
            }
        }

        // Format reference data
        $viewTransactionReference = null;
        if ($viewTransaction) {
            $viewTransactionReference = TransactionReferenceFormatter::resolve(
                $viewTransaction->reference_type,
                $viewTransaction->reference_id,
                $viewTransaction->reference_no
            );
        }

        // Type tab counts (always global, no date filter)
        $typeCounts = Transaction::query()
            ->selectRaw("
                COUNT(*) AS total,
                SUM(CASE WHEN type = 'income'  THEN 1 ELSE 0 END) AS income_count,
                SUM(CASE WHEN type = 'expense' THEN 1 ELSE 0 END) AS expense_count,
                SUM(CASE WHEN type = 'advance' THEN 1 ELSE 0 END) AS advance_count,
                SUM(CASE WHEN adjusted_at IS NOT NULL THEN 1 ELSE 0 END) AS adjusted_count
            ")
            ->first();

        // KPI strip — respects active date filters. Per-account movements are summed
        // from transaction_lines (the header debit/credit columns were removed); the
        // count metrics stay on the transaction header.
        $lineAgg = Transaction::query()
            ->join('transaction_lines as tl', 'tl.transaction_id', '=', 'transactions.id')
            ->when($this->dateFrom, fn (Builder $q) => $q->whereDate('datetime', '>=', $this->dateFrom))
            ->when($this->dateTo,   fn (Builder $q) => $q->whereDate('datetime', '<=', $this->dateTo))
            ->selectRaw("
                SUM(CASE WHEN type = 'income'                  THEN tl.debit  ELSE 0 END) AS total_income,
                SUM(CASE WHEN type = 'expense'                 THEN tl.credit ELSE 0 END) AS total_expense,
                SUM(CASE WHEN type = 'advance' AND tl.debit  > 0 THEN tl.debit  ELSE 0 END) AS advance_in,
                SUM(CASE WHEN type = 'advance' AND tl.credit > 0 THEN tl.credit ELSE 0 END) AS advance_out,
                SUM(tl.debit) - SUM(tl.credit)                                              AS net_position
            ")
            ->first();

        $countAgg = Transaction::query()
            ->when($this->dateFrom, fn (Builder $q) => $q->whereDate('datetime', '>=', $this->dateFrom))
            ->when($this->dateTo,   fn (Builder $q) => $q->whereDate('datetime', '<=', $this->dateTo))
            ->selectRaw("
                COUNT(*)                                                AS total_count,
                SUM(CASE WHEN adjusted_at IS NOT NULL THEN 1 ELSE 0 END) AS adjusted_count
            ")
            ->first();

        $kpi = (object) [
            'total_income'  => $lineAgg->total_income ?? 0,
            'total_expense' => $lineAgg->total_expense ?? 0,
            'advance_in'    => $lineAgg->advance_in ?? 0,
            'advance_out'   => $lineAgg->advance_out ?? 0,
            'net_position'  => $lineAgg->net_position ?? 0,
            'total_count'   => $countAgg->total_count ?? 0,
            'adjusted_count' => $countAgg->adjusted_count ?? 0,
        ];

        // Accounts for filter dropdown
        $accounts = Account::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'type']);

        return view('livewire.admin.accounts.transaction.transaction-list', [
            'transactions'               => $transactions,
            'types'                      => TransactionType::cases(),
            'methods'                    => EntryMethod::cases(),
            'accounts'                   => $accounts,
            'viewTransaction'            => $viewTransaction,
            'viewTransactionFiles'       => $viewTransactionFiles,
            'viewTransactionReference'   => $viewTransactionReference,
            'kpi'                        => $kpi,
            'typeCounts'                 => $typeCounts,
        ])->layout('layouts.admin.admin');
    }
}
