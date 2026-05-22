<?php

namespace App\Livewire\Admin\Accounts\Banking;

use App\Enums\Accounts\EntryMethod;
use App\Enums\Accounts\TransactionType;
use App\Models\Account;
use App\Models\BankAccount;
use App\Models\TransactionLine;
use App\Services\Accounts\LedgerService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin.admin')]
class BankingTransactionList extends Component
{
    use WithPagination;

    // ─── Filters ─────────────────────────────────────────────────────────────
    public ?int    $bankAccountFilter = null;
    public string  $typeFilter        = '';
    public string  $dateFrom          = '';
    public string  $dateTo            = '';
    public string  $search            = '';

    // ─── Deposit modal ────────────────────────────────────────────────────────
    public bool    $showDepositModal   = false;
    public ?int    $deposit_account_id = null;
    public ?int    $deposit_contra_id  = null;
    public string  $deposit_amount     = '';
    public string  $deposit_date       = '';
    public string  $deposit_method     = '';
    public string  $deposit_reference  = '';
    public string  $deposit_notes      = '';

    // ─── Withdrawal modal ────────────────────────────────────────────────────
    public bool    $showWithdrawalModal    = false;
    public ?int    $withdrawal_account_id  = null;
    public ?int    $withdrawal_contra_id   = null;
    public string  $withdrawal_amount      = '';
    public string  $withdrawal_date        = '';
    public string  $withdrawal_method      = '';
    public string  $withdrawal_reference   = '';
    public string  $withdrawal_notes       = '';

    // ─── Transfer modal ──────────────────────────────────────────────────────
    public bool    $showTransferModal  = false;
    public ?int    $transfer_from_id   = null;
    public ?int    $transfer_to_id     = null;
    public string  $transfer_amount    = '';
    public string  $transfer_date      = '';
    public string  $transfer_method    = '';
    public string  $transfer_reference = '';
    public string  $transfer_notes     = '';

    protected string $paginationTheme = 'tailwind';

    public function updatedBankAccountFilter(): void { $this->resetPage(); }
    public function updatedTypeFilter(): void        { $this->resetPage(); }
    public function updatedDateFrom(): void          { $this->resetPage(); }
    public function updatedDateTo(): void            { $this->resetPage(); }
    public function updatedSearch(): void            { $this->resetPage(); }

    // ─── Render ───────────────────────────────────────────────────────────────

