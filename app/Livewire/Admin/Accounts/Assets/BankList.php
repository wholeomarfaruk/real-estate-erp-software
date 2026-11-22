<?php

namespace App\Livewire\Admin\Accounts\Assets;

use App\Enums\Accounts\AccountType;
use App\Enums\Accounts\BankAccountType;
use App\Enums\Accounts\TransactionType;
use App\Livewire\Traits\WithMediaPicker;
use App\Models\Account;
use App\Models\BankAccount;
use App\Models\BankingPaymentRequest;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class BankList extends Component
{
    use WithPagination, WithMediaPicker, \App\Livewire\Concerns\WithProfilePicker;

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

    // ─── Fund entry modal (double entry) ──────────────────────────────────────
    // deposit    : Dr bank/cash        · Cr source account (income, capital, loan…)
    // transfer   : Dr to bank/cash     · Cr from bank/cash          (contra)
    // opening    : Dr bank/cash        · Cr equity / opening account
    // adjustment : Dr any account      · Cr any account
    public const DEPOSIT_MODES = ['deposit', 'transfer', 'opening', 'adjustment'];
    public const DEPOSIT_TYPES = ['income', 'owner_investment', 'loan_received', 'customer_receipt', 'advance_receipt'];

    public bool    $showDepositModal             = false;
    public string  $deposit_mode                 = 'deposit';
    public ?int    $deposit_bank_account_id      = null;   // bank/cash receiving the money (Dr)
    public ?int    $deposit_from_bank_account_id = null;   // transfer: bank/cash sending the money (Cr)
    public string  $deposit_source_type          = 'income';
    public ?int    $deposit_counter_account_id   = null;   // deposit / opening: Cr account · adjustment: Cr account
    public ?int    $deposit_debit_account_id     = null;   // adjustment: Dr account
    public ?int    $deposit_category_id          = null;
    public string  $deposit_amount               = '';
    public string  $deposit_date                 = '';
    public string  $deposit_description          = '';
    public string  $deposit_reference_no         = '';

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
            $flows = DB::table('transaction_lines as tl')
                ->join('transactions as t', 't.id', '=', 'tl.transaction_id')
                ->whereIn('tl.account_id', $linkedAccountIds)
                ->whereDate('t.datetime', today())
                ->selectRaw('tl.account_id, SUM(tl.debit) as total_debit, SUM(tl.credit) as total_credit')
                ->groupBy('tl.account_id')
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
                    $acctId = (int) $viewingAccount->account_id;

                    $viewingAccount->recent_transactions = Transaction::whereHas(
                        'lines',
                        fn ($l) => $l->where('account_id', $acctId)
                    )
                        ->with(['lines' => fn ($l) => $l->where('account_id', $acctId)])
                        ->latest('datetime')
                        ->limit(5)
                        ->get()
                        ->each(function (Transaction $txn): void {
                            // This account's own movement on the transaction.
                            $txn->acct_debit  = (float) $txn->lines->sum('debit');
                            $txn->acct_credit = (float) $txn->lines->sum('credit');
                        });
                }
            }
        }

        $assetAccounts = Account::where('is_active',true)->get();

        $types = AccountType::cases();

        // Deposit modal data
        $depositSourceTypes = collect(self::DEPOSIT_TYPES)
            ->map(fn ($v) => TransactionType::from($v))
            ->values();

        // Ledger accounts for the "other side" of the entry
        $ledgerAccounts = $this->showDepositModal
            ? Account::where('is_active', true)->orderBy('code')->get(['id', 'code', 'name', 'type'])
            : collect();

        // Live Dr / Cr preview
        $depositPreview = null;
        if ($this->showDepositModal) {
            [$dr, $cr, $tp] = $this->depositEntry();
            $names = Account::whereIn('id', array_filter([$dr, $cr]))->get(['id', 'code', 'name'])->keyBy('id');
            $depositPreview = [
                'debit'  => $dr ? trim(($names[$dr]->code ?? '') . ' ' . ($names[$dr]->name ?? '')) : null,
                'credit' => $cr ? trim(($names[$cr]->code ?? '') . ' ' . ($names[$cr]->name ?? '')) : null,
                'type'   => TransactionType::tryFrom($tp)?->label() ?? $tp,
                'amount' => (float) $this->deposit_amount,
            ];
        }

        $depositCategories = $this->deposit_source_type
            ? TransactionCategory::query()
                ->where('is_active', true)
                ->where('type', $this->deposit_source_type)
                ->orderBy('name')
                ->get(['id', 'name'])
            : collect();

        $allBankAccounts = BankAccount::with('account:id,name,code')
            ->where('status', 'active')
            ->whereNotNull('account_id')
            ->orderBy('bank_name')
            ->get(['id', 'bank_name', 'type', 'ac_number', 'account_id']);

        return view('livewire.admin.accounts.assets.bank-list', compact(
            'accounts',
            'typeBalances',
            'totalBalance',
            'activeCount',
            'viewingAccount',
            'assetAccounts',
            'types',
            'depositSourceTypes',
            'depositCategories',
            'ledgerAccounts',
            'depositPreview',
            'allBankAccounts',
        ))->layout('layouts.admin.admin');
    }

    // ─── Deposit / Opening Balance modal ──────────────────────────────────────

    public function openDepositModal(?int $bankAccountId = null, string $mode = 'deposit'): void
    {
        $this->resetValidation();
        $this->deposit_mode                 = in_array($mode, self::DEPOSIT_MODES, true) ? $mode : 'deposit';
        $this->deposit_bank_account_id      = $bankAccountId;
        $this->deposit_from_bank_account_id = null;
        $this->deposit_source_type          = 'income';
        $this->deposit_counter_account_id   = null;
        $this->deposit_debit_account_id     = null;
        $this->deposit_category_id          = null;
        $this->deposit_amount               = '';
        $this->deposit_date                 = now()->toDateString();
        $this->deposit_description          = '';
        $this->deposit_reference_no         = '';
        $this->clearProfile();
        $this->applyDepositDefaults();
        $this->showDepositModal             = true;
    }

    public function closeDepositModal(): void
    {
        $this->showDepositModal = false;
        $this->resetValidation();
    }

    public function setDepositMode(string $mode): void
    {
        if (! in_array($mode, self::DEPOSIT_MODES, true)) {
            return;
        }
        $this->deposit_mode = $mode;
        $this->deposit_counter_account_id = null;
        $this->deposit_debit_account_id = null;
        $this->deposit_category_id = null;
        $this->resetValidation();
        $this->applyDepositDefaults();
    }

    public function updatedDepositSourceType(): void
    {
        $this->deposit_category_id = null;
        $this->deposit_counter_account_id = $this->defaultCounterAccount();
    }

    /** Sensible default for the other side of the entry. */
    protected function applyDepositDefaults(): void
    {
        if (in_array($this->deposit_mode, ['deposit', 'opening'], true)) {
            $this->deposit_counter_account_id = $this->defaultCounterAccount();
        }
    }

    protected function defaultCounterAccount(): ?int
    {
        $codes = match (true) {
            $this->deposit_mode === 'opening'                  => ['EQTY-CAPITAL', 'EQTY'],
            $this->deposit_source_type === 'owner_investment'  => ['EQTY-CAPITAL', 'EQTY'],
            $this->deposit_source_type === 'loan_received'     => ['LIAB-LOAN', 'LIAB'],
            $this->deposit_source_type === 'customer_receipt'  => ['ASSET-AR'],
            $this->deposit_source_type === 'advance_receipt'   => ['LIAB-ADV'],
            default                                            => ['INC', 'INC-SALES'],
        };

        foreach ($codes as $code) {
            if ($id = Account::where('code', $code)->where('is_active', true)->value('id')) {
                return $id;
            }
        }

        return null;
    }

    /** Chart account (ledger) behind a bank/cash record. */
    protected function bankLedgerId(?int $bankAccountId): ?int
    {
        return $bankAccountId ? BankAccount::whereKey($bankAccountId)->value('account_id') : null;
    }

    /**
     * Debit / credit ledger accounts + transaction type for the current mode.
     *
     * @return array{0: ?int, 1: ?int, 2: string}  [debit account, credit account, type]
     */
    protected function depositEntry(): array
    {
        return match ($this->deposit_mode) {
            'transfer'   => [$this->bankLedgerId($this->deposit_bank_account_id), $this->bankLedgerId($this->deposit_from_bank_account_id), TransactionType::TRANSFER->value],
            'opening'    => [$this->bankLedgerId($this->deposit_bank_account_id), $this->deposit_counter_account_id, TransactionType::OPENING_BALANCE->value],
            'adjustment' => [$this->deposit_debit_account_id, $this->deposit_counter_account_id, TransactionType::ADJUSTMENT->value],
            default      => [$this->bankLedgerId($this->deposit_bank_account_id), $this->deposit_counter_account_id, $this->deposit_source_type],
        };
    }

    public function createDeposit(): void
    {
        $mode = in_array($this->deposit_mode, self::DEPOSIT_MODES, true) ? $this->deposit_mode : 'deposit';
        $needsBank = $mode !== 'adjustment';

        $this->validate([
            'deposit_bank_account_id'      => [$needsBank ? 'required' : 'nullable', 'exists:bank_accounts,id'],
            'deposit_from_bank_account_id' => [$mode === 'transfer' ? 'required' : 'nullable', 'exists:bank_accounts,id', $mode === 'transfer' ? 'different:deposit_bank_account_id' : 'nullable'],
            'deposit_source_type'          => [$mode === 'deposit' ? 'required' : 'nullable', Rule::in(self::DEPOSIT_TYPES)],
            'deposit_counter_account_id'   => [in_array($mode, ['deposit', 'opening', 'adjustment'], true) ? 'required' : 'nullable', 'exists:accounts,id'],
            'deposit_debit_account_id'     => [$mode === 'adjustment' ? 'required' : 'nullable', 'exists:accounts,id'],
            'deposit_category_id'          => ['nullable', 'exists:transaction_categories,id'],
            'deposit_amount'               => ['required', 'numeric', 'min:0.001'],
            'deposit_date'                 => ['required', 'date'],
            'deposit_description'          => ['required', 'string', 'max:500'],
            'deposit_reference_no'         => ['nullable', 'string', 'max:100'],
        ] + $this->profilePickerRules(), [
            'deposit_from_bank_account_id.different' => 'Transfer needs two different accounts.',
        ], [
            'deposit_bank_account_id'      => $mode === 'transfer' ? 'to account' : 'bank / cash account',
            'deposit_from_bank_account_id' => 'from account',
            'deposit_counter_account_id'   => $mode === 'adjustment' ? 'credit account' : 'source account',
            'deposit_debit_account_id'     => 'debit account',
        ]);

        [$debitId, $creditId, $type] = $this->depositEntry();

        if (! $debitId || ! $creditId) {
            $this->addError('deposit_bank_account_id', 'This bank / cash record is not linked to a Chart of Accounts ledger.');
            return;
        }
        if ($debitId === $creditId) {
            // Show it on a field the current tab actually displays.
            $mode === 'transfer'
                ? $this->addError('deposit_from_bank_account_id', 'Both accounts post to the same ledger account — pick accounts linked to different ledgers.')
                : $this->addError('deposit_counter_account_id', 'Debit and credit accounts must be different.');
            return;
        }

        $amount = round((float) $this->deposit_amount, 3);

        BankingPaymentRequest::query()->create([
            'request_no'              => BankingPaymentRequest::generateRequestNo(),
            'source_type'             => $type,
            'transaction_category_id' => $mode === 'deposit' ? ($this->deposit_category_id ?: null) : null,
            'bank_account_id'         => $this->deposit_bank_account_id ?: null,
            'account_id'              => $debitId,
            'amount'                  => $amount,
            'payment_date'            => $this->deposit_date,
            'description'             => $this->deposit_description,
            'reference_no'            => $this->deposit_reference_no ?: null,
            'method'                  => $mode === 'adjustment' ? 'journal' : 'bank',
            'status'                  => 'pending',
            'requested_by'            => Auth::id(),
            'master_profile_id'       => $this->master_profile_id,
            'debit_account_id'        => $debitId,
            'debit_amount'            => $amount,
            'credit_account_id'       => $creditId,
            'credit_amount'           => $amount,
            'external_data'           => array_filter([
                'fund_entry_mode'      => $mode,
                'from_bank_account_id' => $this->deposit_from_bank_account_id,
                'entry_date'           => $this->deposit_date,
            ]),
        ]);

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Request submitted to banking — posts after approval.']);
        $this->closeDepositModal();
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
