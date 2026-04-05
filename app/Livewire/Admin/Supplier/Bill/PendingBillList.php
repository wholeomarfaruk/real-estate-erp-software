<?php

namespace App\Livewire\Admin\Supplier\Bill;

use App\Enums\Supplier\SupplierBillStatus;
use App\Livewire\Admin\Supplier\Concerns\InteractsWithSupplierAccess;
use App\Models\Supplier;
use App\Models\SupplierBill;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class PendingBillList extends Component
{
    use InteractsWithSupplierAccess;
    use WithPagination;

    public string $search = '';

    public ?int $supplierFilter = null;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public bool $overdueOnly = false;

    protected string $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->authorizePermission('supplier.bill.pending.view');
        SupplierBill::syncOverdueStatuses();
    }

    public function updatedSearch(): void
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

    public function updatedOverdueOnly(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $this->authorizePermission('supplier.bill.pending.view');

        SupplierBill::syncOverdueStatuses();

        $query = SupplierBill::query()
            ->pending()
            ->with(['supplier:id,name,code', 'creator:id,name'])
            ->when($this->search !== '', function (Builder $builder): void {
                $search = '%'.$this->search.'%';

                $builder->where(function (Builder $query) use ($search): void {
                    $query->where('bill_no', 'like', $search)
                        ->orWhere('notes', 'like', $search)
                        ->orWhereHas('supplier', function (Builder $supplierQuery) use ($search): void {
                            $supplierQuery->where('name', 'like', $search)
                                ->orWhere('code', 'like', $search);
                        });
                });
            })
            ->when($this->supplierFilter, fn (Builder $builder): Builder => $builder->where('supplier_id', $this->supplierFilter))
            ->when($this->dateFrom, fn (Builder $builder): Builder => $builder->whereDate('bill_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn (Builder $builder): Builder => $builder->whereDate('bill_date', '<=', $this->dateTo))
            ->when($this->overdueOnly, fn (Builder $builder): Builder => $builder->where('status', SupplierBillStatus::OVERDUE->value));

        $bills = $query
            ->latest('due_date')
            ->latest('id')
            ->paginate(15);

        $summaryQuery = SupplierBill::query()->pending();

        $totalPendingAmount = (clone $summaryQuery)->sum('due_amount');
        $overdueAmount = (clone $summaryQuery)->where('status', SupplierBillStatus::OVERDUE->value)->sum('due_amount');
        $unpaidBillCount = (clone $summaryQuery)
            ->where('paid_amount', '<=', 0)
            ->where('due_amount', '>', 0)
            ->count();
        $overdueBillCount = (clone $summaryQuery)->where('status', SupplierBillStatus::OVERDUE->value)->count();

        return view('livewire.admin.supplier.bill.pending-bill-list', [
            'bills' => $bills,
            'suppliers' => Supplier::query()->orderBy('name')->get(['id', 'name']),
            'totalPendingAmount' => $totalPendingAmount,
            'overdueAmount' => $overdueAmount,
            'unpaidBillCount' => $unpaidBillCount,
            'overdueBillCount' => $overdueBillCount,
        ])->layout('layouts.admin.admin');
    }
}
