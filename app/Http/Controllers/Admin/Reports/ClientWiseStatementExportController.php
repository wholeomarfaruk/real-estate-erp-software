<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reports\Sales\ClientWiseStatementService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Dedicated export controller for the Client Wise Statement report.
 *
 * Unlike the generic SalesReportExportController (registry + {report} slug),
 * this binds directly to ClientWiseStatementService and renders templates built
 * specifically for the client-wise transaction-detail layout + customer strip.
 */
class ClientWiseStatementExportController extends Controller
{
    public function __construct(private ClientWiseStatementService $service) {}

    public function pdf(Request $request): Response
    {
        $this->authorizePermission('reports.sales.export');

        $payload = $this->service->build($request->all());

        // 10 columns — landscape keeps the table readable.
        $pdf = Pdf::loadView('pdf.reports.sales.exports.client-wise-statement-pdf', [
            'report' => $payload,
        ])->setPaper('a4', 'landscape');

        return $pdf->download($payload['meta']['file_name'] . '.pdf');
    }

    public function print(Request $request): View
    {
        $this->authorizePermission('reports.sales.export');

        $payload = $this->service->build($request->all());

        return view('pdf.reports.sales.exports.client-wise-statement-print-standalone', [
            'report' => $payload,
        ])->layout('layouts.admin.admin');
    }

    public function printStandalone(Request $request): View
    {
        $this->authorizePermission('reports.sales.export');

        $payload = $this->service->build($request->all());

        return view('pdf.reports.sales.exports.client-wise-statement-print-standalone', [
            'report' => $payload,
        ]);
    }

    public function excel(Request $request): Response
    {
        $this->authorizePermission('reports.sales.export');

        $payload = $this->service->build($request->all());

        $content = view('pdf.reports.sales.exports.client-wise-statement-excel', [
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
