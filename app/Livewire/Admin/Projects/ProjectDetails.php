<?php

namespace App\Livewire\Admin\Projects;

use App\Livewire\Traits\WithMediaPicker;
use App\Models\File;
use App\Models\Project;
use App\Models\TimelinePhase;
use App\Models\User;
use Livewire\Component;

class ProjectDetails extends Component
{
    use WithMediaPicker;

    public Project $project;
    public $documents = [];
    public $documentFiles = [];
    public bool $canEdit = false;

    // ── Edit project modal ──────────────────────────────
    public bool $editModal = false;
    public $edit_name;
    public $edit_code;
    public array $edit_project_type = [];
    public $edit_status;
    public $edit_location;
    public $edit_land_area;
    public $edit_building_area;
    public $edit_start_date;
    public $edit_end_date;
    public $edit_handover_date;
    public $edit_budget;
    public $edit_progress_pct;
    public $edit_description;
    public $edit_image;
    public $edit_chief_engineer_id;
    public $edit_site_engineer_id;

    // ── Construction Progress modal ─────────────────────
    public bool $progressModal = false;
    // Each row: ['id' => null|int, 'name' => '', 'progress_percentage' => 0, 'start_date' => '', 'end_date' => '']
    public array $phases = [];

    public function mount(Project $project)
    {
        if (!auth()->user()->can('project.view')) {
            abort(403, 'Unauthorized action.');
        }

        $this->project = $project->load([
            'floors', 'units', 'timelinePhases.tasks',
            'siteEngineer', 'chiefEngineer', 'engineers',
        ]);

        $this->documents     = $this->project->documents ?? [];
        $this->documentFiles = $this->documents
            ? File::whereIn('id', $this->documents)->get()
            : collect();
        $this->canEdit = auth()->user()->can('project.edit');
    }

    // ── Construction Progress ────────────────────────────

    public function openProgressModal(): void
    {
        if (!$this->canEdit) abort(403);

        $this->phases = $this->project->timelinePhases
            ->map(fn($ph) => [
                'id'                  => $ph->id,
                'name'                => $ph->name,
                'progress_percentage' => (int) $ph->progress_percentage,
                'start_date'          => optional($ph->start_date)->format('Y-m-d') ?? '',
                'end_date'            => optional($ph->end_date)->format('Y-m-d') ?? '',
            ])
            ->toArray();

        // If no phases yet, seed with standard construction phases
        if (empty($this->phases)) {
            $defaults = [
                'Foundation', 'Structure', 'Brick Work',
                'Plaster', 'Electrical & Plumbing', 'Finishing',
            ];
            foreach ($defaults as $name) {
                $this->phases[] = [
                    'id' => null, 'name' => $name,
                    'progress_percentage' => 0, 'start_date' => '', 'end_date' => '',
                ];
            }
        }

        $this->progressModal = true;
    }

    public function addPhase(): void
    {
        $this->phases[] = [
            'id' => null, 'name' => '',
            'progress_percentage' => 0, 'start_date' => '', 'end_date' => '',
        ];
    }

    public function removePhase(int $index): void
    {
        // Delete from DB if it has an id
        if (!empty($this->phases[$index]['id'])) {
            TimelinePhase::find($this->phases[$index]['id'])?->delete();
        }
        array_splice($this->phases, $index, 1);
    }

    public function saveProgress(): void
    {
        if (!$this->canEdit) abort(403);

        $this->validate([
            'phases'                       => 'array|min:1',
            'phases.*.name'                => 'required|string|max:100',
            'phases.*.progress_percentage' => 'required|integer|min:0|max:100',
            'phases.*.start_date'          => 'nullable|date',
            'phases.*.end_date'            => 'nullable|date|after_or_equal:phases.*.start_date',
        ], [
            'phases.min'                          => 'Add at least one construction phase.',
            'phases.*.name.required'              => 'Phase name is required.',
            'phases.*.name.max'                   => 'Phase name must be under 100 characters.',
            'phases.*.progress_percentage.min'    => 'Progress cannot be negative.',
            'phases.*.progress_percentage.max'    => 'Progress cannot exceed 100%.',
            'phases.*.end_date.after_or_equal'    => 'End date must be after start date.',
        ]);

        // Sync phases to DB
        $keptIds = [];
        foreach ($this->phases as &$row) {
            $data = [
                'project_id'          => $this->project->id,
                'name'                => $row['name'],
                'progress_percentage' => $row['progress_percentage'],
                'start_date'          => $row['start_date'] ?: null,
                'end_date'            => $row['end_date'] ?: null,
            ];

            if (!empty($row['id'])) {
                TimelinePhase::find($row['id'])?->update($data);
                $keptIds[] = $row['id'];
            } else {
                $phase = TimelinePhase::create($data);
                $row['id'] = $phase->id;
                $keptIds[] = $phase->id;
            }
        }
        unset($row);

        // Delete any phases that were removed during this session but not caught by removePhase
        TimelinePhase::where('project_id', $this->project->id)
            ->whereNotIn('id', $keptIds)
            ->delete();

        // Auto-calculate overall progress_pct as average of all phases
        $avg = count($this->phases) > 0
            ? (int) round(collect($this->phases)->avg('progress_percentage'))
            : 0;

        $this->project->update(['progress_pct' => $avg]);

        // Reload
        $this->project->refresh()->load([
            'floors', 'units', 'timelinePhases.tasks',
            'siteEngineer', 'chiefEngineer', 'engineers',
        ]);

        $this->progressModal = false;
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Construction progress updated.']);
    }

