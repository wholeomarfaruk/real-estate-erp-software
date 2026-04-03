<?php

namespace App\Livewire\Admin\Inventory\Product;

use App\Livewire\Admin\Inventory\Concerns\InteractsWithInventoryAccess;
use App\Models\ProductUnit;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

class UnitList extends Component
{
    use InteractsWithInventoryAccess;

    public string $search = '';

    public ?int $editingId = null;

    public string $name = '';

    public ?string $code = null;

    public bool $status = true;

    public function mount(): void
    {
        $this->authorizePermission('inventory.product.view');
    }

    public function edit(int $unitId): void
    {
        $this->authorizePermission('inventory.product.update');

        $unit = ProductUnit::query()->findOrFail($unitId);
        $this->editingId = $unit->id;
        $this->name = $unit->name;
        $this->code = $unit->code;
        $this->status = (bool) $unit->status;
    }

    public function save(): void
    {
        $this->authorizePermission($this->editingId ? 'inventory.product.update' : 'inventory.product.create');

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('product_units', 'name')->ignore($this->editingId)],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('product_units', 'code')->ignore($this->editingId)],
            'status' => ['required', 'boolean'],
        ]);

        if (! $validated['code']) {
            $validated['code'] = Str::upper(Str::slug($validated['name'], '_'));
        }

        DB::transaction(function () use ($validated): void {
            ProductUnit::query()->updateOrCreate(
                ['id' => $this->editingId],
                $validated
            );
        });

        $this->resetForm();
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Unit saved successfully.']);
    }

    public function delete(int $unitId): void
    {
        $this->authorizePermission('inventory.product.delete');

        $unit = ProductUnit::query()->find($unitId);

        if (! $unit) {
            return;
        }

        if ($unit->products()->exists()) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Unit is used by products and cannot be deleted.']);

            return;
        }

        DB::transaction(function () use ($unit): void {
            $unit->delete();
        });

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Unit deleted successfully.']);
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'code', 'status']);
        $this->status = true;
    }

    public function render(): View
    {
        $units = ProductUnit::query()
            ->when($this->search !== '', function (Builder $query): void {
                $query->where(function (Builder $subQuery): void {
                    $subQuery->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('code', 'like', '%'.$this->search.'%');
                });
            })
            ->orderBy('name')
            ->get();

        return view('livewire.admin.inventory.product.unit-list', [
            'units' => $units,
        ])->layout('layouts.admin.admin');
    }
}
