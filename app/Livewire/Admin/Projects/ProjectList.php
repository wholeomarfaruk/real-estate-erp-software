<?php

namespace App\Livewire\Admin\Projects;

use Livewire\Component;
use App\Models\Project;
use Livewire\WithPagination;

class ProjectList extends Component
{
    use WithPagination;

    public $search = '';
    public $viewModal = false;
    public $selectedProject;

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

        return view('livewire.admin.projects.project-list', compact('projects'))
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