<?php

namespace App\Services\Client;

use App\Models\PaymentSchedule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ClientPaymentHistoryPayloadBuilder
{
    /**
     * "Payment History" payload — every schedule the customer has paid
     * against (partial or fully paid), newest first, grouped by month,
     * each with its own transaction log and a property-filter chip list.
     *
     * @param Collection<int, PaymentSchedule> $schedules
     */
    public static function build(Collection $schedules): array
    {
        $items = $schedules
            ->map(fn (PaymentSchedule $schedule) => self::item($schedule))
            ->sortByDesc(fn (array $item) => self::effectiveDate($item))
            ->values();

        return [
            'summary'    => self::summary($items),
            'properties' => self::propertyFilters($items),
            'groups'     => self::groupByMonth($items),
        ];
    }

    private static function item(PaymentSchedule $schedule): array
    {
        $sale = $schedule->propertySale;
        $property = $sale?->property;
        $transactions = ClientPropertySalePayloadBuilder::transactions($schedule);
        $latest = $transactions[0] ?? null;

        return [
            'id'               => $schedule->id,
            'sale_id'          => $sale?->id,
            'sale_number'      => $sale?->sale_number,
            'payment_category' => $schedule->payment_category,
            'label'            => $schedule->label(),
            'sequence_no'      => $schedule->sequence_no,
            'amount'           => (float) $schedule->amount,
            'paid_amount'      => (float) $schedule->paid_amount,
            'due_amount'       => (float) $schedule->due_amount,
            'status'           => $schedule->status,
            'display_status'   => $schedule->displayStatus(),
            'due_date'         => optional($schedule->due_date)->toDateString(),
            'paid_date'        => $latest['datetime'] ?? null,
            'method'           => $latest['method'] ?? null,
            'property'         => $property ? [
                'id'   => $property->id,
                'name' => $property->name,
                'code' => $property->code,
            ] : null,
            'transactions'     => $transactions,
        ];
    }

    private static function summary(Collection $items): array
    {
        $since = $items
            ->map(fn (array $item) => self::effectiveDate($item))
            ->filter()
            ->min();

        return [
            'total_paid'        => (float) $items->sum('paid_amount'),
            'transaction_count' => $items->sum(fn (array $item) => count($item['transactions'])),
            'since'             => $since?->toDateString(),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private static function propertyFilters(Collection $items): array
    {
        return $items->pluck('property')
            ->filter()
            ->unique('id')
            ->values()
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    private static function groupByMonth(Collection $items): array
    {
        return $items
            ->groupBy(fn (array $item) => self::effectiveDate($item)?->format('Y-m') ?? 'unknown')
            ->map(fn (Collection $groupItems, string $monthKey) => [
                'month'       => $monthKey,
                'month_label' => $monthKey === 'unknown' ? 'Unknown' : Carbon::parse($monthKey . '-01')->format('F Y'),
                'items'       => $groupItems->values()->all(),
            ])
            ->values()
            ->all();
    }

    private static function effectiveDate(array $item): ?Carbon
    {
        $date = $item['paid_date'] ?? $item['due_date'] ?? null;

        return $date ? Carbon::parse($date) : null;
    }
}
