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

        // Apply filters
        if ($filters['sale_type'] !== 'all') {
            $query->where('sale_type', $filters['sale_type']);
        }

        if ($filters['customer_id']) {
            $query->where('customer_id', $filters['customer_id']);
        }

        if ($filters['property_id']) {
            $query->where('property_unit_id', $filters['property_id']);
        }

        if ($filters['from_date'] && $filters['to_date']) {
            $query->whereBetween('sale_date', [
                $filters['from_date'],
                $filters['to_date'],
            ]);
        }

        if ($filters['project_id']) {
            $query->whereHas('propertyUnit.property', fn ($q) =>
                $q->where('project_id', $filters['project_id'])
            );
        }

        $sales = $query->get();

        // Build rows — filter only those with outstanding balance
        $rows = $sales->filter(function (PropertySale $sale) {
            return $sale->totalDue() > 0;
        })->map(function (PropertySale $sale) {
            $totalPaid = $sale->totalPaid();
            $totalDue = $sale->totalDue();
            $outstanding = $totalDue;

            // Get next installment
            $nextSchedule = $sale->paymentSchedules()
                ->where('status', '!=', 'paid')
                ->orderBy('due_date')
                ->first();

            $nextDueDate = $nextSchedule?->due_date?->format('Y-m-d') ?? '-';
            $dueAmount = $nextSchedule?->amount ?? 0;
            $status = $nextSchedule ? ($nextSchedule->isOverdue() ? 'Overdue' : 'Current') : '-';

            return [
                'client_name' => $sale->customer->name,
                'unit_property' => ($sale->propertyUnit?->name ?? '-') . ' / ' . ($sale->propertyUnit?->property?->name ?? '-'),
                'booking_date' => $sale->sale_date->format('d-M-Y'),
                'contract_value' => $sale->net_amount,
                'total_paid' => $totalPaid,
                'outstanding_balance' => $outstanding,
                'next_due_date' => $nextDueDate,
                'due_amount' => $dueAmount,
                'status' => $status,
                'sale_id' => $sale->id,
            ];
        })->values()->toArray();

        // Summary
        $summary = [
            'total_clients' => \count($rows),
            'total_outstanding' => collect($rows)->sum('outstanding_balance'),
            'total_due_this_month' => collect($rows)
                ->filter(fn ($row) => $row['next_due_date'] !== '-' &&
                    Carbon::parse($row['next_due_date'])->isSameMonth(now()))
                ->sum('due_amount'),
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
            'title' => 'Regular Client Statement — All Pending',
            'slug' => 'regular-client-statement',
            'columns' => [
                ['key' => 'client_name', 'label' => 'Client Name', 'align' => 'left'],
                ['key' => 'unit_property', 'label' => 'Unit / Property', 'align' => 'left'],
                ['key' => 'booking_date', 'label' => 'Booking Date', 'align' => 'center'],
                ['key' => 'contract_value', 'label' => 'Contract Value', 'align' => 'right'],
                ['key' => 'total_paid', 'label' => 'Total Paid', 'align' => 'right'],
                ['key' => 'outstanding_balance', 'label' => 'Outstanding Balance', 'align' => 'right'],
                ['key' => 'next_due_date', 'label' => 'Next Due Date', 'align' => 'center'],
                ['key' => 'due_amount', 'label' => 'Due Amount', 'align' => 'right'],
                ['key' => 'status', 'label' => 'Status', 'align' => 'center'],
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
            ->orderBy('name')
            ->get(['id', 'name', 'property_id']);
    }
}
