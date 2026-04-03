<?php

namespace App\Livewire\Admin\Inventory\Supplier;

use App\Models\Supplier;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class SupplierList extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    protected string $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->authorizeView();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function toggleStatus(int $supplierId): void
    {
        $this->authorizeUpdate();

        $supplier = Supplier::query()->find($supplierId);

        if (! $supplier) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Supplier not found.']);

            return;
        }

        DB::transaction(function () use ($supplier): void {
            $supplier->update([
                'status' => ! $supplier->status,
            ]);
        });

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Supplier status updated successfully.']);
    }

    public function deleteSupplier(int $supplierId): void
    {
        $this->authorizeDelete();

        $supplier = Supplier::query()->find($supplierId);

        if (! $supplier) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Supplier not found.']);

            return;
        }

        if (! $this->canDeleteSupplier($supplier)) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Supplier cannot be deleted.']);
            return;
        }

        DB::transaction(function () use ($supplier): void {
            $supplier->delete();
        });

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Supplier deleted successfully.']);
    }

    public function render(): View
    {
        $this->authorizeView();

        $suppliers = Supplier::query()
            ->when($this->search !== '', function (Builder $query): void {
                $query->where(function (Builder $subQuery): void {
                    $subQuery
                        ->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('contact_person', 'like', '%'.$this->search.'%')
                        ->orWhere('phone', 'like', '%'.$this->search.'%')
                        ->orWhere('secondary_phone', 'like', '%'.$this->search.'%')
                        ->orWhere('email', 'like', '%'.$this->search.'%')
                        ->orWhere('address', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->statusFilter !== '', function (Builder $query): void {
                $query->where('status', $this->statusFilter === 'active');
            })
            ->latest()
            ->paginate(15);

        return view('livewire.admin.inventory.supplier.supplier-list', [
            'suppliers' => $suppliers,
        ])->layout('layouts.admin.admin');
    }

    protected function canDeleteSupplier(Supplier $supplier): bool
    {
        return true;
    }

    protected function authorizeView(): void
    {
        abort_unless(auth()->user()?->can('inventory.supplier.view'), 403, 'Unauthorized action.');
    }

    protected function authorizeUpdate(): void
    {
        abort_unless(auth()->user()?->can('inventory.supplier.update'), 403, 'Unauthorized action.');
    }

    protected function authorizeDelete(): void
    {
        abort_unless(auth()->user()?->can('inventory.supplier.delete'), 403, 'Unauthorized action.');
    }
}
