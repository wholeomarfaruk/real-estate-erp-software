<?php

namespace App\Livewire\Admin\Inventory\Store;

use App\Enums\Inventory\StoreType;
use App\Models\Store;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class StoreList extends Component
{
    use WithPagination;

    public string $search = '';

    public string $typeFilter = '';

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

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function toggleStatus(int $id): void
    {
        $this->authorizeUpdate();

        $store = Store::query()->find($id);

        if (! $store) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Store not found.']);

            return;
        }

        DB::transaction(function () use ($store): void {
            $store->update([
                'status' => ! $store->status,
            ]);
        });

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Store status updated successfully.']);
    }

    public function deleteStore(int $id): void
    {
        $this->authorizeDelete();

        $store = Store::query()->find($id);

        if (! $store) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Store not found.']);

            return;
        }

        DB::transaction(function () use ($store): void {
            $store->delete();
        });

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Store deleted successfully.']);
    }

    public function render(): View
    {
        $this->authorizeView();

        $stores = Store::query()
            ->with('project:id,name,code')
            ->when($this->search !== '', function (Builder $query): void {
                $query->where(function (Builder $subQuery): void {
                    $subQuery
                        ->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('code', 'like', '%'.$this->search.'%')
                        ->orWhere('address', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->typeFilter !== '', function (Builder $query): void {
                $query->where('type', $this->typeFilter);
            })
            ->when($this->statusFilter !== '', function (Builder $query): void {
                $query->where('status', $this->statusFilter === 'active');
            })
            ->latest()
            ->paginate(15);

        return view('livewire.admin.inventory.store.store-list', [
            'stores' => $stores,
            'types' => StoreType::cases(),
        ])->layout('layouts.admin.admin');
    }

    protected function authorizeView(): void
    {
        abort_unless(auth()->user()?->can('inventory.store.view'), 403, 'Unauthorized action.');
    }

    protected function authorizeUpdate(): void
    {
        abort_unless(auth()->user()?->can('inventory.store.update'), 403, 'Unauthorized action.');
    }

    protected function authorizeDelete(): void
    {
        abort_unless(auth()->user()?->can('inventory.store.delete'), 403, 'Unauthorized action.');
    }
}
