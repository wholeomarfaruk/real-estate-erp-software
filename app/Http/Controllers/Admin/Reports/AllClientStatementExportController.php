<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reports\Sales\AllClientStatementService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Dedicated export controller for the All Client Statement report.
 *
 * Binds directly to AllClientStatementService and renders its own dedicated
 * export templates (all-client-statement-pdf / -excel / -print-standalone) —
 * a master statement of every client with an outstanding balance showing
 * total amount, paid, outstanding, overdue amount, scheduled & overdue counts.
 */
class AllClientStatementExportController extends Controller
{
    public function __construct(private AllClientStatementService $service) {}

    public function pdf(Request $request): Response
    {
        $this->authorizePermission('reports.sales.export');

        $payload = $this->service->build($request->all());

        $paper = count($payload['columns']) > 6 ? ['a4', 'landscape'] : ['a4', 'portrait'];

        $pdf = Pdf::loadView('pdf.reports.sales.exports.all-client-statement-pdf', [
            'report' => $payload,
        ])->setPaper($paper[0], $paper[1]);

        return $pdf->download($payload['meta']['file_name'] . '.pdf');
    }

    public function print(Request $request): View
    {
        $this->authorizePermission('reports.sales.export');

        $payload = $this->service->build($request->all());

        return view('pdf.reports.sales.exports.all-client-statement-print-standalone', [
            'report' => $payload,
        ])->layout('layouts.admin.admin');
    }

    public function printStandalone(Request $request): View
    {
        $this->authorizePermission('reports.sales.export');

        $payload = $this->service->build($request->all());

        return view('pdf.reports.sales.exports.all-client-statement-print-standalone', [
            'report' => $payload,
        ]);
    }

    public function excel(Request $request): Response
    {
        $this->authorizePermission('reports.sales.export');

        $payload = $this->service->build($request->all());

        $content = view('pdf.reports.sales.exports.all-client-statement-excel', [
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
