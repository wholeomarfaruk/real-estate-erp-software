<?php

namespace App\Livewire\Admin\Reports\Sales;

use App\Services\Reports\Sales\OverdueClientStatementService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class OverdueClientStatement extends Component
{
    public ?int $projectId = null;

    public ?int $customerId = null;

    public ?int $propertyId = null;

    public string $fromDate = '';

    public string $toDate = '';

    public string $saleType = 'all';

    public string $preset = 'month';

    public string $notes = '';

    public function mount(OverdueClientStatementService $service): void
    {
        $this->authorizePermission('reports.sales.overdue-client-statement.view');

        $today = now()->toDateString();
        $this->fromDate = $this->fromDate ?: Carbon::now()->startOfMonth()->toDateString();
        $this->toDate = $this->toDate ?: $today;

        $this->syncPreset();
    }

    public function updated(string $name): void
    {
        if (in_array($name, ['fromDate', 'toDate'], true)) {
            $this->syncPreset();
        }

        if ($name === 'projectId' && $this->propertyId) {
            $this->propertyId = null;
        }
    }

    public function applyPreset(string $preset): void
    {
        $now = now();

        $this->preset = in_array($preset, ['today', 'month', 'year', 'custom'], true) ? $preset : 'today';

        if ($this->preset === 'month') {
            $this->fromDate = $now->copy()->startOfMonth()->toDateString();
            $this->toDate = $now->copy()->endOfMonth()->toDateString();

            return;
        }

        if ($this->preset === 'year') {
            $this->fromDate = $now->copy()->startOfYear()->toDateString();
            $this->toDate = $now->copy()->endOfYear()->toDateString();

            return;
        }

        if ($this->preset === 'custom') {
            return;
        }

        $this->fromDate = $now->toDateString();
        $this->toDate = $now->toDateString();
    }

    public function resetFilters(): void
    {
        $this->projectId = null;
        $this->customerId = null;
        $this->propertyId = null;
        $this->saleType = 'all';

        $this->applyPreset('month');
    }

    public function render(OverdueClientStatementService $service): View
    {
        $this->authorizePermission('reports.sales.overdue-client-statement.view');

        $report = $service->build($this->filterPayload());

        return view('livewire.admin.reports.sales.overdue-client-statement', [
            'report' => $report,
            'projects' => $service->getProjects(),
            'customers' => $service->getCustomers(),
            'properties' => $service->getProperties(),
            'printUrl' => route('admin.reports.sales.overdue.print', $this->exportQuery()),
            'printStandaloneUrl' => route('admin.reports.sales.overdue.print-standalone', $this->exportQuery()),
            'pdfUrl' => route('admin.reports.sales.overdue.pdf', $this->exportQuery()),
            'excelUrl' => route('admin.reports.sales.overdue.excel', $this->exportQuery()),
        ])->layout('layouts.admin.admin');
    }

    protected function filterPayload(): array
    {
        return [
            'project_id' => $this->projectId,
            'customer_id' => $this->customerId,
            'property_id' => $this->propertyId,
            'from_date' => $this->fromDate,
            'to_date' => $this->toDate,
            'sale_type' => $this->saleType,
            'notes' => $this->notes,
        ];
    }

    protected function exportQuery(): array
    {
        return array_filter($this->filterPayload(), static fn (mixed $value): bool => $value !== null && $value !== '' && $value !== 'all');
    }

    protected function syncPreset(): void
    {
        try {
            $from = Carbon::parse($this->fromDate);
            $to = Carbon::parse($this->toDate);
        } catch (\Throwable) {
            $this->preset = 'custom';

            return;
        }

        if ($from->gt($to)) {
            $this->preset = 'custom';

            return;
        }

        if ($from->isSameDay($to) && $from->isToday()) {
            $this->preset = 'today';

            return;
        }

        if (
            $from->isSameMonth($to)
            && $from->copy()->startOfMonth()->isSameDay($from)
            && $to->copy()->endOfMonth()->isSameDay($to)
            && $from->isSameMonth(now())
        ) {
            $this->preset = 'month';

            return;
        }

        if (
            $from->year === $to->year
            && $from->copy()->startOfYear()->isSameDay($from)
            && $to->copy()->endOfYear()->isSameDay($to)
            && $from->year === (int) now()->format('Y')
        ) {
            $this->preset = 'year';

            return;
        }

        $this->preset = 'custom';
    }

    protected function authorizePermission(string $permission): void
    {
        abort_unless(auth()->user()?->can($permission), 403, 'Unauthorized action.');
    }
}
