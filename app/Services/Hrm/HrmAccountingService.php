<?php

namespace App\Services\Hrm;

use App\Accounting\PostingContext;
use App\Enums\Accounts\TransactionType;
use App\Models\Transaction;
use App\Services\Accounts\PostingEngine;
use Carbon\Carbon;

class HrmAccountingService
{
    public function __construct(
        protected PostingEngine $engine,
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

        return $this->engine->record(
            'hrm.salary_generation',
            new PostingContext(
                amount: $amount,
                datetime: $this->asDateTime($date),
                referenceType: $referenceType,
                referenceId: $referenceId,
                notes: $notes,
                actorId: $actorId,
            ),
        );
    }

    public function createPayrollPaymentTransaction(
        float $amount,
        string $date,
        ?string $paymentMethod,
        string $notes,
        int $actorId,
        string $referenceType,
        ?int $referenceId = null,
        ?int $paymentAccountId = null,
        ?int $transactionCategoryId = null,
        ?string $name = null
    ): Transaction {
        if ($amount <= 0) {
            throw new \DomainException('Payroll payment amount must be greater than zero for accounting entry.');
        }

        $normalizedMethod = $paymentMethod ?: 'cash';

        return $this->engine->record(
            'hrm.salary_payment',
            new PostingContext(
                amount: $amount,
                datetime: $this->asDateTime($date),
                paymentAccountId: $paymentAccountId,
                referenceType: $referenceType,
                referenceId: $referenceId,
                method: $normalizedMethod,
                name: $name,
                notes: $notes,
                actorId: $actorId,
            ),
        );
    }

    public function createEmployeeAdvanceTransaction(
        float $amount,
        string $date,
        ?string $method,
        string $notes,
        int $actorId,
        string $referenceType,
        ?int $referenceId = null,
        ?int $paymentAccountId = null
    ): Transaction {
        if ($amount <= 0) {
            throw new \DomainException('Advance amount must be greater than zero for accounting entry.');
        }

        $normalizedMethod = $method ?: 'cash';

        return $this->engine->record(
            'hrm.advance_disbursement',
            new PostingContext(
                amount: $amount,
                datetime: $this->asDateTime($date),
                paymentAccountId: $paymentAccountId,
                referenceType: $referenceType,
                referenceId: $referenceId,
                method: $normalizedMethod,
                notes: $notes,
                actorId: $actorId,
            ),
        );
    }

    public function createAdvanceAdjustmentEntry(
        float $amount,
        string $date,
        int $actorId,
        int $payrollId,
        string $notes
    ): Transaction {
        if ($amount <= 0) {
            throw new \DomainException('Advance adjustment amount must be greater than zero for accounting entry.');
        }

        return $this->engine->record(
            'hrm.advance_adjustment',
            new PostingContext(
                amount: $amount,
                datetime: $this->asDateTime($date),
                referenceType: 'hrm_payroll',
                referenceId: $payrollId,
                notes: $notes,
                actorId: $actorId,
            ),
        );
    }

    protected function asDateTime(string $date): string
    {
        return Carbon::parse($date)->startOfDay()->format('Y-m-d H:i:s');
    }
}
