<?php

namespace App\Livewire\Admin\Projects;

use Livewire\Component;
use App\Models\Unit;
use App\Models\Project;
use App\Models\Floor;
use Livewire\WithPagination;

class UnitList extends Component
{
    use WithPagination;

    public $search = '';
    public $selectedProject;
    public $selectedFloor;
    public $projects = [];
    public $floors = [];

    public function mount()
    {
        if (!auth()->user()->can('project.view')) {
            abort(403, 'Unauthorized action.');
        }

        $this->projects = Project::orderBy('name')->get();
    }

    public function updatedSelectedProject()
    {
        $this->floors = Floor::where('project_id', $this->selectedProject)->orderBy('floor_name')->get();
        $this->selectedFloor = null;
    }

    public function render()
    {
        if (!auth()->user()->can('project.view')) {
            abort(403, 'Unauthorized action.');
        }

        $units = Unit::query()
            ->when($this->selectedProject, function ($query) {
                $query->where('project_id', $this->selectedProject);
            })
            ->when($this->selectedFloor, function ($query) {
                $query->where('floor_id', $this->selectedFloor);
            })
            ->when($this->search, function ($query) {
                $query->where('unit_number', 'like', '%' . $this->search . '%')
                    ->orWhere('unit_type', 'like', '%' . $this->search . '%')
                    ->orWhereHas('project', function ($q) {
                        $q->where('name', 'like', '%' . $this->search . '%');
                    })
                    ->orWhereHas('floor', function ($q) {
                        $q->where('floor_name', 'like', '%' . $this->search . '%');
                    });
            })
            ->with(['project', 'floor'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('livewire.admin.projects.unit-list', compact('units'))
            ->layout('layouts.admin.admin');
    }

    public function deleteUnit($id)
    {
        if (!auth()->user()->can('project.edit')) {
            abort(403, 'Unauthorized action.');
        }

        $unit = Unit::find($id);
        if (!$unit) {
            session()->flash('error', 'Unit not found.');
            return;
        }

        $unit->delete();
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Unit deleted successfully.']);
    }
}