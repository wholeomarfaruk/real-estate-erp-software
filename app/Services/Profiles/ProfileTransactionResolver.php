<?php

namespace App\Services\Profiles;

use App\Models\Customer;
use App\Models\Employee;
use App\Models\MasterProfileRole;
use App\Models\Supplier;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

/**
 * Works out which master profile (party) an accounting entry belongs to.
 *
 * Entries already point at business records through reference_type / reference_id
 * (payroll, employee advance, purchase invoice, installment…). Those records lead
 * to a customer / supplier / employee, and that record's master-profile role gives
 * the profile.
 */
class ProfileTransactionResolver
{
    /**
     * Module record [class, id] a transaction reference points at.
     *
     * @return array{0: class-string, 1: int}|null
     */
    public function moduleRecordFor(?string $referenceType, ?int $referenceId): ?array
    {
        if (! $referenceType || ! $referenceId) {
            return null;
        }

        $employeeOfPayroll = fn ($payrollId) => DB::table('payrolls')->where('id', $payrollId)->value('employee_id');
        $customerOfSale = fn ($saleId) => DB::table('property_sales')->where('id', $saleId)->value('customer_id');

        [$class, $id] = match ($referenceType) {
            Customer::class, 'customer'          => [Customer::class, $referenceId],
            Supplier::class, 'supplier'          => [Supplier::class, $referenceId],
            Employee::class, 'employee'          => [Employee::class, $referenceId],
            'App\Models\PropertySale'            => [Customer::class, $customerOfSale($referenceId)],
            'App\Models\PaymentSchedule'         => [Customer::class, $customerOfSale(DB::table('payment_schedules')->where('id', $referenceId)->value('property_sale_id'))],
            'hrm_payroll', 'App\Models\Payroll', 'payroll_advance_adjustment'
                                                 => [Employee::class, $employeeOfPayroll($referenceId)],
            'payroll_payment', 'App\Models\PayrollPayment'
                                                 => [Employee::class, $employeeOfPayroll(DB::table('payroll_payments')->where('id', $referenceId)->value('payroll_id'))],
            'hrm_employee_advance', 'App\Models\EmployeeAdvance'
                                                 => [Employee::class, DB::table('employee_advances')->where('id', $referenceId)->value('employee_id')],
            'purchase_invoice', 'App\Models\PurchaseInvoice'
                                                 => [Supplier::class, DB::table('purchase_invoices')->where('id', $referenceId)->value('supplier_id')],
            default                              => [null, null],
        };

        return $class && $id ? [$class, (int) $id] : null;
    }

    /** Master profile linked to a module record. */
    public function profileForRecord(string $class, int $id): ?int
    {
        return MasterProfileRole::where('linkable_type', $class)->where('linkable_id', $id)->value('master_profile_id');
    }

    /** Master profile for a transaction reference (incl. payment requests). */
    public function profileFor(?string $referenceType, ?int $referenceId): ?int
    {
        if ($referenceType === 'banking_payment_request' && $referenceId) {
            $request = DB::table('banking_payment_requests')->where('id', $referenceId)
                ->first(['master_profile_id', 'sourceable_type', 'sourceable_id']);

            return $request?->master_profile_id
                ?? ($request ? $this->profileFor($request->sourceable_type, $request->sourceable_id) : null);
        }

        $record = $this->moduleRecordFor($referenceType, $referenceId);

        return $record ? $this->profileForRecord(...$record) : null;
    }

    /**
     * Tag untagged transactions with their profile. Never overwrites a profile
     * chosen by a user.
     *
     * @return int number of transactions tagged
     */
    public function sync(?int $onlyProfileId = null): int
    {
        $tagged = 0;

        Transaction::query()
            ->whereNull('master_profile_id')
            ->whereNotNull('reference_type')
            ->select(['id', 'reference_type', 'reference_id'])
            ->chunkById(500, function ($transactions) use (&$tagged, $onlyProfileId) {
                foreach ($transactions as $tx) {
                    $profileId = $this->profileFor($tx->reference_type, $tx->reference_id);
                    if ($profileId && (! $onlyProfileId || $profileId === $onlyProfileId)) {
                        DB::table('transactions')->where('id', $tx->id)->update(['master_profile_id' => $profileId]);
                        $tagged++;
                    }
                }
            });

        return $tagged;
    }
}
