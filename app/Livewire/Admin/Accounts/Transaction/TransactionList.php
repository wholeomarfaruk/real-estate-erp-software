<?php

namespace App\Livewire\Admin\Accounts\Transaction;

use App\Enums\Accounts\EntryMethod;
use App\Enums\Accounts\TransactionType;
use App\Helpers\TransactionReferenceFormatter;
use App\Livewire\Admin\Accounts\Concerns\InteractsWithAccountsAccess;
use App\Models\Account;
use App\Models\File;
use App\Models\Transaction;
use App\Services\Accounts\TransactionService;
use App\Livewire\Traits\WithMediaPicker;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class TransactionList extends Component
{
    use InteractsWithAccountsAccess;
    use WithPagination;
    use WithMediaPicker;

    /** Newly picked attachment file ids awaiting save (media picker writes here). */
    public array $newAttachments = [];

    public string $search = '';
    public string $typeFilter = '';
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
    public function updatedTypeFilter(): void { $this->resetPage(); }
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
        $this->newAttachments = [];
    }

    public function closeDrawer(): void
    {
        $this->showDrawer = false;
        $this->viewingId = null;
        $this->newAttachments = [];
    }

    /**
     * Append the newly picked attachments to the transaction's attachment list.
     */
    public function saveAttachments(): void
    {
        $this->authorizePermission('accounts.transaction-attachment.create');

        $transaction = $this->viewingId ? Transaction::find($this->viewingId) : null;

        if (! $transaction) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Transaction not found.']);
            return;
        }

        $newIds = array_values(array_unique(array_filter(
            array_map('intval', $this->newAttachments),
            fn ($id) => $id > 0
        )));

        if (empty($newIds)) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Please select at least one file to upload.']);
            return;
        }

        $existing = array_map('intval', $transaction->attachments ?? []);
        $merged   = array_values(array_unique(array_merge($existing, $newIds)));

        $transaction->update(['attachments' => $merged]);

        $this->newAttachments = [];
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Attachment(s) added.']);
    }

    /**
     * Reverse the currently viewed transaction — posts a mirror-image entry
     * (debit↔credit swapped) and marks the original as reversed.
     */
    public function reverse(): void
    {
        $this->authorizePermission('accounts.transaction.view');

        $transaction = $this->viewingId
            ? Transaction::with('lines')->find($this->viewingId)
            : null;

        if (! $transaction) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Transaction not found.']);
            return;
        }

        try {
            $reversal = \Illuminate\Support\Facades\DB::transaction(function () use ($transaction) {
                $service = app(TransactionService::class);

                $reversal = $service->reverse($transaction, (int) auth()->id());

                // Mark the original as reversed so the drawer reflects it immediately.
                $transaction->update([
                    'adjusted_at'             => now(),
                    'adjusted_by'             => (int) auth()->id(),
                    'adjusted_transaction_id' => $reversal->id,
                ]);

                // Roll back any source-module bookkeeping the original transaction
                // updated (e.g. a property payment schedule's paid amount), so the
                // reversal actually un-does the payment everywhere — not just the ledger.
                $this->rollBackSourceEffects($transaction);

                return $reversal;
            });

            $this->dispatch('toast', [
                'type' => 'success',
                'message' => 'Transaction reversed. Reversal TXN-' . $reversal->id . ' created.',
            ]);
        } catch (\DomainException $e) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $e->getMessage()]);
        } catch (\Throwable $e) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Reversal failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Undo the source-module side effects a payment transaction applied, so a
     * reversal cancels the payment fully (not just in the ledger).
     *
     * Currently handles property payment schedules: the original posting bumped
     * the schedule's paid_amount, so reversing must subtract it back and re-sync
     * the sale's payment status. Other reference types are left untouched.
     */
    protected function rollBackSourceEffects(Transaction $transaction): void
    {
        $isPaymentSchedule = $transaction->reference_type === \App\Models\PaymentSchedule::class
            || $transaction->reference_type === 'payment_schedule';

        if (! $isPaymentSchedule || ! $transaction->reference_id) {
            return;
        }

        $schedule = \App\Models\PaymentSchedule::with('propertySale')->find($transaction->reference_id);

        if (! $schedule) {
            return;
        }

        // Payment amount of the original = its debit (money-in) movement.
        $transaction->loadMissing('lines');
        $amount = round((float) $transaction->lines->sum('debit'), 2);

        if ($amount <= 0) {
            return;
        }

        $newPaid = round(max(0, (float) $schedule->paid_amount - $amount), 2);
        $newDue  = round(max(0, (float) $schedule->amount - $newPaid), 2);

        $schedule->update([
            'paid_amount' => $newPaid,
            'due_amount'  => $newDue,
            'status'      => $newPaid <= 0 ? 'pending' : ($newDue <= 0 ? 'paid' : 'partial'),
        ]);

        if ($schedule->propertySale) {
            app(\App\Services\Property\PaymentAllocationService::class)
                ->syncSalePaymentStatus($schedule->propertySale);
        }
    }

    public function render(): View
    {
        $this->authorizePermission('accounts.transaction.list');

        $isAdjustedTab = $this->typeFilter === 'adjusted';
        $typeValue     = $isAdjustedTab ? '' : $this->typeFilter;

        $transactions = Transaction::query()
            ->with([
                'creator:id,name',
                'lines:id,transaction_id,account_id,debit,credit,notes',
                'lines.account:id,name,code,type',
                'lines.account.bankAccount:id,account_id,bank_name,type,code',
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
            ->when($this->methodFilter !== '', fn (Builder $q) => $q->where('method', $this->methodFilter))
            ->when($this->accountFilter !== '', fn (Builder $q) => $q->whereHas(
                'lines',
                fn (Builder $l) => $l->where('account_id', $this->accountFilter)
            ))
            ->when($this->dateFrom, fn (Builder $q) => $q->whereDate('datetime', '>=', $this->dateFrom))
            ->when($this->dateTo,   fn (Builder $q) => $q->whereDate('datetime', '<=', $this->dateTo))
            // Order by actual creation order. Some transactions are posted with a
            // date-only datetime (time 00:00:00), so created_at (with id as a
            // tie-breaker) reflects true recency reliably.
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20);

        // Drawer detail
        $viewTransaction      = null;
        $viewTransactionFiles = collect();

        if ($this->showDrawer && $this->viewingId) {
            $viewTransaction = Transaction::query()
                ->with([
                    'creator:id,name',
                    'adjustedByUser:id,name',
                    'lines:id,transaction_id,account_id,debit,credit,notes',
                    'lines.account:id,name,code,type',
                    'lines.account.bankAccount:id,account_id,bank_name,type,code,ac_number',
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

        // Format reference data
        $viewTransactionReference = null;
        if ($viewTransaction) {
            $viewTransactionReference = TransactionReferenceFormatter::resolve(
                $viewTransaction->reference_type,
                $viewTransaction->reference_id,
                $viewTransaction->reference_no
            );
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

        // KPI strip — respects active date filters. Per-account movements are summed
        // from transaction_lines (the header debit/credit columns were removed); the
        // count metrics stay on the transaction header.
        $lineAgg = Transaction::query()
            ->join('transaction_lines as tl', 'tl.transaction_id', '=', 'transactions.id')
            ->when($this->dateFrom, fn (Builder $q) => $q->whereDate('datetime', '>=', $this->dateFrom))
            ->when($this->dateTo,   fn (Builder $q) => $q->whereDate('datetime', '<=', $this->dateTo))
            ->selectRaw("
                SUM(CASE WHEN type = 'income'                  THEN tl.debit  ELSE 0 END) AS total_income,
                SUM(CASE WHEN type = 'expense'                 THEN tl.credit ELSE 0 END) AS total_expense,
                SUM(CASE WHEN type = 'advance' AND tl.debit  > 0 THEN tl.debit  ELSE 0 END) AS advance_in,
                SUM(CASE WHEN type = 'advance' AND tl.credit > 0 THEN tl.credit ELSE 0 END) AS advance_out,
                SUM(tl.debit) - SUM(tl.credit)                                              AS net_position
            ")
            ->first();

        $countAgg = Transaction::query()
            ->when($this->dateFrom, fn (Builder $q) => $q->whereDate('datetime', '>=', $this->dateFrom))
            ->when($this->dateTo,   fn (Builder $q) => $q->whereDate('datetime', '<=', $this->dateTo))
            ->selectRaw("
                COUNT(*)                                                AS total_count,
                SUM(CASE WHEN adjusted_at IS NOT NULL THEN 1 ELSE 0 END) AS adjusted_count
            ")
            ->first();

        $kpi = (object) [
            'total_income'  => $lineAgg->total_income ?? 0,
            'total_expense' => $lineAgg->total_expense ?? 0,
            'advance_in'    => $lineAgg->advance_in ?? 0,
            'advance_out'   => $lineAgg->advance_out ?? 0,
            'net_position'  => $lineAgg->net_position ?? 0,
            'total_count'   => $countAgg->total_count ?? 0,
            'adjusted_count' => $countAgg->adjusted_count ?? 0,
        ];

        // Accounts for filter dropdown
        $accounts = Account::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'type']);

        return view('livewire.admin.accounts.transaction.transaction-list', [
            'transactions'               => $transactions,
            'types'                      => TransactionType::cases(),
            'methods'                    => EntryMethod::cases(),
            'accounts'                   => $accounts,
            'viewTransaction'            => $viewTransaction,
            'viewTransactionFiles'       => $viewTransactionFiles,
            'viewTransactionReference'   => $viewTransactionReference,
            'kpi'                        => $kpi,
            'typeCounts'                 => $typeCounts,
        ])->layout('layouts.admin.admin');
    }
}
