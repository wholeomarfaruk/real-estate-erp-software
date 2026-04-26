<?php

namespace App\Http\Controllers\Admin\Accounts;

use App\Http\Controllers\Controller;
use App\Services\Accounts\AccountReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AccountReportExportController extends Controller
{
    public function excel(string $report, Request $request, AccountReportService $reportService): Response
    {
        $this->authorizePermission('accounts.report.view');
        abort_unless($reportService->hasReport($report), 404, 'Report not found.');

        $payload = $reportService->build($report, $request->all());

        $content = view('admin.accounts.reports.exports.account-report-excel', [
            'report' => $payload,
        ])->render();

        return response($content, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$payload['meta']['file_name'].'.xls"',
        ]);
    }

    public function pdf(string $report, Request $request, AccountReportService $reportService): Response
    {
        $this->authorizePermission('accounts.report.view');
        abort_unless($reportService->hasReport($report), 404, 'Report not found.');
        abort_unless($reportService->supportsPdfExport(), 404, 'PDF export is not available.');

        $payload = $reportService->build($report, $request->all());
        $paper = count($payload['columns']) > 5 ? ['a4', 'landscape'] : ['a4', 'portrait'];

        $pdf = Pdf::loadView('admin.accounts.reports.exports.account-report-pdf', [
            'report' => $payload,
        ])->setPaper($paper[0], $paper[1]);

        return $pdf->download($payload['meta']['file_name'].'.pdf');
    }

    protected function authorizePermission(string $permission): void
    {
        abort_unless(auth()->user()?->can($permission), 403, 'Unauthorized action.');
    }
}
