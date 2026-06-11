<?php

namespace App\Livewire\Admin\Reports;

use App\Livewire\Admin\Reports\Concerns\InteractsWithReportsAccess;
use App\Services\Reports\ConfigBasedRegistry;
use App\Services\Reports\ReportCategoryAssembler;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin.admin')]
class ReportsHub extends Component
{
    use InteractsWithReportsAccess;

    public function mount(): void
    {
        $this->authorizePermission('reports.hub.view');
    }

    public function render(ReportCategoryAssembler $assembler, ConfigBasedRegistry $registry): View
    {
        $this->authorizePermission('reports.hub.view');

        $categories = $assembler->assemble($registry);

        return view('livewire.admin.reports.hub', [
            'categories'   => $categories,
            'totalReports' => array_sum(array_column($categories, 'count')),
        ]);
    }
}
