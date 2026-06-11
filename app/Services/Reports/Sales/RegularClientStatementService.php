<?php

namespace App\Services\Reports\Sales;

use App\Models\Customer;
use App\Models\PropertySale;
use App\Models\PropertyUnit;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class RegularClientStatementService
{
    public function build(array $filters): array
    {
        $query = PropertySale::with(['customer', 'propertyUnit.property.project', 'paymentSchedules'])
            ->where('payment_status', '!=', 'cancelled');

        // Apply filters with safe defaults
        $saleType = $filters['sale_type'] ?? 'all';
        if ($saleType !== 'all' && $saleType !== '') {
            $query->where('sale_type', $saleType);
        }

        $customerId = $filters['customer_id'] ?? null;
        if ($customerId) {
            $query->where('customer_id', $customerId);
        }

        $propertyId = $filters['property_id'] ?? null;
        if ($propertyId) {
            $query->where('property_unit_id', $propertyId);
        }

        $fromDate = $filters['from_date'] ?? null;
        $toDate = $filters['to_date'] ?? null;
        if ($fromDate && $toDate) {
            $query->whereBetween('sale_date', [
                $fromDate,
                $toDate,
            ]);
        }

        $projectId = $filters['project_id'] ?? null;
        if ($projectId) {
            $query->whereHas('propertyUnit.property', fn ($q) =>
                $q->where('project_id', $projectId)
            );
        }

        $sales = $query->get();

        // Group by customer and aggregate data
        $clientData = $sales->groupBy('customer_id')->map(function ($customerSales) {
            $customer = $customerSales->first()->customer;
            $totalPaid = 0;
            $totalDue = 0;
            $salePropCount = 0;
            $rentPropCount = 0;

            foreach ($customerSales as $sale) {
                $saleTotal = $sale->totalDue();
                if ($saleTotal > 0) {
                    $totalPaid += $sale->totalPaid();
                    $totalDue += $saleTotal;

                    if ($sale->sale_type === 'rent') {
                        $rentPropCount++;
                    } else {
                        $salePropCount++;
                    }
                }
            }

            return [
                'customer_id' => $customer->id,
                'client_name' => $customer->name,
                'sale_property_count' => $salePropCount,
                'rent_property_count' => $rentPropCount,
                'total_paid' => $totalPaid,
                'total_due' => $totalDue,
            ];
        })->filter(fn ($row) => $row['total_due'] > 0)->values()->toArray();

        $rows = $clientData;

        // Summary
        $summary = [
            'total_clients' => \count($rows),
            'total_outstanding' => collect($rows)->sum('total_due'),
            'total_paid' => collect($rows)->sum('total_paid'),
        ];

        // Meta
        $meta = [
            'company_name' => config('app.name'),
            'report_title' => 'Regular Client Statement',
            'report_slug' => 'regular-client-statement',
            'generated_at' => now()->format('d-M-Y H:i A'),
            'from_date' => $filters['from_date'] ?? '-',
            'to_date' => $filters['to_date'] ?? '-',
            'file_name' => 'regular-client-statement-' . now()->format('Y-m-d-His'),
        ];

        return [
            'title' => 'Regular Client Statement — Summary',
            'slug' => 'regular-client-statement',
            'columns' => [
                ['key' => 'client_name', 'label' => 'Client Name', 'align' => 'left'],
                ['key' => 'sale_property_count', 'label' => 'Sale Properties', 'align' => 'center'],
                ['key' => 'rent_property_count', 'label' => 'Rent Properties', 'align' => 'center'],
                ['key' => 'total_paid', 'label' => 'Total Paid', 'align' => 'right'],
                ['key' => 'total_due', 'label' => 'Total Outstanding', 'align' => 'right'],
            ],
            'rows' => $rows,
            'summary' => $summary,
            'meta' => $meta,
        ];
    }

    public function getProjects(): Collection
    {
        return Project::orderBy('name')->get(['id', 'name']);
    }

    public function getCustomers(): Collection
    {
        return Customer::orderBy('name')->get(['id', 'name']);
    }

    public function getProperties(): Collection
    {
        return PropertyUnit::with('property')
            ->orderBy('type')
            ->get(['id', 'type', 'property_id']);
    }
}
