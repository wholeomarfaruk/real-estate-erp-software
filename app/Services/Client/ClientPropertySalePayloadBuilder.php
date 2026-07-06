<?php

namespace App\Services\Client;

use App\Enums\Property\Availability;
use App\Models\File;
use App\Models\PaymentSchedule;
use App\Models\Property;
use App\Models\PropertySale;
use App\Models\PropertySaleUnit;
use App\Models\PropertyUnit;

class ClientPropertySalePayloadBuilder
{
    /** Row shape for the "my properties" list. */
    public static function listItem(PropertySale $sale): array
    {
        return [
            'id'               => $sale->id,
            'sale_number'      => $sale->sale_number,
            'sale_type'        => $sale->sale_type,
            'sale_type_label'  => $sale->saleTypeLabel(),
            'status'           => $sale->status,
            'payment_status'   => $sale->payment_status,
            'sale_date'        => $sale->sale_date,
            'contract_date'    => $sale->contract_date,
            'property'         => self::property($sale->property),
            'unit'             => self::unit($sale->propertyUnit),
            'units'            => $sale->saleUnits->map(fn ($su) => self::unit($su->propertyUnit))->all(),
            'sale_amount'      => (float) $sale->sale_amount,
            'net_amount'       => (float) $sale->net_amount,
            'paid_amount'      => (float) $sale->paymentSchedules->sum('paid_amount'),
            'due_amount'       => (float) $sale->paymentSchedules->sum('due_amount'),
            'schedule_summary' => self::scheduleSummary($sale->paymentSchedules),
            'cover_image_url'  => self::coverImage($sale->property),
        ];
    }

    /** Full detail payload for a single sale. */
    public static function details(PropertySale $sale): array
    {
        return array_merge(self::listItem($sale), [
            'discount_amount'          => (float) $sale->discount_amount,
            'tax_amount'               => (float) $sale->tax_amount,
            'down_payment_amount'      => (float) $sale->down_payment_amount,
            'down_payment_percentage'  => (float) $sale->down_payment_percentage,
            'payment_terms'            => $sale->payment_terms,
            'schedule'                 => [
                'is_scheduled'  => (bool) $sale->is_scheduled,
                'count'         => $sale->schedule_count,
                'amount'        => (float) $sale->schedule_amount,
                'name'          => $sale->schedule_name,
                'type'          => $sale->schedule_type,
                'day'           => $sale->schedule_day,
                'start_date'    => $sale->schedule_start_date,
                'status'        => $sale->schedule_status,
            ],
            'rent'                     => $sale->isRent() ? [
                'start_date'              => $sale->rent_start_date,
                'end_date'                => $sale->rent_end_date,
                'security_deposit_amount' => (float) $sale->security_deposit_amount,
                'is_renewal'              => (bool) $sale->is_renewal,
                'renewal_date'            => $sale->renewal_date,
            ] : null,
            'sales_representative'     => $sale->sales_representative,
            'notes'                    => $sale->notes,
            'features'                 => $sale->extra_data['features'] ?? [],
            'terms_conditions'         => $sale->extra_data['terms_conditions'] ?? [],
            'is_handed_over'           => $sale->isHandedOver(),
            'handover_date'            => $sale->handoverInfo()['date'] ?? null,
            'created_at'               => $sale->created_at,
        ]);
    }

    /** Row shape for the unit-wise "my properties" list — one row per purchased/rented unit. */
    public static function unitWiseItem(PropertySale $sale, PropertySaleUnit $saleUnit): array
    {
        $property = $saleUnit->property ?: $sale->property;

        return [
            'id'               => $saleUnit->id,
            'sale_id'          => $sale->id,
            'sale_number'      => $sale->sale_number,
            'sale_type'        => $sale->sale_type,
            'sale_type_label'  => $sale->saleTypeLabel(),
            'status'           => $sale->status,
            'payment_status'   => $sale->payment_status,
            'sale_date'        => $sale->sale_date,
            'contract_date'    => $sale->contract_date,
            'property'         => self::property($property),
            'unit'             => self::unit($saleUnit->propertyUnit),
            'pricing'          => [
                'sale_amount'             => (float) $saleUnit->sale_amount,
                'discount_amount'         => (float) $saleUnit->discount_amount,
                'tax_amount'              => (float) $saleUnit->tax_amount,
                'net_amount'              => (float) $saleUnit->net_amount,
                'service_charge'          => (float) $saleUnit->service_charge,
                'utility_charge'          => (float) $saleUnit->utility_charge,
                'down_payment_percentage' => (float) $saleUnit->down_payment_percentage,
            ],
            'cover_image_url'  => self::coverImage($property),
            'images'           => self::propertyImages($property),
        ];
    }

    /** Files listed in the sold property's `documents` column (File IDs). */
    public static function documents(PropertySale $sale): array
    {
        return self::propertyDocuments($sale->property);
    }

    /** Full detail payload for a single purchased/rented unit — overview, gallery, and documents. */
    public static function unitDetails(PropertySaleUnit $saleUnit): array
    {
        $sale = $saleUnit->propertySale;
        $property = $saleUnit->property ?: $sale->property;

        return array_merge(self::unitWiseItem($sale, $saleUnit), [
            'documents'      => self::propertyDocuments($property),
            'paid_amount'    => (float) $sale->paymentSchedules->sum('paid_amount'),
            'due_amount'     => (float) $sale->paymentSchedules->sum('due_amount'),
            'notes'          => $sale->notes,
            'is_handed_over' => $sale->isHandedOver(),
            'handover_date'  => $sale->handoverInfo()['date'] ?? null,
            'created_at'     => $sale->created_at,
        ]);
    }

