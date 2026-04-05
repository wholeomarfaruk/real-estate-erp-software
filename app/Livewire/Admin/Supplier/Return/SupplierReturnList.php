<?php

namespace App\Livewire\Admin\Supplier\Return;

use App\Enums\Supplier\SupplierReturnReferenceType;
use App\Enums\Supplier\SupplierReturnStatus;
use App\Livewire\Admin\Supplier\Concerns\InteractsWithSupplierAccess;
use App\Models\Supplier;
use App\Models\SupplierReturn;
use App\Services\Supplier\SupplierReturnService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class SupplierReturnList extends Component
{
    use InteractsWithSupplierAccess;
    use WithPagination;

    public string $search = '';

    public ?int $supplierFilter = null;

    public string $statusFilter = '';

    public string $referenceTypeFilter = '';

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    protected string $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->authorizePermission('supplier.return.list');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedSupplierFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedReferenceTypeFilter(): void
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

    public function approveReturn(int $returnId): void
    {
        $this->authorizePermission('supplier.return.approve');

        $supplierReturn = SupplierReturn::query()->find($returnId);

        if (! $supplierReturn) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Supplier return not found.']);

            return;
        }

        if (! $supplierReturn->canApprove()) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Only draft returns can be approved.']);

            return;
        }

        try {
            app(SupplierReturnService::class)->approveReturn($supplierReturn, (int) auth()->id());
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Supplier return approved successfully.']);
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);
        }
    }

    public function cancelReturn(int $returnId): void
    {
        $this->authorizePermission('supplier.return.cancel');

        $supplierReturn = SupplierReturn::query()->find($returnId);

        if (! $supplierReturn) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Supplier return not found.']);

            return;
        }

        if (! $supplierReturn->canCancel()) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'This return is already cancelled.']);

            return;
        }

        try {
            app(SupplierReturnService::class)->cancelReturn($supplierReturn, (int) auth()->id());
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Supplier return cancelled successfully.']);
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);
        }
    }

    public function render(): View
    {
        $this->authorizePermission('supplier.return.list');

        $query = SupplierReturn::query()
            ->with([
                'supplier:id,name,code',
                'creator:id,name',
                'supplierBill:id,bill_no',
                'stockReceive:id,receive_no',
                'purchaseOrder:id,po_no',
            ])
            ->when($this->search !== '', function (Builder $builder): void {
                $search = '%'.$this->search.'%';

                $builder->where(function (Builder $query) use ($search): void {
                    $query->where('return_no', 'like', $search)
                        ->orWhere('reason', 'like', $search)
                        ->orWhere('notes', 'like', $search)
                        ->orWhereHas('supplier', function (Builder $supplierQuery) use ($search): void {
                            $supplierQuery->where('name', 'like', $search)
                                ->orWhere('code', 'like', $search);
                        })
                        ->orWhereHas('supplierBill', fn (Builder $billQuery): Builder => $billQuery->where('bill_no', 'like', $search))
                        ->orWhereHas('stockReceive', fn (Builder $receiveQuery): Builder => $receiveQuery->where('receive_no', 'like', $search))
                        ->orWhereHas('purchaseOrder', fn (Builder $poQuery): Builder => $poQuery->where('po_no', 'like', $search));
                });
            })
            ->when($this->supplierFilter, fn (Builder $builder): Builder => $builder->where('supplier_id', $this->supplierFilter))
            ->when($this->statusFilter !== '', fn (Builder $builder): Builder => $builder->where('status', $this->statusFilter))
            ->when($this->referenceTypeFilter !== '', fn (Builder $builder): Builder => $builder->where('reference_type', $this->referenceTypeFilter))
            ->when($this->dateFrom, fn (Builder $builder): Builder => $builder->whereDate('return_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn (Builder $builder): Builder => $builder->whereDate('return_date', '<=', $this->dateTo));

        $returns = $query
            ->latest('return_date')
            ->latest('id')
            ->paginate(15);

        $summaryQuery = SupplierReturn::query();
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();

        $totalReturns = (clone $summaryQuery)->count();
        $thisMonthReturns = (clone $summaryQuery)
            ->whereBetween('return_date', [$monthStart, $monthEnd])
            ->count();
        $totalReturnAmount = (clone $summaryQuery)
            ->where('status', '!=', SupplierReturnStatus::CANCELLED->value)
            ->sum('total_amount');
        $approvedReturnsCount = (clone $summaryQuery)
            ->where('status', SupplierReturnStatus::APPROVED->value)
            ->count();

        return view('livewire.admin.supplier.return.supplier-return-list', [
            'returns' => $returns,
            'suppliers' => Supplier::query()->orderBy('name')->get(['id', 'name', 'code']),
            'statuses' => SupplierReturnStatus::cases(),
            'referenceTypes' => SupplierReturnReferenceType::cases(),
            'totalReturns' => $totalReturns,
            'thisMonthReturns' => $thisMonthReturns,
            'totalReturnAmount' => $totalReturnAmount,
            'approvedReturnsCount' => $approvedReturnsCount,
        ])->layout('layouts.admin.admin');
    }
}
