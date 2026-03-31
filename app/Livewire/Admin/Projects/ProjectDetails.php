<?php

namespace App\Livewire\Admin\Projects;

use App\Livewire\Traits\WithMediaPicker;
use App\Models\File;
use App\Models\Project;
use Livewire\Component;

class ProjectDetails extends Component
{
    use WithMediaPicker {
        mediaSelected as baseMediaSelected;
        removeMedia as baseRemoveMedia;
    }

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
        $this->canEdit = auth()->user()->can('project.edit');

        $this->hydrateDocumentFiles();
    }



    public function saveDocuments(bool $notify = true): void
    {
        if (!$this->canEdit) {
            abort(403, 'Unauthorized action.');
        }

        $this->project->update(['documents' => $this->documents]);


        if ($notify) {
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Documents updated successfully.']);
        }
    }
    protected function hydrateDocumentFiles(): void
    {
        $ids = collect($this->documents)->filter()->values();
        $this->documentFiles = $ids->isEmpty()
            ? collect()
            : File::whereIn('id', $ids)->get();
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
