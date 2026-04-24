<?php

namespace App\Services\Hrm;

use App\Models\EmployeeAdvance;
use Illuminate\Support\Facades\DB;

class EmployeeAdvanceService
{
    public function __construct(
        protected HrmAccountingService $accountingService
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function createAdvance(array $payload, int $actorId): EmployeeAdvance
    {
        return DB::transaction(function () use ($payload, $actorId): EmployeeAdvance {
            $amount = round((float) $payload['amount'], 2);

            if ($amount <= 0) {
                throw new \DomainException('Advance amount must be greater than zero.');
            }

            $advance = EmployeeAdvance::query()->create([
                'employee_id' => (int) $payload['employee_id'],
                'advance_date' => $payload['advance_date'],
                'amount' => $amount,
                'adjusted_amount' => 0,
                'remaining_amount' => $amount,
                'status' => 'pending',
                'notes' => $payload['notes'] ?? null,
                'created_by' => $actorId,
            ]);

            $paymentMethod = (string) ($payload['payment_method'] ?? config('hrm.defaults.advance_payment_method', 'cash'));

            $transaction = $this->accountingService->createEmployeeAdvanceTransaction(
                amount: $amount,
                date: (string) $payload['advance_date'],
                paymentMethod: $paymentMethod,
                notes: 'Employee advance issued for employee #'.$advance->employee_id,
                actorId: $actorId,
                referenceType: 'hrm_employee_advance',
                referenceId: $advance->id
            );

            $advance->transaction_id = $transaction->id;
            $advance->save();

            return $advance->refresh();
        });
    }
}

