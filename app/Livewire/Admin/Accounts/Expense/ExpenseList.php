<?php

namespace App\Livewire\Admin\Accounts\Expense;

use App\Enums\Accounts\AccountType;
use App\Livewire\Admin\Accounts\Concerns\InteractsWithAccountsAccess;
use App\Livewire\Traits\WithMediaPicker;
use App\Models\Account;
use App\Models\Expense;
use App\Services\Accounts\AccountingEntryService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class ExpenseList extends Component
{
    use InteractsWithAccountsAccess;
    use WithMediaPicker;
    use WithPagination;

    public string $search = '';

    public ?int $expenseAccountFilter = null;

    public ?int $paymentAccountFilter = null;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public bool $showFormModal = false;

    public ?int $editingId = null;

    public ?string $expense_no = null;

    public string $date = '';

    public string $title = '';

    public ?string $reference_type = null;

    public ?int $reference_id = null;

    public ?int $expense_account_id = null;

    public ?int $payment_account_id = null;

    public float|int|string $amount = '';

    public ?string $notes = null;

    /**
     * @var array<int, int|string>
     */
    public array $attachment_ids = [];

    public bool $showAttachmentModal = false;

    public ?int $attachmentExpenseId = null;

    protected string $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->authorizePermission('accounts.expense.list');
        $this->date = now()->toDateString();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedExpenseAccountFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPaymentAccountFilter(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->authorizePermission('accounts.expense.create');

        $this->resetForm();
        $this->showFormModal = true;
    }
    public function updatedExpenseAccountId($id): void
    {
        
    }

    public function openEditModal(int $id): void
    {
        $this->authorizePermission('accounts.expense.edit');

        $expense = Expense::query()->find($id);

        if (! $expense) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Expense not found.']);

            return;
        }

        $this->editingId = (int) $expense->id;
        $this->expense_no = $expense->expense_no;
        $this->date = optional($expense->date)->toDateString() ?? now()->toDateString();
        $this->title = $expense->title;
        $this->reference_type = $expense->reference_type;
        $this->reference_id = $expense->reference_id ? (int) $expense->reference_id : null;
        $this->expense_account_id = $expense->expense_account_id ? (int) $expense->expense_account_id : null;
        $this->payment_account_id = $expense->payment_account_id ? (int) $expense->payment_account_id : null;
        $this->amount = (float) $expense->amount;
        $this->notes = $expense->notes;
        $this->attachment_ids = $expense->transaction?->attachments?->pluck('file_id')->map(static fn ($id): int => (int) $id)->values()->all() ?? [];
        $this->showFormModal = true;
    }

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
    }

    public function openAttachmentModal(int $id): void
    {
        $this->authorizePermission('accounts.transaction-attachment.view');

        $exists = Expense::query()->whereKey($id)->exists();

        if (! $exists) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Expense not found.']);

            return;
        }

        $this->attachmentExpenseId = $id;
        $this->showAttachmentModal = true;
    }

    public function closeAttachmentModal(): void
    {
        $this->showAttachmentModal = false;
        $this->attachmentExpenseId = null;
    }

    public function removeAttachment(int $attachmentId): void
    {
        $this->authorizePermission('accounts.expense.edit');

        if (! $this->attachmentExpenseId) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'No expense selected.']);

            return;
        }

        $expense = Expense::query()->find($this->attachmentExpenseId);

        if (! $expense || ! $expense->transaction_id) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Expense transaction not found.']);

            return;
        }

        $transaction = $expense->transaction;

        if (! $transaction) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Expense transaction not found.']);

            return;
        }

        $deleted = $transaction->attachments()
            ->whereKey($attachmentId)
            ->delete();

        if (! $deleted) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Attachment not found.']);

            return;
        }

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Attachment removed successfully.']);
    }

    public function save(): void
    {
        $permission = $this->editingId ? 'accounts.expense.edit' : 'accounts.expense.create';
        $this->authorizePermission($permission);

        $validated = $this->validate($this->rules(), $this->messages());

        try {
            $expense = $this->editingId
                ? Expense::query()->findOrFail($this->editingId)
                : null;

            app(AccountingEntryService::class)->saveExpense($validated, $expense, (int) auth()->id());
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);

            return;
        }

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Expense saved successfully.']);

        $this->showFormModal = false;
        $this->resetForm();
    }

    public function deleteExpense(int $id): void
    {
        $this->authorizePermission('accounts.expense.delete');

        $expense = Expense::query()->find($id);

        if (! $expense) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Expense not found.']);

            return;
        }

        try {
            app(AccountingEntryService::class)->deleteExpense($expense);
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Expense deleted successfully.']);
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);
        }
    }

    public function render(): View
    {
        $this->authorizePermission('accounts.expense.list');

        $expenses = Expense::query()
            ->with([
                'expenseAccount:id,name,code,type',
                'paymentAccount:id,name,code,type',
                'creator:id,name',
                'transaction:id',
                'transaction.attachments:id,transaction_id,file_id',
            ])
            ->when($this->search !== '', function (Builder $query): void {
                $search = '%'.$this->search.'%';

                $query->where(function (Builder $subQuery) use ($search): void {
                    $subQuery->where('expense_no', 'like', $search)
                        ->orWhere('title', 'like', $search)
                        ->orWhere('notes', 'like', $search)
                        ->orWhere('reference_type', 'like', $search)
                        ->orWhereRaw('CAST(reference_id as CHAR) like ?', [$search]);
                });
            })
            ->when($this->expenseAccountFilter, fn (Builder $query): Builder => $query->where('expense_account_id', $this->expenseAccountFilter))
            ->when($this->paymentAccountFilter, fn (Builder $query): Builder => $query->where('payment_account_id', $this->paymentAccountFilter))
            ->when($this->dateFrom, fn (Builder $query): Builder => $query->whereDate('date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn (Builder $query): Builder => $query->whereDate('date', '<=', $this->dateTo))
            ->latest('date')
            ->latest('id')
            ->paginate(15);

        $attachmentExpense = null;

        if ($this->showAttachmentModal && $this->attachmentExpenseId) {
            $attachmentExpense = Expense::query()
                ->with([
                    'transaction:id',
                    'transaction.attachments:id,transaction_id,file_id,category,notes,created_by,created_at',
                    'transaction.attachments.file:id,name,type,extension',
                ])
                ->find($this->attachmentExpenseId);

            if (! $attachmentExpense) {
                $this->showAttachmentModal = false;
                $this->attachmentExpenseId = null;
            }
        }

        $accounts = Account::query()
            ->where('is_active', true)
            ->orderBy('type')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'type']);

        $groupedAccounts = $accounts->groupBy(fn (Account $account): string => $account->type?->value ?? AccountType::ASSET->value);

        return view('livewire.admin.accounts.expense.expense-list', [
            'expenses' => $expenses,
            'types' => AccountType::cases(),
            'accounts' => $accounts,
            'groupedAccounts' => $groupedAccounts,
            'attachmentExpense' => $attachmentExpense,
        ])->layout('layouts.admin.admin');
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'expense_no' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('expenses', 'expense_no')->ignore($this->editingId),
            ],
            'date' => ['required', 'date'],
            'title' => ['required', 'string', 'max:150'],
            'reference_type' => ['nullable', 'string', 'max:100'],
            'reference_id' => ['nullable', 'integer', 'min:1'],
            'expense_account_id' => ['required', 'exists:accounts,id'],
            'payment_account_id' => ['required', 'exists:accounts,id'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'notes' => ['nullable', 'string'],
            'attachment_ids' => ['nullable', 'array'],
            'attachment_ids.*' => ['integer', 'exists:files,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'title.required' => 'Expense title is required.',
            'expense_account_id.required' => 'Expense account is required.',
            'payment_account_id.required' => 'Payment account is required.',
            'amount.required' => 'Amount is required.',
        ];
    }

    protected function resetForm(): void
    {
        $this->reset([
            'editingId',
            'expense_no',
            'title',
            'reference_type',
            'reference_id',
            'expense_account_id',
            'payment_account_id',
            'amount',
            'notes',
            'attachment_ids',
        ]);

        $this->date = now()->toDateString();
    }
}
