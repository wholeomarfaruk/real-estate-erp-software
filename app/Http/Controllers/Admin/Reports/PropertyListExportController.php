<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reports\Projects\PropertyListService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PropertyListExportController extends Controller
{
    public function __construct(private PropertyListService $service) {}

    public function pdf(Request $request): Response
    {
        $this->authorizePermission('reports.projects.export');

        $payload = $this->service->build($request->all());

        $pdf = Pdf::loadView('pdf.reports.projects.property-list-pdf', [
            'report' => $payload,
        ])->setPaper('a4', 'landscape');

        return $pdf->download($payload['meta']['file_name'] . '.pdf');
    }

    public function print(Request $request): View
    {
        $this->authorizePermission('reports.projects.export');

        return view('pdf.reports.projects.property-list-print-standalone', [
            'report' => $this->service->build($request->all()),
        ]);
    }

    public function printStandalone(Request $request): View
    {
        $this->authorizePermission('reports.projects.export');

        return view('pdf.reports.projects.property-list-print-standalone', [
            'report' => $this->service->build($request->all()),
        ]);
    }

    public function excel(Request $request): Response
    {
        $this->authorizePermission('reports.projects.export');

        $payload = $this->service->build($request->all());

        $content = view('pdf.reports.projects.property-list-excel', [
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
