<?php

namespace App\Services\Hrm;

use App\Models\Employee;
use App\Models\EmployeeAdvance;
use App\Models\EmployeeAdvanceAdjustment;
use App\Models\Payroll;
use App\Models\PayrollPayment;
use App\Models\SalaryStructure;
use Illuminate\Support\Facades\DB;

class PayrollService
{
    public function __construct(
        protected HrmAccountingService $accountingService
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function generatePayroll(array $payload, int $actorId): Payroll
    {
        return DB::transaction(function () use ($payload, $actorId): Payroll {
            $employee = Employee::query()->findOrFail((int) $payload['employee_id']);

            $month = (int) $payload['month'];
            $year = (int) $payload['year'];
            $payrollDate = (string) $payload['payroll_date'];
            $notes = (string) ($payload['notes'] ?? '');

            $exists = Payroll::query()
                ->where('employee_id', $employee->id)
                ->where('month', $month)
                ->where('year', $year)
                ->exists();

            if ($exists) {
                throw new \DomainException('Payroll already exists for this employee and month.');
            }

            $salaryStructure = $this->resolveSalaryStructure($employee, $payrollDate);

            $bonusItems = $this->normalizeItems($payload['bonus_items'] ?? []);
            $deductionItems = $this->normalizeItems($payload['deduction_items'] ?? []);
            $advanceAdjustments = $this->normalizeAdvanceAdjustments($payload['advance_adjustments'] ?? []);

            $basicSalary = round((float) $salaryStructure->basic_salary, 2);
            $allowanceTotal = round(
                (float) $salaryStructure->house_rent
                + (float) $salaryStructure->medical_allowance
                + (float) $salaryStructure->transport_allowance
                + (float) $salaryStructure->food_allowance
                + (float) $salaryStructure->other_allowance,
                2
            );
            $grossSalary = round((float) $salaryStructure->gross_salary, 2);
            $bonusTotal = round($this->sumItemAmounts($bonusItems), 2);
            $deductionTotal = round($this->sumItemAmounts($deductionItems) + array_sum($advanceAdjustments), 2);
            $netSalary = round(max(0, $grossSalary + $bonusTotal - $deductionTotal), 2);

            $payroll = Payroll::query()->create([
                'employee_id' => $employee->id,
                'salary_structure_id' => $salaryStructure->id,
                'month' => $month,
                'year' => $year,
                'payroll_date' => $payrollDate,
                'basic_salary' => $basicSalary,
                'allowance_total' => $allowanceTotal,
                'bonus_total' => $bonusTotal,
                'deduction_total' => $deductionTotal,
                'gross_salary' => $grossSalary,
                'net_salary' => $netSalary,
                'payment_status' => 'pending',
                'notes' => $notes ?: null,
                'generated_by' => $actorId,
            ]);

            $transaction = $this->accountingService->createPayrollGenerationTransaction(
                amount: $netSalary,
                date: $payrollDate,
                notes: 'Payroll generated for '.$employee->name.' ('.$month.'/'.$year.')',
                actorId: $actorId,
                referenceType: 'hrm_payroll',
                referenceId: $payroll->id
            );

            $payroll->transaction_id = $transaction->id;
            $payroll->save();

            $this->createPayrollItems($payroll, $salaryStructure, $bonusItems, $deductionItems, array_sum($advanceAdjustments));
            $this->applyAdvanceAdjustments($payroll, $advanceAdjustments);

            return $payroll->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function addPayrollPayment(Payroll $payroll, array $payload, int $actorId): PayrollPayment
    {
        return DB::transaction(function () use ($payroll, $payload, $actorId): PayrollPayment {
            $payroll = Payroll::query()->lockForUpdate()->findOrFail($payroll->id);

            $amount = round((float) $payload['amount'], 2);

            if ($amount <= 0) {
                throw new \DomainException('Payment amount must be greater than zero.');
            }

            $alreadyPaid = round((float) PayrollPayment::query()
                ->where('payroll_id', $payroll->id)
                ->sum('amount'), 2);

            $remaining = round(max(0, (float) $payroll->net_salary - $alreadyPaid), 2);

            if ($amount > $remaining) {
                throw new \DomainException('Payment amount cannot exceed unpaid salary amount.');
            }

            $payment = PayrollPayment::query()->create([
                'payroll_id' => $payroll->id,
                'payment_date' => $payload['payment_date'],
                'amount' => $amount,
                'payment_method' => $payload['payment_method'] ?? null,
                'reference_no' => $payload['reference_no'] ?? null,
                'notes' => $payload['notes'] ?? null,
                'received_by' => $actorId,
            ]);

            $transaction = $this->accountingService->createPayrollPaymentTransaction(
                amount: $amount,
                date: (string) $payload['payment_date'],
                paymentMethod: (string) ($payload['payment_method'] ?? ''),
                notes: 'Payroll payment for payroll #'.$payroll->id,
                actorId: $actorId,
                referenceType: 'hrm_payroll_payment',
                referenceId: $payment->id
            );

            $payment->transaction_id = $transaction->id;
            $payment->save();

            $this->recalculatePayrollPaymentStatus($payroll->id);

            return $payment->refresh();
        });
    }

    protected function recalculatePayrollPaymentStatus(int $payrollId): void
    {
        $payroll = Payroll::query()->lockForUpdate()->findOrFail($payrollId);

        $paid = round((float) PayrollPayment::query()
            ->where('payroll_id', $payrollId)
            ->sum('amount'), 2);
        $latestPaymentDate = PayrollPayment::query()
            ->where('payroll_id', $payrollId)
            ->max('payment_date');

        if ($paid <= 0) {
            $payroll->payment_status = 'pending';
            $payroll->payment_date = null;
            $payroll->save();

            return;
        }

        if ($paid >= (float) $payroll->net_salary) {
            $payroll->payment_status = 'paid';
            $payroll->payment_date = $latestPaymentDate;
            $payroll->save();

            return;
        }

        $payroll->payment_status = 'partial';
        $payroll->payment_date = $latestPaymentDate;
        $payroll->save();
    }

    protected function resolveSalaryStructure(Employee $employee, string $payrollDate): SalaryStructure
    {
        $structure = SalaryStructure::query()
            ->where('employee_id', $employee->id)
            ->where('status', true)
            ->whereDate('effective_from', '<=', $payrollDate)
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->first();

        if (! $structure) {
            $structure = SalaryStructure::query()
                ->where('employee_id', $employee->id)
                ->where('status', true)
                ->orderByDesc('effective_from')
                ->orderByDesc('id')
                ->first();
        }

        if (! $structure) {
            throw new \DomainException('No active salary structure found for this employee.');
        }

        return $structure;
    }

    /**
     * @param  array<int, mixed>  $items
     * @return array<int, array{label:string,amount:float}>
     */
    protected function normalizeItems(array $items): array
    {
        $normalized = [];

        foreach ($items as $item) {
            $label = trim((string) ($item['label'] ?? ''));
            $amount = round((float) ($item['amount'] ?? 0), 2);

            if ($label === '' || $amount <= 0) {
                continue;
            }

            $normalized[] = [
                'label' => $label,
                'amount' => $amount,
            ];
        }

        return $normalized;
    }

    /**
     * @param  array<string|int, mixed>  $adjustments
     * @return array<int, float>
     */
    protected function normalizeAdvanceAdjustments(array $adjustments): array
    {
        $normalized = [];

        foreach ($adjustments as $advanceId => $amount) {
            $id = (int) $advanceId;
            $amount = round((float) $amount, 2);

            if ($id <= 0 || $amount <= 0) {
                continue;
            }

            $normalized[$id] = $amount;
        }

        return $normalized;
    }

    /**
     * @param  array<int, array{label:string,amount:float}>  $items
     */
    protected function sumItemAmounts(array $items): float
    {
        return array_reduce($items, static fn (float $carry, array $item): float => $carry + (float) $item['amount'], 0.0);
    }

    /**
     * @param  array<int, array{label:string,amount:float}>  $bonusItems
     * @param  array<int, array{label:string,amount:float}>  $deductionItems
     */
    protected function createPayrollItems(
        Payroll $payroll,
        SalaryStructure $salaryStructure,
        array $bonusItems,
        array $deductionItems,
        float $advanceAdjustmentTotal
    ): void {
        $sortOrder = 1;

        $earningRows = [
            ['label' => 'Basic Salary', 'amount' => (float) $salaryStructure->basic_salary],
            ['label' => 'House Rent', 'amount' => (float) $salaryStructure->house_rent],
            ['label' => 'Medical Allowance', 'amount' => (float) $salaryStructure->medical_allowance],
            ['label' => 'Transport Allowance', 'amount' => (float) $salaryStructure->transport_allowance],
            ['label' => 'Food Allowance', 'amount' => (float) $salaryStructure->food_allowance],
            ['label' => 'Other Allowance', 'amount' => (float) $salaryStructure->other_allowance],
        ];

        foreach ($earningRows as $row) {
            if ($row['amount'] <= 0) {
                continue;
            }

            $payroll->items()->create([
                'type' => 'earning',
                'label' => $row['label'],
                'amount' => round($row['amount'], 2),
                'sort_order' => $sortOrder++,
            ]);
        }

        foreach ($bonusItems as $item) {
            $payroll->items()->create([
                'type' => 'bonus',
                'label' => $item['label'],
                'amount' => $item['amount'],
                'sort_order' => $sortOrder++,
            ]);
        }

        foreach ($deductionItems as $item) {
            $payroll->items()->create([
                'type' => 'deduction',
                'label' => $item['label'],
                'amount' => $item['amount'],
                'sort_order' => $sortOrder++,
            ]);
        }

        if ($advanceAdjustmentTotal > 0) {
            $payroll->items()->create([
                'type' => 'deduction',
                'label' => 'Advance Adjustment',
                'amount' => round($advanceAdjustmentTotal, 2),
                'sort_order' => $sortOrder,
            ]);
        }
    }

    /**
     * @param  array<int, float>  $advanceAdjustments
     */
    protected function applyAdvanceAdjustments(Payroll $payroll, array $advanceAdjustments): void
    {
        foreach ($advanceAdjustments as $advanceId => $adjustmentAmount) {
            $advance = EmployeeAdvance::query()
                ->lockForUpdate()
                ->where('employee_id', $payroll->employee_id)
                ->find($advanceId);

            if (! $advance) {
                throw new \DomainException("Employee advance #$advanceId not found for selected employee.");
            }

            $remaining = round((float) $advance->remaining_amount, 2);

            if ($adjustmentAmount > $remaining) {
                throw new \DomainException("Adjustment for advance #$advanceId exceeds remaining amount.");
            }

            EmployeeAdvanceAdjustment::query()->create([
                'employee_advance_id' => $advance->id,
                'payroll_id' => $payroll->id,
                'amount' => $adjustmentAmount,
                'adjustment_date' => $payroll->payroll_date ?: now()->toDateString(),
            ]);

            $advance->adjusted_amount = round((float) $advance->adjusted_amount + $adjustmentAmount, 2);
            $advance->recalculateStatus();
            $advance->save();
        }
    }
}

