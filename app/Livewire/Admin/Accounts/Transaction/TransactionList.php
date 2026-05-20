<?php

namespace App\Livewire\Admin\Accounts\Transaction;

use App\Enums\Accounts\EntryMethod;
use App\Enums\Accounts\TransactionType;
use App\Livewire\Admin\Accounts\Concerns\InteractsWithAccountsAccess;
use App\Models\Account;
use App\Models\File;
use App\Models\Transaction;
use App\Models\TransactionCategory;
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
    public string $categoryFilter = '';
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
    public function updatedTypeFilter(): void { $this->resetPage(); $this->categoryFilter = ''; }
    public function updatedCategoryFilter(): void { $this->resetPage(); }
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
                'account:id,name,code,type',
                'account.bankAccount:id,account_id,bank_name,type,code',
                'transactionCategory:id,name,type,parent_id',
                'transactionCategory.parent:id,name',
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
            ->when($this->categoryFilter !== '', fn (Builder $q) => $q->where('transaction_category_id', $this->categoryFilter))
            ->when($this->methodFilter !== '', fn (Builder $q) => $q->where('method', $this->methodFilter))
            ->when($this->accountFilter !== '', fn (Builder $q) => $q->where('account_id', $this->accountFilter))
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
                    'account:id,name,code,type',
                    'account.bankAccount:id,account_id,bank_name,type,code,ac_number',
                    'transactionCategory:id,name,type,parent_id',
                    'transactionCategory.parent:id,name',
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

        // KPI strip — respects active date filters
        $kpi = Transaction::query()
            ->when($this->dateFrom, fn (Builder $q) => $q->whereDate('datetime', '>=', $this->dateFrom))
            ->when($this->dateTo,   fn (Builder $q) => $q->whereDate('datetime', '<=', $this->dateTo))
            ->selectRaw("
                SUM(CASE WHEN type = 'income'                        THEN debit  ELSE 0 END) AS total_income,
                SUM(CASE WHEN type = 'expense'                       THEN credit ELSE 0 END) AS total_expense,
                SUM(CASE WHEN type = 'advance' AND debit  > 0        THEN debit  ELSE 0 END) AS advance_in,
                SUM(CASE WHEN type = 'advance' AND credit > 0        THEN credit ELSE 0 END) AS advance_out,
                SUM(debit) - SUM(credit)                                                     AS net_position,
                COUNT(*)                                                                     AS total_count,
                SUM(CASE WHEN adjusted_at IS NOT NULL THEN 1 ELSE 0 END)                     AS adjusted_count
            ")
            ->first();

        // Category filter (contextual to selected type)
        $categories = TransactionCategory::query()
            ->active()
            ->when($typeValue !== '', fn (Builder $q) => $q->where('type', $typeValue))
            ->orderByRaw('ISNULL(parent_id), parent_id, name')
            ->get(['id', 'name', 'type', 'parent_id']);

        // Accounts for filter dropdown
        $accounts = Account::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'type']);

        return view('livewire.admin.accounts.transaction.transaction-list', [
            'transactions'         => $transactions,
            'types'                => TransactionType::cases(),
            'methods'              => EntryMethod::cases(),
            'categories'           => $categories,
            'accounts'             => $accounts,
            'viewTransaction'      => $viewTransaction,
            'viewTransactionFiles' => $viewTransactionFiles,
            'kpi'                  => $kpi,
            'typeCounts'           => $typeCounts,
        ])->layout('layouts.admin.admin');
    }
}
