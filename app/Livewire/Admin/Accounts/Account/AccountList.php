<?php

namespace App\Livewire\Admin\Accounts\Account;

use App\Enums\Accounts\AccountType;
use App\Livewire\Admin\Accounts\Concerns\InteractsWithAccountsAccess;
use App\Models\Account;
use App\Models\AccountCollection;
use App\Models\Expense;
use App\Models\Payment;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class AccountList extends Component
{
    use InteractsWithAccountsAccess;
    use WithPagination;

    public string $search = '';

    public string $typeFilter = '';

    public string $statusFilter = '';

    public bool $showFormModal = false;

    public ?int $editingId = null;

    public ?string $code = null;

    public string $name = '';

    public string $type = '';

    public ?int $parent_id = null;

    public bool $is_active = true;

    protected string $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->authorizePermission('accounts.chart.list');
        $this->type = AccountType::ASSET->value;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->authorizePermission('accounts.chart.create');

        $this->resetForm();
        $this->type = $this->typeFilter !== '' ? $this->typeFilter : AccountType::ASSET->value;
        $this->showFormModal = true;
    }

    public function openEditModal(int $id): void
    {
        $this->authorizePermission('accounts.chart.edit');

        $account = Account::query()->find($id);

        if (! $account) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Account not found.']);

            return;
        }

        $this->editingId = (int) $account->id;
        $this->code = $account->code;
        $this->name = $account->name;
        $this->type = $account->type?->value ?? AccountType::ASSET->value;
        $this->parent_id = $account->parent_id ? (int) $account->parent_id : null;
        $this->is_active = (bool) $account->is_active;
        $this->showFormModal = true;
    }

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
    }

    public function save(): void
    {
        $permission = $this->editingId ? 'accounts.chart.edit' : 'accounts.chart.create';
        $this->authorizePermission($permission);

        $validated = $this->validate($this->rules(), $this->messages());

        $parentId = $validated['parent_id'] ? (int) $validated['parent_id'] : null;

        if ($this->editingId && $parentId === (int) $this->editingId) {
            $this->addError('parent_id', 'Parent account cannot be the same account.');

            return;
        }

        if ($this->wouldCreateParentCycle($parentId, $this->editingId)) {
            $this->addError('parent_id', 'Selected parent creates an invalid parent loop.');

            return;
        }

        DB::transaction(function () use ($validated): void {
            if ($this->editingId) {
                $account = Account::query()->findOrFail($this->editingId);
                $account->update($validated);
            } else {
                Account::query()->create($validated);
            }
        });

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Account saved successfully.']);

        $this->showFormModal = false;
        $this->resetForm();
    }

    public function toggleStatus(int $id): void
    {
        $this->authorizePermission('accounts.chart.edit');

        $account = Account::query()->find($id);

        if (! $account) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Account not found.']);

            return;
        }

        $account->update([
            'is_active' => ! $account->is_active,
        ]);

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Account status updated successfully.']);
    }

    public function deleteAccount(int $id): void
    {
        $this->authorizePermission('accounts.chart.delete');

        $account = Account::query()->find($id);

        if (! $account) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Account not found.']);

            return;
        }

        if ($account->children()->exists()) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Account has child accounts and cannot be deleted.']);

            return;
        }

        if ($this->isAccountInUse((int) $account->id)) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Account is used in transactions or business entries and cannot be deleted.']);

            return;
        }

        $account->delete();

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Account deleted successfully.']);
    }

    public function render(): View
    {
        $this->authorizePermission('accounts.chart.list');

        $accounts = Account::query()
            ->with('parent:id,name,parent_id')
            ->when($this->search !== '', function (Builder $query): void {
                $search = '%'.$this->search.'%';

                $query->where(function (Builder $subQuery) use ($search): void {
                    $subQuery->where('name', 'like', $search)
                        ->orWhere('code', 'like', $search);
                });
            })
            ->when($this->typeFilter !== '', function (Builder $query): void {
                $query->where('type', $this->typeFilter);
            })
            ->when($this->statusFilter !== '', function (Builder $query): void {
                $query->where('is_active', $this->statusFilter === 'active');
            })
            ->orderBy('type')
            ->orderBy('parent_id')
            ->orderBy('name')
            ->paginate(15);

        $accounts->getCollection()->transform(function (Account $account): Account {
            $depth = 0;
            $cursor = $account;

            while ($cursor->parent) {
                $depth++;
                if ($depth >= 10) {
                    break;
                }

                $cursor = $cursor->parent;
            }

            $account->setAttribute('depth', $depth);

            return $account;
        });

        $parentOptions = Account::query()
            ->when($this->editingId, fn (Builder $query): Builder => $query->where('id', '!=', $this->editingId))
            ->orderBy('type')
            ->orderBy('name')
            ->get(['id', 'name', 'type']);

        $cashBankAccounts = Account::query()
            ->where('type', AccountType::ASSET->value)
            ->where(function (Builder $query): void {
                $query->where('name', 'like', '%cash%')
                    ->orWhere('name', 'like', '%bank%')
                    ->orWhere('code', 'like', '%cash%')
                    ->orWhere('code', 'like', '%bank%');
            })
            ->withSum('transactionLines as total_debit', 'debit')
            ->withSum('transactionLines as total_credit', 'credit')
            ->orderBy('name')
            ->get();

        $cashBankAccounts->transform(function (Account $account): Account {
            $debit = round((float) ($account->total_debit ?? 0), 3);
            $credit = round((float) ($account->total_credit ?? 0), 3);
            $balance = round($debit - $credit, 3);

            $account->setAttribute('computed_balance', $balance);

            return $account;
        });

        $totalCashBalance = round((float) $cashBankAccounts
            ->filter(fn (Account $account): bool => Str::contains(Str::lower($account->name.' '.$account->code), 'cash'))
            ->sum('computed_balance'), 3);

        $totalBankBalance = round((float) $cashBankAccounts
            ->filter(fn (Account $account): bool => Str::contains(Str::lower($account->name.' '.$account->code), 'bank'))
            ->sum('computed_balance'), 3);

        return view('livewire.admin.accounts.account.account-list', [
            'accounts' => $accounts,
            'types' => AccountType::cases(),
            'parentOptions' => $parentOptions,
            'cashBankAccounts' => $cashBankAccounts,
            'totalCashBalance' => $totalCashBalance,
            'totalBankBalance' => $totalBankBalance,
        ])->layout('layouts.admin.admin');
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('accounts', 'code')->ignore($this->editingId),
            ],
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', Rule::in(array_map(static fn (AccountType $type): string => $type->value, AccountType::cases()))],
            'parent_id' => ['nullable', 'exists:accounts,id'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'name.required' => 'Account name is required.',
            'type.required' => 'Account type is required.',
            'code.unique' => 'Account code must be unique.',
        ];
    }

    protected function resetForm(): void
    {
        $this->reset(['editingId', 'code', 'name', 'parent_id']);
        $this->type = AccountType::ASSET->value;
        $this->is_active = true;
    }

    protected function wouldCreateParentCycle(?int $parentId, ?int $currentId): bool
    {
        if (! $parentId || ! $currentId) {
            return false;
        }

        $seen = [];
        $cursor = $parentId;

        while ($cursor) {
            if ($cursor === $currentId) {
                return true;
            }

            if (in_array($cursor, $seen, true)) {
                return true;
            }

            $seen[] = $cursor;
            $cursor = (int) (Account::query()->where('id', $cursor)->value('parent_id') ?? 0);

            if ($cursor <= 0) {
                break;
            }
        }

        return false;
    }

    protected function isAccountInUse(int $accountId): bool
    {
        return DB::transaction(function () use ($accountId): bool {
            if (DB::table('transaction_lines')->where('account_id', $accountId)->exists()) {
                return true;
            }

            if (Payment::query()->where('payment_account_id', $accountId)->orWhere('purpose_account_id', $accountId)->exists()) {
                return true;
            }

            if (AccountCollection::query()->where('collection_account_id', $accountId)->orWhere('target_account_id', $accountId)->exists()) {
                return true;
            }

            return Expense::query()->where('expense_account_id', $accountId)->orWhere('payment_account_id', $accountId)->exists();
        });
    }
}
