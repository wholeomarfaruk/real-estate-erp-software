<?php

namespace App\Livewire\Admin\Accounts\Assets;

use App\Enums\Accounts\AccountSubType;
use App\Enums\Accounts\BankAccountType;
use App\Livewire\Traits\WithMediaPicker;
use App\Models\Account;
use App\Models\BankAccount;
use App\Models\Transaction;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class BankList extends Component
{
    use WithPagination, WithMediaPicker;

    // ─── Filters ─────────────────────────────────────────────────────────────
    public string $search      = '';
    public string $statusFilter = '';
    public string $typeFilter   = '';

    // ─── Form modal ───────────────────────────────────────────────────────────
    public bool    $showFormModal = false;
    public ?int    $editingId     = null;
    public string  $type          = 'bank';
    public ?string $code          = null;
    public string  $bank_name     = '';
    public string  $ac_number     = '';
    public string  $holder_name   = '';
    public string  $branch        = '';
    public ?string $route_code    = null;
    public ?string $swift_code    = null;
    public ?string $address       = null;
    public ?string $note          = null;
    public ?string $phone         = null;
    public ?string $email         = null;
    public ?int    $account_id    = null;
    public string  $status        = 'active';
    public mixed   $files         = null;

    // ─── Detail modal ─────────────────────────────────────────────────────────
    public bool $showDetailModal = false;
    public ?int $viewingId       = null;

    protected string $paginationTheme = 'tailwind';

    public function updatedSearch(): void    { $this->resetPage(); }
    public function updatedStatusFilter(): void { $this->resetPage(); }
    public function updatedTypeFilter(): void   { $this->resetPage(); }

    public function render()
    {
        // ── Paginated list ──────────────────────────────────────────────────
        $accounts = BankAccount::query()
            ->with('account')
            ->when($this->search, fn ($q, $s) => $q->where(fn ($q) =>
                $q->where('bank_name', 'like', "%{$s}%")
                  ->orWhere('ac_number', 'like', "%{$s}%")
                  ->orWhere('code', 'like', "%{$s}%")
            ))
            ->when($this->statusFilter, fn ($q, $s) => $q->where('status', $s))
            ->when($this->typeFilter,   fn ($q, $t) => $q->where('type', $t))
            ->latest('id')
            ->paginate(12);

        // Set balance on each card
        $accounts->each(fn ($a) => $a->computed_balance = (float) ($a->account?->balance ?? 0));

        // Compute today's inflow / outflow in one query
        $linkedAccountIds = $accounts->pluck('account_id')->filter()->values()->all();
        if ($linkedAccountIds) {
            $flows = Transaction::whereIn('account_id', $linkedAccountIds)
                ->whereDate('datetime', today())
                ->selectRaw('account_id, SUM(debit) as total_debit, SUM(credit) as total_credit')
                ->groupBy('account_id')
                ->get()
                ->keyBy('account_id');

            $accounts->each(function ($account) use ($flows) {
                $row = $account->account_id ? ($flows[$account->account_id] ?? null) : null;
                $account->today_inflow  = $row ? (float) $row->total_debit  : 0;
                $account->today_outflow = $row ? (float) $row->total_credit : 0;
            });
        } else {
            $accounts->each(function ($account) {
                $account->today_inflow  = 0;
                $account->today_outflow = 0;
            });
        }

        // ── Totals bar (all active, not filtered) ───────────────────────────
        $allActive = BankAccount::with('account')->where('status', 'active')->get();
        $allActive->each(fn ($a) => $a->computed_balance = (float) ($a->account?->balance ?? 0));

        $typeBalances = [];
        foreach (BankAccountType::cases() as $case) {
            $subset = $allActive->where('type', $case->value);
            $typeBalances[$case->value] = [
                'balance' => $subset->sum('computed_balance'),
                'count'   => $subset->count(),
                'label'   => $case->label(),
                'color'   => $case->color(),
                'badge'   => $case->tailwindBadgeClass(),
            ];
        }
        $totalBalance = $allActive->sum('computed_balance');
        $activeCount  = $allActive->count();

        // ── Detail modal ────────────────────────────────────────────────────
        $viewingAccount = null;
        if ($this->viewingId) {
            $viewingAccount = BankAccount::with('account')->find($this->viewingId);
            if ($viewingAccount) {
                $viewingAccount->computed_balance = (float) ($viewingAccount->account?->balance ?? 0);
                if ($viewingAccount->account_id) {
                    $viewingAccount->recent_transactions = Transaction::where('account_id', $viewingAccount->account_id)
                        ->latest('datetime')
                        ->limit(5)
                        ->get();
                }
            }
        }

        $assetAccounts = Account::where('is_active',true)->get();
           

        $types = BankAccountType::cases();
   

        return view('livewire.admin.accounts.assets.bank-list', compact(
            'accounts',
            'typeBalances',
            'totalBalance',
            'activeCount',
            'viewingAccount',
            'assetAccounts',
            'types',
        ))->layout('layouts.admin.admin');
    }

    // ─── Detail modal ──────────────────────────────────────────────────────────
    public function openDetailModal(int $id): void
    {
        $this->viewingId       = $id;
        $this->showDetailModal = true;
    }

    public function closeDetailModal(): void
    {
        $this->showDetailModal = false;
        $this->viewingId       = null;
    }

    // ─── Form modal ───────────────────────────────────────────────────────────
    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->showFormModal = true;
    }

    public function openEditModal(int $id): void
    {
        $account = BankAccount::query()->find($id);
        if (! $account) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Account not found.']);
            return;
        }

        $this->editingId    = $account->id;
        $this->type         = $account->type    ?? 'bank';
        $this->code         = $account->code;
        $this->bank_name    = $account->bank_name;
        $this->ac_number    = $account->ac_number;
        $this->holder_name  = $account->holder_name;
        $this->branch       = $account->branch;
        $this->route_code   = $account->route_code;
        $this->swift_code   = $account->swift_code;
        $this->address      = $account->address;
        $this->note         = $account->note;
        $this->status       = $account->status;
        $this->account_id   = $account->account_id;
        $this->phone        = $account->phone;
        $this->email        = $account->email;
        $this->files        = $account->files;

        $this->showFormModal = true;
    }

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
    }

    public function save(): void
    {
        $validated = $this->validate($this->rules(), $this->messages());

        BankAccount::query()->updateOrCreate(
            ['id' => $this->editingId],
            $validated
        );

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Account saved successfully.']);
        $this->showFormModal = false;
        $this->resetForm();
    }

    public function toggleStatus(int $id): void
    {
        $account = BankAccount::query()->find($id);
        if (! $account) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Account not found.']);
            return;
        }

        $account->update([
            'status' => $account->status === 'active' ? 'inactive' : 'active',
        ]);

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Account status updated.']);
    }

    public function deleteAccount(int $id): void
    {
        $account = BankAccount::query()->find($id);
        if ($account) {
            $account->delete();
        }
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Account deleted.']);
    }

    protected function rules(): array
    {
        return [
            'type'        => ['required', Rule::in(array_column(BankAccountType::cases(), 'value'))],
            'code'        => ['nullable', 'string', 'max:50', Rule::unique('bank_accounts', 'code')->ignore($this->editingId)],
            'bank_name'   => ['required', 'string', 'max:150'],
            'ac_number'   => ['nullable', 'string', 'max:50'],
            'holder_name' => ['required', 'string', 'max:150'],
            'branch'      => ['nullable', 'string', 'max:150'],
            'route_code'  => ['nullable', 'string', 'max:50'],
            'swift_code'  => ['nullable', 'string', 'max:50'],
            'address'     => ['nullable', 'string', 'max:255'],
            'note'        => ['nullable', 'string', 'max:500'],
            'status'      => ['required', Rule::in(['active', 'inactive'])],
            'account_id'  => ['nullable', 'exists:accounts,id'],
            'phone'       => ['nullable', 'string', 'max:20'],
            'email'       => ['nullable', 'string', 'email', 'max:255'],
            'files'       => ['nullable', 'array'],
        ];
    }

    protected function messages(): array
    {
        return [
            'bank_name.required'   => 'Bank / account name is required.',
            'holder_name.required' => 'Holder name is required.',
            'code.unique'          => 'Account code must be unique.',
            'status.required'      => 'Status is required.',
            'type.required'        => 'Account type is required.',
        ];
    }

    protected function resetForm(): void
    {
        $this->reset([
            'editingId', 'code', 'bank_name', 'ac_number', 'holder_name',
            'branch', 'route_code', 'swift_code', 'address', 'note',
            'account_id', 'phone', 'email', 'files',
        ]);
        $this->type   = 'bank';
        $this->status = 'active';
    }
}
