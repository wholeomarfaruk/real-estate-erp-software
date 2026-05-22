<?php

namespace App\Livewire\Admin\Accounts\Reports;

use App\Services\Accounts\AccountReportService;
use Illuminate\Contracts\View\View;

class BalanceSheetReport extends BaseAccountReport
{
    protected string $reportKey = 'balance-sheet';

    public function render(AccountReportService $reportService): View
    {
        $this->authorizePermission($this->permission);

        return view('livewire.admin.accounts.reports.balance-sheet-report', [
            'data'              => $reportService->buildBalanceSheetData($this->filters()),
            'supportsPdfExport' => $reportService->supportsPdfExport(),
            'excelUrl'          => route('admin.accounts.reports.export.excel', array_merge(['report' => 'balance-sheet'], $this->exportQuery())),
            'pdfUrl'            => route('admin.accounts.reports.export.pdf',   array_merge(['report' => 'balance-sheet'], $this->exportQuery())),
        ])->layout('layouts.admin.admin');
    }
}
