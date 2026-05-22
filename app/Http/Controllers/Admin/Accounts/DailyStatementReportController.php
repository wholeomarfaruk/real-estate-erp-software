<?php

namespace App\Http\Controllers\Admin\Accounts;

use App\Http\Controllers\Controller;
use App\Services\Accounts\DailyStatementReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DailyStatementReportController extends Controller
{
    public function preview(Request $request, DailyStatementReportService $dailyStatementReportService): View
    {
        $this->authorizePermission('accounts.reports.statement.view');

        return view('pdf.accounts.daily_statement', array_merge(
            $dailyStatementReportService->build($request->all()),
            [
                'showActions' => ! $request->boolean('embedded'),
                'allowPrint' => auth()->user()?->can('accounts.reports.statement.print') ?? false,
                'backUrl' => route('admin.accounts.reports.daily-statement', array_filter([
                    'report_date' => $request->input('report_date'),
                    'bank_account_id' => $request->input('bank_account_id'),
                ], static fn (mixed $value): bool => $value !== null && $value !== '')),
            ]
        ));
    }

    public function export(Request $request, DailyStatementReportService $dailyStatementReportService): Response
    {
        $this->authorizePermission('accounts.reports.statement.export');

        abort_unless($dailyStatementReportService->supportsPdfExport(), 404, 'PDF export is not available.');

        $report = $dailyStatementReportService->build($request->all());

        $pdf = Pdf::loadView('pdf.accounts.daily_statement', array_merge($report, [
            'showActions' => false,
            'allowPrint' => false,
            'backUrl' => null,
        ]))->setPaper('a4', 'portrait');

        return $pdf->download('daily-statement-'.$report['meta']['report_date'].'.pdf');
    }

    protected function authorizePermission(string $permission): void
    {
        abort_unless(auth()->user()?->can($permission), 403, 'Unauthorized action.');
    }
}
