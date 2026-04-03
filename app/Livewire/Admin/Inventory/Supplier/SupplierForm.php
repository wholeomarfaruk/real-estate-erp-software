<?php

namespace App\Livewire\Admin\Inventory\Supplier;

use App\Models\Supplier;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class SupplierForm extends Component
{
    public ?Supplier $supplierRecord = null;

    public ?int $supplierId = null;

    public bool $editMode = false;

    public string $name = '';

    public ?string $contact_person = null;

    public ?string $phone = null;

    public ?string $secondary_phone = null;

    public ?string $email = null;

    public ?string $address = null;

    public bool $status = true;

    public function mount(?Supplier $supplier = null): void
    {
        if ($supplier && $supplier->exists) {
            $this->authorizeUpdate();

            $this->editMode = true;
            $this->supplierRecord = $supplier;
            $this->supplierId = $supplier->id;
            $this->name = $supplier->name;
            $this->contact_person = $supplier->contact_person;
            $this->phone = $supplier->phone;
            $this->secondary_phone = $supplier->secondary_phone;
            $this->email = $supplier->email;
            $this->address = $supplier->address;
            $this->status = (bool) $supplier->status;

            return;
        }

        $this->authorizeCreate();
    }

    public function save()
    {
        if ($this->editMode) {
            $this->authorizeUpdate();
        } else {
            $this->authorizeCreate();
        }

        $validated = $this->validate($this->rules());

        DB::transaction(function () use ($validated): void {
            if ($this->editMode && $this->supplierRecord) {
                $this->supplierRecord->update($validated);

                return;
            }

            Supplier::query()->create($validated);
        });

        $this->dispatch('toast', [
            'type' => 'success',
            'message' => $this->editMode ? 'Supplier updated successfully.' : 'Supplier created successfully.',
        ]);

        return redirect()->route('admin.inventory.suppliers.index');
    }

    public function render(): View
    {
        if ($this->editMode) {
            $this->authorizeUpdate();
        } else {
            $this->authorizeCreate();
        }

        return view('livewire.admin.inventory.supplier.supplier-form')
            ->layout('layouts.admin.admin');
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'secondary_phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'boolean'],
        ];
    }

    protected function authorizeCreate(): void
    {
        abort_unless(auth()->user()?->can('inventory.supplier.create'), 403, 'Unauthorized action.');
    }

    protected function authorizeUpdate(): void
    {
        abort_unless(auth()->user()?->can('inventory.supplier.update'), 403, 'Unauthorized action.');
    }

    protected function authorizeView(): void
    {
        abort_unless(auth()->user()?->can('inventory.supplier.view'), 403, 'Unauthorized action.');
    }
}
