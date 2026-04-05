<?php

namespace App\Livewire\Admin\Supplier\Supplier;

use App\Livewire\Admin\Supplier\Concerns\InteractsWithSupplierAccess;
use App\Models\Supplier;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class SupplierList extends Component
{
    use InteractsWithSupplierAccess;
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public string $hasDueFilter = '';

    protected string $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->authorizePermission('supplier.list.view');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedHasDueFilter(): void
    {
        $this->resetPage();
    }

    public function activateSupplier(int $supplierId): void
    {
        $this->authorizePermission('supplier.status.change');

        $this->updateSupplierStatus(
            supplierId: $supplierId,
            attributes: ['status' => true, 'is_blocked' => false],
            successMessage: 'Supplier activated successfully.'
        );
    }

    public function deactivateSupplier(int $supplierId): void
    {
        $this->authorizePermission('supplier.status.change');

        $this->updateSupplierStatus(
            supplierId: $supplierId,
            attributes: ['status' => false, 'is_blocked' => false],
            successMessage: 'Supplier deactivated successfully.'
        );
    }

    public function blockSupplier(int $supplierId): void
    {
        $this->authorizePermission('supplier.status.change');

        $this->updateSupplierStatus(
            supplierId: $supplierId,
            attributes: ['status' => false, 'is_blocked' => true],
            successMessage: 'Supplier blocked successfully.'
        );
    }

    public function unblockSupplier(int $supplierId): void
    {
        $this->authorizePermission('supplier.status.change');

        $this->updateSupplierStatus(
            supplierId: $supplierId,
            attributes: ['status' => false, 'is_blocked' => false],
            successMessage: 'Supplier unblocked successfully.'
        );
    }

    public function render(): View
    {
        $this->authorizePermission('supplier.list.view');

        $query = Supplier::query()
            ->withCurrentDue()
            ->when($this->search !== '', function (Builder $builder): void {
                $searchTerm = '%'.$this->search.'%';

                $builder->where(function (Builder $query) use ($searchTerm): void {
                    $query->where('code', 'like', $searchTerm)
                        ->orWhere('name', 'like', $searchTerm)
                        ->orWhere('company_name', 'like', $searchTerm)
                        ->orWhere('contact_person', 'like', $searchTerm)
                        ->orWhere('phone', 'like', $searchTerm)
                        ->orWhere('alternate_phone', 'like', $searchTerm)
                        ->orWhere('secondary_phone', 'like', $searchTerm)
                        ->orWhere('email', 'like', $searchTerm)
                        ->orWhere('address', 'like', $searchTerm);
                });
            })
            ->when($this->statusFilter !== '', function (Builder $builder): void {
                if ($this->statusFilter === 'active') {
                    $builder->active();

                    return;
                }

                if ($this->statusFilter === 'inactive') {
                    $builder->inactive();

                    return;
                }

                if ($this->statusFilter === 'blocked') {
                    $builder->blocked();
                }
            })
            ->when($this->hasDueFilter !== '', function (Builder $builder): void {
                if ($this->hasDueFilter === 'due') {
                    $builder->hasDue();

                    return;
                }

                if ($this->hasDueFilter === 'no_due') {
                    $builder->withoutDue();
                }
            });

        $suppliers = $query
            ->latest('created_at')
            ->latest('id')
            ->paginate(15);

        return view('livewire.admin.supplier.supplier.supplier-list', [
            'suppliers' => $suppliers,
        ])->layout('layouts.admin.admin');
    }

    protected function updateSupplierStatus(int $supplierId, array $attributes, string $successMessage): void
    {
        $supplier = Supplier::query()->find($supplierId);

        if (! $supplier) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Supplier not found.']);

            return;
        }

        DB::transaction(function () use ($supplier, $attributes): void {
            $supplier->update([
                ...$attributes,
                'updated_by' => auth()->id(),
            ]);
        });

        $this->dispatch('toast', ['type' => 'success', 'message' => $successMessage]);
    }
}
