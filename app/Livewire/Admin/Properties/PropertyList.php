<?php

namespace App\Livewire\Admin\Properties;

use Livewire\Component;
use App\Models\Property;
use App\Models\Project;
use Livewire\WithPagination;

class PropertyList extends Component
{
    use WithPagination;

    public $search = '';
    public $selectedProject;
    public $selectedType;
    public $selectedPurpose;
    public $selectedStatus;
    public $viewModal = false;
    public $selectedProperty;
    public $projects = [];

    public function mount()
    {
        
        if (!auth()->user()->can('property.view')) {
            abort(403, 'Unauthorized action.');
        }

        $this->projects = Project::orderBy('name')->get();
    }

    public function render()
    {
        
        if (!auth()->user()->can('property.view')) {
            abort(403, 'Unauthorized action.');
        }

        $properties = Property::query()
            ->when($this->selectedProject, function ($query) {
                $query->where('project_id', $this->selectedProject);
            })
            ->when($this->selectedType, function ($query) {
                $query->where('property_type', $this->selectedType);
            })
            ->when($this->selectedPurpose, function ($query) {
                $query->where('purpose', $this->selectedPurpose);
            })
            ->when($this->selectedStatus, function ($query) {
                $query->where('status', $this->selectedStatus);
            })
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('code', 'like', '%' . $this->search . '%')
                    ->orWhere('address', 'like', '%' . $this->search . '%');
            })
            ->with('project')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('livewire.admin.properties.property-list', compact('properties'))
            ->layout('layouts.admin.admin');
    }

    public function openViewModal($id)
    {
        $this->selectedProperty = Property::with(['floors', 'units'])->find($id);
        $this->viewModal = true;
    }

    public function closeViewModal()
    {
        $this->viewModal = false;
        $this->selectedProperty = null;
    }

    public function deleteProperty($id)
    {
        if (!auth()->user()->can('property.delete')) {
            abort(403, 'Unauthorized action.');
        }

        $property = Property::find($id);
        if (!$property) {
            session()->flash('error', 'Property not found.');
            return;
        }

        // prevent delete if floors/units exist
        if ($property->units()->count() > 0 || $property->floors()->count() > 0) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Cannot delete property with existing floors or units.']);
            return;
        }

        $property->delete();
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Property deleted successfully.']);
    }
}
