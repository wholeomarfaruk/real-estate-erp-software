<?php

namespace App\Services\Hrm;

use App\Enums\Accounts\EntryMethod;
use App\Enums\Accounts\PaymentRequestSourceType;
use App\Models\Account;
use App\Models\BankingPaymentRequest;
use App\Models\Employee;
use App\Models\EmployeeAdvance;
use App\Models\EmployeeAdvanceAdjustment;
use App\Models\Payroll;
use App\Models\PayrollPayment;
use App\Models\SalaryStructure;
use App\Models\TransactionCategory;
use Illuminate\Database\Eloquent\Builder;
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
            $deductionTotal = round($this->sumItemAmounts($deductionItems), 2);
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

            $this->createPayrollItems($payroll, $salaryStructure, $bonusItems, $deductionItems, 0);

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

            $paymentType = $payload['payment_type'] ?? 'bank';

            // Advance Recovery Path
            if ($paymentType === 'advance') {
                $advanceId = (int) ($payload['advance_id'] ?? 0);
                $advance = EmployeeAdvance::query()
                    ->lockForUpdate()
                    ->where('employee_id', $payroll->employee_id)
                    ->findOrFail($advanceId);

                if ($amount > round((float) $advance->remaining_amount, 2)) {
                    throw new \DomainException('Recovery amount exceeds advance remaining balance.');
                }

                $salaryPayableAccount = Account::query()
                    ->where('code', 'LIAB-SAL-PAY')
                    ->where('is_active', true)
                    ->firstOrFail();

                $advanceAccount = Account::query()
                    ->where('code', 'ASSET-EMP-ADV')
                    ->where('is_active', true)
                    ->firstOrFail();

                $payment = PayrollPayment::query()->create([
                    'payroll_id' => $payroll->id,
                    'payment_date' => $payload['payment_date'],
                    'amount' => $amount,
                    'payment_method' => 'advance',
                    'reference_no' => $payload['reference_no'] ?? null,
                    'notes' => $payload['notes'] ?? null,
                    'received_by' => $actorId,
                ]);

                BankingPaymentRequest::query()->create([
                    'request_no' => BankingPaymentRequest::generateRequestNo(),
                    'source_type' => PaymentRequestSourceType::PAYROLL->value,
                    'sourceable_type' => Employee::class,
                    'sourceable_id' => $payroll->employee_id,
                    'transaction_category_id' => null,
                    'transaction_id' => null,
                    'amount' => $amount,
                    'description' => $this->payrollRequestDescription($payroll),
                    'bank_account_id' => null,
                    'account_id' => null,

                    // Double-Entry for Advance Recovery
                    // DR: Salary Payable (liability) / CR: Employee Advance
                    'debit_account_id' => $salaryPayableAccount->id,
                    'debit_amount' => $amount,
                    'credit_account_id' => $advanceAccount->id,
                    'credit_amount' => $amount,

                    'status' => 'pending',
                    'notes' => $this->payrollRequestNotes($payroll, $payment),
                    'external_data' => [
                        'advance_id' => $advanceId,
                        'payroll_payment_id' => $payment->id,
                    ],
                    'requested_by' => $actorId,
                ]);

                return $payment->refresh()->load('bankingRequest');
            }

            // Bank/Cash Payment Path
            $paymentAccount = Account::query()->findOrFail((int) $payload['bank_account_id']);

            $alreadyCommitted = $this->committedPayrollAmount($payroll->id);
            $remaining = round(max(0, (float) $payroll->net_salary - $alreadyCommitted), 2);

            if ($amount > $remaining) {
                throw new \DomainException('Payment amount cannot exceed unpaid salary amount.');
            }

            $paymentMethod = $this->validatePaymentMethod(
                $payload['payment_method'] ?? null,
                $paymentAccount->type
            );

            // Resolve salary payable account (debit account for payment)
            $salaryPayableAccount = Account::query()
                ->where('code', 'LIAB-SAL-PAY')
                ->where('is_active', true)
                ->firstOrFail();

            // Payment account (credit account - cash/bank)
            $paymentAccountId = (int) $paymentAccount->id;

            $payment = PayrollPayment::query()->create([
                'payroll_id' => $payroll->id,
                'payment_date' => $payload['payment_date'],
                'amount' => $amount,
                'payment_method' => $paymentMethod,
                'reference_no' => $payload['reference_no'] ?? null,
                'notes' => $payload['notes'] ?? null,
                'received_by' => $actorId,
            ]);

            BankingPaymentRequest::query()->create([
                'request_no' => BankingPaymentRequest::generateRequestNo(),
                'source_type' => PaymentRequestSourceType::PAYROLL->value,
                'sourceable_type' => PayrollPayment::class,
                'sourceable_id' => $payment->id,
                'transaction_category_id' => $this->payrollCategoryId(),
                'transaction_id' => null,
                'amount' => $amount,
                'description' => $this->payrollRequestDescription($payroll),
                'bank_account_id' => null,
                'account_id' => $paymentAccountId,

                // Double-Entry (pre-stored for direct LedgerService posting)
                'debit_account_id' => $salaryPayableAccount->id,
                'debit_amount' => $amount,
                'credit_account_id' => $paymentAccountId,
                'credit_amount' => $amount,

                'status' => 'pending',
                'notes' => $this->payrollRequestNotes($payroll, $payment),
                'requested_by' => $actorId,
            ]);

            return $payment->refresh()->load('bankingRequest');
        });
    }


    public function recalculatePayrollPaymentStatus(int $payrollId): void
    {
        $payroll = Payroll::query()->lockForUpdate()->findOrFail($payrollId);

        $paid = round((float) PayrollPayment::query()
            ->where('payroll_id', $payrollId)
            ->whereNotNull('transaction_id')
            ->sum('amount'), 2);
        $latestPaymentDate = PayrollPayment::query()
            ->where('payroll_id', $payrollId)
            ->whereNotNull('transaction_id')
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

    protected function committedPayrollAmount(int $payrollId): float
    {
        return round((float) PayrollPayment::query()
            ->where('payroll_id', $payrollId)
            ->where(function (Builder $query): void {
                $query->whereNotNull('transaction_id')
                    ->orWhereHas('bankingRequest', function (Builder $requestQuery): void {
                        $requestQuery->whereIn('status', ['pending', 'approved', 'released', 'completed']);
                    });
            })
            ->sum('amount'), 2);
    }

    protected function payrollCategoryId(): ?int
    {
        return TransactionCategory::query()
            ->where('is_active', true)
            ->where(function (Builder $query): void {
                $query->where('slug', 'payroll')
                    ->orWhere(function (Builder $subQuery): void {
                        $subQuery->where('type', 'expense')
                            ->whereRaw('LOWER(name) = ?', ['payroll']);
                    });
            })
            ->value('id');
    }

    protected function payrollRequestDescription(Payroll $payroll): string
    {
        $period = now()->setDate($payroll->year, $payroll->month, 1)->format('F Y');

        return sprintf(
            'Payroll payment - %s - %s',
            $payroll->employee?->name ?? 'Employee',
            $period
        );
    }

    protected function payrollRequestNotes(Payroll $payroll, PayrollPayment $payment): string
    {
        $parts = [
            'Basic Salary: '.number_format((float) $payroll->basic_salary, 2),
            'Allowance: '.number_format((float) $payroll->allowance_total, 2),
            'Bonus: '.number_format((float) $payroll->bonus_total, 2),
        ];

        if ($payroll->deduction_total > 0) {
            $parts[] = 'Deduction: '.number_format((float) $payroll->deduction_total, 2);
        }

        if ($payment->reference_no) {
            $parts[] = 'Reference: '.$payment->reference_no;
        }

        if ($payment->notes) {
            $parts[] = trim((string) $payment->notes);
        }

        return implode(' | ', array_filter($parts));
    }

    protected function payrollCompletionNotes(Payroll $payroll, PayrollPayment $payment): string
    {
        return $payment->notes
            ? trim((string) $payment->notes)
            : 'Payroll payment for '.$payroll->employee?->name.' ('.$payroll->month.'/'.$payroll->year.')';
    }

    protected function validatePaymentMethod(?string $paymentMethod, ?\App\Enums\Accounts\AccountType $accountType = null): ?string
    {
        if (!$paymentMethod) {
            return null;
        }

        try {
            $method = EntryMethod::tryFrom(strtolower(trim($paymentMethod)));
            if (!$method || !$accountType) {
                return $method?->value;
            }

            // Verify the payment method matches the account type
            if ($method->accountType() === $accountType) {
                return $method->value;
            }
        } catch (\Throwable) {
            // Invalid enum value
        }

        return null;
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
    protected function applyAdvanceAdjustments(Payroll $payroll, array $advanceAdjustments, int $actorId): void
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

            if ($advance->transaction_id) {
                $this->accountingService->createAdvanceAdjustmentEntry(
                    amount: $adjustmentAmount,
                    date: $payroll->payroll_date?->format('Y-m-d') ?? now()->toDateString(),
                    actorId: $actorId,
                    payrollId: $payroll->id,
                    notes: 'Advance recovery for '.$payroll->employee?->name.' ('.$payroll->month.'/'.$payroll->year.')',
                );
            }
        }
    }
}
