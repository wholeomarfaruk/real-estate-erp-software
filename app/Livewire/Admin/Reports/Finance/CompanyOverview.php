<?php

namespace App\Livewire\Admin\Reports\Finance;

use App\Services\Reports\Finance\CompanyOverviewService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class CompanyOverview extends Component
{
    public ?int $projectId = null;

    public ?int $customerId = null;

    public ?int $propertyId = null;

    public string $unitType = '';

    public string $purpose = 'sale';

    public string $fromDate = '';

    public string $toDate = '';

    public string $preset = 'year';

    public string $notes = '';

    public function mount(): void
    {
        $this->authorizePermission('reports.finance.company-overview.view');

        // Default to the whole current year (sales span multiple months).
        $this->fromDate = $this->fromDate ?: Carbon::now()->startOfYear()->toDateString();
        $this->toDate   = $this->toDate ?: Carbon::now()->endOfYear()->toDateString();
    }

    public function updated(string $name): void
    {
        if (in_array($name, ['fromDate', 'toDate'], true)) {
            $this->preset = 'custom';
        }

        if ($name === 'projectId' && $this->propertyId) {
            $this->propertyId = null;
        }
    }

    public function applyPreset(string $preset): void
    {
        $now = now();
        $this->preset = in_array($preset, ['month', 'year', 'all', 'custom'], true) ? $preset : 'year';

        if ($this->preset === 'month') {
            $this->fromDate = $now->copy()->startOfMonth()->toDateString();
            $this->toDate   = $now->copy()->endOfMonth()->toDateString();
        } elseif ($this->preset === 'year') {
            $this->fromDate = $now->copy()->startOfYear()->toDateString();
            $this->toDate   = $now->copy()->endOfYear()->toDateString();
        } elseif ($this->preset === 'all') {
            $this->fromDate = '';
            $this->toDate   = '';
        }
    }

    public function resetFilters(): void
    {
        $this->projectId  = null;
        $this->customerId = null;
        $this->propertyId = null;
        $this->unitType   = '';
        $this->purpose    = 'sale';
        $this->applyPreset('year');
    }

    public function render(CompanyOverviewService $service): View
    {
        $this->authorizePermission('reports.finance.company-overview.view');

        $report = $service->build($this->filterPayload());

        return view('livewire.admin.reports.finance.company-overview', [
            'report'             => $report,
            'projects'           => $service->getProjects(),
            'customers'          => $service->getCustomers(),
            'properties'         => $service->getProperties(),
            'unitTypes'          => $service->getUnitTypes(),
            'pdfUrl'             => route('admin.reports.finance.company-overview.pdf', $this->exportQuery()),
            'excelUrl'           => route('admin.reports.finance.company-overview.excel', $this->exportQuery()),
            'printUrl'           => route('admin.reports.finance.company-overview.print', $this->exportQuery()),
            'printStandaloneUrl' => route('admin.reports.finance.company-overview.print-standalone', $this->exportQuery()),
        ])->layout('layouts.admin.admin');
    }

    protected function filterPayload(): array
    {
        return [
            'project_id'  => $this->projectId,
            'customer_id' => $this->customerId,
            'property_id' => $this->propertyId,
            'unit_type'   => $this->unitType,
            'purpose'     => $this->purpose,
            'from_date'   => $this->fromDate,
            'to_date'     => $this->toDate,
            'notes'       => $this->notes,
        ];
    }

    protected function exportQuery(): array
    {
        return array_filter(
            $this->filterPayload(),
            static fn (mixed $value): bool => $value !== null && $value !== '',
        );
    }

    protected function authorizePermission(string $permission): void
    {
        abort_unless(auth()->user()?->can($permission), 403, 'Unauthorized action.');
    }
}
