<?php

namespace App\Livewire\Admin\Reports;

use App\Livewire\Admin\Reports\Concerns\InteractsWithReportsAccess;
use App\Services\Reports\ConfigBasedRegistry;
use App\Services\Reports\ReportCategoryAssembler;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin.admin')]
class ReportsCategory extends Component
{
    use InteractsWithReportsAccess;

    public string $key = '';

    public function mount(string $key, ReportCategoryAssembler $assembler, ConfigBasedRegistry $registry): void
    {
        $this->authorizePermission('reports.category.view');
        $this->key = $key;

        $all = $assembler->assemble($registry);
        abort_unless(collect($all)->contains('key', $key), 404);
    }

    public function render(ReportCategoryAssembler $assembler, ConfigBasedRegistry $registry): View
    {
        $this->authorizePermission('reports.category.view');

        $all = $assembler->assemble($registry);
        $category = collect($all)->firstWhere('key', $this->key);

        return view('livewire.admin.reports.category', [
            'category'      => $category,
            'allCategories' => $all,
        ]);
    }
}
