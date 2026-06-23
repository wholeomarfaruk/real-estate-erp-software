<?php

namespace App\Services\Reports\Projects;

use App\Models\Project;
use App\Models\Property;
use Illuminate\Support\Collection;

/**
 * Property List Report.
 *
 * One row per property with its project, type, area and unit/floor counts.
 * Built into the standard report payload shape (title / slug / columns / rows /
 * summary / meta) consumed by the on-screen table and export templates.
 */
class PropertyListService
{
    public function build(array $filters): array
    {
        $query = Property::query()
            ->with(['project:id,name'])
            ->withCount(['units', 'floors']);

        $projectId = $filters['project_id'] ?? null;
        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        $status = $filters['status'] ?? null;
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        $type = $filters['type'] ?? null;
        if ($type && $type !== 'all') {
            // `type` is stored as a JSON array of strings.
            $query->whereJsonContains('type', $type);
        }

        $properties = $query->orderBy('name')->orderBy('id')->get();

        $rows = $properties->values()->map(function (Property $property, int $i): array {
            $types = $property->type;
            $typeLabel = is_array($types)
                ? implode(', ', array_map(fn ($t) => ucfirst((string) $t), $types))
                : ucfirst((string) ($types ?? '—'));

            return [
                'sl_no'        => $i + 1,
                'name'         => $property->name ?? '—',
                'code'         => $property->code ?? '—',
                'project'      => $property->project?->name ?? '—',
                'type'         => $typeLabel ?: '—',
                'address'      => $property->address ?? '—',
                'total_area'   => (float) ($property->total_area ?? 0),
                'land_size'    => (float) ($property->land_size ?? 0),
                'floors'       => (int) ($property->floors_count ?? 0),
                'units'        => (int) ($property->units_count ?? 0),
                'status'       => ucwords(str_replace('_', ' ', $property->status ?? '—')),
            ];
        })->all();

        $summary = [
            'total_properties' => count($rows),
            'total_units'      => collect($rows)->sum('units'),
            'total_area'       => collect($rows)->sum('total_area'),
        ];

        $meta = [
            'company_name' => config('app.name'),
            'report_title' => 'Property List',
            'report_slug'  => 'property-list',
            'generated_at' => now()->format('d-M-Y H:i A'),
            'generated_by' => auth()->user()?->name ?? 'System',
            'from_date'    => '-',
            'to_date'      => '-',
            'file_name'    => 'property-list-' . now()->format('Y-m-d-His'),
            'notes'        => $filters['notes'] ?? '',
        ];

        return [
            'title'   => 'Property List',
            'slug'    => 'property-list',
            'columns' => $this->columns(),
            'rows'    => $rows,
            'summary' => $summary,
            'meta'    => $meta,
        ];
    }

    public function columns(): array
    {
        return [
            ['key' => 'sl_no',      'label' => 'Sl No',        'align' => 'center', 'type' => 'text'],
            ['key' => 'name',       'label' => 'Property Name', 'align' => 'left',   'type' => 'text'],
            ['key' => 'code',       'label' => 'Code',          'align' => 'center', 'type' => 'text'],
            ['key' => 'project',    'label' => 'Project',       'align' => 'left',   'type' => 'text'],
            ['key' => 'type',       'label' => 'Type',          'align' => 'left',   'type' => 'text'],
            ['key' => 'address',    'label' => 'Address',       'align' => 'left',   'type' => 'text'],
            ['key' => 'total_area', 'label' => 'Total Area',    'align' => 'right',  'type' => 'number'],
            ['key' => 'land_size',  'label' => 'Land Size',     'align' => 'right',  'type' => 'number'],
            ['key' => 'floors',     'label' => 'Floors',        'align' => 'center', 'type' => 'number'],
            ['key' => 'units',      'label' => 'Units',         'align' => 'center', 'type' => 'number'],
            ['key' => 'status',     'label' => 'Status',        'align' => 'center', 'type' => 'text'],
        ];
    }

    public function getProjects(): Collection
    {
        return Project::orderBy('name')->get(['id', 'name']);
    }

    /** Distinct status values present on properties. */
    public function getStatuses(): Collection
    {
        return Property::query()
            ->whereNotNull('status')
            ->distinct()
            ->orderBy('status')
            ->pluck('status');
    }
}
