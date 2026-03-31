<?php

namespace App\Livewire\Admin\Projects;

use App\Livewire\Traits\WithMediaPicker;
use App\Models\File;
use App\Models\Project;
use Livewire\Component;

class ProjectDetails extends Component
{
    use WithMediaPicker;

    public Project $project;
    public $documents = [];
    public $documentFiles = [];
    public bool $canEdit = false;

    public function mount(Project $project)
    {
        if (!auth()->user()->can('project.view')) {
            abort(403, 'Unauthorized action.');
        }

        $this->project = $project->load(['floors', 'units']);
        $this->documents = $this->project->documents ?? [];
        $this->documentFiles = $this->documents ? File::whereIn('id', $this->documents)->get() : [];
        $this->canEdit = auth()->user()->can('project.edit');

    }



    public function saveDocuments(): void
    {
        if (!$this->canEdit) {
            abort(403, 'Unauthorized action.');
        }

        $project = Project::find($this->project->id);
        if (!$project) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Project not found.']);
            return;
        }
        $project->documents = $this->documents;
        $project->save();
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Documents updated successfully.']);

    }

    public function render()
    {
        return view('livewire.admin.projects.project-details', [
            'stats' => [
                'floors' => $this->project->floors->count(),
                'units' => $this->project->units->count(),
                'available_units' => $this->project->units->where('availability_status', 'available')->count(),
                'sold_units' => $this->project->units->where('availability_status', 'sold')->count(),
            ],
        ])->layout('layouts.admin.admin');
    }
}
