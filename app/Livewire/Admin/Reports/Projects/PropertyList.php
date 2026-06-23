<?php

namespace App\Livewire\Admin\Reports\Projects;

use App\Services\Reports\Projects\PropertyListService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class PropertyList extends Component
{
    public ?int $projectId = null;

    public string $status = 'all';

    public string $type = 'all';

    public string $notes = '';

    public function mount(): void
    {
        $this->authorizePermission('reports.projects.property-list.view');
    }

    public function resetFilters(): void
    {
        $this->projectId = null;
        $this->status    = 'all';
        $this->type      = 'all';
    }

    public function render(PropertyListService $service): View
    {
        $this->authorizePermission('reports.projects.property-list.view');

        $report = $service->build($this->filterPayload());

        return view('livewire.admin.reports.projects.property-list', [
            'report'             => $report,
            'projects'           => $service->getProjects(),
            'statuses'           => $service->getStatuses(),
            'pdfUrl'             => route('admin.reports.projects.property-list.pdf', $this->exportQuery()),
            'excelUrl'           => route('admin.reports.projects.property-list.excel', $this->exportQuery()),
            'printUrl'           => route('admin.reports.projects.property-list.print', $this->exportQuery()),
            'printStandaloneUrl' => route('admin.reports.projects.property-list.print-standalone', $this->exportQuery()),
        ])->layout('layouts.admin.admin');
    }

    protected function filterPayload(): array
    {
        return [
            'project_id' => $this->projectId,
            'status'     => $this->status,
            'type'       => $this->type,
            'notes'      => $this->notes,
        ];
    }

    protected function exportQuery(): array
    {
        return array_filter(
            $this->filterPayload(),
            static fn (mixed $value): bool => $value !== null && $value !== '' && $value !== 'all',
        );
    }

    protected function authorizePermission(string $permission): void
    {
        abort_unless(auth()->user()?->can($permission), 403, 'Unauthorized action.');
    }
}
