<?php

namespace App\Livewire\Admin\Accounts\Reports;

use App\Livewire\Admin\Accounts\Concerns\InteractsWithAccountsAccess;
use App\Services\Accounts\AccountReportService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

abstract class BaseAccountReport extends Component
{
    use InteractsWithAccountsAccess;

    public ?string $from_date = null;

    public ?string $to_date = null;

    public ?int $account_id = null;

    public ?int $project_id = null;

    public ?int $supplier_id = null;

    public string $customer_name = '';

    protected string $permission = 'accounts.report.view';

    protected string $reportKey = '';

    public function mount(AccountReportService $reportService): void
    {
        $this->authorizePermission($this->permission);

        $this->from_date = $this->from_date ?: now()->startOfMonth()->toDateString();
        $this->to_date = $this->to_date ?: now()->toDateString();

        $definition = $reportService->definition($this->reportKey());

        if (! ($definition['filters']['project'] ?? false)) {
            $this->project_id = null;
        }

        if (! ($definition['filters']['supplier'] ?? false)) {
            $this->supplier_id = null;
        }

        if (! ($definition['filters']['customer_name'] ?? false)) {
            $this->customer_name = '';
        }
    }

    public function applyFilters(): void
    {
        //
    }

    public function resetFilters(): void
    {
        $definition = app(AccountReportService::class)->definition($this->reportKey());

        $this->from_date = now()->startOfMonth()->toDateString();
        $this->to_date = now()->toDateString();
        $this->account_id = null;
        $this->project_id = ($definition['filters']['project'] ?? false) ? null : null;
        $this->supplier_id = ($definition['filters']['supplier'] ?? false) ? null : null;
        $this->customer_name = ($definition['filters']['customer_name'] ?? false) ? '' : '';
    }

    public function render(AccountReportService $reportService): View
    {
        $this->authorizePermission($this->permission);

        $definition = $reportService->definition($this->reportKey());
        $report = $reportService->build($this->reportKey(), $this->filters());

        return view('livewire.admin.accounts.reports.base-account-report', [
            'definition' => $definition,
            'report' => $report,
            'accounts' => ($definition['filters']['account'] ?? false) ? $reportService->getAccounts() : collect(),
            'projects' => ($definition['filters']['project'] ?? false) ? $reportService->getProjects() : collect(),
            'suppliers' => ($definition['filters']['supplier'] ?? false) ? $reportService->getSuppliers() : collect(),
            'customers' => ($definition['filters']['customer_name'] ?? false) ? $reportService->getCustomerNames() : collect(),
            'excelUrl' => route('admin.accounts.reports.export.excel', array_merge(['report' => $this->reportKey()], $this->exportQuery())),
            'pdfUrl' => route('admin.accounts.reports.export.pdf', array_merge(['report' => $this->reportKey()], $this->exportQuery())),
            'supportsPdfExport' => $reportService->supportsPdfExport(),
        ])->layout('layouts.admin.admin');
    }

    /**
     * @return array<string, mixed>
     */
    protected function filters(): array
    {
        return [
            'from_date' => $this->from_date,
            'to_date' => $this->to_date,
            'account_id' => $this->account_id,
            'project_id' => $this->project_id,
            'supplier_id' => $this->supplier_id,
            'customer_name' => $this->customer_name,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function exportQuery(): array
    {
        return array_filter($this->filters(), static fn (mixed $value): bool => $value !== null && $value !== '');
    }

    protected function reportKey(): string
    {
        return $this->reportKey;
    }
}
