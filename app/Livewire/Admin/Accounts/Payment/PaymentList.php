<?php

namespace App\Livewire\Admin\Accounts\Payment;

use App\Enums\Accounts\AccountType;
use App\Enums\Accounts\EntryMethod;
use App\Livewire\Admin\Accounts\Concerns\InteractsWithAccountsAccess;
use App\Livewire\Traits\WithMediaPicker;
use App\Models\Account;
use App\Models\Payment;
use App\Services\Accounts\AccountingEntryService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class PaymentList extends Component
{
    use InteractsWithAccountsAccess;
    use WithMediaPicker;
    use WithPagination;

    public string $search = '';

    public string $methodFilter = '';

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public bool $showFormModal = false;

    public ?int $editingId = null;

    public ?string $payment_no = null;

    public string $date = '';

    public string $method = '';

    public ?int $payment_account_id = null;

    public ?int $purpose_account_id = null;

    public float|int|string $amount = '';

    public ?string $payee_name = null;

    public ?string $reference_type = null;

    public ?int $reference_id = null;

    public ?string $notes = null;

    /**
     * @var array<int, int|string>
     */
    public array $attachment_ids = [];

    public bool $showAttachmentModal = false;

    public ?int $attachmentPaymentId = null;

    protected string $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->authorizePermission('accounts.payment.list');

        $this->method = EntryMethod::CASH->value;
        $this->date = now()->toDateString();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedMethodFilter(): void
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
        $this->authorizePermission('accounts.payment.create');

        $this->resetForm();
        $this->showFormModal = true;
    }

    public function openEditModal(int $id): void
    {
        $this->authorizePermission('accounts.payment.edit');

        $payment = Payment::query()->find($id);

        if (! $payment) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Payment not found.']);

            return;
        }

        $this->editingId = (int) $payment->id;
        $this->payment_no = $payment->payment_no;
        $this->date = optional($payment->date)->toDateString() ?? now()->toDateString();
        $this->method = $payment->method?->value ?? EntryMethod::CASH->value;
        $this->payment_account_id = $payment->payment_account_id ? (int) $payment->payment_account_id : null;
        $this->purpose_account_id = $payment->purpose_account_id ? (int) $payment->purpose_account_id : null;
        $this->amount = (float) $payment->amount;
        $this->payee_name = $payment->payee_name;
        $this->reference_type = $payment->reference_type;
        $this->reference_id = $payment->reference_id ? (int) $payment->reference_id : null;
        $this->notes = $payment->notes;
        $this->attachment_ids = $payment->transaction?->attachments?->pluck('file_id')->map(static fn ($id): int => (int) $id)->values()->all() ?? [];
        $this->showFormModal = true;
    }

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
    }

    public function openAttachmentModal(int $id): void
    {
        $this->authorizePermission('accounts.transaction-attachment.view');

        $exists = Payment::query()->whereKey($id)->exists();

        if (! $exists) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Payment not found.']);

            return;
        }

        $this->attachmentPaymentId = $id;
        $this->showAttachmentModal = true;
    }

    public function closeAttachmentModal(): void
    {
        $this->showAttachmentModal = false;
        $this->attachmentPaymentId = null;
    }

    public function removeAttachment(int $attachmentId): void
    {
        $this->authorizePermission('accounts.payment.edit');

        if (! $this->attachmentPaymentId) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'No payment selected.']);

            return;
        }

        $payment = Payment::query()->find($this->attachmentPaymentId);

        if (! $payment || ! $payment->transaction_id) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Payment transaction not found.']);

            return;
        }

        $transaction = $payment->transaction;

        if (! $transaction) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Payment transaction not found.']);

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
        $permission = $this->editingId ? 'accounts.payment.edit' : 'accounts.payment.create';
        $this->authorizePermission($permission);

        $validated = $this->validate($this->rules(), $this->messages());

        try {
            $payment = $this->editingId
                ? Payment::query()->findOrFail($this->editingId)
                : null;

            app(AccountingEntryService::class)->savePayment($validated, $payment, (int) auth()->id());
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);

            return;
        }

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Payment saved successfully.']);

        $this->showFormModal = false;
        $this->resetForm();
    }

    public function deletePayment(int $id): void
    {
        $this->authorizePermission('accounts.payment.delete');

        $payment = Payment::query()->find($id);

        if (! $payment) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Payment not found.']);

            return;
        }

        try {
            app(AccountingEntryService::class)->deletePayment($payment);
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Payment deleted successfully.']);
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);
        }
    }

    public function render(): View
    {
        $this->authorizePermission('accounts.payment.list');

        $payments = Payment::query()
            ->with([
                'paymentAccount:id,name,code,type',
                'purposeAccount:id,name,code,type',
                'creator:id,name',
                'transaction:id',
                'transaction.attachments:id,transaction_id,file_id',
            ])
            ->when($this->search !== '', function (Builder $query): void {
                $search = '%'.$this->search.'%';

                $query->where(function (Builder $subQuery) use ($search): void {
                    $subQuery->where('payment_no', 'like', $search)
                        ->orWhere('payee_name', 'like', $search)
                        ->orWhere('notes', 'like', $search)
                        ->orWhere('reference_type', 'like', $search)
                        ->orWhereRaw('CAST(reference_id as CHAR) like ?', [$search]);
                });
            })
            ->when($this->methodFilter !== '', fn (Builder $query): Builder => $query->where('method', $this->methodFilter))
            ->when($this->dateFrom, fn (Builder $query): Builder => $query->whereDate('date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn (Builder $query): Builder => $query->whereDate('date', '<=', $this->dateTo))
            ->latest('date')
            ->latest('id')
            ->paginate(15);

        $attachmentPayment = null;

        if ($this->showAttachmentModal && $this->attachmentPaymentId) {
            $attachmentPayment = Payment::query()
                ->with([
                    'transaction:id',
                    'transaction.attachments:id,transaction_id,file_id,category,notes,created_by,created_at',
                    'transaction.attachments.file:id,name,type,extension',
                ])
                ->find($this->attachmentPaymentId);

            if (! $attachmentPayment) {
                $this->showAttachmentModal = false;
                $this->attachmentPaymentId = null;
            }
        }

        $accounts = Account::query()
            ->where('is_active', true)
            ->orderBy('type')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'type']);

        $groupedAccounts = $accounts->groupBy(fn (Account $account): string => $account->type?->value ?? AccountType::ASSET->value);

        return view('livewire.admin.accounts.payment.payment-list', [
            'payments' => $payments,
            'methods' => EntryMethod::cases(),
            'types' => AccountType::cases(),
            'groupedAccounts' => $groupedAccounts,
            'attachmentPayment' => $attachmentPayment,
        ])->layout('layouts.admin.admin');
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'payment_no' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('payments', 'payment_no')->ignore($this->editingId),
            ],
            'date' => ['required', 'date'],
            'method' => ['required', Rule::in(array_map(static fn (EntryMethod $method): string => $method->value, EntryMethod::cases()))],
            'payment_account_id' => ['required', 'exists:accounts,id'],
            'purpose_account_id' => ['required', 'exists:accounts,id'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'payee_name' => ['nullable', 'string', 'max:150'],
            'reference_type' => ['nullable', 'string', 'max:100'],
            'reference_id' => ['nullable', 'integer', 'min:1'],
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
            'payment_account_id.required' => 'Payment account is required.',
            'purpose_account_id.required' => 'Purpose account is required for double-entry.',
            'amount.required' => 'Amount is required.',
            'amount.gt' => 'Amount must be greater than zero.',
        ];
    }

    protected function resetForm(): void
    {
        $this->reset([
            'editingId',
            'payment_no',
            'payment_account_id',
            'purpose_account_id',
            'amount',
            'payee_name',
            'reference_type',
            'reference_id',
            'notes',
            'attachment_ids',
        ]);

        $this->method = EntryMethod::CASH->value;
        $this->date = now()->toDateString();
    }
}
