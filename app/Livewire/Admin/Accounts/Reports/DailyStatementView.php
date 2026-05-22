<?php

namespace App\Livewire\Admin\Accounts\Reports;

use App\Livewire\Admin\Accounts\Concerns\InteractsWithAccountsAccess;
use App\Services\Accounts\DailyStatementReportService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class DailyStatementView extends Component
{
    use InteractsWithAccountsAccess;

    public string $reportDate = '';

    public ?int $bankAccountId = null;

    public bool $supportsPdfExport = false;

    public function mount(DailyStatementReportService $dailyStatementReportService): void
    {
        $this->authorizePermission('accounts.reports.statement.view');

        $this->reportDate = $this->reportDate ?: now()->toDateString();
        $this->supportsPdfExport = $dailyStatementReportService->supportsPdfExport();
    }

    public function resetFilters(): void
    {
        $this->reportDate = now()->toDateString();
        $this->bankAccountId = null;
    }

    public function render(DailyStatementReportService $dailyStatementReportService): View
    {
        $this->authorizePermission('accounts.reports.statement.view');

        return view('pdf.accounts.daily_statement_view', [
            'bankAccounts' => $dailyStatementReportService->getBankAccounts(),
            'previewUrl' => route('admin.accounts.reports.daily-statement.preview', $this->query()),
            'embedUrl' => route('admin.accounts.reports.daily-statement.preview', array_merge($this->query(), ['embedded' => 1])),
            'downloadUrl' => route('admin.accounts.reports.daily-statement.pdf', $this->query()),
            'supportsPdfExport' => $this->supportsPdfExport,
        ])->layout('layouts.admin.admin');
    }

    /**
     * @return array<string, mixed>
     */
    protected function query(): array
    {
        return array_filter([
            'report_date' => $this->reportDate,
            'bank_account_id' => $this->bankAccountId,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');
    }
}