    public function render()
    {
        $bankAccounts   = BankAccount::whereNotNull('account_id')
            ->where('status', 'active')
            ->orderBy('bank_name')
            ->get();

        $bankAccountIds = $bankAccounts->pluck('account_id');

        $selectedAccountId = null;
        if ($this->bankAccountFilter) {
            $selectedAccountId = $bankAccounts->firstWhere('id', $this->bankAccountFilter)?->account_id;
        }

        $baseQuery = TransactionLine::query()
            ->with(['transaction', 'account'])
            ->whereIn('account_id', $bankAccountIds)
            ->when($selectedAccountId, fn ($q) => $q->where('account_id', $selectedAccountId))
            ->when($this->typeFilter, fn ($q) => $q->whereHas('transaction',
                fn ($q2) => $q2->where('type', $this->typeFilter)
            ))
            ->when($this->search, fn ($q) => $q->whereHas('transaction', fn ($q2) =>
                $q2->where('notes', 'like', "%{$this->search}%")
                   ->orWhere('reference_no', 'like', "%{$this->search}%")
                   ->orWhere('name', 'like', "%{$this->search}%")
            ))
            ->when($this->dateFrom, fn ($q) => $q->whereHas('transaction',
                fn ($q2) => $q2->whereDate('datetime', '>=', $this->dateFrom)
            ))
            ->when($this->dateTo, fn ($q) => $q->whereHas('transaction',
                fn ($q2) => $q2->whereDate('datetime', '<=', $this->dateTo)
            ));

        $kpi = (clone $baseQuery)
            ->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->first();

        $lines = (clone $baseQuery)
            ->orderByRaw('(SELECT datetime FROM transactions WHERE id = transaction_id) DESC')
            ->orderByDesc('id')
            ->paginate(25);

        $contraAccounts = Account::where('is_active', true)
            ->where('type', 'ledger')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'account_type']);

        return view('livewire.admin.accounts.banking.banking-transaction-list', [
            'lines'          => $lines,
            'kpi'            => $kpi,
            'bankAccounts'   => $bankAccounts,
            'contraAccounts' => $contraAccounts,
            'entryMethods'   => EntryMethod::cases(),
            'transactionTypes' => TransactionType::cases(),
        ]);
    }

    // ─── Deposit ──────────────────────────────────────────────────────────────

    public function openDepositModal(): void
    {
        $this->reset([
            'deposit_account_id', 'deposit_contra_id', 'deposit_amount',
            'deposit_method', 'deposit_reference', 'deposit_notes',
        ]);
        $this->deposit_date    = now()->toDateString();
        $this->showDepositModal = true;
    }

    public function closeDepositModal(): void
    {
        $this->showDepositModal = false;
    }

    public function deposit(LedgerService $ledger): void
    {
        $this->validate([
            'deposit_account_id' => ['required', 'integer', 'exists:bank_accounts,id'],
            'deposit_contra_id'  => ['required', 'integer', 'exists:accounts,id'],
            'deposit_amount'     => ['required', 'numeric', 'min:0.001'],
            'deposit_date'       => ['required', 'date'],
            'deposit_method'     => ['required', 'in:' . implode(',', array_column(EntryMethod::cases(), 'value'))],
            'deposit_reference'  => ['nullable', 'string', 'max:100'],
            'deposit_notes'      => ['nullable', 'string', 'max:500'],
        ]);

        $bankAccount = BankAccount::findOrFail((int) $this->deposit_account_id);

        if (! $bankAccount->account_id) {
            $this->addError('deposit_account_id', 'This bank account is not linked to a ledger account.');
            return;
        }

        $amount = round((float) $this->deposit_amount, 3);

        try {
            $ledger->post(
                [
                    'datetime'     => $this->deposit_date . ' 00:00:00',
                    'type'         => TransactionType::RECEIPT->value,
                    'method'       => $this->deposit_method,
                    'reference_no' => $this->deposit_reference ?: null,
                    'notes'        => $this->deposit_notes ?: ('Deposit – ' . $bankAccount->bank_name),
                    'created_by'   => Auth::id(),
                ],
                [
                    ['account_id' => $bankAccount->account_id, 'debit' => $amount,  'credit' => 0,       'description' => 'Deposit'],
                    ['account_id' => $this->deposit_contra_id, 'debit' => 0,        'credit' => $amount, 'description' => 'Deposit contra'],
                ]
            );

            $this->dispatch('toast', ['type' => 'success', 'message' => 'Deposit recorded successfully.']);
            $this->showDepositModal = false;
        } catch (\Throwable $e) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    // ─── Withdrawal ───────────────────────────────────────────────────────────

    public function openWithdrawalModal(): void
    {
        $this->reset([
            'withdrawal_account_id', 'withdrawal_contra_id', 'withdrawal_amount',
            'withdrawal_method', 'withdrawal_reference', 'withdrawal_notes',
        ]);
        $this->withdrawal_date    = now()->toDateString();
        $this->showWithdrawalModal = true;
    }

    public function closeWithdrawalModal(): void
    {
        $this->showWithdrawalModal = false;
    }

    public function withdraw(LedgerService $ledger): void
    {
        $this->validate([
            'withdrawal_account_id' => ['required', 'integer', 'exists:bank_accounts,id'],
            'withdrawal_contra_id'  => ['required', 'integer', 'exists:accounts,id'],
            'withdrawal_amount'     => ['required', 'numeric', 'min:0.001'],
            'withdrawal_date'       => ['required', 'date'],
            'withdrawal_method'     => ['required', 'in:' . implode(',', array_column(EntryMethod::cases(), 'value'))],
            'withdrawal_reference'  => ['nullable', 'string', 'max:100'],
            'withdrawal_notes'      => ['nullable', 'string', 'max:500'],
        ]);

        $bankAccount = BankAccount::findOrFail((int) $this->withdrawal_account_id);

        if (! $bankAccount->account_id) {
            $this->addError('withdrawal_account_id', 'This bank account is not linked to a ledger account.');
            return;
        }

        $amount = round((float) $this->withdrawal_amount, 3);

        try {
            $ledger->post(
                [
                    'datetime'     => $this->withdrawal_date . ' 00:00:00',
                    'type'         => TransactionType::PAYMENT->value,
                    'method'       => $this->withdrawal_method,
                    'reference_no' => $this->withdrawal_reference ?: null,
                    'notes'        => $this->withdrawal_notes ?: ('Withdrawal – ' . $bankAccount->bank_name),
                    'created_by'   => Auth::id(),
                ],
                [
                    ['account_id' => $this->withdrawal_contra_id, 'debit' => $amount, 'credit' => 0,       'description' => 'Withdrawal contra'],
                    ['account_id' => $bankAccount->account_id,    'debit' => 0,       'credit' => $amount, 'description' => 'Withdrawal'],
                ]
            );

            $this->dispatch('toast', ['type' => 'success', 'message' => 'Withdrawal recorded successfully.']);
            $this->showWithdrawalModal = false;
        } catch (\Throwable $e) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    // ─── Transfer ─────────────────────────────────────────────────────────────

    public function openTransferModal(): void
    {
        $this->reset([
            'transfer_from_id', 'transfer_to_id', 'transfer_amount',
            'transfer_method', 'transfer_reference', 'transfer_notes',
        ]);
        $this->transfer_date    = now()->toDateString();
        $this->showTransferModal = true;
    }

    public function closeTransferModal(): void
    {
        $this->showTransferModal = false;
    }

    public function transfer(LedgerService $ledger): void
    {
        $this->validate([
            'transfer_from_id'   => ['required', 'integer', 'exists:bank_accounts,id'],
            'transfer_to_id'     => ['required', 'integer', 'exists:bank_accounts,id', 'different:transfer_from_id'],
            'transfer_amount'    => ['required', 'numeric', 'min:0.001'],
            'transfer_date'      => ['required', 'date'],
            'transfer_method'    => ['required', 'in:' . implode(',', array_column(EntryMethod::cases(), 'value'))],
            'transfer_reference' => ['nullable', 'string', 'max:100'],
            'transfer_notes'     => ['nullable', 'string', 'max:500'],
        ]);

        $fromAccount = BankAccount::findOrFail((int) $this->transfer_from_id);
        $toAccount   = BankAccount::findOrFail((int) $this->transfer_to_id);

        if (! $fromAccount->account_id) {
            $this->addError('transfer_from_id', 'Source account is not linked to a ledger account.');
            return;
        }
        if (! $toAccount->account_id) {
            $this->addError('transfer_to_id', 'Destination account is not linked to a ledger account.');
            return;
        }

        $amount = round((float) $this->transfer_amount, 3);

        try {
            $ledger->post(
                [
                    'datetime'     => $this->transfer_date . ' 00:00:00',
                    'type'         => TransactionType::TRANSFER->value,
                    'method'       => $this->transfer_method,
                    'reference_no' => $this->transfer_reference ?: null,
                    'notes'        => $this->transfer_notes ?: ($fromAccount->bank_name . ' → ' . $toAccount->bank_name),
                    'created_by'   => Auth::id(),
                ],
                [
                    ['account_id' => $toAccount->account_id,   'debit' => $amount, 'credit' => 0,       'description' => 'Transfer in'],
                    ['account_id' => $fromAccount->account_id, 'debit' => 0,       'credit' => $amount, 'description' => 'Transfer out'],
                ]
            );

            $this->dispatch('toast', ['type' => 'success', 'message' => 'Transfer recorded successfully.']);
            $this->showTransferModal = false;
        } catch (\Throwable $e) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }
}
