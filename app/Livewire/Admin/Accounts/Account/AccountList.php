<?php

namespace App\Livewire\Admin\Accounts\Account;

use App\Enums\Accounts\AccountGroupType;
use App\Enums\Accounts\AccountType;
use App\Livewire\Admin\Accounts\Concerns\InteractsWithAccountsAccess;
use App\Models\Account;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
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

    public ?string $group = null;

    public ?int $parent_id = null;

    public bool $is_active = true;
    public ?string $sub_type = null;

    /**
     * @var array<int, string>
     */
    public array $allowed_reference_keys = [];

    protected string $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->authorizePermission('accounts.chart.list');
        $this->type = AccountType::CASH->value;
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
        $this->type = $this->typeFilter !== '' ? $this->typeFilter : AccountType::CASH->value;
        $this->showFormModal = true;
    }


    public function openEditModal(int $id): void
    {
        $this->authorizePermission('accounts.chart.edit');

        $account = Account::query()
            ->with('referenceKeys:id,account_id,reference_key')
            ->find($id);

        if (! $account) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Account not found.']);

            return;
        }

        $this->editingId = (int) $account->id;
        $this->code = $account->code;
        $this->name = $account->name;
        $this->type = $account->type?->value ?? AccountType::CASH->value;
        $this->group = $account->group?->value;
        $this->parent_id = $account->parent_id ? (int) $account->parent_id : null;
        $this->is_active = (bool) $account->is_active;
        $this->sub_type = $account->sub_type;
        $this->showFormModal = true;
        $this->allowed_reference_keys = $account->referenceKeys->pluck('reference_key')->all();
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
        $allowedReferenceKeys = collect($validated['allowed_reference_keys'] ?? [])
            ->filter(static fn (mixed $key): bool => is_string($key) && $key !== '')
            ->unique()
            ->values()
            ->all();
        
        unset($validated['allowed_reference_keys']);

        $parentId = $validated['parent_id'] ? (int) $validated['parent_id'] : null;

        if ($this->editingId && $parentId === (int) $this->editingId) {
            $this->addError('parent_id', 'Parent account cannot be the same account.');

            return;
        }

        if ($this->wouldCreateParentCycle($parentId, $this->editingId)) {
            $this->addError('parent_id', 'Selected parent creates an invalid parent loop.');

            return;
        }

        DB::transaction(function () use ($validated, $allowedReferenceKeys): void {
            if ($this->editingId) {

                $account = Account::query()->findOrFail($this->editingId);
            } else {
                $account = new Account;
            }

            $account->fill($validated);
            $account->save();
       
            $this->syncAllowedReferences($account, $allowedReferenceKeys);
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

        if ($account->is_locked) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'This is a system account and cannot be deleted.']);

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

        // Load the whole chart (bounded size) with each account's own balance summed
        // from the double-entry ledger lines, then assemble it into a nested tree.
        $allAccounts = Account::query()
            ->withSum('lines as line_debit', 'debit')
            ->withSum('lines as line_credit', 'credit')
            ->orderByRaw('ISNULL(`group`), `group`')
            ->orderByRaw('ISNULL(`parent_id`), `parent_id`')
            ->orderBy('name')
            ->get();

        $allAccounts->each(function (Account $account): void {
            $account->setAttribute(
                'own_balance',
                round((float) ($account->line_debit ?? 0) - (float) ($account->line_credit ?? 0), 2)
            );
        });

        $tree = $this->buildTree($allAccounts);

        $parentOptions = Account::query()
            ->when($this->editingId, fn (Builder $query): Builder => $query->where('id', '!=', $this->editingId))
            ->orderBy('type')
            ->orderBy('name')
            ->get(['id', 'name', 'type']);

        // Per-account balance is summed from the double-entry ledger lines
        // (transactions no longer carry debit/credit on the header).
        $cashBankAccounts = Account::query()
            ->whereIn('type', [AccountType::CASH->value, AccountType::BANK->value])
            ->withSum('lines as total_debit', 'debit')
            ->withSum('lines as total_credit', 'credit')
            ->orderBy('type')
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
            ->filter(fn (Account $account): bool => $account->type?->value === AccountType::CASH->value)
            ->sum('computed_balance'), 3);

        $totalBankBalance = round((float) $cashBankAccounts
            ->filter(fn (Account $account): bool => $account->type?->value === AccountType::BANK->value)
            ->sum('computed_balance'), 3);

    
        return view('livewire.admin.accounts.account.account-list', [
            'tree' => $tree,
            'types' => AccountType::cases(),
            'groupOptions' => AccountGroupType::options(),
            'parentOptions' => $parentOptions,
            'referenceOptions' => collect(account_reference_config())
                ->mapWithKeys(static fn (array $reference, string $key): array => [$key => (string) ($reference['label'] ?? $key)])
                ->all(),
            'cashBankAccounts' => $cashBankAccounts,
            'totalCashBalance' => $totalCashBalance,
            'totalBankBalance' => $totalBankBalance,
        ])->layout('layouts.admin.admin');
    }

    /**
     * Assemble the flat account collection into a nested tree of root nodes.
     * Applies the active search/type/status filters while keeping the ancestor
     * chain of any match visible, attaches `depth`, and rolls each parent's
     * balance up from its descendants.
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\Account>  $accounts
     * @return \Illuminate\Support\Collection<int, \App\Models\Account>
     */
    protected function buildTree($accounts)
    {
        $matchedIds = $this->filteredAccountIds($accounts);

        // Group children by their parent id for O(1) lookup.
        $byParent = $accounts->groupBy(fn (Account $a): string => (string) ($a->parent_id ?? 'root'));

        $attach = function (Account $node, int $depth) use (&$attach, $byParent, $matchedIds): bool {
            $node->setAttribute('depth', $depth);

            $children = collect();
            $anyChildVisible = false;

            foreach ($byParent->get((string) $node->id, collect()) as $child) {
                if ($attach($child, $depth + 1)) {
                    $children->push($child);
                    $anyChildVisible = true;
                }
            }

            $node->setRelation('treeChildren', $children->values());

            // Roll-up balance = own movement + sum of visible descendants.
            $rollup = (float) $node->own_balance
                + (float) $children->sum(fn (Account $c): float => (float) $c->rollup_balance);
            $node->setAttribute('rollup_balance', round($rollup, 2));

            // A node stays in the tree if it matches the filter itself or has a
            // visible (matching) descendant.
            return $matchedIds === null || in_array($node->id, $matchedIds, true) || $anyChildVisible;
        };

        return $accounts
            ->filter(fn (Account $a): bool => $a->parent_id === null)
            ->filter(fn (Account $a): bool => $attach($a, 0))
            ->values();
    }

    /**
     * Ids of accounts that directly match the active filters, or null when no
     * filter is active (meaning "show everything").
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\Account>  $accounts
     * @return array<int, int>|null
     */
    protected function filteredAccountIds($accounts): ?array
    {
        $search = trim($this->search);
        $hasFilter = $search !== '' || $this->typeFilter !== '' || $this->statusFilter !== '';

        if (! $hasFilter) {
            return null;
        }

        return $accounts
            ->filter(function (Account $a) use ($search): bool {
                if ($search !== ''
                    && stripos((string) $a->name, $search) === false
                    && stripos((string) $a->code, $search) === false) {
                    return false;
                }

                if ($this->typeFilter !== '' && ($a->type?->value ?? null) !== $this->typeFilter) {
                    return false;
                }

                if ($this->statusFilter !== '' && $a->is_active !== ($this->statusFilter === 'active')) {
                    return false;
                }

                return true;
            })
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
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
            'group' => ['required', Rule::in(array_map(static fn (AccountGroupType $g): string => $g->value, AccountGroupType::cases()))],
            'parent_id' => ['nullable', 'exists:accounts,id'],
            'is_active' => ['required', 'boolean'],
            'sub_type' => ['nullable', 'string'],
            'allowed_reference_keys' => ['array'],
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
            'group.required' => 'Account group is required.',
            'group.in' => 'Select a valid account group.',
            'code.unique' => 'Account code must be unique.',
            'sub_type.string' => 'Account sub type must be a string.',
        ];
    }

    protected function resetForm(): void
    {
        $this->reset(['editingId', 'code', 'name', 'parent_id', 'sub_type', 'group', 'allowed_reference_keys']);
        $this->type = AccountType::CASH->value;
        $this->is_active = true;
    }

    /**
     * @param  array<int, string>  $referenceKeys
     */
    protected function syncAllowedReferences(Account $account, array $referenceKeys): void
    {
        $account->referenceKeys()->delete();

        if ($referenceKeys === []) {
            return;
        }

        $account->referenceKeys()->createMany(
            collect($referenceKeys)
                ->map(static fn (string $referenceKey): array => ['reference_key' => $referenceKey])
                ->all()
        );
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
        return \App\Models\TransactionLine::query()->where('account_id', $accountId)->exists();
    }
}
