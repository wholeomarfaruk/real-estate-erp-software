<?php

namespace App\Services\Hrm;

use App\Enums\Accounts\PaymentRequestSourceType;
use App\Models\BankingPaymentRequest;
use App\Models\Employee;
use App\Models\EmployeeAdvance;
use Illuminate\Support\Facades\DB;

class EmployeeAdvanceService
{
    public function __construct(
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function createAdvance(array $payload, int $actorId): EmployeeAdvance
    {
        return DB::transaction(function () use ($payload, $actorId): EmployeeAdvance {
            $employeeId = (int) $payload['employee_id'];
            $amount = round((float) $payload['amount'], 2);

            if ($amount <= 0) {
                throw new \DomainException('Advance amount must be greater than zero.');
            }

            $employee = Employee::query()->findOrFail($employeeId);

            // Get payment account from Chart of Accounts
            $paymentAccountId = (int) ($payload['account_id'] ?? 0);
            if ($paymentAccountId <= 0) {
                throw new \DomainException('Payment account is required for advance disbursement.');
            }

            // Verify account exists and is active
            $paymentAccount = \App\Models\Account::query()->findOrFail($paymentAccountId);
            if (!$paymentAccount->is_active) {
                throw new \DomainException('Selected payment account is inactive.');
            }

            // Resolve the fixed debit account (employee advance receivable)
            $advanceAccount = \App\Models\Account::query()
                ->where('code', 'ASSET-EMP-ADV')
                ->where('is_active', true)
                ->firstOrFail();

            $advance = EmployeeAdvance::query()->create([
                'employee_id' => $employeeId,
                'advance_date' => $payload['advance_date'],
                'amount' => $amount,
                'adjusted_amount' => 0,
                'remaining_amount' => $amount,
                'status' => 'pending',
                'notes' => $payload['notes'] ?? null,
                'created_by' => $actorId,
            ]);

            // Create banking request with pre-stored double-entry accounts
            $bankingRequestData = [
                // Request Identity
                'request_no' => BankingPaymentRequest::generateRequestNo(),
                'source_type' => PaymentRequestSourceType::EMPLOYEE_ADVANCE->value,
                'sourceable_type' => Employee::class,
                'sourceable_id' => $employee->id,

                // Amount & Dates
                'amount' => $amount,
                'payment_date' => $payload['advance_date'] ?? now()->toDateString(),

                // Account Information
                'account_id' => $paymentAccountId,

                // Double-Entry (pre-stored for direct LedgerService posting)
                'debit_account_id' => $advanceAccount->id,
                'debit_amount' => $amount,
                'credit_account_id' => $paymentAccountId,
                'credit_amount' => $amount,

                // Transaction Details
                'reference_no' => 'ADV-' . $advance->id,
                'name' => $employee->name,
                'phone' => $employee->phone ?? null,
                'method' => $payload['payment_method'] ?? 'cash',
                'description' => 'Employee advance — ' . $employee->name,
                'notes' => $payload['notes'] ?? null,

                // Workflow Status
                'status' => 'pending',
                'rejection_reason' => null,

                // Audit Trail
                'requested_by' => $actorId,
                'approved_by' => null,
                'approved_at' => null,
                'released_by' => null,
                'released_at' => null,
                'completed_by' => null,
                'completed_at' => null,
                'rejected_by' => null,
                'rejected_at' => null,

                // Context
                'external_data' => [
                    'employee_advance_type' => EmployeeAdvance::class,
                    'employee_advance_id' => $advance->id,
                    'employee_id' => $employeeId,
                    'payment_account_id' => $paymentAccountId,
                ],
            ];

            BankingPaymentRequest::query()->create($bankingRequestData);

            return $advance->refresh();
        });
    }

}

