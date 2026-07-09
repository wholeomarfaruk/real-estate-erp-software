<?php

namespace App\Services\Client;

use App\Models\Customer;

class ClientDashboardService
{
    public function build(?Customer $customer): array
    {
        if (! $customer) {
            return [
                'summary'      => $this->emptySummary(),
                'next_payment' => null,
            ];
        }

        $sales = $customer->propertySales()
            ->with(['property', 'propertyUnit', 'paymentSchedules'])
            ->get();

        $totalPaid    = 0.0;
        $totalDue     = 0.0;
        $overdueCount = 0;

        $nextSchedule = null;
        $nextSale     = null;

        foreach ($sales as $sale) {
            $totalPaid  += (float) $sale->paymentSchedules->sum('paid_amount');
            $totalDue   += (float) $sale->paymentSchedules->sum('due_amount');
            $overdueCount += $sale->paymentSchedules->filter(fn ($s) => $s->isOverdue())->count();

            $candidate = $sale->paymentSchedules
                ->filter(fn ($s) => $s->isUnpaid())
                ->sortBy('due_date')
                ->first();

            if ($candidate && (! $nextSchedule || $candidate->due_date->lt($nextSchedule->due_date))) {
                $nextSchedule = $candidate;
                $nextSale     = $sale;
            }
        }

        $nextPayment = $nextSchedule ? [
            'amount'      => (float) $nextSchedule->due_amount,
            'due_date'    => $nextSchedule->due_date,
            'label'       => $nextSchedule->label(),
            'sale_id'     => $nextSale->id,
            'sale_number' => $nextSale->sale_number,
            'property'    => $nextSale->property?->name,
            'unit'        => $nextSale->propertyUnit?->effective_code,
        ] : null;

        return [
            'summary' => array_merge([
                'properties_count'  => $sales->count(),
                'total_paid'        => $totalPaid,
                'total_outstanding' => $totalDue,
                'overdue_count'     => $overdueCount,
            ], ClientNotificationCounts::unread($customer)),
            'next_payment' => $nextPayment,
        ];
    }

    private function emptySummary(): array
    {
        return array_merge([
            'properties_count'  => 0,
            'total_paid'        => 0.0,
            'total_outstanding' => 0.0,
            'overdue_count'     => 0,
        ], ClientNotificationCounts::unread(null));
    }
}
