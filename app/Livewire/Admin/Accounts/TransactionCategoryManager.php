<?php

namespace App\Livewire\Admin\Accounts;

use App\Models\TransactionCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Component;

class TransactionCategoryManager extends Component
{
    public bool   $showModal   = false;
    public bool   $isEditing   = false;
    public ?int   $editingId   = null;

    public string $name        = '';
    public string $type        = '';
    public ?int   $parent_id   = null;
    public string $description = '';
    public string $search      = '';
    public string $filterType  = '';

    protected function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:100'],
            'type'        => ['required', 'string'],
            'parent_id'   => ['nullable', 'integer', 'exists:transaction_categories,id'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->isEditing = false;
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $cat = TransactionCategory::findOrFail($id);

        $this->editingId   = $id;
        $this->name        = $cat->name;
        $this->type        = $cat->type;
        $this->parent_id   = $cat->parent_id;
        $this->description = $cat->description ?? '';
        $this->isEditing   = true;
        $this->showModal   = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'name'        => $this->name,
            'type'        => $this->type,
            'parent_id'   => $this->parent_id ?: null,
            'slug'        => Str::slug($this->name),
            'description' => $this->description ?: null,
            'is_active'   => true,
            'is_locked'   => false,
        ];

        if ($this->isEditing && $this->editingId) {
            $cat = TransactionCategory::findOrFail($this->editingId);

            if ($cat->is_locked) {
                $this->dispatch('toast', ['type' => 'error', 'message' => 'System categories cannot be edited.']);
                return;
            }

            $cat->update($data);
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Category updated.']);
        } else {
            TransactionCategory::create($data);
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Category created.']);
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function toggleActive(int $id): void
    {
        $cat = TransactionCategory::findOrFail($id);

        if ($cat->is_locked) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'System categories cannot be deactivated.']);
            return;
        }

        $cat->update(['is_active' => ! $cat->is_active]);
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Status updated.']);
    }

    public function delete(int $id): void
    {
        $cat = TransactionCategory::findOrFail($id);

        if ($cat->is_locked) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'System categories cannot be deleted.']);
            return;
        }

        if ($cat->transactions()->exists()) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Category has transactions and cannot be deleted.']);
            return;
        }

        if ($cat->children()->exists()) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Delete sub-categories first.']);
            return;
        }

        $cat->delete();
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Category deleted.']);
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function render(): View
    {
        $query = TransactionCategory::query()
            ->with('children.children.children')
            ->whereNull('parent_id')
            ->when($this->filterType, fn ($q) => $q->where('type', $this->filterType))
            ->when($this->search, function ($q) {
                $q->where(function ($q2) {
                    $q2->where('name', 'like', '%' . $this->search . '%')
                       ->orWhereHas('children', fn ($c) => $c->where('name', 'like', '%' . $this->search . '%'))
                       ->orWhereHas('children.children', fn ($c) => $c->where('name', 'like', '%' . $this->search . '%'));
                });
            })
            ->orderBy('type')
            ->orderBy('name');

        $groups = $query->get();

        $parentOptions = TransactionCategory::query()
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get(['id', 'name', 'type']);

        $types = [
            'income'          => 'Income',
            'expense'         => 'Expense',
            'advance'         => 'Advance',
            'transfer'        => 'Transfer',
            'adjustment'      => 'Adjustment',
            'opening_balance' => 'Opening Balance',
            'purchase_invoice'=> 'Purchase Invoice',
        ];

        return view('livewire.admin.accounts.transaction-category-manager', [
            'groups'        => $groups,
            'parentOptions' => $parentOptions,
            'types'         => $types,
        ])->layout('layouts.admin.admin');
    }

    private function resetForm(): void
    {
        $this->editingId   = null;
        $this->name        = '';
        $this->type        = '';
        $this->parent_id   = null;
        $this->description = '';
        $this->resetValidation();
    }
}
