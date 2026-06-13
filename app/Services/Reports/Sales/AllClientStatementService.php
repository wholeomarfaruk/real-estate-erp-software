<?php

namespace App\Services\Reports\Sales;

use App\Models\Customer;
use App\Models\PropertySale;
use App\Models\PropertyUnit;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AllClientStatementService
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
            $totalAmount = 0;
            $totalPaid = 0;
            $totalDue = 0;
            $overdueAmount = 0;
            $scheduledCount = 0;
            $overdueCount = 0;

            foreach ($customerSales as $sale) {
                $totalAmount += $sale->totalScheduled();
                $totalPaid += $sale->totalPaid();
                $totalDue += $sale->totalDue();

                // Per-installment metrics across all of this client's sales.
                foreach ($sale->paymentSchedules as $schedule) {
                    $scheduledCount++;

                    if ($schedule->isOverdue()) {
                        $overdueCount++;
                        $overdueAmount += (float) $schedule->due_amount;
                    }
                }
            }

            return [
                'customer_id' => $customer->id,
                'client_display_id' => $customer->customer_id,
                'client_name' => $customer->name,
                'total_amount' => $totalAmount,
                'total_paid' => $totalPaid,
                'total_due' => $totalDue,
                'overdue_amount' => $overdueAmount,
                'scheduled_count' => $scheduledCount,
                'overdue_count' => $overdueCount,
            ];
        })
            // All clients that still carry an outstanding balance.
            ->filter(fn ($row) => $row['total_due'] > 0)
            ->values()
            ->toArray();

        $rows = $clientData;

        // Summary
        $summary = [
            'total_clients' => \count($rows),
            'total_amount' => collect($rows)->sum('total_amount'),
            'total_paid' => collect($rows)->sum('total_paid'),
            'total_outstanding' => collect($rows)->sum('total_due'),
            'total_overdue_amount' => collect($rows)->sum('overdue_amount'),
            'total_scheduled_count' => collect($rows)->sum('scheduled_count'),
            'total_overdue_count' => collect($rows)->sum('overdue_count'),
        ];

        // Meta
        $meta = [
            'company_name' => config('app.name'),
            'report_title' => 'All Client Statement',
            'report_slug' => 'all-client-statement',
            'generated_at' => now()->format('d-M-Y H:i A'),
            'generated_by' => auth()->user()?->name ?? 'System',
            'from_date' => $filters['from_date'] ?? '-',
            'to_date' => $filters['to_date'] ?? '-',
            'file_name' => 'all-client-statement-' . now()->format('Y-m-d-His'),
            'notes' => $filters['notes'] ?? '',
        ];

        return [
            'title' => 'All Client Statement',
            'slug' => 'all-client-statement',
            'columns' => [
                ['key' => 'client_name', 'label' => 'Client Name', 'align' => 'left'],
                ['key' => 'total_amount', 'label' => 'Total Amount', 'align' => 'right'],
                ['key' => 'total_paid', 'label' => 'Paid Amount', 'align' => 'right'],
                ['key' => 'total_due', 'label' => 'Outstanding', 'align' => 'right'],
                ['key' => 'overdue_amount', 'label' => 'Overdue Amount', 'align' => 'right'],
                ['key' => 'scheduled_count', 'label' => 'Scheduled', 'align' => 'center'],
                ['key' => 'overdue_count', 'label' => 'Overdue', 'align' => 'center'],
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
