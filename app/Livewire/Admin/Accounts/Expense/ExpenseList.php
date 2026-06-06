<?php

namespace App\Livewire\Admin\Accounts\Expense;

use App\Enums\Accounts\TransactionType;
use App\Livewire\Admin\Accounts\Concerns\InteractsWithAccountsAccess;
use App\Models\BankingPaymentRequest;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class ExpenseList extends Component
{
    use InteractsWithAccountsAccess;
    use WithPagination;

    public string $search        = '';
    public string $dateFrom      = '';
    public string $dateTo        = '';
    public string $categoryFilter = '';

    protected string $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->authorizePermission('accounts.expense.list');
    }

    public function updatedSearch(): void         { $this->resetPage(); }
    public function updatedDateFrom(): void       { $this->resetPage(); }
    public function updatedDateTo(): void         { $this->resetPage(); }
    public function updatedCategoryFilter(): void { $this->resetPage(); }

    public function render(): View
    {
        // Expenses live in the ledger: transactions of type 'expense'.
        // Show the DR side (debit > 0) so each expense appears once.
        $expenses = Transaction::query()
            ->where('type', TransactionType::EXPENSE->value)
            ->where('debit', '>', 0)
            ->with([
                'account:id,name,code',
                'transactionCategory:id,name',
                'creator:id,name',
            ])
            ->when($this->search, fn ($q, $s) =>
                $q->where(fn ($q2) =>
                    $q2->where('name', 'like', "%{$s}%")
                       ->orWhere('notes', 'like', "%{$s}%")
                )
            )
            ->when($this->categoryFilter, fn ($q, $c) => $q->where('transaction_category_id', (int) $c))
            ->when($this->dateFrom, fn ($q, $d) => $q->whereDate('datetime', '>=', $d))
            ->when($this->dateTo,   fn ($q, $d) => $q->whereDate('datetime', '<=', $d))
            ->latest('datetime')
            ->latest('id')
            ->paginate(20);

        // Resolve project/supplier reference via the linked banking payment request's sourceable.
        $bprIds = $expenses->getCollection()
            ->where('reference_type', 'banking_payment_request')
            ->pluck('reference_id')
            ->filter()
            ->unique();

        $bprs = $bprIds->isNotEmpty()
            ? BankingPaymentRequest::with('sourceable')->whereIn('id', $bprIds)->get()->keyBy('id')
            : collect();

        $expenseCategories = TransactionCategory::query()
            ->where('type', 'expense')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $kpi = Transaction::query()
            ->where('type', TransactionType::EXPENSE->value)
            ->where('debit', '>', 0)
            ->selectRaw('COUNT(*) AS cnt, COALESCE(SUM(debit),0) AS total')
            ->first();

        return view('livewire.admin.accounts.expense.expense-list', compact(
            'expenses', 'bprs', 'expenseCategories', 'kpi'
        ))->layout('layouts.admin.admin');
    }
}
