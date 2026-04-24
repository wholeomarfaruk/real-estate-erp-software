<?php

namespace App\Livewire\Admin\Hrm\EmployeeAdvance;

use App\Livewire\Admin\Hrm\Concerns\InteractsWithHrmAccess;
use App\Models\EmployeeAdvance;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class EmployeeAdvanceView extends Component
{
    use InteractsWithHrmAccess;

    public EmployeeAdvance $employeeAdvance;

    public function mount(EmployeeAdvance $employeeAdvance): void
    {
        $this->authorizePermission('hrm.employee-advances.view');
        $this->employeeAdvance = $employeeAdvance;
    }

    public function render(): View
    {
        $this->authorizePermission('hrm.employee-advances.view');

        $employeeAdvance = EmployeeAdvance::query()
            ->with([
                'employee.department:id,name',
                'employee.designation:id,name',
                'creator:id,name',
                'approver:id,name',
                'adjustments:id,employee_advance_id,payroll_id,amount,adjustment_date,notes',
                'adjustments.payroll:id,employee_id,month,year,payroll_date,net_salary,payment_status',
            ])
            ->findOrFail($this->employeeAdvance->id);

        return view('livewire.admin.hrm.employee-advance.employee-advance-view', [
            'employeeAdvance' => $employeeAdvance,
        ])->layout('layouts.admin.admin');
    }
}

