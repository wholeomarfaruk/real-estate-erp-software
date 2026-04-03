<?php

namespace App\Livewire\Admin\Inventory\StockReceive;

use App\Enums\Inventory\StockReceiveStatus;
use App\Livewire\Admin\Inventory\Concerns\InteractsWithInventoryAccess;
use App\Models\StockReceive;
use App\Services\Inventory\StockReceiveService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class StockReceiveList extends Component
{
    use InteractsWithInventoryAccess;
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public ?int $storeFilter = null;

    public ?int $supplierFilter = null;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    protected string $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->authorizePermission('inventory.stock.receive.view');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStoreFilter(): void
    {
        $this->resetPage();
    }

    public function updatedSupplierFilter(): void
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

    public function postReceive(int $stockReceiveId): void
    {
        $this->authorizePermission('inventory.stock.receive.post');

        $stockReceive = StockReceive::query()->find($stockReceiveId);

        if (! $stockReceive) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Stock receive not found.']);

            return;
        }

        $this->ensureStoreAccessible((int) $stockReceive->store_id);

        try {
            app(StockReceiveService::class)->postReceive($stockReceive);
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Stock receive posted successfully.']);
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);
        }
    }

    public function cancelReceive(int $stockReceiveId): void
    {
        $this->authorizePermission('inventory.stock.receive.update');

        $stockReceive = StockReceive::query()->find($stockReceiveId);

        if (! $stockReceive) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Stock receive not found.']);

            return;
        }

        $this->ensureStoreAccessible((int) $stockReceive->store_id);

        if ($stockReceive->status !== StockReceiveStatus::DRAFT) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Only draft receive can be cancelled.']);

            return;
        }

        DB::transaction(function () use ($stockReceive): void {
            $stockReceive->update([
                'status' => StockReceiveStatus::CANCELLED->value,
            ]);
        });

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Stock receive cancelled successfully.']);
    }

    public function deleteReceive(int $stockReceiveId): void
    {
        $this->authorizePermission('inventory.stock.receive.delete');

        $stockReceive = StockReceive::query()->find($stockReceiveId);

        if (! $stockReceive) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Stock receive not found.']);

            return;
        }

        $this->ensureStoreAccessible((int) $stockReceive->store_id);

        if ($stockReceive->status !== StockReceiveStatus::DRAFT) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Only draft receive can be deleted.']);

            return;
        }

        DB::transaction(function () use ($stockReceive): void {
            $stockReceive->items()->delete();
            $stockReceive->delete();
        });

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Stock receive deleted successfully.']);
    }

    public function render(): View
    {
        $this->authorizePermission('inventory.stock.receive.view');

        $query = StockReceive::query()
            ->with(['supplier:id,name,contact_person,phone', 'store:id,name,code'])
            ->withSum('items as grand_total', 'total_price')
            ->when($this->search !== '', function (Builder $builder): void {
                $builder->where(function (Builder $subQuery): void {
                    $subQuery->where('receive_no', 'like', '%'.$this->search.'%')
                        ->orWhere('supplier_voucher', 'like', '%'.$this->search.'%')
                        ->orWhere('remarks', 'like', '%'.$this->search.'%')
                        ->orWhereHas('supplier', function (Builder $supplierQuery): void {
                            $supplierQuery->where('name', 'like', '%'.$this->search.'%');
                        });
                });
            })
            ->when($this->statusFilter !== '', function (Builder $builder): void {
                $builder->where('status', $this->statusFilter);
            })
            ->when($this->storeFilter, fn (Builder $builder): Builder => $builder->where('store_id', $this->storeFilter))
            ->when($this->supplierFilter, fn (Builder $builder): Builder => $builder->where('supplier_id', $this->supplierFilter))
            ->when($this->dateFrom, fn (Builder $builder): Builder => $builder->whereDate('receive_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn (Builder $builder): Builder => $builder->whereDate('receive_date', '<=', $this->dateTo));

        $this->applyStoreRestriction($query);

        $receives = $query->latest('receive_date')->latest('id')->paginate(15);

        $statsQuery = StockReceive::query();
        $this->applyStoreRestriction($statsQuery);

        $totalReceives = (clone $statsQuery)->count();
        $postedReceives = (clone $statsQuery)->where('status', StockReceiveStatus::POSTED->value)->count();
        $draftReceives = (clone $statsQuery)->where('status', StockReceiveStatus::DRAFT->value)->count();

        return view('livewire.admin.inventory.stock-receive.stock-receive-list', [
            'receives' => $receives,
            'statuses' => StockReceiveStatus::cases(),
            'stores' => \App\Models\Store::query()->active()->office()->orderBy('name')->get(['id', 'name']),
            'suppliers' => \App\Models\Supplier::query()->active()->orderBy('name')->get(['id', 'name']),
            'totalReceives' => $totalReceives,
            'postedReceives' => $postedReceives,
            'draftReceives' => $draftReceives,
        ])->layout('layouts.admin.admin');
    }
}
