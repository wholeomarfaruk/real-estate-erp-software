<?php

namespace App\Livewire\Admin\Properties;

use Livewire\Component;
use App\Models\Property;
use App\Models\PropertyUnit;
use Illuminate\Contracts\View\View;

class UnitView extends Component
{
    public Property $property;
    public PropertyUnit $unit;

    public function mount(Property $property, PropertyUnit $unit): void
    {
        if (! auth()->user()?->can('property.view')) {
            abort(403, 'Unauthorized action.');
        }

        if ($unit->property_id !== $property->id) {
            abort(404);
        }

        $this->property = $property;
        $this->unit = $unit;
    }

    public function render(): View
    {
        return view('livewire.admin.properties.unit-view', [
            'property' => $this->property,
            'unit' => $this->unit,
        ])->layout('layouts.admin.admin');
    }
}
