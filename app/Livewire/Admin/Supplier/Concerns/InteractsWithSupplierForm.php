<?php

namespace App\Livewire\Admin\Supplier\Concerns;

use App\Livewire\Forms\SupplierForm;
use App\Models\Supplier;

/**
 * Shared "create / edit supplier" modal behaviour, reused by both the supplier
 * list and the supplier detail page so they drive the exact same modal + form.
 *
 * The host component must:
 *   - declare `public SupplierForm $form;`
 *   - use the WithMediaPicker trait and declare `public array $documents = [];`
 */
trait InteractsWithSupplierForm
{
    // Modal state
    public bool $modalOpen = false;
    public bool $editMode  = false;
    public ?int $editingId = null;

    public int $nextCode = 0;

    /** Keep the next auto-code in sync (used by the "new supplier" header). */
    protected function refreshNextCode(): void
    {
        $this->nextCode = (int) (Supplier::withTrashed()->max('id') ?? 0) + 1;
    }

    /** WithMediaPicker writes $this->documents; mirror it into the form. */
    public function updatedDocuments(): void
    {
        $this->form->documents = $this->documents;
    }

    public function openCreate(): void
    {
        $this->authorizePermission('supplier.create');
        $this->resetModal();
        $this->editMode  = false;
        $this->editingId = null;
        $this->modalOpen = true;
    }

    public function edit(int $id): void
    {
        $this->authorizePermission('supplier.edit');

        $s = Supplier::findOrFail($id);

        $this->resetModal();
        $this->editMode  = true;
        $this->editingId = $id;

        $this->form->name             = $s->name;
        $this->form->contact_person   = $s->contact_person ?? '';
        $this->form->email            = $s->email ?? '';
        $this->form->phone            = $s->phone ?? '';
        $this->form->alternate_phone  = $s->alternate_phone ?? '';
        $this->form->address          = $s->address ?? '';
        $this->form->trade_license_no = $s->trade_license_no ?? '';
        $this->form->tin_no           = $s->tin_no ?? '';
        $this->form->bin_no           = $s->bin_no ?? '';
        $this->form->notes            = $s->notes ?? '';
        $this->form->status           = $s->status_key; // 'active'|'inactive'|'blocked'

        $this->documents       = array_values(array_filter((array) ($s->documents ?? [])));
        $this->form->documents = $this->documents;

        $this->modalOpen = true;
    }

    public function save(): void
    {
        if ($this->editMode) {
            $this->authorizePermission('supplier.edit');
        } else {
            $this->authorizePermission('supplier.create');
        }

        $this->form->validate();

        if ($this->editMode && $this->editingId) {
            $s = Supplier::findOrFail($this->editingId);
            $s->update($this->form->toAttributes() + ['updated_by' => auth()->id()]);
            $label = "Supplier \"{$s->name}\" updated.";
        } else {
            $s = Supplier::create($this->form->toAttributes() + ['created_by' => auth()->id()]);
            $this->refreshNextCode();
            $label = "Supplier \"{$s->name}\" created.";
        }

        $this->resetModal();
        $this->modalOpen = false;
        $this->editMode  = false;
        $this->editingId = null;

        $this->dispatch('toast', ['type' => 'success', 'message' => $label]);
        $this->dispatch('supplier-saved');
    }

    public function closeModal(): void
    {
        $this->modalOpen = false;
        $this->editMode  = false;
        $this->editingId = null;
        $this->resetModal();
    }

    protected function resetModal(): void
    {
        $this->form->reset();
        $this->documents = [];
    }
}
