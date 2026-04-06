<?php

namespace App\Livewire\Admin\Properties;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\PropertyFloor;
use App\Models\Property;

class FloorList extends Component
{
    use WithPagination;

    public $search = '';
    public Property $property;

    public function mount(Property $property)
    {
        if (!auth()->user()->can('property.view')) {
            abort(403, 'Unauthorized action.');
        }

        $this->property = $property;
    }

    public function render()
    {
        if (!auth()->user()->can('property.view')) {
            abort(403, 'Unauthorized action.');
        }

        $floors = PropertyFloor::query()
            ->where('property_id', $this->property->id)
            ->when($this->search, function ($query) {
                $query->where('floor_name', 'like', '%' . $this->search . '%')
                    ->orWhere('floor_type', 'like', '%' . $this->search . '%');
            })
            ->orderBy('floor_number')
            ->paginate(15);

        return view('livewire.admin.properties.floor-list', compact('floors'))
            ->layout('layouts.admin.admin');
    }

    public function deleteFloor($id)
    {
        if (!auth()->user()->can('property.edit')) {
            abort(403, 'Unauthorized action.');
        }

        $floor = PropertyFloor::find($id);
        if (!$floor) {
            session()->flash('error', 'Floor not found.');
            return;
        }

        if ($floor->units()->count() > 0) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Cannot delete floor with existing units.']);
            return;
        }

        $floor->delete();
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Floor deleted successfully.']);
    }
}
