<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reports\ConfigBasedRegistry;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SalesReportExportController extends Controller
{
    public function __construct(private ConfigBasedRegistry $registry) {}



    public function printStandalone(string $report, Request $request): View
    {
        $this->authorizePermission('reports.sales.export');

        $serviceClass = $this->registry->getServiceClass($report);
        abort_unless($serviceClass, 404, 'Report not found.');

        $service = app($serviceClass);
        $payload = $service->build($request->all());

        return view('pdf.reports.sales.exports.report-print-standalone', [
            'report' => $payload,
        ]);
    }

    public function excel(string $report, Request $request): Response
    {
        $this->authorizePermission('reports.sales.export');

        $serviceClass = $this->registry->getServiceClass($report);
        abort_unless($serviceClass, 404, 'Report not found.');

        $service = app($serviceClass);
        $payload = $service->build($request->all());

        $content = view('pdf.reports.sales.exports.report-excel', [
            'report' => $payload,
        ])->render();

        return response($content, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $payload['meta']['file_name'] . '.xls"',
        ]);
    }

    public function pdf(string $report, Request $request): Response
    {
        $this->authorizePermission('reports.sales.export');

        $serviceClass = $this->registry->getServiceClass($report);
        abort_unless($serviceClass, 404, 'Report not found.');

        $service = app($serviceClass);
        $payload = $service->build($request->all());
       

        $paper = count($payload['columns']) > 6 ? ['a4', 'landscape'] : ['a4', 'portrait'];

        $pdf = Pdf::loadView('pdf.reports.sales.exports.report-pdf', [
            'report' => $payload,
        ])->setPaper($paper[0], $paper[1]);

        return $pdf->download($payload['meta']['file_name'] . '.pdf');
    }

    protected function authorizePermission(string $permission): void
    {
        abort_unless(auth()->user()?->can($permission), 403, 'Unauthorized action.');
    }
}
