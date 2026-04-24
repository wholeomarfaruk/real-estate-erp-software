<?php

namespace App\Services\Hrm;

use App\Enums\Accounts\TransactionType;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class HrmAccountingService
{
    public function __construct(
        protected HrmAccountResolver $accountResolver
    ) {}

    public function createPayrollGenerationTransaction(
        float $amount,
        string $date,
        string $notes,
        int $actorId,
        string $referenceType,
        ?int $referenceId = null
    ): Transaction {
        if ($amount <= 0) {
            throw new \DomainException('Payroll amount must be greater than zero for accounting entry.');
        }

        $salaryExpense = $this->accountResolver->resolveRequiredAccount('salary_expense');
        $salaryPayable = $this->accountResolver->resolveRequiredAccount('salary_payable');

        return $this->createJournalTransaction(
            date: $date,
            notes: $notes,
            actorId: $actorId,
            referenceType: $referenceType,
            referenceId: $referenceId,
            lines: [
                [
                    'account_id' => $salaryExpense->id,
                    'debit' => $amount,
                    'credit' => 0,
                    'description' => 'Payroll generated (Salary Expense)',
                ],
                [
                    'account_id' => $salaryPayable->id,
                    'debit' => 0,
                    'credit' => $amount,
                    'description' => 'Payroll generated (Salary Payable)',
                ],
            ]
        );
    }

    public function createPayrollPaymentTransaction(
        float $amount,
        string $date,
        ?string $paymentMethod,
        string $notes,
        int $actorId,
        string $referenceType,
        ?int $referenceId = null
    ): Transaction {
        if ($amount <= 0) {
            throw new \DomainException('Payroll payment amount must be greater than zero for accounting entry.');
        }

        $salaryPayable = $this->accountResolver->resolveRequiredAccount('salary_payable');
        $paymentAccount = $this->accountResolver->resolvePaymentAccountByMethod($paymentMethod);

        return $this->createJournalTransaction(
            date: $date,
            notes: $notes,
            actorId: $actorId,
            referenceType: $referenceType,
            referenceId: $referenceId,
            lines: [
                [
                    'account_id' => $salaryPayable->id,
                    'debit' => $amount,
                    'credit' => 0,
                    'description' => 'Payroll payment (Salary Payable)',
                ],
                [
                    'account_id' => $paymentAccount->id,
                    'debit' => 0,
                    'credit' => $amount,
                    'description' => 'Payroll payment (Cash/Bank)',
                ],
            ]
        );
    }

    public function createEmployeeAdvanceTransaction(
        float $amount,
        string $date,
        ?string $paymentMethod,
        string $notes,
        int $actorId,
        string $referenceType,
        ?int $referenceId = null
    ): Transaction {
        if ($amount <= 0) {
            throw new \DomainException('Advance amount must be greater than zero for accounting entry.');
        }

        $employeeAdvance = $this->accountResolver->resolveRequiredAccount('employee_advance');
        $paymentAccount = $this->accountResolver->resolvePaymentAccountByMethod($paymentMethod);

        return $this->createJournalTransaction(
            date: $date,
            notes: $notes,
            actorId: $actorId,
            referenceType: $referenceType,
            referenceId: $referenceId,
            lines: [
                [
                    'account_id' => $employeeAdvance->id,
                    'debit' => $amount,
                    'credit' => 0,
                    'description' => 'Employee advance disbursed',
                ],
                [
                    'account_id' => $paymentAccount->id,
                    'debit' => 0,
                    'credit' => $amount,
                    'description' => 'Employee advance paid from Cash/Bank',
                ],
            ]
        );
    }

    /**
     * @param  array<int, array{account_id:int,debit:float|int,credit:float|int,description:string}>  $lines
     */
    protected function createJournalTransaction(
        string $date,
        string $notes,
        int $actorId,
        string $referenceType,
        ?int $referenceId,
        array $lines
    ): Transaction {
        return DB::transaction(function () use ($date, $notes, $actorId, $referenceType, $referenceId, $lines): Transaction {
            $totalDebit = 0.0;
            $totalCredit = 0.0;

            foreach ($lines as $line) {
                $totalDebit += (float) $line['debit'];
                $totalCredit += (float) $line['credit'];
            }

            $totalDebit = round($totalDebit, 3);
            $totalCredit = round($totalCredit, 3);

            if (abs($totalDebit - $totalCredit) > 0.0001) {
                throw new \DomainException('Journal entry is not balanced.');
            }

            $transaction = Transaction::query()->create([
                'date' => $date,
                'type' => TransactionType::JOURNAL->value,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'notes' => $notes,
                'created_by' => $actorId,
            ]);

            $transaction->lines()->createMany($lines);

            return $transaction;
        });
    }
}

