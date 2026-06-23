<?php

namespace App\Livewire\Admin\Reports\Projects;

use App\Services\Reports\Projects\ProjectListService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ProjectList extends Component
{
    public string $status = 'all';

    public string $type = 'all';

    public string $notes = '';

    public function mount(): void
    {
        $this->authorizePermission('reports.projects.project-list.view');
    }

    public function resetFilters(): void
    {
        $this->status = 'all';
        $this->type   = 'all';
    }

    public function render(ProjectListService $service): View
    {
        $this->authorizePermission('reports.projects.project-list.view');

        $report = $service->build($this->filterPayload());

        return view('livewire.admin.reports.projects.project-list', [
            'report'             => $report,
            'statuses'           => $service->getStatuses(),
            'pdfUrl'             => route('admin.reports.projects.project-list.pdf', $this->exportQuery()),
            'excelUrl'           => route('admin.reports.projects.project-list.excel', $this->exportQuery()),
            'printUrl'           => route('admin.reports.projects.project-list.print', $this->exportQuery()),
            'printStandaloneUrl' => route('admin.reports.projects.project-list.print-standalone', $this->exportQuery()),
        ])->layout('layouts.admin.admin');
    }

    protected function filterPayload(): array
    {
        return [
            'status' => $this->status,
            'type'   => $this->type,
            'notes'  => $this->notes,
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
