<?php

namespace App\Livewire\Admin\Hrm\PayrollPayment;

use App\Livewire\Admin\Hrm\Concerns\InteractsWithHrmAccess;
use App\Models\Employee;
use App\Models\PayrollPayment;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class PayrollPaymentList extends Component
{
    use InteractsWithHrmAccess;
    use WithPagination;

    public string $search = '';

    public ?int $employeeFilter = null;

    public string $methodFilter = '';

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    protected string $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->authorizePermission('hrm.payroll-payments.view');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedEmployeeFilter(): void
    {
        $this->resetPage();
    }

    public function updatedMethodFilter(): void
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

    public function render(): View
    {
        $this->authorizePermission('hrm.payroll-payments.view');

        $payments = PayrollPayment::query()
            ->with([
                'payroll:id,employee_id,month,year,net_salary,payment_status',
                'payroll.employee:id,name,employee_id',
                'receiver:id,name',
            ])
            ->when($this->search !== '', function (Builder $query): void {
                $search = '%'.$this->search.'%';

                $query->where(function (Builder $subQuery) use ($search): void {
                    $subQuery->where('reference_no', 'like', $search)
                        ->orWhere('notes', 'like', $search);
                })
                    ->orWhereHas('payroll.employee', function (Builder $employeeQuery) use ($search): void {
                        $employeeQuery->where('name', 'like', $search)
                            ->orWhere('employee_id', 'like', $search);
                    });
            })
            ->when($this->employeeFilter, function (Builder $query): Builder {
                return $query->whereHas('payroll', fn (Builder $payrollQuery) => $payrollQuery->where('employee_id', $this->employeeFilter));
            })
            ->when($this->methodFilter !== '', fn (Builder $query): Builder => $query->where('payment_method', $this->methodFilter))
            ->when($this->dateFrom, fn (Builder $query): Builder => $query->whereDate('payment_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn (Builder $query): Builder => $query->whereDate('payment_date', '<=', $this->dateTo))
            ->latest('payment_date')
            ->latest('id')
            ->paginate(15);

        $employees = Employee::query()->orderBy('name')->get(['id', 'name', 'employee_id']);

        return view('livewire.admin.hrm.payroll-payment.payroll-payment-list', [
            'payments' => $payments,
            'employees' => $employees,
            'paymentMethods' => ['cash', 'bank', 'cheque', 'mobile_banking'],
        ])->layout('layouts.admin.admin');
    }
}

