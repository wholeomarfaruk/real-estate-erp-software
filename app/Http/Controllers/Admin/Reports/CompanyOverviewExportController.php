<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reports\Finance\CompanyOverviewService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CompanyOverviewExportController extends Controller
{
    public function __construct(private CompanyOverviewService $service) {}

    public function pdf(Request $request): Response
    {
        $this->authorizePermission('reports.finance.export');

        $payload = $this->service->build($request->all());

        // 18 wide columns — always A4 landscape.
        $pdf = Pdf::loadView('pdf.reports.finance.company-overview-pdf', [
            'report' => $payload,
        ])->setPaper('a4', 'landscape');

        return $pdf->download($payload['meta']['file_name'] . '.pdf');
    }

    public function print(Request $request): View
    {
        $this->authorizePermission('reports.finance.export');

        return view('pdf.reports.finance.company-overview-print-standalone', [
            'report' => $this->service->build($request->all()),
        ]);
    }

    public function printStandalone(Request $request): View
    {
        $this->authorizePermission('reports.finance.export');

        return view('pdf.reports.finance.company-overview-print-standalone', [
            'report' => $this->service->build($request->all()),
        ]);
    }

    public function excel(Request $request): Response
    {
        $this->authorizePermission('reports.finance.export');

        $payload = $this->service->build($request->all());

        $content = view('pdf.reports.finance.company-overview-excel', [
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
