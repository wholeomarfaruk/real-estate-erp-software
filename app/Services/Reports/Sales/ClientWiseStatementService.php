<?php

namespace App\Services\Reports\Sales;

use App\Models\Customer;
use App\Models\PropertySale;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ClientWiseStatementService
{
    public function build(array $filters): array
    {
        $customerId = $filters['customer_id'] ?? null;

        $query = PropertySale::with(['customer', 'propertyUnit.property.project', 'propertyUnit.floor', 'paymentSchedules'])
            ->where('customer_id', $customerId)
            ->where('payment_status', '!=', 'cancelled')
            ->where('status', '!=', 'cancelled');

        $transactionType = $filters['transaction_type'] ?? 'all';
        if ($transactionType !== 'all' && $transactionType !== '') {
            $query->where('sale_type', $transactionType);
        }

        $fromDate = $filters['from_date'] ?? null;
        $toDate = $filters['to_date'] ?? null;
        if ($fromDate && $toDate) {
            $query->whereBetween('sale_date', [
                $fromDate,
                $toDate,
            ]);
        }

        $sales = $query->orderBy('sale_date', 'desc')->get();

        $customer = $sales->first()?->customer ?? Customer::find($customerId);

        $rows = $sales->map(function (PropertySale $sale) {
            $totalPaid = $sale->totalPaid();
            $totalDue = $sale->totalDue();

            $scheduledCount = $sale->paymentSchedules->count();
            $overdueCount = $sale->paymentSchedules->filter(fn ($schedule) => $schedule->isOverdue())->count();

            $propertyUnit = $sale->propertyUnit;
            $propertyName = $propertyUnit?->property?->name ?? '-';
            $unitCode = $propertyUnit?->code ?? $propertyUnit?->unit_number ?? '-';
            $unitType = $propertyUnit?->type ?? $propertyUnit?->unit_type ?? '-';
            $floorCode = $propertyUnit?->floor?->code ?? $propertyUnit?->floor?->label ?? '-';

            $unitDisplay = collect([
                $unitCode,
                $floorCode,
                $propertyName,
            ])->filter(fn ($v) => $v !== '-')->implode(', ') ?: '-';

            return [
                'sale_id' => $sale->id,
                'sale_number' => $sale->sale_number,
                'purpose' => ucfirst($sale->sale_type),
                'unit_type' => ucfirst($unitType),
                'property_unit' => $unitDisplay,
                'sale_date' => $sale->sale_date->format('d-M-Y'),
                'amount' => $sale->net_amount,
                'total_paid' => $totalPaid,
                'total_due' => $totalDue,
                'scheduled_count' => $scheduledCount,
                'overdue_count' => $overdueCount,
                // Use the sale's actual payment_status (source of truth, matches the
                // property-sale detail page) rather than re-deriving from amounts.
                'status' => ucfirst($sale->payment_status),
                'actions' => [
                    'schedule_url' => route('admin.properties.sales.schedule', $sale->id),
                    'invoice_url' => route('admin.properties.sales.invoice', $sale->id),
                ],
            ];
        })->toArray();

        $summary = [
            'customer_name' => $customer?->name ?? 'Unknown',
            'total_transactions' => \count($rows),
            'total_sale_amount' => collect($rows)
                ->filter(fn ($row) => strtolower($row['purpose']) === 'sale')
                ->sum('amount'),
            'total_rent_amount' => collect($rows)
                ->filter(fn ($row) => strtolower($row['purpose']) === 'rent')
                ->sum('amount'),
            'total_paid' => collect($rows)->sum('total_paid'),
            'total_outstanding' => collect($rows)->sum('total_due'),
            'total_scheduled' => collect($rows)->sum('scheduled_count'),
            'total_overdue' => collect($rows)->sum('overdue_count'),
        ];

        $customerInfo = $customer ? [
            'id' => $customer->id,
            'name' => $customer->name,
            'code' => $customer->customer_id,
            'type' => $customer->type,
            'company_name' => $customer->company_name,
            'phone' => $customer->phone,
            'email' => $customer->email,
            'address' => collect([$customer->address, $customer->district, $customer->division])
                ->filter()
                ->implode(', ') ?: null,
            'status' => $customer->status,
            'kyc_status' => $customer->kyc_status,
            'initials' => collect(explode(' ', trim((string) $customer->name)))
                ->filter()
                ->take(2)
                ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
                ->implode('') ?: '?',
        ] : null;

        $meta = [
            'company_name' => config('app.name'),
            'report_title' => 'Client Wise Statement',
            'report_slug' => 'client-wise-statement',
            'generated_at' => now()->format('d-M-Y H:i A'),
            'generated_by' => auth()->user()?->name ?? 'System',
            'from_date' => $filters['from_date'] ?? '-',
            'to_date' => $filters['to_date'] ?? '-',
            'file_name' => 'client-wise-statement-' . ($customer?->id ?? 'unknown') . '-' . now()->format('Y-m-d-His'),
            'notes' => $filters['notes'] ?? '',
        ];

        return [
            'title' => 'Client Statement — ' . ($customer?->name ?? 'Customer'),
            'slug' => 'client-wise-statement',
            'columns' => [
                ['key' => 'sale_number', 'label' => 'Sale #', 'align' => 'left'],
                ['key' => 'purpose', 'label' => 'Purpose', 'align' => 'center'],
                ['key' => 'unit_type', 'label' => 'Type', 'align' => 'center'],
                ['key' => 'property_unit', 'label' => 'Unit/Section/Property', 'align' => 'left'],
                ['key' => 'sale_date', 'label' => 'Date', 'align' => 'center'],
                ['key' => 'amount', 'label' => 'Amount', 'align' => 'right'],
                ['key' => 'total_paid', 'label' => 'Paid', 'align' => 'right'],
                ['key' => 'total_due', 'label' => 'Outstanding', 'align' => 'right'],
                ['key' => 'scheduled_count', 'label' => 'Scheduled', 'align' => 'center'],
                ['key' => 'overdue_count', 'label' => 'Overdue', 'align' => 'center'],
                ['key' => 'status', 'label' => 'Status', 'align' => 'center'],
                ['key' => 'actions', 'label' => 'Actions', 'align' => 'center'],
            ],
            'rows' => $rows,
            'summary' => $summary,
            'customer' => $customerInfo,
            'meta' => $meta,
        ];
    }

    public function getTransactionTypes(): Collection
    {
        return collect([
            ['id' => 'all', 'name' => 'All Types'],
            ['id' => 'sale', 'name' => 'Sale'],
            ['id' => 'rent', 'name' => 'Rent'],
        ]);
    }
}
