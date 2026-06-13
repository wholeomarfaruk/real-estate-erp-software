<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reports\Sales\OverdueClientStatementService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Dedicated export controller for the Overdue Client Statement report.
 *
 * Binds directly to OverdueClientStatementService and reuses the shared,
 * column-driven sales report templates (report-pdf / report-excel /
 * report-print-standalone) — the overdue payload uses the same row shape
 * as the regular statement, just filtered to clients with overdue installments.
 */
class OverdueClientStatementExportController extends Controller
{
    public function __construct(private OverdueClientStatementService $service) {}

    public function pdf(Request $request): Response
    {
        $this->authorizePermission('reports.sales.export');

        $payload = $this->service->build($request->all());

        $paper = count($payload['columns']) > 6 ? ['a4', 'landscape'] : ['a4', 'portrait'];

        $pdf = Pdf::loadView('pdf.reports.sales.exports.report-pdf', [
            'report' => $payload,
        ])->setPaper($paper[0], $paper[1]);

        return $pdf->download($payload['meta']['file_name'] . '.pdf');
    }

    public function print(Request $request): View
    {
        $this->authorizePermission('reports.sales.export');

        $payload = $this->service->build($request->all());

        return view('pdf.reports.sales.exports.report-print-standalone', [
            'report' => $payload,
        ])->layout('layouts.admin.admin');
    }

    public function printStandalone(Request $request): View
    {
        $this->authorizePermission('reports.sales.export');

        $payload = $this->service->build($request->all());

        return view('pdf.reports.sales.exports.report-print-standalone', [
            'report' => $payload,
        ]);
    }

    public function excel(Request $request): Response
    {
        $this->authorizePermission('reports.sales.export');

        $payload = $this->service->build($request->all());

        $content = view('pdf.reports.sales.exports.report-excel', [
            'report' => $payload,
        ])->render();

        return response($content, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $payload['meta']['file_name'] . '.xls"',
        ]);
    }

    protected function authorizePermission(string $permission): void
    {
        abort_unless(auth()->user()?->can($permission), 403, 'Unauthorized action.');
    }
}
