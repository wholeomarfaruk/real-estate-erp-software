<?php

namespace App\Livewire\Admin\Projects;

use Livewire\Component;
use App\Models\Floor;
use App\Models\Project;
use Livewire\WithPagination;

class FloorList extends Component
{
    use WithPagination;

    public $search = '';
    public $selectedProject;
    public $projects = [];

    public function mount()
    {
        if (!auth()->user()->can('project.view')) {
            abort(403, 'Unauthorized action.');
        }

        $this->projects = Project::orderBy('name')->get();
    }

    public function render()
    {
        if (!auth()->user()->can('project.view')) {
            abort(403, 'Unauthorized action.');
        }

        $floors = Floor::query()
            ->when($this->selectedProject, function ($query) {
                $query->where('project_id', $this->selectedProject);
            })
            ->when($this->search, function ($query) {
                $query->where('floor_name', 'like', '%' . $this->search . '%')
                    ->orWhere('floor_type', 'like', '%' . $this->search . '%')
                    ->orWhereHas('project', function ($q) {
                        $q->where('name', 'like', '%' . $this->search . '%');
                    });
            })
            ->with('project')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('livewire.admin.projects.floor-list', compact('floors'))
            ->layout('layouts.admin.admin');
    }

    public function deleteFloor($id)
    {
        if (!auth()->user()->can('project.edit')) {
            abort(403, 'Unauthorized action.');
        }

        $floor = Floor::find($id);
        if (!$floor) {
            session()->flash('error', 'Floor not found.');
            return;
        }

        // Check if floor has units
        if ($floor->units()->count() > 0) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Cannot delete floor with existing units.']);
            return;
        }

        $floor->delete();
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Floor deleted successfully.']);
    }
}