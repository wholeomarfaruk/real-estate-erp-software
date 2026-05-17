<?php

namespace App\Traits;

use App\Models\PaymentSchedule;
use Carbon\Carbon;

trait HasPaymentSchedules
{
    public function totalScheduled(): float
    {
        return (float) $this->paymentSchedules()->sum('amount');
    }

    public function totalPaid(): float
    {
        return (float) $this->paymentSchedules()->sum('paid_amount');
    }

    public function totalDue(): float
    {
        return (float) $this->paymentSchedules()->sum('due_amount');
    }

    public function overdueCount(): int
    {
        return $this->paymentSchedules()
            ->where('due_date', '<', Carbon::today())
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->count();
    }

    public function isFullyPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function hasOverdueSchedules(): bool
    {
        return $this->overdueCount() > 0;
    }
}
