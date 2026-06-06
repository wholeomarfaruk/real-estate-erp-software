<?php

namespace App\Http\Controllers\Admin\Projects;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class ProjectPdfController extends Controller
{
    public function details(Project $project): Response
    {
        abort_unless(auth()->user()?->can('project.view'), 403, 'Unauthorized.');

        $project->load([
            'timelinePhases',
            'siteEngineer',
            'chiefEngineer',
            'engineers',
            'floors',
            'units',
        ]);

        $totalSpent = $project->totalSpent();
        $budget     = (float) ($project->budget ?? 0);
        $remaining  = $budget - $totalSpent;
        $daysLeft   = $project->daysToHandover();

        $pdf = Pdf::loadView('pdf.project-details', compact(
            'project', 'totalSpent', 'remaining', 'daysLeft'
        ))->setPaper('a4', 'portrait');

        $filename = 'Project-' . ($project->code ?? $project->id) . '-Details.pdf';

        return $pdf->download($filename);
    }
}
