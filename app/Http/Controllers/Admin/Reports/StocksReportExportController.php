<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reports\Inventory\StocksReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StocksReportExportController extends Controller
{
    public function __construct(private StocksReportService $service) {}

    public function pdf(Request $request): Response
    {
        $this->authorizePermission('reports.inventory.export');

        $payload = $this->service->build($request->all());

        $pdf = Pdf::loadView('pdf.reports.inventory.stocks-report-pdf', [
            'report' => $payload,
        ])->setPaper('a4', 'landscape');

        return $pdf->download($payload['meta']['file_name'] . '.pdf');
    }

    public function print(Request $request): View
    {
        $this->authorizePermission('reports.inventory.export');

        return view('pdf.reports.inventory.stocks-report-print-standalone', [
            'report' => $this->service->build($request->all()),
        ]);
    }

    public function printStandalone(Request $request): View
    {
        $this->authorizePermission('reports.inventory.export');

        return view('pdf.reports.inventory.stocks-report-print-standalone', [
            'report' => $this->service->build($request->all()),
        ]);
    }

    public function excel(Request $request): Response
    {
        $this->authorizePermission('reports.inventory.export');

        $payload = $this->service->build($request->all());

        $content = view('pdf.reports.inventory.stocks-report-excel', [
            'report' => $payload,
        ])->render();

        return response($content, 200, [
            'Content-Type'        => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $payload['meta']['file_name'] . '.xls"',
        ]);
    }

    protected function authorizePermission(string $permission): void
    {
        abort_unless(auth()->user()?->can($permission), 403, 'Unauthorized action.');
    }
}
