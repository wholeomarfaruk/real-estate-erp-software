<?php

namespace App\Livewire\Admin\Supplier\Bill;

use App\Enums\Supplier\SupplierBillStatus;
use App\Livewire\Admin\Supplier\Concerns\InteractsWithSupplierAccess;
use App\Models\Supplier;
use App\Models\SupplierBill;
use App\Services\Supplier\SupplierLedgerService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class BillList extends Component
{
    use InteractsWithSupplierAccess;
    use WithPagination;

    public string $search = '';

    public ?int $supplierFilter = null;

    public string $statusFilter = '';

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public bool $overdueOnly = false;

    public bool $unpaidOnly = false;

    protected string $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->authorizePermission('supplier.bill.list');
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

    public function updatedStatusFilter(): void
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

    public function updatedUnpaidOnly(): void
    {
        $this->resetPage();
    }

    public function cancelBill(int $billId): void
    {
        $this->authorizePermission('supplier.bill.cancel');

        $bill = SupplierBill::query()->find($billId);

        if (! $bill) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Bill not found.']);

            return;
        }

        if (! $bill->canCancel()) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'This bill cannot be cancelled.']);

            return;
        }

        DB::transaction(function () use ($bill): void {
            $bill->update([
                'status' => SupplierBillStatus::CANCELLED->value,
                'updated_by' => auth()->id(),
            ]);

            app(SupplierLedgerService::class)->postBill($bill, (int) auth()->id(), false);
        });

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Bill cancelled successfully.']);
    }

    public function render(): View
    {
        $this->authorizePermission('supplier.bill.list');

        SupplierBill::syncOverdueStatuses();

        $query = SupplierBill::query()
            ->with(['supplier:id,name,code', 'creator:id,name', 'purchaseOrder:id,po_no', 'stockReceive:id,receive_no'])
            ->when($this->search !== '', function (Builder $builder): void {
                $search = '%'.$this->search.'%';

                $builder->where(function (Builder $query) use ($search): void {
                    $query->where('bill_no', 'like', $search)
                        ->orWhere('notes', 'like', $search)
                        ->orWhereHas('supplier', function (Builder $supplierQuery) use ($search): void {
                            $supplierQuery->where('name', 'like', $search)
                                ->orWhere('code', 'like', $search);
                        })
                        ->orWhereHas('purchaseOrder', function (Builder $poQuery) use ($search): void {
                            $poQuery->where('po_no', 'like', $search);
                        })
                        ->orWhereHas('stockReceive', function (Builder $receiveQuery) use ($search): void {
                            $receiveQuery->where('receive_no', 'like', $search);
                        });
                });
            })
            ->when($this->supplierFilter, fn (Builder $builder): Builder => $builder->where('supplier_id', $this->supplierFilter))
            ->when($this->statusFilter !== '', fn (Builder $builder): Builder => $builder->where('status', $this->statusFilter))
            ->when($this->dateFrom, fn (Builder $builder): Builder => $builder->whereDate('bill_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn (Builder $builder): Builder => $builder->whereDate('bill_date', '<=', $this->dateTo))
            ->when($this->overdueOnly, fn (Builder $builder): Builder => $builder->where('status', SupplierBillStatus::OVERDUE->value))
            ->when($this->unpaidOnly, function (Builder $builder): void {
                $builder
                    ->where('paid_amount', '<=', 0)
                    ->where('due_amount', '>', 0)
                    ->where('status', '!=', SupplierBillStatus::CANCELLED->value);
            });

        $bills = $query
            ->latest('bill_date')
            ->latest('id')
            ->paginate(15);

        $summaryQuery = SupplierBill::query();

        $totalBills = (clone $summaryQuery)->count();
        $openBills = (clone $summaryQuery)->where('status', SupplierBillStatus::OPEN->value)->count();
        $overdueBills = (clone $summaryQuery)->where('status', SupplierBillStatus::OVERDUE->value)->count();
        $totalDueAmount = (clone $summaryQuery)
            ->where('status', '!=', SupplierBillStatus::CANCELLED->value)
            ->sum('due_amount');

        return view('livewire.admin.supplier.bill.bill-list', [
            'bills' => $bills,
            'suppliers' => Supplier::query()->orderBy('name')->get(['id', 'name']),
            'statuses' => SupplierBillStatus::cases(),
            'totalBills' => $totalBills,
            'openBills' => $openBills,
            'overdueBills' => $overdueBills,
            'totalDueAmount' => $totalDueAmount,
        ])->layout('layouts.admin.admin');
    }
}
