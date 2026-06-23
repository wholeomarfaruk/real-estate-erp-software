<?php

namespace App\Services\Reports\Finance;

use App\Models\Customer;
use App\Models\Project;
use App\Models\PropertySale;
use App\Models\PropertyUnit;
use App\Models\UnitType;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Company Overview Report.
 *
 * One wide row per property sale: client, flat, financials, installment plan,
 * recovery and outstanding. Built into the standard report payload shape
 * (title / slug / columns / rows / summary / meta) consumed by the on-screen
 * table and the export templates.
 */
class CompanyOverviewService
{
    public function build(array $filters): array
    {
        $query = PropertySale::with([
                'customer',
                'propertyUnit',
                'saleUnits.propertyUnit',
                'paymentSchedules',
            ])
            ->where('status', '!=', 'cancelled')
            ->where('payment_status', '!=', 'cancelled');

        // Purpose = sale / rent (defaults to sale; 'all' shows both).
        $purpose = $filters['purpose'] ?? 'sale';
        if ($purpose !== 'all' && $purpose !== '') {
            $query->where('sale_type', $purpose);
        }

        // Unit type = flat / shop / parking — sales that include such a unit.
        $unitType = $filters['unit_type'] ?? null;
        if ($unitType) {
            $query->whereHas('saleUnits.propertyUnit', fn ($q) => $q->where('type', $unitType));
        }

        $customerId = $filters['customer_id'] ?? null;
        if ($customerId) {
            $query->where('customer_id', $customerId);
        }

        $propertyId = $filters['property_id'] ?? null;
        if ($propertyId) {
            $query->where('property_unit_id', $propertyId);
        }

        $projectId = $filters['project_id'] ?? null;
        if ($projectId) {
            $query->whereHas('propertyUnit.property', fn ($q) => $q->where('project_id', $projectId));
        }

        $fromDate = $filters['from_date'] ?? null;
        $toDate   = $filters['to_date'] ?? null;
        if ($fromDate && $toDate) {
            $query->whereBetween('sale_date', [$fromDate, $toDate]);
        }

        $sales = $query->orderBy('sale_date')->orderBy('id')->get();

        $rows = $sales->values()->map(function (PropertySale $sale, int $i): array {
            return $this->mapRow($sale, $i + 1);
        })->all();

        $summary = [
            'total_clients'       => count($rows),
            'total_flat_value'    => collect($rows)->sum('total_flat_value'),
            'total_recovery'      => collect($rows)->sum('total_recovery'),
            'total_outstanding'   => collect($rows)->sum('present_outstanding'),
        ];

        $meta = [
            'company_name' => config('app.name'),
            'report_title' => 'Company Overview Report',
            'report_slug'  => 'company-overview',
            'generated_at' => now()->format('d-M-Y H:i A'),
            'generated_by' => auth()->user()?->name ?? 'System',
            'from_date'    => $filters['from_date'] ?? '-',
            'to_date'      => $filters['to_date'] ?? '-',
            'file_name'    => 'company-overview-' . now()->format('Y-m-d-His'),
            'notes'        => $filters['notes'] ?? '',
        ];

        return [
            'title'   => 'Company Overview Report',
            'slug'    => 'company-overview',
            'columns' => $this->columns(),
            'rows'    => $rows,
            'summary' => $summary,
            'meta'    => $meta,
        ];
    }

