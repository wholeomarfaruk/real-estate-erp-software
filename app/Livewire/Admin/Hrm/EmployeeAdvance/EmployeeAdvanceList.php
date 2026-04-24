<?php

namespace App\Livewire\Admin\Hrm\EmployeeAdvance;

use App\Livewire\Admin\Hrm\Concerns\InteractsWithHrmAccess;
use App\Models\Employee;
use App\Models\EmployeeAdvance;
use App\Services\Hrm\EmployeeAdvanceService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class EmployeeAdvanceList extends Component
{
    use InteractsWithHrmAccess;
    use WithPagination;

    public string $search = '';

    public ?int $employeeFilter = null;

    public string $statusFilter = '';

    public bool $showCreateModal = false;

    public ?int $employee_id = null;

    public string $advance_date = '';

    public float|int|string $amount = '';

    public string $payment_method = 'cash';

    public ?string $notes = null;

    protected string $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->authorizePermission('hrm.employee-advances.view');
        $this->advance_date = now()->toDateString();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedEmployeeFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->authorizePermission('hrm.employee-advances.create');

        $this->resetForm();
        $this->showCreateModal = true;
    }

    public function closeCreateModal(): void
    {
        $this->showCreateModal = false;
    }

    public function saveAdvance(): void
    {
        $this->authorizePermission('hrm.employee-advances.create');

        $validated = $this->validate($this->rules(), $this->messages());

        try {
            app(EmployeeAdvanceService::class)->createAdvance($validated, (int) auth()->id());
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);

            return;
        }

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Employee advance saved successfully.']);
        $this->showCreateModal = false;
        $this->resetForm();
    }

    public function render(): View
    {
        $this->authorizePermission('hrm.employee-advances.view');

        $advances = EmployeeAdvance::query()
            ->with(['employee:id,name,employee_id', 'creator:id,name'])
            ->when($this->search !== '', function (Builder $query): void {
                $search = '%'.$this->search.'%';

                $query->whereHas('employee', function (Builder $subQuery) use ($search): void {
                    $subQuery->where('name', 'like', $search)
                        ->orWhere('employee_id', 'like', $search);
                });
            })
            ->when($this->employeeFilter, fn (Builder $query): Builder => $query->where('employee_id', $this->employeeFilter))
            ->when($this->statusFilter !== '', fn (Builder $query): Builder => $query->where('status', $this->statusFilter))
            ->latest('advance_date')
            ->latest('id')
            ->paginate(15);

        $employees = Employee::query()->where('status', 'active')->orderBy('name')->get(['id', 'name', 'employee_id']);

        return view('livewire.admin.hrm.employee-advance.employee-advance-list', [
            'advances' => $advances,
            'employees' => $employees,
            'statusOptions' => ['pending', 'partial', 'cleared', 'cancelled'],
            'paymentMethods' => ['cash', 'bank', 'cheque', 'mobile_banking'],
        ])->layout('layouts.admin.admin');
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'employee_id' => ['required', 'exists:employees,id'],
            'advance_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_method' => ['required', Rule::in(['cash', 'bank', 'cheque', 'mobile_banking'])],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'employee_id.required' => 'Employee is required.',
            'amount.required' => 'Amount is required.',
            'amount.gt' => 'Amount must be greater than zero.',
            'advance_date.required' => 'Advance date is required.',
        ];
    }

    protected function resetForm(): void
    {
        $this->reset(['employee_id', 'amount', 'notes']);
        $this->advance_date = now()->toDateString();
        $this->payment_method = 'cash';
    }
}

