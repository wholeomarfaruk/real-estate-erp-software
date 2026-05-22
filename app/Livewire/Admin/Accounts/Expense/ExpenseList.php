<?php

namespace App\Livewire\Admin\Accounts\Expense;

use App\Livewire\Admin\Accounts\Concerns\InteractsWithAccountsAccess;
use App\Models\Account;
use App\Models\Expense;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class ExpenseList extends Component
{
    use InteractsWithAccountsAccess;
    use WithPagination;

    public string  $search        = '';
    public string  $statusFilter  = '';
    public string  $dateFrom      = '';
    public string  $dateTo        = '';
    public string  $accountFilter = '';

    protected string $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->authorizePermission('accounts.expense.list');
    }

    public function updatedSearch(): void        { $this->resetPage(); }
    public function updatedStatusFilter(): void  { $this->resetPage(); }
    public function updatedDateFrom(): void      { $this->resetPage(); }
    public function updatedDateTo(): void        { $this->resetPage(); }
    public function updatedAccountFilter(): void { $this->resetPage(); }

    public function render(): View
    {
        $expenses = Expense::query()
            ->with([
                'expenseAccount:id,name,code',
                'transactionCategory:id,name',
                'bankAccount:id,bank_name,type',
                'creator:id,name',
            ])
            ->when($this->search, fn ($q, $s) =>
                $q->where(fn ($q2) =>
                    $q2->where('expense_no', 'like', "%{$s}%")
                       ->orWhere('title', 'like', "%{$s}%")
                )
            )
            ->when($this->statusFilter,  fn ($q, $s) => $q->where('status', $s))
            ->when($this->dateFrom,      fn ($q, $d) => $q->whereDate('date', '>=', $d))
            ->when($this->dateTo,        fn ($q, $d) => $q->whereDate('date', '<=', $d))
            ->when($this->accountFilter, fn ($q, $a) => $q->where('expense_account_id', (int) $a))
            ->latest('date')
            ->latest('id')
            ->paginate(20);

        $expenseAccounts = Account::query()
            ->where('type', 'ledger')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $kpi = Expense::query()
            ->selectRaw("
                SUM(CASE WHEN status='draft'   THEN 1 ELSE 0 END) AS draft_count,
                SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) AS pending_count,
                SUM(CASE WHEN status='posted'  THEN 1 ELSE 0 END) AS posted_count,
                SUM(CASE WHEN status='posted'  THEN amount ELSE 0 END) AS total_posted
            ")
            ->first();

        return view('livewire.admin.accounts.expense.expense-list', compact(
            'expenses', 'expenseAccounts', 'kpi'
        ))->layout('layouts.admin.admin');
    }
}
