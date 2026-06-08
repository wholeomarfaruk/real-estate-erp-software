<?php

namespace App\Livewire\Admin\Projects;

use Livewire\Component;
use App\Livewire\Traits\WithMediaPicker;
use App\Models\Project;
use App\Models\User;
use Livewire\WithPagination;

class ProjectList extends Component
{
    use WithPagination, WithMediaPicker;

    public $search = '';
    public $viewModal = false;
    public $selectedProject;

    // ── Edit project modal ──────────────────────────────
    public bool $editModal = false;
    public ?int $editProjectId = null;
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

    // ── Create project modal ────────────────────────────
    public bool $createModal = false;
    public $create_name;
    public $create_code;
    public array $create_project_type = [];
    public $create_status = 'upcoming';
    public $create_location;
    public $create_land_area;
    public $create_building_area;
    public $create_start_date;
    public $create_end_date;
    public $create_handover_date;
    public $create_budget;
    public $create_description;
    public $create_image;
    public $create_chief_engineer_id;
    public $create_site_engineer_id;

    public function mount()
    {
        if (!auth()->user()->can('project.view')) {
            abort(403, 'Unauthorized action.');
        }
    }

    public function render()
    {
        if (!auth()->user()->can('project.view')) {
            abort(403, 'Unauthorized action.');
        }

        $projects = Project::query()
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('code', 'like', '%' . $this->search . '%')
                    ->orWhere('project_type', 'like', '%' . $this->search . '%')
                    ->orWhere('location', 'like', '%' . $this->search . '%');
            })
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $engineers = User::orderBy('name')->get(['id', 'name']);

        return view('livewire.admin.projects.project-list', compact('projects', 'engineers'))
            ->layout('layouts.admin.admin');
    }

    public function openViewModal($id)
    {
        $this->selectedProject = Project::with(['user', 'floors', 'units'])->find($id);
        $this->viewModal = true;
    }

    public function closeViewModal()
    {
        $this->viewModal = false;
        $this->selectedProject = null;
    }

    // ── Create modal ─────────────────────────────────────

    public function resetCreate(): void
    {
        $this->resetValidation();
        $this->reset([
            'create_name', 'create_code', 'create_project_type', 'create_location',
            'create_land_area', 'create_building_area', 'create_start_date',
            'create_end_date', 'create_handover_date', 'create_budget',
            'create_description', 'create_image', 'create_chief_engineer_id',
            'create_site_engineer_id',
        ]);
        $this->create_status = 'upcoming';
    }

    public function saveCreate(): void
    {
        if (!auth()->user()->can('project.create')) abort(403);

        $this->validate([
            'create_name'            => 'required|string|max:255',
            'create_project_type'    => 'required|array|min:1',
            'create_project_type.*'  => 'string|in:residential,commercial,luxury,classic',
            'create_status'          => 'required',
            'create_location'        => 'required|string|max:500',
            'create_start_date'      => 'required|date',
            'create_end_date'        => 'required|date|after_or_equal:create_start_date',
            'create_handover_date'   => 'nullable|date',
            'create_budget'          => 'nullable|numeric|min:0',
            'create_land_area'       => 'nullable|numeric|min:0',
            'create_building_area'   => 'nullable|numeric|min:0',
            'create_description'     => 'nullable|string|max:2000',
            'create_chief_engineer_id' => 'nullable|exists:users,id',
            'create_site_engineer_id'  => 'nullable|exists:users,id',
        ]);

        Project::create([
            'name'              => $this->create_name,
            'code'              => $this->create_code,
            'project_type'      => $this->create_project_type,
            'status'            => $this->create_status,
            'location'          => $this->create_location,
            'land_area'         => $this->create_land_area,
            'building_area'     => $this->create_building_area,
            'start_date'        => $this->create_start_date,
            'end_date'          => $this->create_end_date,
            'handover_date'     => $this->create_handover_date ?: null,
            'budget'            => $this->create_budget,
            'description'       => $this->create_description,
            'image'             => $this->create_image,
            'chief_engineer_id' => $this->create_chief_engineer_id ?: null,
            'site_engineer_id'  => $this->create_site_engineer_id ?: null,
            'created_by'        => auth()->id(),
        ]);

        $this->createModal = false;
        $this->resetPage();
        // dispatch close signal so Alpine @wire:end closes the modal
        $this->dispatch('close-create-modal');
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Project created successfully.']);
    }

    // ── Edit modal ────────────────────────────────────────

    // Called by Alpine after it opens the modal instantly — loads data in background
    public function loadEditData(int $id): void
    {
        if (!auth()->user()->can('project.edit')) abort(403);

        $p = Project::findOrFail($id);
        $this->editProjectId          = $p->id;
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
        $this->resetValidation();

        // Signal Alpine to hide skeleton and show the form
        $this->dispatch('edit-data-ready');
    }

    public function saveEdit(): void
    {
        if (!auth()->user()->can('project.edit')) abort(403);

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

        $project = Project::findOrFail($this->editProjectId);
        $project->update([
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

        $this->editProjectId = null;
        $this->editModal = false;
        $this->dispatch('close-edit-modal');
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Project updated successfully.']);
    }

    public function deleteProject($id)
    {
        if (!auth()->user()->can('project.delete')) {
            abort(403, 'Unauthorized action.');
        }

        $project = Project::find($id);
        if (!$project) {
            session()->flash('error', 'Project not found.');
            return;
        }

        $project->delete();
        session()->flash('success', 'Project deleted successfully.');
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Project deleted successfully.']);
    }
}
