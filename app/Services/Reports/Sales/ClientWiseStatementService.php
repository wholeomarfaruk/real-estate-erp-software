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

        $query = PropertySale::with(['customer', 'propertyUnit.property.project', 'paymentSchedules'])
            ->where('customer_id', $customerId)
            ->where('payment_status', '!=', 'cancelled');

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

            return [
                'sale_id' => $sale->id,
                'sale_number' => 'ORD-' . str_pad($sale->id, 6, '0', STR_PAD_LEFT),
                'type' => ucfirst($sale->sale_type),
                'property_unit' => ($sale->propertyUnit?->name ?? '-') . ' / ' . ($sale->propertyUnit?->property?->name ?? '-'),
                'sale_date' => $sale->sale_date->format('d-M-Y'),
                'amount' => $sale->net_amount,
                'total_paid' => $totalPaid,
                'total_due' => $totalDue,
                'status' => $totalDue > 0 ? ($totalDue == $sale->net_amount ? 'Pending' : 'Partial') : 'Completed',
            ];
        })->toArray();

        $summary = [
            'customer_name' => $customer?->name ?? 'Unknown',
            'total_transactions' => \count($rows),
            'total_sale_amount' => collect($rows)
                ->filter(fn ($row) => $row['type'] === 'Sale')
                ->sum('amount'),
            'total_rent_amount' => collect($rows)
                ->filter(fn ($row) => $row['type'] === 'Rent')
                ->sum('amount'),
            'total_paid' => collect($rows)->sum('total_paid'),
            'total_outstanding' => collect($rows)->sum('total_due'),
        ];

        $meta = [
            'company_name' => config('app.name'),
            'report_title' => 'Client Wise Statement',
            'report_slug' => 'client-wise-statement',
            'generated_at' => now()->format('d-M-Y H:i A'),
            'from_date' => $filters['from_date'] ?? '-',
            'to_date' => $filters['to_date'] ?? '-',
            'file_name' => 'client-wise-statement-' . ($customer?->id ?? 'unknown') . '-' . now()->format('Y-m-d-His'),
        ];

        return [
            'title' => 'Client Wise Statement — ' . ($customer?->name ?? 'Customer'),
            'slug' => 'client-wise-statement',
            'columns' => [
                ['key' => 'sale_number', 'label' => 'Sale #', 'align' => 'left'],
                ['key' => 'type', 'label' => 'Type', 'align' => 'center'],
                ['key' => 'property_unit', 'label' => 'Property / Unit', 'align' => 'left'],
                ['key' => 'sale_date', 'label' => 'Date', 'align' => 'center'],
                ['key' => 'amount', 'label' => 'Amount', 'align' => 'right'],
                ['key' => 'total_paid', 'label' => 'Paid', 'align' => 'right'],
                ['key' => 'total_due', 'label' => 'Outstanding', 'align' => 'right'],
                ['key' => 'status', 'label' => 'Status', 'align' => 'center'],
            ],
            'rows' => $rows,
            'summary' => $summary,
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
