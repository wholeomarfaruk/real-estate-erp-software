<?php

namespace App\Livewire\Admin\Reports\Inventory;

use App\Services\Reports\Inventory\StocksReportService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class StocksReport extends Component
{
    public ?int $productId = null;

    public string $notes = '';

    public function mount(): void
    {
        $this->authorizePermission('reports.inventory.stocks-report.view');
    }

    public function resetFilters(): void
    {
        $this->productId = null;
    }

    public function render(StocksReportService $service): View
    {
        $this->authorizePermission('reports.inventory.stocks-report.view');

        $report = $service->build($this->filterPayload());

        return view('livewire.admin.reports.inventory.stocks-report', [
            'report'             => $report,
            'products'           => $service->getProducts(),
            'pdfUrl'             => route('admin.reports.inventory.stocks-report.pdf', $this->exportQuery()),
            'excelUrl'           => route('admin.reports.inventory.stocks-report.excel', $this->exportQuery()),
            'printUrl'           => route('admin.reports.inventory.stocks-report.print', $this->exportQuery()),
            'printStandaloneUrl' => route('admin.reports.inventory.stocks-report.print-standalone', $this->exportQuery()),
        ])->layout('layouts.admin.admin');
    }

    protected function filterPayload(): array
    {
        return [
            'product_id' => $this->productId,
            'notes'      => $this->notes,
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
