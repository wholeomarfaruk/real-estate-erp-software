<?php

namespace App\Livewire\Admin\Properties;

use App\Livewire\Traits\WithMediaPicker;
use App\Models\File;
use App\Models\Property;
use Livewire\Component;

class PropertyDetails extends Component
{
    use WithMediaPicker;

    public Property $property;
    public $documents = [];
    public $documentFiles = [];
    public bool $canEdit = false;

    public function mount(Property $property)
    {
        if (!auth()->user()->can('property.view')) {
            abort(403, 'Unauthorized action.');
        }

        $this->property = $property->load(['floors', 'units']);
        $this->documents = $this->property->documents ?? [];
        $this->documentFiles = $this->documents ? File::whereIn('id', $this->documents)->get() : [];
        $this->canEdit = auth()->user()->can('property.edit');
    }

    public function saveDocuments(): void
    {
        if (!$this->canEdit) {
            abort(403, 'Unauthorized action.');
        }

        $property = Property::find($this->property->id);
        if (!$property) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Property not found.']);
            return;
        }
        $property->documents = $this->documents;
        $property->save();
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Documents updated successfully.']);
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.admin.properties.property-details', [
            'stats' => [
                'floors' => $this->property->floors->count(),
                'units' => $this->property->units->count(),
                'available_units' => $this->property->units->where('availability_status', 'available')->count(),
                'sold_units' => $this->property->units->where('availability_status', 'sold')->count(),
            ],
        ])->layout('layouts.admin.admin');
    }
}
