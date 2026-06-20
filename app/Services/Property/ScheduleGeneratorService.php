<?php

namespace App\Services\Property;

use App\Enums\Property\PaymentCategory;
use App\Models\PaymentSchedule;
use App\Models\PropertySale;
use Carbon\CarbonImmutable;

class ScheduleGeneratorService
{
    /**
     * Generate all schedules for a property sale immediately after creation.
     * Called explicitly from Livewire – NOT from observer – to avoid double fire.
     */
    public function generateForSale(
        PropertySale $sale,
        ?float $serviceChargeOverride = null,
        ?float $utilityChargeOverride = null,
    ): void {
        if (!$sale->relationLoaded('propertyUnit')) {
            $sale->load('propertyUnit');
        }

        $serviceCharge = $serviceChargeOverride ?? (float) ($sale->propertyUnit?->service_charge ?? 0);
        $utilityCharge = $utilityChargeOverride ?? (float) ($sale->propertyUnit?->utility_charge ?? 0);

        if ($sale->sale_type === 'sale') {
            $this->generateSaleSchedules($sale, $serviceCharge, $utilityCharge);
        } elseif ($sale->sale_type === 'rent') {
            $this->generateRentSchedules($sale, $serviceCharge);
        }
    }

    /**
     * Regenerate: delete existing auto-generated pending schedules, re-create.
     */
    public function regenerateForSale(PropertySale $sale): void
    {
        PaymentSchedule::where('property_sale_id', $sale->id)
            ->where('is_auto_generated', true)
            ->whereIn('status', ['pending', 'overdue'])
            ->delete();

        $this->generateForSale($sale);
    }

    // ── Sale type generation ──────────────────────────────────────────────────

    private function generateSaleSchedules(PropertySale $sale, float $serviceCharge, float $utilityCharge = 0): void
    {
        // Down payment
        if ($sale->down_payment_amount > 0) {
            PaymentSchedule::create([
                'property_sale_id'  => $sale->id,
                'payment_category'  => PaymentCategory::DOWN_PAYMENT->value,
                'sequence_no'       => null,
                'due_date'          => $sale->sale_date ?? $sale->contract_date ?? today(),
                'amount'            => $sale->down_payment_amount,
                'paid_amount'       => 0,
                'due_amount'        => $sale->down_payment_amount,
                'status'            => 'pending',
                'is_auto_generated' => true,
                'remarks'           => 'Down payment',
            ]);
        }

        // Service charge
        if ($serviceCharge > 0) {
            PaymentSchedule::create([
                'property_sale_id'  => $sale->id,
                'payment_category'  => PaymentCategory::EXTRA_CHARGE->value,
                'sequence_no'       => null,
                'due_date'          => $sale->sale_date ?? $sale->contract_date ?? today(),
                'amount'            => $serviceCharge,
                'paid_amount'       => 0,
                'due_amount'        => $serviceCharge,
                'status'            => 'pending',
                'is_auto_generated' => true,
                'remarks'           => 'Service charge',
            ]);
        }

        // Utility charge
        if ($utilityCharge > 0) {
            PaymentSchedule::create([
                'property_sale_id'  => $sale->id,
                'payment_category'  => PaymentCategory::EXTRA_CHARGE->value,
                'sequence_no'       => null,
                'due_date'          => $sale->sale_date ?? $sale->contract_date ?? today(),
                'amount'            => $utilityCharge,
                'paid_amount'       => 0,
                'due_amount'        => $utilityCharge,
                'status'            => 'pending',
                'is_auto_generated' => true,
                'remarks'           => 'Utility charge',
            ]);
        }

        // Installments
        if ($sale->is_scheduled && $sale->schedule_count > 0 && $sale->schedule_start_date) {
            $dates = $this->generateDates(
                $sale->schedule_type,
                $sale->schedule_day,
                $sale->schedule_start_date->toDateString(),
                (int) $sale->schedule_count,
            );

            foreach ($dates as $i => $date) {
                PaymentSchedule::create([
                    'property_sale_id'  => $sale->id,
                    'payment_category'  => PaymentCategory::INSTALLMENT->value,
                    'sequence_no'       => $i + 1,
                    'due_date'          => $date,
                    'amount'            => $sale->schedule_amount,
                    'paid_amount'       => 0,
                    'due_amount'        => $sale->schedule_amount,
                    'status'            => 'pending',
                    'is_auto_generated' => true,
                    'remarks'           => "Installment #" . str_pad($i + 1, 2, '0', STR_PAD_LEFT),
                ]);
            }
        }
    }

