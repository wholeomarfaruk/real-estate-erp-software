<?php

namespace App\Services\Property;

use App\Models\PaymentSchedule;
use App\Models\PropertySale;

class PaymentAllocationService
{
    /**
     * Recompute the PropertySale payment_status from its schedules.
     */
    public function syncSalePaymentStatus(PropertySale $sale): void
    {
        $schedules = $sale->paymentSchedules()->get();

        if ($schedules->isEmpty()) {
            return;
        }

        $total = (float) $schedules->sum('amount');
        $paid  = (float) $schedules->sum('paid_amount');

        $status = match(true) {
            $paid <= 0              => 'pending',
            $paid >= $total         => 'paid',
            default                 => 'partial',
        };

        $sale->update(['payment_status' => $status]);
    }

    private function deriveScheduleStatus(float $total, float $paid): string
    {
        if ($paid <= 0)           return 'pending';
        if ($paid >= $total)      return 'paid';
        return 'partial';
    }
}
