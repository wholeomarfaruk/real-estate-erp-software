<?php

namespace App\Livewire\Admin\Hrm\Employee;

use App\Livewire\Admin\Hrm\Concerns\InteractsWithHrmAccess;
use App\Livewire\Traits\WithMediaPicker;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;

class EmployeeForm extends Component
{
    use InteractsWithHrmAccess;
    use WithMediaPicker;

    public ?Employee $employee = null;

    public ?int $department_id = null;

    public ?int $designation_id = null;

    public ?int $user_id = null;

    public string $employee_id = '';

    public string $name = '';

    public ?string $phone = null;

    public ?string $email = null;

    public ?string $gender = null;

    public ?string $date_of_birth = null;

    public string $joining_date = '';

    public ?string $confirmation_date = null;

    public ?string $exit_date = null;

    public ?string $employment_type = null;

    public float|int|string $basic_salary = 0;

    public string $status = 'active';

    public bool $has_login = false;

    public ?int $photo_file_id = null;

    public ?string $address = null;

    public ?string $notes = null;

    public function mount(?Employee $employee = null): void
    {
        $this->employee = $employee?->exists ? $employee : null;

        if ($this->employee) {
            $this->authorizePermission('hrm.employees.update');
            $this->fillFromModel($this->employee);

            return;
        }

        $this->authorizePermission('hrm.employees.create');
        $this->joining_date = now()->toDateString();
    }

    public function updatedUserId(): void
    {
        $this->has_login = (bool) $this->user_id;
    }

    public function save(): mixed
    {
        $permission = $this->employee ? 'hrm.employees.update' : 'hrm.employees.create';
        $this->authorizePermission($permission);

        $validated = $this->validate($this->rules(), $this->messages());

        $validated['has_login'] = (bool) $validated['user_id'];

        if ($this->employee) {
            $this->employee->update($validated);
            $saved = $this->employee;
        } else {
            $saved = Employee::query()->create($validated);
        }

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Employee saved successfully.']);

        return redirect()->route('admin.hrm.employees.view', $saved);
    }

    public function render(): View
    {
        $departments = Department::query()->where('status', true)->orderBy('name')->get(['id', 'name']);
        $designations = Designation::query()
            ->where('status', true)
            ->orderBy('name')
            ->get(['id', 'name', 'department_id']);
        $users = User::query()->orderBy('name')->get(['id', 'name', 'email']);

        return view('livewire.admin.hrm.employee.employee-form', [
            'departments' => $departments,
            'designations' => $designations,
            'users' => $users,
            'genderOptions' => ['male', 'female', 'other'],
            'employmentTypes' => ['permanent', 'contractual', 'intern', 'daily'],
            'statusOptions' => ['active', 'inactive', 'resigned', 'terminated'],
        ])->layout('layouts.admin.admin');
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'department_id' => ['nullable', 'exists:departments,id'],
            'designation_id' => ['nullable', 'exists:designations,id'],
            'user_id' => [
                'nullable',
                'exists:users,id',
                Rule::unique('employees', 'user_id')->ignore($this->employee?->id),
            ],
            'employee_id' => [
                'required',
                'string',
                'max:50',
                Rule::unique('employees', 'employee_id')->ignore($this->employee?->id),
            ],
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'date_of_birth' => ['nullable', 'date'],
            'joining_date' => ['required', 'date'],
            'confirmation_date' => ['nullable', 'date'],
            'exit_date' => ['nullable', 'date'],
            'employment_type' => ['nullable', 'string', 'max:50'],
            'basic_salary' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'string', 'max:30'],
            'photo_file_id' => ['nullable', 'exists:files,id'],
            'address' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'employee_id.required' => 'Employee ID is required.',
            'employee_id.unique' => 'Employee ID already exists.',
            'joining_date.required' => 'Joining date is required.',
            'user_id.unique' => 'This user is already linked to another employee.',
        ];
    }

    protected function fillFromModel(Employee $employee): void
    {
        $this->department_id = $employee->department_id ? (int) $employee->department_id : null;
        $this->designation_id = $employee->designation_id ? (int) $employee->designation_id : null;
        $this->user_id = $employee->user_id ? (int) $employee->user_id : null;
        $this->employee_id = (string) $employee->employee_id;
        $this->name = (string) $employee->name;
        $this->phone = $employee->phone;
        $this->email = $employee->email;
        $this->gender = $employee->gender;
        $this->date_of_birth = optional($employee->date_of_birth)->toDateString();
        $this->joining_date = optional($employee->joining_date)->toDateString() ?? now()->toDateString();
        $this->confirmation_date = optional($employee->confirmation_date)->toDateString();
        $this->exit_date = optional($employee->exit_date)->toDateString();
        $this->employment_type = $employee->employment_type;
        $this->basic_salary = (float) $employee->basic_salary;
        $this->status = (string) $employee->status;
        $this->has_login = (bool) $employee->has_login;
        $this->photo_file_id = $employee->photo_file_id ? (int) $employee->photo_file_id : null;
        $this->address = $employee->address;
        $this->notes = $employee->notes;
    }
}

