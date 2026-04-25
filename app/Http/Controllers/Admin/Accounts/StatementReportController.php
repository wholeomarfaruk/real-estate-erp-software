<?php

namespace App\Http\Controllers\Admin\Accounts;

use App\Http\Controllers\Controller;
use App\Services\Accounts\StatementReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StatementReportController extends Controller
{
    public function print(Request $request, StatementReportService $statementReportService): View
    {
        $this->authorizePermission('accounts.reports.statement.print');

        return view('admin.accounts.reports.statement-print', [
            'report' => $statementReportService->build($request->all()),
        ]);
    }

    public function export(Request $request, StatementReportService $statementReportService): Response
    {
        $this->authorizePermission('accounts.reports.statement.export');

        abort_unless($statementReportService->supportsPdfExport(), 404, 'PDF export is not available.');

        $report = $statementReportService->build($request->all());

        $pdf = Pdf::loadView('admin.accounts.reports.statement-pdf', [
            'report' => $report,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('statement-sheet-'.$report['meta']['file_label'].'.pdf');
    }

    protected function authorizePermission(string $permission): void
    {
        abort_unless(auth()->user()?->can($permission), 403, 'Unauthorized action.');
    }
}
