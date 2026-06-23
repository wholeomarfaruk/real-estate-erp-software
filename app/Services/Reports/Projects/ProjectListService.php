<?php

namespace App\Services\Reports\Projects;

use App\Models\Project;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Project List Report.
 *
 * One row per project with type, location, area, budget, progress, engineers and
 * property/unit counts. Built into the standard report payload shape
 * (title / slug / columns / rows / summary / meta).
 */
class ProjectListService
{
    public function build(array $filters): array
    {
        $query = Project::query()
            ->with(['chiefEngineer:id,name', 'siteEngineer:id,name'])
            ->withCount('units')
            ->addSelect([
                'properties_count' => DB::table('properties')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('properties.project_id', 'projects.id')
                    ->whereNull('properties.deleted_at'),
            ]);

        $status = $filters['status'] ?? null;
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        $type = $filters['type'] ?? null;
        if ($type && $type !== 'all') {
            // `project_type` is stored as a JSON array of strings.
            $query->whereJsonContains('project_type', $type);
        }

        $projects = $query->orderBy('name')->orderBy('id')->get();

        $rows = $projects->values()->map(function (Project $project, int $i): array {
            $types = $project->project_type;
            $typeLabel = is_array($types)
                ? implode(', ', array_map(fn ($t) => ucfirst((string) $t), $types))
                : ucfirst((string) ($types ?? '—'));

            return [
                'sl_no'        => $i + 1,
                'name'         => $project->name ?? '—',
                'code'         => $project->code ?? '—',
                'type'         => $typeLabel ?: '—',
                'location'     => $project->location ?? '—',
                'land_area'    => (float) ($project->land_area ?? 0),
                'building_area'=> (float) ($project->building_area ?? 0),
                'budget'       => (float) ($project->budget ?? 0),
                'progress'     => (int) ($project->progress_pct ?? 0) . '%',
                'properties'   => (int) ($project->properties_count ?? 0),
                'units'        => (int) ($project->units_count ?? 0),
                'chief_engineer' => $project->chiefEngineer?->name ?? '—',
                'handover_date'  => optional($project->handover_date)->format('d/m/Y') ?? '—',
                'status'       => $project->status?->label() ?? '—',
            ];
        })->all();

        $summary = [
            'total_projects'   => count($rows),
            'total_budget'     => collect($rows)->sum('budget'),
            'total_properties' => collect($rows)->sum('properties'),
            'total_units'      => collect($rows)->sum('units'),
        ];

        $meta = [
            'company_name' => config('app.name'),
            'report_title' => 'Project List',
            'report_slug'  => 'project-list',
            'generated_at' => now()->format('d-M-Y H:i A'),
            'generated_by' => auth()->user()?->name ?? 'System',
            'from_date'    => '-',
            'to_date'      => '-',
            'file_name'    => 'project-list-' . now()->format('Y-m-d-His'),
            'notes'        => $filters['notes'] ?? '',
        ];

        return [
            'title'   => 'Project List',
            'slug'    => 'project-list',
            'columns' => $this->columns(),
            'rows'    => $rows,
            'summary' => $summary,
            'meta'    => $meta,
        ];
    }

    public function columns(): array
    {
        return [
            ['key' => 'sl_no',         'label' => 'Sl No',          'align' => 'center', 'type' => 'text'],
            ['key' => 'name',          'label' => 'Project Name',   'align' => 'left',   'type' => 'text'],
            ['key' => 'code',          'label' => 'Code',           'align' => 'center', 'type' => 'text'],
            ['key' => 'type',          'label' => 'Type',           'align' => 'left',   'type' => 'text'],
            ['key' => 'location',      'label' => 'Location',       'align' => 'left',   'type' => 'text'],
            ['key' => 'land_area',     'label' => 'Land Area',      'align' => 'right',  'type' => 'number'],
            ['key' => 'building_area', 'label' => 'Building Area',  'align' => 'right',  'type' => 'number'],
            ['key' => 'budget',        'label' => 'Budget',         'align' => 'right',  'type' => 'money'],
            ['key' => 'progress',      'label' => 'Progress',       'align' => 'center', 'type' => 'text'],
            ['key' => 'properties',    'label' => 'Properties',     'align' => 'center', 'type' => 'number'],
            ['key' => 'units',         'label' => 'Units',          'align' => 'center', 'type' => 'number'],
            ['key' => 'chief_engineer','label' => 'Chief Engineer', 'align' => 'left',   'type' => 'text'],
            ['key' => 'handover_date', 'label' => 'Handover Date',  'align' => 'center', 'type' => 'text'],
            ['key' => 'status',        'label' => 'Status',         'align' => 'center', 'type' => 'text'],
        ];
    }

    /**
     * Status options for the filter: value (enum value) => label.
     * Project.status is cast to the Status enum, so use its cases.
     */
    public function getStatuses(): Collection
    {
        return collect(\App\Enums\Project\Status::cases())
            ->mapWithKeys(fn ($case) => [$case->value => $case->label()]);
    }
}