    private function generateRentSchedules(PropertySale $sale, float $serviceCharge): void
    {
        // Security deposit
        if ($sale->security_deposit_amount > 0) {
            PaymentSchedule::create([
                'property_sale_id'  => $sale->id,
                'payment_category'  => PaymentCategory::SECURITY_DEPOSIT->value,
                'sequence_no'       => null,
                'due_date'          => $sale->rent_start_date ?? today(),
                'amount'            => $sale->security_deposit_amount,
                'paid_amount'       => 0,
                'due_amount'        => $sale->security_deposit_amount,
                'status'            => 'pending',
                'is_auto_generated' => true,
                'remarks'           => 'Security deposit',
            ]);
        }

        // Service charge
        if ($serviceCharge > 0) {
            PaymentSchedule::create([
                'property_sale_id'  => $sale->id,
                'payment_category'  => PaymentCategory::EXTRA_CHARGE->value,
                'sequence_no'       => null,
                'due_date'          => $sale->rent_start_date ?? today(),
                'amount'            => $serviceCharge,
                'paid_amount'       => 0,
                'due_amount'        => $serviceCharge,
                'status'            => 'pending',
                'is_auto_generated' => true,
                'remarks'           => 'Service charge',
            ]);
        }

        // Monthly rent
        if ($sale->is_scheduled && $sale->schedule_count > 0 && $sale->schedule_start_date) {
            $dates = $this->generateDates(
                $sale->schedule_type,
                $sale->schedule_day,
                $sale->schedule_start_date->toDateString(),
                (int) $sale->schedule_count,
            );

            foreach ($dates as $i => $date) {
                PaymentSchedule::create([
                    'property_sale_id'  => $sale->id,
                    'payment_category'  => PaymentCategory::MONTHLY_RENT->value,
                    'sequence_no'       => $i + 1,
                    'due_date'          => $date,
                    'amount'            => $sale->schedule_amount,
                    'paid_amount'       => 0,
                    'due_amount'        => $sale->schedule_amount,
                    'status'            => 'pending',
                    'is_auto_generated' => true,
                    'remarks'           => "Month #" . str_pad($i + 1, 2, '0', STR_PAD_LEFT),
                ]);
            }
        }
    }

    // ── Date calculation ──────────────────────────────────────────────────────

    /**
     * Generate N due dates.
     *
     * For monthly: first occurrence = startDate's month at scheduleDay (or next month if day already passed).
     * Then each subsequent period adds one interval.
     */
    public function generateDates(
        ?string $scheduleType,
        ?string $scheduleDay,
        string  $startDate,
        int     $count,
    ): array {
        $type  = $scheduleType ?? 'monthly';
        $day   = (int) ($scheduleDay ?? 5);
        $start = CarbonImmutable::parse($startDate);
        $dates = [];

        if ($type === 'monthly') {
            // First due date: set day in start month, advance to next month if already passed
            $first = $start->setDay(min($day, $start->daysInMonth));
            if ($first->lte($start)) {
                $next  = $start->addMonthNoOverflow();
                $first = $next->setDay(min($day, $next->daysInMonth));
            }

            for ($i = 0; $i < $count; $i++) {
                $base = $first->addMonthsNoOverflow($i);
                $dates[] = $base->setDay(min($day, $base->daysInMonth))->toDateString();
            }

        } elseif ($type === 'weekly') {
            $first = $start->addWeek();
            for ($i = 0; $i < $count; $i++) {
                $dates[] = $first->addWeeks($i)->toDateString();
            }

        } elseif ($type === 'daily') {
            for ($i = 1; $i <= $count; $i++) {
                $dates[] = $start->addDays($i)->toDateString();
            }

        } elseif ($type === 'yearly') {
            $first = $start->setDay(min($day, $start->daysInMonth));
            if ($first->lte($start)) {
                $next  = $start->addYearNoOverflow();
                $first = $next->setDay(min($day, $next->daysInMonth));
            }
            for ($i = 0; $i < $count; $i++) {
                $base = $first->addYearsNoOverflow($i);
                $dates[] = $base->setDay(min($day, $base->daysInMonth))->toDateString();
            }
        }

        return $dates;
    }

    /**
     * Preview dates only (no DB writes) — used by Livewire for live preview.
     */
    public function previewDates(
        ?string $scheduleType,
        ?string $scheduleDay,
        string  $startDate,
        int     $count,
    ): array {
        if ($count <= 0 || !$startDate) {
            return [];
        }

        return $this->generateDates($scheduleType, $scheduleDay, $startDate, $count);
    }
}
