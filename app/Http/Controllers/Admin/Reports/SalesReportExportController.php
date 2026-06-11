<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reports\Sales\RegularClientStatementService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SalesReportExportController extends Controller
{
    private array $reportServices = [
        'regular-client-statement' => RegularClientStatementService::class,
    ];

    public function print(string $report, Request $request): View
    {
        $this->authorizePermission('reports.sales.export');
        abort_unless(isset($this->reportServices[$report]), 404, 'Report not found.');

        $serviceClass = $this->reportServices[$report];
        $service = app($serviceClass);
        $payload = $service->build($request->all());

        return view('admin.reports.sales.exports.report-print', [
            'report' => $payload,
        ])->layout('layouts.admin.admin');
    }

    public function excel(string $report, Request $request): Response
    {
        $this->authorizePermission('reports.sales.export');
        abort_unless(isset($this->reportServices[$report]), 404, 'Report not found.');

        $serviceClass = $this->reportServices[$report];
        $service = app($serviceClass);
        $payload = $service->build($request->all());

        $content = view('admin.reports.sales.exports.report-excel', [
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
        abort_unless(isset($this->reportServices[$report]), 404, 'Report not found.');

        $serviceClass = $this->reportServices[$report];
        $service = app($serviceClass);
        $payload = $service->build($request->all());

        $paper = count($payload['columns']) > 6 ? ['a4', 'landscape'] : ['a4', 'portrait'];

        $pdf = Pdf::loadView('admin.reports.sales.exports.report-pdf', [
            'report' => $payload,
        ])->setPaper($paper[0], $paper[1]);

        return $pdf->download($payload['meta']['file_name'] . '.pdf');
    }

    protected function authorizePermission(string $permission): void
    {
        abort_unless(auth()->user()?->can($permission), 403, 'Unauthorized action.');
    }
}
