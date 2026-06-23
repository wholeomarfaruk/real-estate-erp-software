<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reports\Finance\DailyStatementService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DailyStatementExportController extends Controller
{
    public function __construct(private DailyStatementService $service) {}

    public function pdf(Request $request): Response
    {
        $this->authorizePermission('reports.finance.export');

        $payload = $this->service->build(['date' => $request->query('date', now()->toDateString())]);

        $pdf = Pdf::loadView('pdf.reports.finance.exports.daily-statement-pdf', [
            'report' => $payload,
        ])->setPaper('a4', 'portrait');

        $fileName = 'Daily-Statement-' . str_replace(' ', '-', $payload['meta']['statement_date']);
        return $pdf->download($fileName . '.pdf');
    }

    public function print(Request $request): View
    {
        $this->authorizePermission('reports.finance.export');

        $payload = $this->service->build(['date' => $request->query('date', now()->toDateString())]);

        return view('pdf.reports.finance.exports.daily-statement-print-standalone', [
            'report' => $payload,
        ])->layout('layouts.admin.admin');
    }

    public function printStandalone(Request $request): View
    {
        $this->authorizePermission('reports.finance.export');

        $payload = $this->service->build(['date' => $request->query('date', now()->toDateString())]);

        return view('pdf.reports.finance.exports.daily-statement-print-standalone', [
            'report' => $payload,
        ]);
    }

    public function excel(Request $request): Response
    {
        $this->authorizePermission('reports.finance.export');

        $payload = $this->service->build(['date' => $request->query('date', now()->toDateString())]);

        $content = view('pdf.reports.finance.exports.daily-statement-excel', [
            'report' => $payload,
        ])->render();

        $fileName = 'Daily-Statement-' . str_replace(' ', '-', $payload['meta']['statement_date']);
        return response($content, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '.xls"',
        ]);
    }

    protected function authorizePermission(string $permission): void
    {
        abort_unless(auth()->user()?->can($permission), 403, 'Unauthorized action.');
    }
}