    /** Payment schedules with each schedule's individual payment transactions. */
    public static function paymentSchedules(PropertySale $sale): array
    {
        return $sale->paymentSchedules
            ->map(fn (PaymentSchedule $schedule) => self::schedule($schedule))
            ->all();
    }

    // ── Component mappers ───────────────────────────────────────────────────

    private static function schedule(PaymentSchedule $schedule): array
    {
        return [
            'id'              => $schedule->id,
            'payment_category' => $schedule->payment_category,
            'label'           => $schedule->label(),
            'sequence_no'     => $schedule->sequence_no,
            'due_date'        => $schedule->due_date,
            'amount'          => (float) $schedule->amount,
            'paid_amount'     => (float) $schedule->paid_amount,
            'due_amount'      => (float) $schedule->due_amount,
            'status'          => $schedule->status,
            'display_status'  => $schedule->displayStatus(),
            'is_overdue'      => $schedule->isOverdue(),
            'remarks'         => $schedule->remarks,
            'transactions'    => self::transactions($schedule),
        ];
    }

    /**
     * A schedule's individual payment transactions (excluding reversed ones).
     * Public — also reused by ClientPaymentHistoryPayloadBuilder.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function transactions(PaymentSchedule $schedule): array
    {
        return $schedule->paymentTransactions()
            ->with('lines')
            ->whereNull('adjusted_at')
            ->where(function ($q) {
                $q->whereNull('relation_type')
                    ->orWhere('relation_type', '!=', \App\Enums\Accounts\TransactionRelationType::REVERSE->value);
            })
            ->orderByDesc('datetime')
            ->get()
            ->map(fn ($tx) => [
                'id'           => $tx->id,
                'amount'       => (float) $tx->lines->sum('debit'),
                'method'       => $tx->method?->label(),
                'reference_no' => $tx->reference_no,
                'payer_name'   => $tx->name,
                'phone'        => $tx->phone,
                'notes'        => $tx->notes,
                'datetime'     => optional($tx->datetime)->toDateTimeString(),
                'attachments'  => self::attachments($tx->attachments ?? []),
            ])
            ->all();
    }

    /** @param \Illuminate\Support\Collection<int, PaymentSchedule> $schedules */
    private static function scheduleSummary($schedules): array
    {
        $statuses = $schedules->map(fn (PaymentSchedule $schedule) => $schedule->displayStatus());

        return [
            'total'   => $schedules->count(),
            'paid'    => $statuses->filter(fn ($status) => $status === 'paid')->count(),
            'overdue' => $statuses->filter(fn ($status) => $status === 'overdue')->count(),
            'unpaid'  => $statuses->filter(fn ($status) => ! in_array($status, ['paid', 'overdue'], true))->count(),
        ];
    }

    private static function propertyDocuments(?Property $property): array
    {
        $fileIds = $property?->documents ?? [];

        if (empty($fileIds)) {
            return [];
        }

        return File::whereIn('id', $fileIds)
            ->get()
            ->map(fn (File $file) => self::file($file))
            ->all();
    }

    /** @param array<int, int> $fileIds */
    private static function attachments(array $fileIds): array
    {
        if (empty($fileIds)) {
            return [];
        }

        return File::whereIn('id', $fileIds)
            ->get()
            ->map(fn (File $file) => self::file($file))
            ->all();
    }

    private static function file(File $file): array
    {
        return [
            'id'        => $file->id,
            'name'      => $file->name,
            'caption'   => $file->caption,
            'extension' => $file->extension,
            'url'       => file_path($file->id),
        ];
    }

    private static function property(?Property $property): ?array
    {
        if (! $property) {
            return null;
        }

        return [
            'id'         => $property->id,
            'name'       => $property->name,
            'code'       => $property->code,
            'address'    => $property->address,
            'type'       => $property->type ?? $property->property_type,
            'total_area' => $property->total_area !== null ? (float) $property->total_area : null,
            'land_size'  => $property->land_size !== null ? (float) $property->land_size : null,
        ];
    }

    private static function unit(?PropertyUnit $unit): ?array
    {
        if (! $unit) {
            return null;
        }

        return [
            'id'           => $unit->id,
            'code'         => $unit->effective_code,
            'type'         => $unit->effective_type,
            'status'       => $unit->effective_status,
            'status_label' => (Availability::tryFrom($unit->effective_status) ?? Availability::AVAILABLE)->label(),
            'area'         => $unit->effective_area,
            'price'        => $unit->effective_price,
            'bedrooms'     => $unit->bedrooms,
            'bathrooms'    => $unit->bathrooms,
            'balcony'      => $unit->balcony,
            'facing'       => $unit->facing,
            'floor'        => $unit->floor?->label ?? $unit->floor?->floor_name,
        ];
    }

    private static function coverImage(?Property $property): ?string
    {
        $imageId = $property?->property_images[0] ?? null;

        return $imageId ? file_path($imageId) : null;
    }

    /** @return array<int, string> */
    private static function propertyImages(?Property $property): array
    {
        $imageIds = $property?->property_images ?? [];

        if (empty($imageIds)) {
            return [];
        }

        return array_map(fn ($imageId) => file_path($imageId), $imageIds);
    }
}
