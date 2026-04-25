<?php

namespace App\Livewire\Admin\Accounts\Collection;

use App\Enums\Accounts\AccountType;
use App\Enums\Accounts\CollectionType;
use App\Enums\Accounts\EntryMethod;
use App\Livewire\Admin\Accounts\Concerns\InteractsWithAccountsAccess;
use App\Livewire\Traits\WithMediaPicker;
use App\Models\Account;
use App\Models\AccountCollection;
use App\Services\Accounts\AccountingEntryService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class CollectionList extends Component
{
    use InteractsWithAccountsAccess;
    use WithMediaPicker;
    use WithPagination;

    public string $search = '';

    public string $methodFilter = '';

    public string $collectionTypeFilter = '';

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public bool $showFormModal = false;

    public ?int $editingId = null;

    public ?string $collection_no = null;

    public string $date = '';

    public string $method = '';

    public ?int $collection_account_id = null;

    public ?int $target_account_id = null;

    public float|int|string $amount = '';

    public ?string $payer_name = null;

    public string $collection_type = '';

    public ?string $reference_type = null;

    public ?int $reference_id = null;

    public ?string $notes = null;

    /**
     * @var array<int, int|string>
     */
    public array $attachment_ids = [];

    public bool $showAttachmentModal = false;

    public ?int $attachmentCollectionId = null;

    protected string $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->authorizePermission('accounts.collection.list');

        $this->method = EntryMethod::CASH->value;
        $this->collection_type = CollectionType::OTHER->value;
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

    public function updatedCollectionTypeFilter(): void
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
        $this->authorizePermission('accounts.collection.create');

        $this->resetForm();
        $this->showFormModal = true;
    }

    public function openEditModal(int $id): void
    {
        $this->authorizePermission('accounts.collection.edit');

        $collection = AccountCollection::query()->find($id);

        if (! $collection) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Collection not found.']);

            return;
        }

        $this->editingId = (int) $collection->id;
        $this->collection_no = $collection->collection_no;
        $this->date = optional($collection->date)->toDateString() ?? now()->toDateString();
        $this->method = $collection->method?->value ?? EntryMethod::CASH->value;
        $this->collection_account_id = $collection->collection_account_id ? (int) $collection->collection_account_id : null;
        $this->target_account_id = $collection->target_account_id ? (int) $collection->target_account_id : null;
        $this->amount = (float) $collection->amount;
        $this->payer_name = $collection->payer_name;
        $this->collection_type = $collection->collection_type?->value ?? CollectionType::OTHER->value;
        $this->reference_type = $collection->reference_type;
        $this->reference_id = $collection->reference_id ? (int) $collection->reference_id : null;
        $this->notes = $collection->notes;
        $this->attachment_ids = $collection->transaction?->attachments?->pluck('file_id')->map(static fn ($id): int => (int) $id)->values()->all() ?? [];
        $this->showFormModal = true;
    }

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
    }

    public function openAttachmentModal(int $id): void
    {
        $this->authorizePermission('accounts.transaction-attachment.view');

        $exists = AccountCollection::query()->whereKey($id)->exists();

        if (! $exists) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Collection not found.']);

            return;
        }

        $this->attachmentCollectionId = $id;
        $this->showAttachmentModal = true;
    }

    public function closeAttachmentModal(): void
    {
        $this->showAttachmentModal = false;
        $this->attachmentCollectionId = null;
    }

    public function removeAttachment(int $attachmentId): void
    {
        $this->authorizePermission('accounts.collection.edit');

        if (! $this->attachmentCollectionId) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'No collection selected.']);

            return;
        }

        $collection = AccountCollection::query()->find($this->attachmentCollectionId);

        if (! $collection || ! $collection->transaction_id) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Collection transaction not found.']);

            return;
        }

        $transaction = $collection->transaction;

        if (! $transaction) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Collection transaction not found.']);

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
        $permission = $this->editingId ? 'accounts.collection.edit' : 'accounts.collection.create';
        $this->authorizePermission($permission);

        $validated = $this->validate($this->rules(), $this->messages());

        try {
            $collection = $this->editingId
                ? AccountCollection::query()->findOrFail($this->editingId)
                : null;

            app(AccountingEntryService::class)->saveCollection($validated, $collection, (int) auth()->id());
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);

            return;
        }

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Collection saved successfully.']);

        $this->showFormModal = false;
        $this->resetForm();
    }

    public function deleteCollection(int $id): void
    {
        $this->authorizePermission('accounts.collection.delete');

        $collection = AccountCollection::query()->find($id);

        if (! $collection) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Collection not found.']);

            return;
        }

        try {
            app(AccountingEntryService::class)->deleteCollection($collection);
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Collection deleted successfully.']);
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);
        }
    }

    public function render(): View
    {
        $this->authorizePermission('accounts.collection.list');

        $collections = AccountCollection::query()
            ->with([
                'collectionAccount:id,name,code,type',
                'targetAccount:id,name,code,type',
                'creator:id,name',
                'transaction:id',
                'transaction.attachments:id,transaction_id,file_id',
            ])
            ->when($this->search !== '', function (Builder $query): void {
                $search = '%'.$this->search.'%';

                $query->where(function (Builder $subQuery) use ($search): void {
                    $subQuery->where('collection_no', 'like', $search)
                        ->orWhere('payer_name', 'like', $search)
                        ->orWhere('notes', 'like', $search)
                        ->orWhere('reference_type', 'like', $search)
                        ->orWhereRaw('CAST(reference_id as CHAR) like ?', [$search]);
                });
            })
            ->when($this->methodFilter !== '', fn (Builder $query): Builder => $query->where('method', $this->methodFilter))
            ->when($this->collectionTypeFilter !== '', fn (Builder $query): Builder => $query->where('collection_type', $this->collectionTypeFilter))
            ->when($this->dateFrom, fn (Builder $query): Builder => $query->whereDate('date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn (Builder $query): Builder => $query->whereDate('date', '<=', $this->dateTo))
            ->latest('date')
            ->latest('id')
            ->paginate(15);

        $attachmentCollection = null;

        if ($this->showAttachmentModal && $this->attachmentCollectionId) {
            $attachmentCollection = AccountCollection::query()
                ->with([
                    'transaction:id',
                    'transaction.attachments:id,transaction_id,file_id,category,notes,created_by,created_at',
                    'transaction.attachments.file:id,name,type,extension',
                ])
                ->find($this->attachmentCollectionId);

            if (! $attachmentCollection) {
                $this->showAttachmentModal = false;
                $this->attachmentCollectionId = null;
            }
        }

        $accounts = Account::query()
            ->where('is_active', true)
            ->orderBy('type')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'type']);

        $groupedAccounts = $accounts->groupBy(fn (Account $account): string => $account->type?->value ?? AccountType::ASSET->value);

        return view('livewire.admin.accounts.collection.collection-list', [
            'collections' => $collections,
            'methods' => EntryMethod::cases(),
            'collectionTypes' => CollectionType::cases(),
            'types' => AccountType::cases(),
            'groupedAccounts' => $groupedAccounts,
            'attachmentCollection' => $attachmentCollection,
        ])->layout('layouts.admin.admin');
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'collection_no' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('collections', 'collection_no')->ignore($this->editingId),
            ],
            'date' => ['required', 'date'],
            'method' => ['required', Rule::in(array_map(static fn (EntryMethod $method): string => $method->value, EntryMethod::cases()))],
            'collection_account_id' => ['required', 'exists:accounts,id'],
            'target_account_id' => ['required', 'exists:accounts,id'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'payer_name' => ['nullable', 'string', 'max:150'],
            'collection_type' => ['required', Rule::in(array_map(static fn (CollectionType $type): string => $type->value, CollectionType::cases()))],
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
            'collection_account_id.required' => 'Collection account is required.',
            'target_account_id.required' => 'Target account is required for double-entry.',
            'amount.required' => 'Amount is required.',
            'collection_type.required' => 'Collection type is required.',
        ];
    }

    protected function resetForm(): void
    {
        $this->reset([
            'editingId',
            'collection_no',
            'collection_account_id',
            'target_account_id',
            'amount',
            'payer_name',
            'reference_type',
            'reference_id',
            'notes',
            'attachment_ids',
        ]);

        $this->method = EntryMethod::CASH->value;
        $this->collection_type = CollectionType::OTHER->value;
        $this->date = now()->toDateString();
    }
}
