<?php

namespace App\Livewire\Admin\Hrm\Payroll;

use App\Livewire\Admin\Hrm\Concerns\InteractsWithHrmAccess;
use App\Models\Payroll;
use App\Services\Hrm\PayrollService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;

class PayrollView extends Component
{
    use InteractsWithHrmAccess;

    public Payroll $payroll;

    public bool $showPaymentModal = false;

    public string $payment_date = '';

    public float|int|string $amount = '';

    public ?string $payment_method = null;

    public ?string $reference_no = null;

    public ?string $notes = null;

    public function mount(Payroll $payroll): void
    {
        $this->authorizePermission('hrm.payrolls.view');
        $this->payroll = $payroll;
        $this->payment_date = now()->toDateString();
    }

    public function openPaymentModal(): void
    {
        $this->authorizePermission('hrm.payrolls.pay');

        $this->resetPaymentForm();
        $this->showPaymentModal = true;
    }

    public function closePaymentModal(): void
    {
        $this->showPaymentModal = false;
    }

    public function addPayment(): void
    {
        $this->authorizePermission('hrm.payrolls.pay');

        $validated = $this->validate($this->paymentRules(), $this->paymentMessages());

        try {
            app(PayrollService::class)->addPayrollPayment($this->payroll, $validated, (int) auth()->id());
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);

            return;
        }

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Payroll payment saved successfully.']);
        $this->showPaymentModal = false;
        $this->resetPaymentForm();
    }

    public function render(): View
    {
        $this->authorizePermission('hrm.payrolls.view');

        $payroll = Payroll::query()
            ->with([
                'employee.department:id,name',
                'employee.designation:id,name',
                'salaryStructure:id,employee_id,effective_from,gross_salary',
                'generatedBy:id,name',
                'approvedBy:id,name',
                'items:id,payroll_id,type,label,amount,sort_order',
                'payments:id,payroll_id,transaction_id,payment_date,amount,payment_method,reference_no,notes,received_by',
                'payments.receiver:id,name',
                'advanceAdjustments:id,payroll_id,employee_advance_id,amount,adjustment_date',
                'advanceAdjustments.employeeAdvance:id,employee_id,amount,remaining_amount,status',
            ])
            ->withSum('payments as total_paid', 'amount')
            ->findOrFail($this->payroll->id);

        $totalPaid = round((float) ($payroll->total_paid ?? 0), 2);
        $dueAmount = round(max(0, (float) $payroll->net_salary - $totalPaid), 2);

        return view('livewire.admin.hrm.payroll.payroll-view', [
            'payroll' => $payroll,
            'itemsByType' => $payroll->items->groupBy('type'),
            'totalPaid' => $totalPaid,
            'dueAmount' => $dueAmount,
            'paymentMethods' => ['cash', 'bank', 'cheque', 'mobile_banking'],
        ])->layout('layouts.admin.admin');
    }

    /**
     * @return array<string, mixed>
     */
    protected function paymentRules(): array
    {
        return [
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_method' => ['nullable', Rule::in(['cash', 'bank', 'cheque', 'mobile_banking'])],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function paymentMessages(): array
    {
        return [
            'amount.required' => 'Payment amount is required.',
            'amount.gt' => 'Payment amount must be greater than zero.',
            'payment_date.required' => 'Payment date is required.',
        ];
    }

    protected function resetPaymentForm(): void
    {
        $this->reset(['amount', 'payment_method', 'reference_no', 'notes']);
        $this->payment_date = now()->toDateString();
    }
}