    /**
     * Column definitions — key/label/align drive both the screen table and exports.
     */
    public function columns(): array
    {
        return [
            ['key' => 'sl_no',               'label' => 'Sl No',               'align' => 'center', 'type' => 'text'],
            ['key' => 'client_name',         'label' => 'Client Name',         'align' => 'left',   'type' => 'text'],
            ['key' => 'commitment_date',     'label' => 'Commitment Date',     'align' => 'center', 'type' => 'text'],
            ['key' => 'flat_code',           'label' => 'Flat Code',           'align' => 'center', 'type' => 'text'],
            ['key' => 'area_sqft',           'label' => 'Area (sqft)',         'align' => 'right',  'type' => 'number'],
            ['key' => 'rate_sqft',           'label' => 'Rate/sqft',           'align' => 'right',  'type' => 'number'],
            ['key' => 'flat_value',          'label' => 'Flat Value',          'align' => 'right',  'type' => 'money'],
            ['key' => 'utility_charge',      'label' => 'Utility Charge',      'align' => 'right',  'type' => 'money'],
            ['key' => 'parking',             'label' => 'Parking',             'align' => 'right',  'type' => 'money'],
            ['key' => 'total_flat_value',    'label' => 'Total Flat Value',    'align' => 'right',  'type' => 'money'],
            ['key' => 'down_payment',        'label' => 'Down Payment',        'align' => 'right',  'type' => 'money'],
            ['key' => 'no_of_inst',          'label' => 'No. of Inst',         'align' => 'center', 'type' => 'number'],
            ['key' => 'installment_size',    'label' => 'Installment Size',    'align' => 'right',  'type' => 'money'],
            ['key' => 'due_date',            'label' => 'Due Date',            'align' => 'center', 'type' => 'text'],
            ['key' => 'total_recovery',      'label' => 'Total Recovery',      'align' => 'right',  'type' => 'money'],
            ['key' => 'present_outstanding', 'label' => 'Present Outstanding',  'align' => 'right',  'type' => 'money'],
            ['key' => 'payment_status',      'label' => 'Payment Status',      'align' => 'center', 'type' => 'text'],
            ['key' => 'status',              'label' => 'Status',              'align' => 'center', 'type' => 'text'],
            ['key' => 'reference',           'label' => 'Reference',           'align' => 'left',   'type' => 'text'],
        ];
    }

    protected function mapRow(PropertySale $sale, int $slNo): array
    {
        // Split sale units into the main flat vs parking (by unit type).
        $saleUnits   = $sale->saleUnits;
        $parkingUnit = $saleUnits->first(fn ($su) => $su->propertyUnit?->type === 'parking');
        $flatUnit    = $saleUnits->first(fn ($su) => $su->propertyUnit?->type !== 'parking')
            ?? $saleUnits->first();

        // Unit attributes — prefer the flat sale-unit's unit, else the sale's primary unit.
        $unit = $flatUnit?->propertyUnit ?? $sale->propertyUnit;

        // Installment plan.
        $installments    = $sale->paymentSchedules->where('payment_category', 'installment');
        $installmentSize = (float) ($installments->sortBy('sequence_no')->first()?->amount ?? 0);

        // Down payment — schedule entry if present, else the sale header amount.
        $downPayment = (float) ($sale->paymentSchedules
            ->firstWhere('payment_category', 'down_payment')?->amount
            ?? $sale->down_payment_amount
            ?? 0);

        // Utility charge — sum across sale units (utility + service).
        $utilityCharge = (float) $saleUnits->sum(fn ($su) => (float) $su->utility_charge + (float) $su->service_charge);

        $flatValue    = (float) ($flatUnit?->net_amount ?? $sale->net_amount);
        $parkingValue = (float) ($parkingUnit?->net_amount ?? 0);

        $totalRecovery = $sale->totalPaid();
        $outstanding   = $sale->totalDue();

        return [
            'sl_no'               => $slNo,
            'client_name'         => $sale->customer?->name ?? '—',
            'commitment_date'     => optional($sale->contract_date ?? $sale->sale_date)?->format('d/m/Y') ?? '—',
            'flat_code'           => $unit?->code ?? '—',
            'area_sqft'           => (float) ($unit?->area ?? $unit?->size_sqft ?? 0),
            'rate_sqft'           => (float) ($unit?->rate_per_sqft ?? 0),
            'flat_value'          => $flatValue,
            'utility_charge'      => $utilityCharge,
            'parking'             => $parkingValue,
            'total_flat_value'    => (float) $sale->net_amount,
            'down_payment'        => $downPayment,
            'no_of_inst'          => $installments->count(),
            'installment_size'    => $installmentSize,
            'due_date'            => $this->nextDueDate($sale),
            'total_recovery'      => $totalRecovery,
            'present_outstanding' => $outstanding,
            'payment_status'      => ucfirst($sale->payment_status ?? '—'),
            'status'              => ucwords(str_replace('_', ' ', $sale->status ?? '—')),
            'reference'           => $sale->sales_representative ?: '—',
        ];
    }

    /** Earliest unpaid (pending/partial/overdue) installment due date. */
    protected function nextDueDate(PropertySale $sale): string
    {
        $next = $sale->paymentSchedules
            ->where('payment_category', 'installment')
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->sortBy('due_date')
            ->first();

        return optional($next?->due_date)->format('d/m/Y') ?? '—';
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
            ->get(['id', 'type', 'property_id', 'code']);
    }

    /** Unit types from the master unit_types table (slug matches property_units.type). */
    public function getUnitTypes(): Collection
    {
        return UnitType::orderBy('name')->get(['slug', 'name']);
    }
}
