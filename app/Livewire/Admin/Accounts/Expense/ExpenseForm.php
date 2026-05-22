<?php

namespace App\Livewire\Admin\Accounts\Expense;

use App\Livewire\Admin\Accounts\Concerns\InteractsWithAccountsAccess;
use App\Models\Account;
use App\Models\BankAccount;
use App\Models\Expense;
use App\Models\TransactionCategory;
use App\Services\Accounts\ExpenseService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ExpenseForm extends Component
{
    use InteractsWithAccountsAccess;

    public ?Expense $expense = null;

    // ─── Form fields ─────────────────────────────────────────────────────────
    public string $title                    = '';
    public string $date                     = '';
    public ?int   $expense_account_id       = null;
    public ?int   $transaction_category_id  = null;
    public ?int   $bank_account_id          = null;
    public string $amount                   = '';
    public string $notes                    = '';

    public function mount(?Expense $expense = null): void
    {
        $this->authorizePermission('accounts.expense.list');
        $this->date = now()->toDateString();

        if ($expense && $expense->exists) {
            $this->expense = $expense->loadMissing([
                'expenseAccount:id,name,code',
                'paymentAccount:id,name,code',
                'bankAccount:id,bank_name,type,ac_number',
                'transactionCategory:id,name',
                'transaction',
                'bankingRequest.transaction',
            ]);

            $this->title                   = $expense->title;
            $this->date                    = $expense->date->toDateString();
            $this->expense_account_id      = $expense->expense_account_id;
            $this->transaction_category_id = $expense->transaction_category_id;
            $this->bank_account_id         = $expense->bank_account_id;
            $this->amount                  = (string) $expense->amount;
            $this->notes                   = $expense->notes ?? '';
        }
    }

    // ─── Actions ─────────────────────────────────────────────────────────────

    public function save(): void
    {
        $this->authorizePermission('accounts.expense.create');

        $data = $this->validate([
            'title'                   => ['required', 'string', 'max:200'],
            'date'                    => ['required', 'date'],
            'expense_account_id'      => ['required', 'integer', 'exists:accounts,id'],
            'transaction_category_id' => ['required', 'integer', 'exists:transaction_categories,id'],
            'bank_account_id'         => ['required', 'integer', 'exists:bank_accounts,id'],
            'amount'                  => ['required', 'numeric', 'gt:0'],
            'notes'                   => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $expense = app(ExpenseService::class)->create($data, (int) Auth::id());
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Expense saved as draft.']);
            $this->redirect(route('admin.accounts.expenses.show', $expense), navigate: true);
        } catch (\Throwable $e) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function update(): void
    {
        $this->authorizePermission('accounts.expense.edit');

        if (! $this->expense || ! $this->expense->isDraft()) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Only draft expenses can be edited.']);
            return;
        }

        $data = $this->validate([
            'title'                   => ['required', 'string', 'max:200'],
            'date'                    => ['required', 'date'],
            'expense_account_id'      => ['required', 'integer', 'exists:accounts,id'],
            'transaction_category_id' => ['required', 'integer', 'exists:transaction_categories,id'],
            'bank_account_id'         => ['required', 'integer', 'exists:bank_accounts,id'],
            'amount'                  => ['required', 'numeric', 'gt:0'],
            'notes'                   => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->expense->update($data);
            $this->expense = $this->expense->fresh();
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Expense updated.']);
        } catch (\Throwable $e) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function post(): void
    {
        $this->authorizePermission('accounts.expense.create');

        if (! $this->expense || ! $this->expense->isDraft()) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Only draft expenses can be posted.']);
            return;
        }

        try {
            app(ExpenseService::class)->post($this->expense, (int) Auth::id());

            $this->expense = $this->expense->fresh()->loadMissing([
                'expenseAccount:id,name,code',
                'paymentAccount:id,name,code',
                'bankAccount:id,bank_name,type,ac_number',
                'transactionCategory:id,name',
                'transaction',
                'bankingRequest.transaction',
            ]);

            $this->dispatch('toast', ['type' => 'success', 'message' => 'Expense posted. Awaiting banking approval.']);
        } catch (\Throwable $e) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    // ─── Render ──────────────────────────────────────────────────────────────

    public function render(): View
    {
        $expenseAccounts = Account::query()
            ->where('type', 'ledger')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $expenseCategories = TransactionCategory::query()
            ->where('is_active', true)
            ->where('type', 'expense')
            ->orderBy('name')
            ->get(['id', 'name']);

        $bankAccounts = BankAccount::query()
            ->where('status', 'active')
            ->orderBy('bank_name')
            ->get(['id', 'bank_name', 'ac_number', 'type']);

        $bankingRequest = $this->expense?->bankingRequest?->loadMissing([
            'requestedBy:id,name',
            'approvedBy:id,name',
            'releasedBy:id,name',
            'completedBy:id,name',
            'transaction',
        ]);

        return view('livewire.admin.accounts.expense.expense-form', compact(
            'expenseAccounts', 'expenseCategories', 'bankAccounts', 'bankingRequest'
        ))->layout('layouts.admin.admin');
    }
}
