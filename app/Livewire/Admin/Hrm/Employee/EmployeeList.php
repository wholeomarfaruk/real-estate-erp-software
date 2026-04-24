<?php

namespace App\Livewire\Admin\Hrm\Employee;

use App\Livewire\Admin\Hrm\Concerns\InteractsWithHrmAccess;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class EmployeeList extends Component
{
    use InteractsWithHrmAccess;
    use WithPagination;

    public string $search = '';

    public ?int $departmentFilter = null;

    public ?int $designationFilter = null;

    public string $statusFilter = '';

    protected string $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->authorizePermission('hrm.employees.view');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedDepartmentFilter(): void
    {
        $this->resetPage();
    }

    public function updatedDesignationFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $this->authorizePermission('hrm.employees.view');

        $employees = Employee::query()
            ->with([
                'department:id,name',
                'designation:id,name',
                'user:id,name,email',
            ])
            ->withCount(['payrolls', 'advances'])
            ->when($this->search !== '', function (Builder $query): void {
                $search = '%'.$this->search.'%';
                $query->where(function (Builder $subQuery) use ($search): void {
                    $subQuery->where('employee_id', 'like', $search)
                        ->orWhere('name', 'like', $search)
                        ->orWhere('phone', 'like', $search)
                        ->orWhere('email', 'like', $search);
                });
            })
            ->when($this->departmentFilter, fn (Builder $query): Builder => $query->where('department_id', $this->departmentFilter))
            ->when($this->designationFilter, fn (Builder $query): Builder => $query->where('designation_id', $this->designationFilter))
            ->when($this->statusFilter !== '', fn (Builder $query): Builder => $query->where('status', $this->statusFilter))
            ->latest('id')
            ->paginate(15);

        $departments = Department::query()->where('status', true)->orderBy('name')->get(['id', 'name']);
        $designations = Designation::query()->where('status', true)->orderBy('name')->get(['id', 'name']);

        return view('livewire.admin.hrm.employee.employee-list', [
            'employees' => $employees,
            'departments' => $departments,
            'designations' => $designations,
            'statusOptions' => ['active', 'inactive', 'resigned', 'terminated'],
        ])->layout('layouts.admin.admin');
    }
}

