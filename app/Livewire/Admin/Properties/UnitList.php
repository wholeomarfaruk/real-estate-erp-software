<?php

namespace App\Livewire\Admin\Properties;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\PropertyUnit;
use App\Models\Property;
use App\Models\PropertyFloor;

class UnitList extends Component
{
    use WithPagination;

    public $search = '';
    public Property $property;
    public $selectedFloor;
    public $floors = [];

    public function mount(Property $property)
    {
        if (!auth()->user()->can('property.view')) {
            abort(403, 'Unauthorized action.');
        }

        $this->property = $property;
        $this->floors = PropertyFloor::where('property_id', $this->property->id)->orderBy('floor_number')->get();
    }

    public function updatedSelectedFloor()
    {
        // noop, Livewire will re-render
    }

    public function render()
    {
        if (!auth()->user()->can('property.view')) {
            abort(403, 'Unauthorized action.');
        }

        $units = PropertyUnit::query()
            ->where('property_id', $this->property->id)
            ->when($this->selectedFloor, function ($query) {
                $query->where('property_floor_id', $this->selectedFloor);
            })
            ->when($this->search, function ($query) {
                $query->where('unit_number', 'like', '%' . $this->search . '%')
                    ->orWhere('unit_name', 'like', '%' . $this->search . '%')
                    ->orWhere('unit_type', 'like', '%' . $this->search . '%');
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('livewire.admin.properties.unit-list', compact('units'))
            ->layout('layouts.admin.admin');
    }

    public function deleteUnit($id)
    {
        if (!auth()->user()->can('property.edit')) {
            abort(403, 'Unauthorized action.');
        }

        $unit = PropertyUnit::find($id);
        if (!$unit) {
            session()->flash('error', 'Unit not found.');
            return;
        }

        $unit->delete();
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Unit deleted successfully.']);
    }
}
