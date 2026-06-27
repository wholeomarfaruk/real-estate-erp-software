<?php

namespace App\Livewire\Admin\Accounts\Entry;

use App\Enums\Accounts\EntryWorkflow;
use App\Enums\Accounts\TransactionType;
use App\Models\AccountEntryCategory;
use App\Models\AccountEntryType;
use App\Models\Feature;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.admin.admin')]
class EntryTypeManager extends Component
{
    public bool $showCreateModal = false;
    public bool $showEditModal = false;
    public ?int $editingId = null;

    public string $name = '';
    public string $description = '';
    public string $slug = '';
    public string $category_key = 'receipts';
    public string $workflow = 'banking_request';
    public ?string $transaction_type = null;
    public ?string $accounting_event_key = null;
    public ?string $debit_feature_type = null;
    public ?string $debit_account_group = null;
    public ?string $debit_account_type = null;
    public ?string $credit_feature_type = null;
    public ?string $credit_account_group = null;
    public ?string $credit_account_type = null;
    public string $permission = 'accounts.entry.create';
    public int $sort_order = 0;
    public bool $is_active = true;
    public bool $is_visible = true;

    protected $rules = [
        'name' => 'required|string|max:120',
        'description' => 'nullable|string|max:500',
        'slug' => 'required|string|max:80|unique:account_entry_types,slug',
        'category_key' => 'required|exists:account_entry_categories,key',
        'workflow' => 'required|in:banking_request,direct_ledger,posting_engine',
        'transaction_type' => 'nullable|string|max:50',
        'accounting_event_key' => 'nullable|string|max:100',
        'debit_feature_type' => 'nullable|string|max:50',
        'debit_account_group' => 'nullable|string|max:50',
        'debit_account_type' => 'nullable|string|max:50',
        'credit_feature_type' => 'nullable|string|max:50',
        'credit_account_group' => 'nullable|string|max:50',
        'credit_account_type' => 'nullable|string|max:50',
        'permission' => 'required|string|max:100',
        'sort_order' => 'integer|min:0',
    ];

    public function mount(): void
    {
        $this->authorizePermission('accounts.entry.manage');
    }

    public function render(): View
    {
        $categories = AccountEntryCategory::active()->ordered()->get();
        $entries = AccountEntryType::orderBy('is_locked', 'desc')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
        $features = Feature::active()->ordered()->get();

        return view('livewire.admin.accounts.entry.entry-type-manager', [
            'categories' => $categories,
            'entries' => $entries,
            'features' => $features,
            'workflows' => EntryWorkflow::cases(),
            'transactionTypes' => TransactionType::cases(),
        ]);
    }

    public function openCreateModal(): void
    {
        $this->resetFields();
        $this->showCreateModal = true;
    }

    public function closeModals(): void
    {
        $this->showCreateModal = false;
        $this->showEditModal = false;
        $this->resetFields();
    }

    public function openEditModal(int $id): void
    {
        $entry = AccountEntryType::findOrFail($id);

        $this->editingId = $id;
        $this->name = $entry->name;
        $this->description = $entry->description;
        $this->slug = $entry->slug;
        $this->category_key = $entry->category_key;
        $this->workflow = $entry->workflow->value;
        $this->transaction_type = $entry->transaction_type;
        $this->accounting_event_key = $entry->accounting_event_key;
        $this->debit_feature_type = $entry->debit_feature_type;
        $this->debit_account_group = $entry->debit_account_group;
        $this->debit_account_type = $entry->debit_account_type;
        $this->credit_feature_type = $entry->credit_feature_type;
        $this->credit_account_group = $entry->credit_account_group;
        $this->credit_account_type = $entry->credit_account_type;
        $this->permission = $entry->permission;
        $this->sort_order = $entry->sort_order;
        $this->is_active = $entry->is_active;
        $this->is_visible = $entry->is_visible;

        $this->showEditModal = true;
    }

    public function save(): void
    {
        if ($this->editingId) {
            $this->update();
        } else {
            $this->create();
        }
    }

    private function create(): void
    {
        $validated = $this->validate();

        AccountEntryType::create(array_merge($validated, [
            'is_locked' => false,
            'form_component' => null,
        ]));

        $this->dispatch('toast', type: 'success', message: 'Entry type created successfully!');
        $this->closeModals();
    }

    private function update(): void
    {
        $rules = $this->rules;
        $rules['slug'] = 'required|string|max:80|unique:account_entry_types,slug,' . $this->editingId;

        $validated = $this->validate($rules);

        $entry = AccountEntryType::findOrFail($this->editingId);

        $entry->update($validated);

        $this->dispatch('toast', type: 'success', message: 'Entry type updated successfully!');
        $this->closeModals();
    }

    public function deleteEntry(int $id): void
    {
        $entry = AccountEntryType::findOrFail($id);

        if ($entry->isLocked()) {
            $this->dispatch('toast', type: 'error', message: 'Cannot delete locked entry types!');
            return;
        }

        $entry->delete();
        $this->dispatch('toast', type: 'success', message: 'Entry type deleted successfully!');
    }

    public function toggleActive(int $id): void
    {
        $entry = AccountEntryType::findOrFail($id);
        $entry->update(['is_active' => !$entry->is_active]);
        $this->dispatch('toast', type: 'success', message: 'Entry type visibility toggled!');
    }

    private function resetFields(): void
    {
        $this->name = '';
        $this->description = '';
        $this->slug = '';
        $this->category_key = 'receipts';
        $this->workflow = 'banking_request';
        $this->transaction_type = null;
        $this->accounting_event_key = null;
        $this->debit_feature_type = null;
        $this->debit_account_group = null;
        $this->debit_account_type = null;
        $this->credit_feature_type = null;
        $this->credit_account_group = null;
        $this->credit_account_type = null;
        $this->permission = 'accounts.entry.create';
        $this->sort_order = 0;
        $this->is_active = true;
        $this->is_visible = true;
        $this->editingId = null;
    }

    private function authorizePermission(string $permission): void
    {
        abort_unless(auth()->user()?->can($permission), 403);
    }
}
