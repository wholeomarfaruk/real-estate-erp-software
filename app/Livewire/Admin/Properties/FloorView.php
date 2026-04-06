<?php

namespace App\Livewire\Admin\Properties;

use Livewire\Component;
use App\Models\Property;
use App\Models\PropertyFloor;
use Illuminate\Contracts\View\View;

class FloorView extends Component
{
    public Property $property;
    public PropertyFloor $floor;

    public function mount(Property $property, PropertyFloor $floor): void
    {
        if (! auth()->user()?->can('property.view')) {
            abort(403, 'Unauthorized action.');
        }

        if ($floor->property_id !== $property->id) {
            abort(404);
        }

        $this->property = $property;
        $this->floor = $floor;
    }

    public function render(): View
    {
        return view('livewire.admin.properties.floor-view', [
            'property' => $this->property,
            'floor' => $this->floor,
        ])->layout('layouts.admin.admin');
    }
}
