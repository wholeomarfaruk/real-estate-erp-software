<?php

namespace App\Livewire\Admin\Accounts\Reports;

use App\Livewire\Admin\Accounts\Concerns\InteractsWithAccountsAccess;
use App\Services\Accounts\StatementReportService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class StatementReport extends Component
{
    use InteractsWithAccountsAccess;

    public string $fromDate = '';

    public string $toDate = '';

    public ?int $bankAccountId = null;

    public ?int $projectId = null;

    public ?int $propertyId = null;

    public string $preset = 'today';

    public bool $supportsProjectFilter = false;

    public bool $supportsPropertyFilter = false;

    public bool $supportsPdfExport = false;

    public function mount(StatementReportService $statementReportService): void
    {
        $this->authorizePermission('accounts.reports.statement.view');

        $this->supportsProjectFilter = $statementReportService->supportsProjectFilter();
        $this->supportsPropertyFilter = $statementReportService->supportsPropertyFilter();
        $this->supportsPdfExport = $statementReportService->supportsPdfExport();

        $today = now()->toDateString();

        $this->fromDate = $this->fromDate ?: $today;
        $this->toDate = $this->toDate ?: $today;

        if (! $this->supportsProjectFilter) {
            $this->projectId = null;
        }

        if (! $this->supportsPropertyFilter) {
            $this->propertyId = null;
        }

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

        if (! $this->supportsProjectFilter) {
            $this->projectId = null;
        }

        if (! $this->supportsPropertyFilter) {
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
        $this->bankAccountId = null;
        $this->projectId = null;
        $this->propertyId = null;

        $this->applyPreset('today');
    }

    public function render(StatementReportService $statementReportService): View
    {
        $this->authorizePermission('accounts.reports.statement.view');

        $report = $statementReportService->build($this->filterPayload());
        $properties = $statementReportService->getProperties();

        if ($this->projectId) {
            $properties = $properties->where('project_id', $this->projectId)->values();
        }

        return view('livewire.admin.accounts.reports.statement-report', [
            'report' => $report,
            'bankAccounts' => $statementReportService->getBankAccounts(),
            'projects' => $statementReportService->getProjects(),
            'properties' => $properties,
            'printUrl' => route('admin.accounts.reports.statement.print', $this->exportQuery()),
            'exportUrl' => route('admin.accounts.reports.statement.export', $this->exportQuery()),
            'supportsPdfExport' => $this->supportsPdfExport,
        ])->layout('layouts.admin.admin');
    }

    /**
     * @return array<string, mixed>
     */
    protected function filterPayload(): array
    {
        return [
            'from_date' => $this->fromDate,
            'to_date' => $this->toDate,
            'bank_account_id' => $this->bankAccountId,
            'project_id' => $this->projectId,
            'property_id' => $this->propertyId,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function exportQuery(): array
    {
        return array_filter([
            'from_date' => $this->fromDate,
            'to_date' => $this->toDate,
            'bank_account_id' => $this->bankAccountId,
            'project_id' => $this->supportsProjectFilter ? $this->projectId : null,
            'property_id' => $this->supportsPropertyFilter ? $this->propertyId : null,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');
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
}
