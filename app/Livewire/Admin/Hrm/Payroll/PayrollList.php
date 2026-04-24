<?php

namespace App\Livewire\Admin\Hrm\Payroll;

use App\Livewire\Admin\Hrm\Concerns\InteractsWithHrmAccess;
use App\Models\Employee;
use App\Models\EmployeeAdvance;
use App\Models\Payroll;
use App\Services\Hrm\PayrollService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class PayrollList extends Component
{
    use InteractsWithHrmAccess;
    use WithPagination;

    public string $search = '';

    public ?int $employeeFilter = null;

    public ?int $monthFilter = null;

    public ?int $yearFilter = null;

    public string $paymentStatusFilter = '';

    public bool $showGenerateModal = false;

    public ?int $employee_id = null;

    public int $month;

    public int $year;

    public string $payroll_date = '';

    public ?string $notes = null;

    /**
     * @var array<int, array{label:string,amount:float|int|string}>
     */
    public array $bonus_items = [];

    /**
     * @var array<int, array{label:string,amount:float|int|string}>
     */
    public array $deduction_items = [];

    /**
     * @var array<int|string, float|int|string>
     */
    public array $advance_adjustments = [];

    protected string $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->authorizePermission('hrm.payrolls.view');

        $this->month = (int) now()->month;
        $this->year = (int) now()->year;
        $this->payroll_date = now()->toDateString();
        $this->bonus_items = [['label' => '', 'amount' => '']];
        $this->deduction_items = [['label' => '', 'amount' => '']];
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedEmployeeFilter(): void
    {
        $this->resetPage();
    }

    public function updatedMonthFilter(): void
    {
        $this->resetPage();
    }

    public function updatedYearFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPaymentStatusFilter(): void
    {
        $this->resetPage();
    }

    public function openGenerateModal(): void
    {
        $this->authorizePermission('hrm.payrolls.create');

        $this->resetGenerateForm();
        $this->showGenerateModal = true;
    }

    public function closeGenerateModal(): void
    {
        $this->showGenerateModal = false;
    }

    public function addBonusRow(): void
    {
        $this->bonus_items[] = ['label' => '', 'amount' => ''];
    }

    public function removeBonusRow(int $index): void
    {
        unset($this->bonus_items[$index]);
        $this->bonus_items = array_values($this->bonus_items);
    }

    public function addDeductionRow(): void
    {
        $this->deduction_items[] = ['label' => '', 'amount' => ''];
    }

    public function removeDeductionRow(int $index): void
    {
        unset($this->deduction_items[$index]);
        $this->deduction_items = array_values($this->deduction_items);
    }

    public function generatePayroll(): mixed
    {
        $this->authorizePermission('hrm.payrolls.create');

        $validated = $this->validate($this->rules(), $this->messages());

        try {
            $payroll = app(PayrollService::class)->generatePayroll($validated, (int) auth()->id());
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);

            return null;
        }

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Payroll generated successfully.']);
        $this->showGenerateModal = false;
        $this->resetGenerateForm();

        return redirect()->route('admin.hrm.payrolls.view', $payroll);
    }

    public function render(): View
    {
        $this->authorizePermission('hrm.payrolls.view');

        $payrolls = Payroll::query()
            ->with([
                'employee:id,name,employee_id',
                'generatedBy:id,name',
            ])
            ->withSum('payments as total_paid', 'amount')
            ->when($this->search !== '', function (Builder $query): void {
                $search = '%'.$this->search.'%';
                $query->whereHas('employee', function (Builder $subQuery) use ($search): void {
                    $subQuery->where('name', 'like', $search)
                        ->orWhere('employee_id', 'like', $search);
                });
            })
            ->when($this->employeeFilter, fn (Builder $query): Builder => $query->where('employee_id', $this->employeeFilter))
            ->when($this->monthFilter, fn (Builder $query): Builder => $query->where('month', $this->monthFilter))
            ->when($this->yearFilter, fn (Builder $query): Builder => $query->where('year', $this->yearFilter))
            ->when($this->paymentStatusFilter !== '', fn (Builder $query): Builder => $query->where('payment_status', $this->paymentStatusFilter))
            ->latest('year')
            ->latest('month')
            ->latest('id')
            ->paginate(15);

        $employees = Employee::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'employee_id']);

        $pendingAdvances = collect();

        if ($this->showGenerateModal && $this->employee_id) {
            $pendingAdvances = EmployeeAdvance::query()
                ->where('employee_id', $this->employee_id)
                ->whereIn('status', ['pending', 'partial'])
                ->where('remaining_amount', '>', 0)
                ->orderBy('advance_date')
                ->get(['id', 'advance_date', 'amount', 'adjusted_amount', 'remaining_amount']);
        }

        return view('livewire.admin.hrm.payroll.payroll-list', [
            'payrolls' => $payrolls,
            'employees' => $employees,
            'pendingAdvances' => $pendingAdvances,
            'statusOptions' => ['pending', 'partial', 'paid'],
            'monthOptions' => range(1, 12),
            'yearOptions' => range((int) now()->year - 3, (int) now()->year + 1),
        ])->layout('layouts.admin.admin');
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'employee_id' => ['required', 'exists:employees,id'],
            'month' => ['required', 'integer', 'between:1,12'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'payroll_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'bonus_items' => ['nullable', 'array'],
            'bonus_items.*.label' => ['nullable', 'string', 'max:100'],
            'bonus_items.*.amount' => ['nullable', 'numeric', 'min:0'],
            'deduction_items' => ['nullable', 'array'],
            'deduction_items.*.label' => ['nullable', 'string', 'max:100'],
            'deduction_items.*.amount' => ['nullable', 'numeric', 'min:0'],
            'advance_adjustments' => ['nullable', 'array'],
            'advance_adjustments.*' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'employee_id.required' => 'Employee is required.',
            'month.required' => 'Month is required.',
            'year.required' => 'Year is required.',
            'payroll_date.required' => 'Payroll date is required.',
        ];
    }

    protected function resetGenerateForm(): void
    {
        $this->reset([
            'employee_id',
            'notes',
            'advance_adjustments',
        ]);
        $this->month = (int) now()->month;
        $this->year = (int) now()->year;
        $this->payroll_date = now()->toDateString();
        $this->bonus_items = [['label' => '', 'amount' => '']];
        $this->deduction_items = [['label' => '', 'amount' => '']];
    }
}