    public function closeProgressModal(): void
    {
        $this->progressModal = false;
        $this->resetValidation();
    }

    // ── Edit project ─────────────────────────────────────

    public function openEditModal(): void
    {
        if (!$this->canEdit) abort(403);

        $p = $this->project;
        $this->edit_name              = $p->name;
        $this->edit_code              = $p->code;
        $this->edit_project_type      = (array) ($p->project_type ?? []);
        $this->edit_status            = $p->status?->value;
        $this->edit_location          = $p->location;
        $this->edit_land_area         = $p->land_area;
        $this->edit_building_area     = $p->building_area;
        $this->edit_start_date        = optional($p->start_date)->format('Y-m-d');
        $this->edit_end_date          = optional($p->end_date)->format('Y-m-d');
        $this->edit_handover_date     = optional($p->handover_date)->format('Y-m-d');
        $this->edit_budget            = $p->budget;
        $this->edit_progress_pct      = $p->progress_pct ?? 0;
        $this->edit_description       = $p->description;
        $this->edit_image             = $p->image;
        $this->edit_chief_engineer_id = $p->chief_engineer_id;
        $this->edit_site_engineer_id  = $p->site_engineer_id;

        $this->editModal = true;
    }

    public function closeEditModal(): void
    {
        $this->editModal = false;
        $this->resetValidation();
    }

    public function saveEdit(): void
    {
        if (!$this->canEdit) abort(403);

        $this->validate([
            'edit_name'            => 'required|string|max:255',
            'edit_project_type'    => 'required|array|min:1',
            'edit_project_type.*'  => 'string|in:residential,commercial,luxury,classic',
            'edit_status'          => 'required',
            'edit_location'        => 'required|string|max:500',
            'edit_start_date'      => 'required|date',
            'edit_end_date'        => 'required|date|after_or_equal:edit_start_date',
            'edit_handover_date'   => 'nullable|date',
            'edit_budget'          => 'nullable|numeric|min:0',
            'edit_progress_pct'    => 'nullable|integer|min:0|max:100',
            'edit_land_area'       => 'nullable|numeric|min:0',
            'edit_building_area'   => 'nullable|numeric|min:0',
            'edit_description'     => 'nullable|string|max:2000',
            'edit_chief_engineer_id' => 'nullable|exists:users,id',
            'edit_site_engineer_id'  => 'nullable|exists:users,id',
        ]);

        $this->project->update([
            'name'              => $this->edit_name,
            'code'              => $this->edit_code,
            'project_type'      => $this->edit_project_type,
            'status'            => $this->edit_status,
            'location'          => $this->edit_location,
            'land_area'         => $this->edit_land_area,
            'building_area'     => $this->edit_building_area,
            'start_date'        => $this->edit_start_date,
            'end_date'          => $this->edit_end_date,
            'handover_date'     => $this->edit_handover_date ?: null,
            'budget'            => $this->edit_budget,
            'progress_pct'      => $this->edit_progress_pct ?? 0,
            'description'       => $this->edit_description,
            'image'             => $this->edit_image,
            'chief_engineer_id' => $this->edit_chief_engineer_id ?: null,
            'site_engineer_id'  => $this->edit_site_engineer_id ?: null,
        ]);

        $this->project->refresh()->load([
            'floors', 'units', 'timelinePhases.tasks',
            'siteEngineer', 'chiefEngineer', 'engineers',
        ]);

        $this->editModal = false;
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Project updated successfully.']);
    }

    // ── Documents ────────────────────────────────────────

    public function saveDocuments(): void
    {
        if (!$this->canEdit) abort(403);

        $this->project->update(['documents' => $this->documents]);
        $this->documentFiles = $this->documents
            ? File::whereIn('id', $this->documents)->get()
            : collect();

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Documents updated.']);
    }

    // ── Render ───────────────────────────────────────────

    public function render()
    {
        $totalSpent = $this->project->totalSpent();
        $budget     = (float) ($this->project->budget ?? 0);
        $remaining  = $budget - $totalSpent;
        $daysLeft   = $this->project->daysToHandover();

        $engineers = User::orderBy('name')->get(['id', 'name']);
        $showEditButton = true;

        return view('livewire.admin.projects.project-details', [
            'project'    => $this->project,
            'totalSpent' => $totalSpent,
            'remaining'  => $remaining,
            'daysLeft'   => $daysLeft,
            'engineers'  => $engineers,
            'showEditButton' => $showEditButton,
            'stats'      => [
                'floors'          => $this->project->floors->count(),
                'units'           => $this->project->units->count(),
                'available_units' => $this->project->units->where('availability_status', 'available')->count(),
                'sold_units'      => $this->project->units->where('availability_status', 'sold')->count(),
            ],
        ])->layout('layouts.admin.admin');
    }
}
