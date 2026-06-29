<?php

namespace App\Livewire\Admin\Hrm\Employee;

use App\Livewire\Admin\Hrm\Concerns\InteractsWithHrmAccess;
use App\Models\Employee;
use App\Models\PayrollPayment;
use App\Models\SalaryStructure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class EmployeeView extends Component
{
    use InteractsWithHrmAccess;
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    public Employee $employee;

    public bool $showSalaryStructureModal = false;

    public ?int $editingSalaryStructureId = null;

    public bool $showViewSalaryStructureModal = false;

    public ?int $viewingSalaryStructureId = null;

    public string $effective_from = '';

    public float|int|string $basic_salary = 0;

    public float|int|string $house_rent = 0;

    public float|int|string $medical_allowance = 0;

    public float|int|string $transport_allowance = 0;

    public float|int|string $food_allowance = 0;

    public float|int|string $other_allowance = 0;

    public bool $status = true;

    public ?string $notes = null;

    public function mount(Employee $employee): void
    {
        $this->authorizePermission('hrm.employees.view');
        $this->employee = $employee;
        $this->effective_from = now()->toDateString();
        $this->basic_salary = (float) $employee->basic_salary;
    }

    public function openSalaryStructureModal(): void
    {
        $this->authorizePermission('hrm.salary-structures.create');

        $this->resetSalaryStructureForm();
        $this->editingSalaryStructureId = null;
        $this->effective_from = now()->toDateString();
        $this->basic_salary = (float) $this->employee->basic_salary;
        $this->showSalaryStructureModal = true;
    }

    public function openEditSalaryStructureModal(int $id): void
    {
        $this->authorizePermission('hrm.salary-structures.update');

        $structure = SalaryStructure::findOrFail($id);

        $this->resetSalaryStructureForm();
        $this->editingSalaryStructureId = $id;
        $this->effective_from = $structure->effective_from->toDateString();
        $this->basic_salary = (float) $structure->basic_salary;
        $this->house_rent = (float) $structure->house_rent;
        $this->medical_allowance = (float) $structure->medical_allowance;
        $this->transport_allowance = (float) $structure->transport_allowance;
        $this->food_allowance = (float) $structure->food_allowance;
        $this->other_allowance = (float) $structure->other_allowance;
        $this->status = (bool) $structure->status;
        $this->notes = $structure->notes;
        $this->showSalaryStructureModal = true;
    }

    public function closeSalaryStructureModal(): void
    {
        $this->showSalaryStructureModal = false;
        $this->editingSalaryStructureId = null;
    }

    public function saveSalaryStructure(): void
    {
        $validated = $this->validate($this->salaryStructureRules());

        if ($this->editingSalaryStructureId) {
            $this->authorizePermission('hrm.salary-structures.update');

            DB::transaction(function () use ($validated): void {
                SalaryStructure::findOrFail($this->editingSalaryStructureId)->update([
                    'effective_from' => $validated['effective_from'],
                    'basic_salary' => $validated['basic_salary'],
                    'house_rent' => $validated['house_rent'],
                    'medical_allowance' => $validated['medical_allowance'],
                    'transport_allowance' => $validated['transport_allowance'],
                    'food_allowance' => $validated['food_allowance'],
                    'other_allowance' => $validated['other_allowance'],
                    'status' => (bool) $validated['status'],
                    'notes' => $validated['notes'] ?? null,
                ]);
            });

            $this->dispatch('toast', ['type' => 'success', 'message' => 'Salary structure updated successfully.']);
        } else {
            $this->authorizePermission('hrm.salary-structures.create');

            DB::transaction(function () use ($validated): void {
                SalaryStructure::query()->create([
                    'employee_id' => $this->employee->id,
                    'effective_from' => $validated['effective_from'],
                    'basic_salary' => $validated['basic_salary'],
                    'house_rent' => $validated['house_rent'],
                    'medical_allowance' => $validated['medical_allowance'],
                    'transport_allowance' => $validated['transport_allowance'],
                    'food_allowance' => $validated['food_allowance'],
                    'other_allowance' => $validated['other_allowance'],
                    'status' => (bool) $validated['status'],
                    'notes' => $validated['notes'] ?? null,
                ]);
            });

            $this->dispatch('toast', ['type' => 'success', 'message' => 'Salary structure saved successfully.']);
        }

        $this->showSalaryStructureModal = false;
        $this->resetSalaryStructureForm();
    }

    public function toggleSalaryStructureStatus(int $id): void
    {
        $this->authorizePermission('hrm.salary-structures.update');

        $structure = SalaryStructure::findOrFail($id);
        $structure->update(['status' => ! $structure->status]);

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Status updated successfully.']);
    }

    public function openViewSalaryStructureModal(int $id): void
    {
        $this->authorizePermission('hrm.employees.view');

        $this->viewingSalaryStructureId = $id;
        $this->showViewSalaryStructureModal = true;
    }

    public function closeViewSalaryStructureModal(): void
    {
        $this->showViewSalaryStructureModal = false;
        $this->viewingSalaryStructureId = null;
    }

    public function render(): View
    {
        $this->authorizePermission('hrm.employees.view');

        $employee = Employee::query()
            ->with([
                'department:id,name',
                'designation:id,name',
                'user:id,name,email',
                'photo:id,name,extension',
            ])
            ->findOrFail($this->employee->id);

        $salaryStructures = SalaryStructure::query()
            ->where('employee_id', $employee->id)
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->paginate(10, ['*'], 'salaryPage');

        $payrolls = $employee->payrolls()
            ->with(['items', 'payments'])
            ->limit(12)
            ->get();

        $advances = $employee->advances()
            ->with('adjustments')
            ->limit(12)
            ->get();

        $paymentHistory = PayrollPayment::query()
            ->whereHas('payroll', fn ($query) => $query->where('employee_id', $employee->id))
            ->whereNotNull('transaction_id')
            ->with('payroll:id,employee_id,month,year')
            ->latest('payment_date')
            ->latest('id')
            ->limit(15)
            ->get();

        $totalPaidSalary = round((float) PayrollPayment::query()
            ->whereHas('payroll', fn ($query) => $query->where('employee_id', $employee->id))
            ->whereNotNull('transaction_id')
            ->sum('amount'), 2);

        $viewingStructure = $this->viewingSalaryStructureId
            ? SalaryStructure::find($this->viewingSalaryStructureId)
            : null;

        return view('livewire.admin.hrm.employee.employee-view', [
            'employee' => $employee,
            'salaryStructures' => $salaryStructures,
            'viewingStructure' => $viewingStructure,
            'payrolls' => $payrolls,
            'advances' => $advances,
            'paymentHistory' => $paymentHistory,
            'summary' => [
                'total_payrolls' => $payrolls->count(),
                'total_net_salary' => round((float) $payrolls->sum('net_salary'), 2),
                'total_advances' => round((float) $advances->sum('amount'), 2),
                'total_advance_remaining' => round((float) $advances->sum('remaining_amount'), 2),
                'total_paid_salary' => $totalPaidSalary,
            ],
        ])->layout('layouts.admin.admin');
    }

    /**
     * @return array<string, mixed>
     */
    protected function salaryStructureRules(): array
    {
        return [
            'effective_from' => ['required', 'date'],
            'basic_salary' => ['required', 'numeric', 'min:0'],
            'house_rent' => ['required', 'numeric', 'min:0'],
            'medical_allowance' => ['required', 'numeric', 'min:0'],
            'transport_allowance' => ['required', 'numeric', 'min:0'],
            'food_allowance' => ['required', 'numeric', 'min:0'],
            'other_allowance' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }

    protected function resetSalaryStructureForm(): void
    {
        $this->reset([
            'effective_from',
            'basic_salary',
            'house_rent',
            'medical_allowance',
            'transport_allowance',
            'food_allowance',
            'other_allowance',
            'notes',
            'editingSalaryStructureId',
        ]);
        $this->status = true;
    }
}
