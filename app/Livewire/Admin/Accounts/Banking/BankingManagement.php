<?php

namespace App\Livewire\Admin\Accounts\Banking;

use App\Enums\Accounts\PaymentRequestSourceType;
use App\Enums\Accounts\TransactionType;
use App\Models\BankAccount;
use App\Models\BankingPaymentRequest;
use App\Models\PayrollPayment;
use App\Models\PurchaseFund;
use App\Models\TransactionCategory;
use App\Services\Hrm\PayrollService;
use App\Services\Inventory\FundReleaseService;
use App\Services\Inventory\PurchaseInvoicePaymentService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class BankingManagement extends Component
{
    use WithPagination;

    // ─── Filters ─────────────────────────────────────────────────────────────
    public string $statusFilter   = '';
    public string $sourceFilter   = '';
    public string $accountFilter  = '';
    public string $search         = '';

    // ─── Detail drawer ────────────────────────────────────────────────────────
    public bool $showDrawer  = false;
    public ?int $viewingId   = null;

    // ─── Create modal ─────────────────────────────────────────────────────────
    public bool    $showCreateModal          = false;
    public string  $source_type              = 'expense';
    public ?int    $transaction_category_id  = null;
    public string  $amount                   = '';
    public string  $description              = '';
    public ?int    $bank_account_id          = null;
    public string  $notes                    = '';

    // ─── Rejection modal ─────────────────────────────────────────────────────
    public bool   $showRejectModal   = false;
    public ?int   $rejectingId       = null;
    public string $rejection_reason  = '';

    protected string $paginationTheme = 'tailwind';

    public function updatedStatusFilter(): void  { $this->resetPage(); }
    public function updatedSourceFilter(): void  { $this->resetPage(); }
    public function updatedAccountFilter(): void { $this->resetPage(); }
    public function updatedSearch(): void        { $this->resetPage(); }

    public function render()
    {
        $requests = BankingPaymentRequest::query()
            ->with(['bankAccount', 'requestedBy', 'transactionCategory'])
            ->when($this->statusFilter,  fn ($q, $s) => $q->where('status', $s))
            ->when($this->sourceFilter,  fn ($q, $s) => $q->where('source_type', $s))
            ->when($this->accountFilter, fn ($q, $a) => $q->where('bank_account_id', $a))
            ->when($this->search, fn ($q, $s) => $q->where(fn ($q) =>
                $q->where('request_no', 'like', "%{$s}%")
                  ->orWhere('description', 'like', "%{$s}%")
            ))
            ->latest()
            ->paginate(20);

        // KPI counts
        $kpi = BankingPaymentRequest::query()
            ->selectRaw("
                SUM(CASE WHEN status='pending'   THEN 1 ELSE 0 END) AS pending,
                SUM(CASE WHEN status='approved'  THEN 1 ELSE 0 END) AS approved,
                SUM(CASE WHEN status='released'  THEN 1 ELSE 0 END) AS released,
                SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) AS completed,
                SUM(CASE WHEN status='rejected'  THEN 1 ELSE 0 END) AS rejected,
                SUM(amount) AS total_amount
            ")
            ->first();

        $viewingRequest = null;
        if ($this->viewingId) {
            $viewingRequest = BankingPaymentRequest::with([
                'bankAccount.account',
                'transactionCategory',
                'requestedBy',
                'approvedBy',
                'releasedBy',
                'completedBy',
                'rejectedBy',
            ])->find($this->viewingId);
        }

        $bankAccounts = BankAccount::where('status', 'active')->orderBy('bank_name')->get();
        $filterSourceTypes = collect(TransactionType::cases())
            ->push(PaymentRequestSourceType::PAYROLL)
            ->values();
        $createSourceTypes = collect(TransactionType::cases())->values();
        $advanceCategories = TransactionCategory::query()
            ->active()
            ->where('type', 'advance')
            ->whereIn('slug', ['employee-advance', 'supplier-advance'])
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('livewire.admin.accounts.banking.banking-management', compact(
            'requests', 'kpi', 'viewingRequest', 'bankAccounts', 'filterSourceTypes', 'createSourceTypes', 'advanceCategories',
        ))->layout('layouts.admin.admin');
    }

    // ─── Drawer ───────────────────────────────────────────────────────────────
    public function openDrawer(int $id): void
    {
        $this->viewingId  = $id;
        $this->showDrawer = true;
    }

    public function closeDrawer(): void
    {
        $this->showDrawer = false;
        $this->viewingId  = null;
    }

    // ─── Create ───────────────────────────────────────────────────────────────
    public function openCreateModal(): void
    {
        $this->resetCreateForm();
        $this->showCreateModal = true;
    }

    public function closeCreateModal(): void
    {
        $this->showCreateModal = false;
        $this->resetCreateForm();
    }

    public function createRequest(): void
    {
        $this->validate([
            'source_type'             => ['required', 'in:' . implode(',', $this->manualSourceTypes())],
            'transaction_category_id' => [$this->source_type === 'advance' ? 'required' : 'nullable', 'nullable', 'exists:transaction_categories,id'],
            'amount'                  => ['required', 'numeric', 'min:0.001'],
            'description'             => ['required', 'string', 'max:500'],
            'bank_account_id'         => ['required', 'exists:bank_accounts,id'],
            'notes'                   => ['nullable', 'string', 'max:500'],
        ]);

        BankingPaymentRequest::create([
            'request_no'              => BankingPaymentRequest::generateRequestNo(),
            'source_type'             => $this->source_type,
            'transaction_category_id' => $this->transaction_category_id ?: null,
            'amount'                  => $this->amount,
            'description'             => $this->description,
            'bank_account_id'         => $this->bank_account_id,
            'status'                  => 'pending',
            'notes'                   => $this->notes ?: null,
            'requested_by'            => Auth::id(),
        ]);

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Payment request created.']);
        $this->closeCreateModal();
    }

    // ─── Status transitions ───────────────────────────────────────────────────
    public function approve(int $id): void
    {
        $this->transition($id, 'pending', 'approved', [
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);
    }

    public function release(int $id): void
    {
        $this->transition($id, 'approved', 'released', [
            'released_by' => Auth::id(),
            'released_at' => now(),
        ]);
    }

    public function markCompleted(int $id): void
    {
        $request = BankingPaymentRequest::where('id', $id)->where('status', 'released')->first();
        if (! $request) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Cannot perform this action.']);
            return;
        }

        // Fund advance from PO flow — create ledger transaction on completion
        if (
            $request->source_type === TransactionType::ADVANCE->value
            && $request->sourceable_type === PurchaseFund::class
            && $request->sourceable_id
        ) {
            try {
                app(FundReleaseService::class)->completeRelease($request, (int) Auth::id());
                $this->dispatch('toast', ['type' => 'success', 'message' => 'Fund release completed. Transaction recorded.']);
            } catch (\Throwable $e) {
                $this->dispatch('toast', ['type' => 'error', 'message' => $e->getMessage()]);
            }
            return;
        }

        // Supplier invoice payment flow
        if (
            $request->source_type === PaymentRequestSourceType::SUPPLIER->value
            && $request->sourceable_type === \App\Models\PurchaseInvoice::class
            && $request->sourceable_id
        ) {
            try {
                app(PurchaseInvoicePaymentService::class)->completePayment($request, (int) Auth::id());
                $this->dispatch('toast', ['type' => 'success', 'message' => 'Supplier payment completed. Invoice updated.']);
            } catch (\Throwable $e) {
                $this->dispatch('toast', ['type' => 'error', 'message' => $e->getMessage()]);
            }
            return;
        }

        // Expense banking flow — create DR/CR transactions on completion
        if (
            $request->source_type === TransactionType::EXPENSE->value

        ) {
            try {
                app(\App\Services\Accounts\ExpenseService::class)->completeExpense($request, (int) Auth::id());
                $this->dispatch('toast', ['type' => 'success', 'message' => 'Expense completed. Transaction recorded.']);
            } catch (\Throwable $e) {
                $this->dispatch('toast', ['type' => 'error', 'message' => $e->getMessage()]);
            }
            return;
        }

        if ($request->source_type === PaymentRequestSourceType::PAYROLL->value) {
            if ($request->sourceable_type !== PayrollPayment::class || ! $request->sourceable_id) {
                $this->dispatch('toast', ['type' => 'error', 'message' => 'Payroll requests must come from the payroll module.']);
                return;
            }

            try {
                app(PayrollService::class)->completePayrollPayment($request, (int) Auth::id());
                $this->dispatch('toast', ['type' => 'success', 'message' => 'Payroll payment completed. Transaction recorded.']);
            } catch (\Throwable $e) {
                $this->dispatch('toast', ['type' => 'error', 'message' => $e->getMessage()]);
            }
            return;
        }

        // Generic deposit / income / opening-balance — create a single ledger transaction
        // using the ledger Account linked to the BankAccount (BankAccount.account_id)
        if ($request->bank_account_id) {
            $bankAccount = \App\Models\BankAccount::find($request->bank_account_id);
            $ledgerAccountId = $bankAccount?->account_id ? (int) $bankAccount->account_id : 0;

            if ($ledgerAccountId <= 0) {
                $this->dispatch('toast', ['type' => 'error', 'message' => 'Bank account has no linked Chart of Accounts entry. Please link it first.']);
                return;
            }

            try {
                // Balanced double-entry: DR bank/cash ledger (money in) and CR a
                // generic income / opening-balance equity contra ledger so the
                // entry balances. The bank (DR) line drives the header summary.
                $contraAccount = \App\Models\Account::query()->firstOrCreate(
                    ['name' => 'Opening Balance / Income', 'type' => \App\Enums\Accounts\AccountType::LEDGER->value, 'parent_id' => null],
                    ['is_active' => true]
                );

                $transaction = app(\App\Services\Accounts\LedgerService::class)->post(
                    [
                        'datetime'                => now()->format('Y-m-d H:i:s'),
                        'type'                    => $request->source_type,
                        'transaction_category_id' => $request->transaction_category_id,
                        'reference_type'          => 'banking_payment_request',
                        'reference_id'            => $request->id,
                        'notes'                   => $request->description,
                        'created_by'              => (int) Auth::id(),
                    ],
                    [
                        ['account_id' => $ledgerAccountId,          'debit' => (float) $request->amount, 'credit' => 0,                    'notes' => 'Bank/Cash'],
                        ['account_id' => (int) $contraAccount->id,  'debit' => 0,                        'credit' => (float) $request->amount, 'notes' => 'Income / opening balance'],
                    ],
                );

                $request->update([
                    'transaction_id' => $transaction->id,
                    'status'       => 'completed',
                    'completed_by' => Auth::id(),
                    'completed_at' => now(),
                ]);

                $this->dispatch('toast', ['type' => 'success', 'message' => 'Completed. Transaction recorded.']);
            } catch (\Throwable $e) {
                $this->dispatch('toast', ['type' => 'error', 'message' => $e->getMessage()]);
            }
            return;
        }

        // Fallback — no bank account linked, just update status
        $this->transition($id, 'released', 'completed', [
            'completed_by' => Auth::id(),
            'completed_at' => now(),
        ]);
    }

    public function openRejectModal(int $id): void
    {
        $this->rejectingId      = $id;
        $this->rejection_reason = '';
        $this->showRejectModal  = true;
    }

    public function closeRejectModal(): void
    {
        $this->showRejectModal = false;
        $this->rejectingId     = null;
    }

    public function confirmReject(): void
    {
        $this->validate([
            'rejection_reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $request = BankingPaymentRequest::find($this->rejectingId);
        if ($request && in_array($request->status, ['pending', 'approved', 'released'])) {
            $request->update([
                'status'           => 'rejected',
                'rejection_reason' => $this->rejection_reason,
                'rejected_by'      => Auth::id(),
                'rejected_at'      => now(),
            ]);
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Request rejected.']);
        }

        $this->closeRejectModal();
    }

    // ─── Helper ───────────────────────────────────────────────────────────────
    private function transition(int $id, string $fromStatus, string $toStatus, array $extra): void
    {
        $request = BankingPaymentRequest::where('id', $id)->where('status', $fromStatus)->first();
        if (! $request) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Cannot perform this action.']);
            return;
        }

        $request->update(array_merge(['status' => $toStatus], $extra));
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Status updated to ' . ucfirst($toStatus) . '.']);

        if ($this->viewingId === $id) {
            $this->viewingId = $id;
        }
    }

    private function resetCreateForm(): void
    {
        $this->reset(['source_type', 'transaction_category_id', 'amount', 'description', 'bank_account_id', 'notes']);
        $this->source_type = 'expense';
    }

    /**
     * @return array<int, string>
     */
    private function manualSourceTypes(): array
    {
        return collect(TransactionType::cases())
            ->map(static fn (TransactionType $type): string => $type->value)
            ->values()
            ->all();
    }
}
