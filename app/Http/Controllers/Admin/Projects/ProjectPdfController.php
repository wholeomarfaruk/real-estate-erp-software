<?php

namespace App\Http\Controllers\Admin\Projects;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectEstimate;
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

    public function estimate(Project $project, ProjectEstimate $estimate): Response
    {
        abort_unless(auth()->user()?->can('project.view'), 403, 'Unauthorized.');
        abort_unless($estimate->project_id === $project->id, 403, 'Estimate does not belong to this project.');

        $estimate->load(['items', 'createdBy', 'approvedBy']);

        $totals = [
            'material' => (float) $estimate->items->where('cost_type', 'material')->sum('estimated_amount'),
            'labour' => (float) $estimate->items->where('cost_type', 'labour')->sum('estimated_amount'),
            'overhead' => (float) $estimate->items->where('cost_type', 'overhead')->sum('estimated_amount'),
            'indirect' => (float) $estimate->items->where('cost_type', 'indirect')->sum('estimated_amount'),
        ];
        $totals['grand'] = array_sum($totals);

        $boqItems = $estimate->items->sortBy('sort_order')->groupBy('work_phase');

        $pdf = Pdf::loadView('pdf.project-estimate', compact(
            'project', 'estimate', 'boqItems', 'totals'
        ))->setPaper('a4', 'portrait');

        $filename = ($estimate->estimate_no ?? ('EST-V' . $estimate->version)) . '.pdf';

        return $pdf->download($filename);
    }
}
