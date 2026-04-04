<?php

namespace App\Livewire\Admin\Inventory\StockRequest;

use App\Enums\Inventory\StockRequestPriority;
use App\Enums\Inventory\StockRequestStatus;
use App\Livewire\Admin\Inventory\Concerns\InteractsWithInventoryAccess;
use App\Models\Project;
use App\Models\StockRequest;
use App\Models\Store;
use App\Services\Inventory\StockRequestService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class StockRequestList extends Component
{
    use InteractsWithInventoryAccess;
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public ?int $requesterStoreFilter = null;

    public ?int $sourceStoreFilter = null;

    public string $priorityFilter = '';

    public ?int $projectFilter = null;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    protected string $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->authorizePermission('inventory.stock_request.view');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedRequesterStoreFilter(): void
    {
        $this->resetPage();
    }

    public function updatedSourceStoreFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPriorityFilter(): void
    {
        $this->resetPage();
    }

    public function updatedProjectFilter(): void
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

    public function submitRequest(int $stockRequestId): void
    {
        $this->authorizePermission('inventory.stock_request.submit');

        $stockRequest = StockRequest::query()->find($stockRequestId);

        if (! $stockRequest) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Stock request not found.']);

            return;
        }

        $this->ensureRequestAccessible($stockRequest);

        try {
            app(StockRequestService::class)->submitRequest($stockRequest, (int) auth()->id());
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Stock request submitted successfully.']);
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);
        }
    }

    public function approveRequest(int $stockRequestId): void
    {
        $this->authorizePermission('inventory.stock_request.approve');

        $stockRequest = StockRequest::query()->find($stockRequestId);

        if (! $stockRequest) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Stock request not found.']);

            return;
        }

        $this->ensureRequestAccessible($stockRequest);

        try {
            app(StockRequestService::class)->approveRequest($stockRequest, (int) auth()->id());
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Stock request approved successfully.']);
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);
        }
    }

    public function rejectRequest(int $stockRequestId): void
    {
        $this->authorizePermission('inventory.stock_request.reject');

        $stockRequest = StockRequest::query()->find($stockRequestId);

        if (! $stockRequest) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Stock request not found.']);

            return;
        }

        $this->ensureRequestAccessible($stockRequest);

        try {
            app(StockRequestService::class)->rejectRequest($stockRequest, (int) auth()->id());
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Stock request rejected successfully.']);
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);
        }
    }

    public function cancelRequest(int $stockRequestId): void
    {
        $this->authorizePermission('inventory.stock_request.update');

        $stockRequest = StockRequest::query()->find($stockRequestId);

        if (! $stockRequest) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Stock request not found.']);

            return;
        }

        $this->ensureRequestAccessible($stockRequest);

        try {
            app(StockRequestService::class)->cancelRequest($stockRequest, (int) auth()->id());
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Stock request cancelled successfully.']);
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);
        }
    }

    public function recalculateFulfillment(int $stockRequestId): void
    {
        $this->authorizePermission('inventory.stock_request.approve');

        $stockRequest = StockRequest::query()->find($stockRequestId);

        if (! $stockRequest) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Stock request not found.']);

            return;
        }

        $this->ensureRequestAccessible($stockRequest);

        try {
            app(StockRequestService::class)->recalculateFulfillmentStatus($stockRequest, (int) auth()->id());
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Fulfillment status recalculated successfully.']);
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);
        }
    }

    public function deleteRequest(int $stockRequestId): void
    {
        $this->authorizePermission('inventory.stock_request.delete');

        $stockRequest = StockRequest::query()->find($stockRequestId);

        if (! $stockRequest) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Stock request not found.']);

            return;
        }

        $this->ensureRequestAccessible($stockRequest);

        if ($stockRequest->status !== StockRequestStatus::DRAFT) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Only draft stock request can be deleted.']);

            return;
        }

        DB::transaction(function () use ($stockRequest): void {
            $stockRequest->items()->delete();
            $stockRequest->transferLinks()->delete();
            $stockRequest->delete();
        });

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Stock request deleted successfully.']);
    }

    public function render(): View
    {
        $this->authorizePermission('inventory.stock_request.view');

        $query = StockRequest::query()
            ->with([
                'requesterStore:id,name,code,type',
                'sourceStore:id,name,code,type',
                'project:id,name,code',
                'requester:id,name',
            ])
            ->withCount('transfers')
            ->withSum('items as total_requested_qty', 'quantity')
            ->withSum('items as total_fulfilled_qty', 'fulfilled_quantity')
            ->when($this->search !== '', function (Builder $builder): void {
                $builder->where(function (Builder $subQuery): void {
                    $subQuery->where('request_no', 'like', '%'.$this->search.'%')
                        ->orWhere('remarks', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->statusFilter !== '', fn (Builder $builder): Builder => $builder->where('status', $this->statusFilter))
            ->when($this->requesterStoreFilter, fn (Builder $builder): Builder => $builder->where('requester_store_id', $this->requesterStoreFilter))
            ->when($this->sourceStoreFilter, fn (Builder $builder): Builder => $builder->where('source_store_id', $this->sourceStoreFilter))
            ->when($this->priorityFilter !== '', fn (Builder $builder): Builder => $builder->where('priority', $this->priorityFilter))
            ->when($this->projectFilter, fn (Builder $builder): Builder => $builder->where('project_id', $this->projectFilter))
            ->when($this->dateFrom, fn (Builder $builder): Builder => $builder->whereDate('request_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn (Builder $builder): Builder => $builder->whereDate('request_date', '<=', $this->dateTo));

        $this->applyRequestAccessRestriction($query);

        $stockRequests = $query
            ->latest('request_date')
            ->latest('id')
            ->paginate(15);

        $statsQuery = StockRequest::query();
        $this->applyRequestAccessRestriction($statsQuery);

        $totalRequests = (clone $statsQuery)->count();
        $pendingRequests = (clone $statsQuery)->where('status', StockRequestStatus::PENDING->value)->count();
        $approvedRequests = (clone $statsQuery)->where('status', StockRequestStatus::APPROVED->value)->count();
        $partialRequests = (clone $statsQuery)->where('status', StockRequestStatus::PARTIALLY_FULFILLED->value)->count();
        $fulfilledRequests = (clone $statsQuery)->where('status', StockRequestStatus::FULFILLED->value)->count();

        $storesQuery = Store::query()->active()->orderBy('name');
        if (! $this->canViewAllStores()) {
            $storeIds = $this->getAccessibleStoreIds();
            $storesQuery->whereIn('id', $storeIds === [] ? [0] : $storeIds);
        }

        return view('livewire.admin.inventory.stock-request.stock-request-list', [
            'stockRequests' => $stockRequests,
            'statuses' => StockRequestStatus::cases(),
            'priorities' => StockRequestPriority::cases(),
            'stores' => $storesQuery->get(['id', 'name', 'code', 'type']),
            'projects' => Project::query()->orderBy('name')->get(['id', 'name', 'code']),
            'totalRequests' => $totalRequests,
            'pendingRequests' => $pendingRequests,
            'approvedRequests' => $approvedRequests,
            'partialRequests' => $partialRequests,
            'fulfilledRequests' => $fulfilledRequests,
        ])->layout('layouts.admin.admin');
    }

    protected function applyRequestAccessRestriction(Builder $query): Builder
    {
        if ($this->canViewAllStores()) {
            return $query;
        }

        $storeIds = $this->getAccessibleStoreIds();

        return $query->where(function (Builder $builder) use ($storeIds): void {
            $builder->whereIn('requester_store_id', $storeIds === [] ? [0] : $storeIds)
                ->orWhereIn('source_store_id', $storeIds === [] ? [0] : $storeIds);
        });
    }

    protected function ensureRequestAccessible(StockRequest $stockRequest): void
    {
        if ($this->canViewAllStores()) {
            return;
        }

        $storeIds = $this->getAccessibleStoreIds();

        abort_unless(
            in_array((int) $stockRequest->requester_store_id, $storeIds, true)
            || ($stockRequest->source_store_id && in_array((int) $stockRequest->source_store_id, $storeIds, true)),
            403,
            'You are not allowed to access this stock request.'
        );
    }
}
