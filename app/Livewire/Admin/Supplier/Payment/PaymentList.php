<?php

namespace App\Livewire\Admin\Supplier\Payment;

use App\Enums\Supplier\SupplierPaymentMethod;
use App\Enums\Supplier\SupplierPaymentStatus;
use App\Livewire\Admin\Supplier\Concerns\InteractsWithSupplierAccess;
use App\Models\Supplier;
use App\Models\SupplierBill;
use App\Models\SupplierPayment;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class PaymentList extends Component
{
    use InteractsWithSupplierAccess;
    use WithPagination;

    public string $search = '';

    public ?int $supplierFilter = null;

    public string $paymentMethodFilter = '';

    public string $statusFilter = '';

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    protected string $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->authorizePermission('supplier.payment.list');
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

    public function updatedPaymentMethodFilter(): void
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

    public function cancelPayment(int $paymentId): void
    {
        $this->authorizePermission('supplier.payment.cancel');

        $payment = SupplierPayment::query()->find($paymentId);

        if (! $payment) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Payment not found.']);

            return;
        }

        if (! $payment->canCancel()) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'This payment cannot be cancelled.']);

            return;
        }

        try {
            app(\App\Services\Supplier\SupplierPaymentService::class)->cancelPayment($payment, (int) auth()->id());
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Payment cancelled successfully.']);
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);
        }
    }

    public function render(): View
    {
        $this->authorizePermission('supplier.payment.list');

        SupplierBill::syncOverdueStatuses();

        $query = SupplierPayment::query()
            ->with(['supplier:id,name,code', 'creator:id,name'])
            ->when($this->search !== '', function (Builder $builder): void {
                $search = '%'.$this->search.'%';

                $builder->where(function (Builder $query) use ($search): void {
                    $query->where('payment_no', 'like', $search)
                        ->orWhere('reference_no', 'like', $search)
                        ->orWhere('transaction_no', 'like', $search)
                        ->orWhere('cheque_no', 'like', $search)
                        ->orWhere('account_reference', 'like', $search)
                        ->orWhereHas('supplier', function (Builder $supplierQuery) use ($search): void {
                            $supplierQuery->where('name', 'like', $search)
                                ->orWhere('code', 'like', $search);
                        });
                });
            })
            ->when($this->supplierFilter, fn (Builder $builder): Builder => $builder->where('supplier_id', $this->supplierFilter))
            ->when($this->paymentMethodFilter !== '', fn (Builder $builder): Builder => $builder->where('payment_method', $this->paymentMethodFilter))
            ->when($this->statusFilter !== '', fn (Builder $builder): Builder => $builder->where('status', $this->statusFilter))
            ->when($this->dateFrom, fn (Builder $builder): Builder => $builder->whereDate('payment_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn (Builder $builder): Builder => $builder->whereDate('payment_date', '<=', $this->dateTo));

        $payments = $query
            ->latest('payment_date')
            ->latest('id')
            ->paginate(15);

        $summaryQuery = SupplierPayment::query();

        $totalPayments = (clone $summaryQuery)->count();
        $thisMonthPaymentsCount = (clone $summaryQuery)
            ->whereBetween('payment_date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
            ->count();
        $thisMonthPaymentsAmount = (clone $summaryQuery)
            ->whereBetween('payment_date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
            ->sum('total_amount');
        $totalAllocatedAmount = (clone $summaryQuery)
            ->where('status', '!=', SupplierPaymentStatus::CANCELLED->value)
            ->sum('allocated_amount');
        $totalUnallocatedAmount = (clone $summaryQuery)
            ->where('status', '!=', SupplierPaymentStatus::CANCELLED->value)
            ->sum('unallocated_amount');

        return view('livewire.admin.supplier.payment.payment-list', [
            'payments' => $payments,
            'suppliers' => Supplier::query()->orderBy('name')->get(['id', 'name']),
            'paymentMethods' => SupplierPaymentMethod::cases(),
            'statuses' => SupplierPaymentStatus::cases(),
            'totalPayments' => $totalPayments,
            'thisMonthPaymentsCount' => $thisMonthPaymentsCount,
            'thisMonthPaymentsAmount' => $thisMonthPaymentsAmount,
            'totalAllocatedAmount' => $totalAllocatedAmount,
            'totalUnallocatedAmount' => $totalUnallocatedAmount,
        ])->layout('layouts.admin.admin');
    }
}
