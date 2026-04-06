<?php

namespace App\Livewire\Admin\Properties;

use Livewire\Component;
use App\Models\Property;

class Overview extends Component
{
    public Property $property;

    public function mount(Property $property)
    {
        if (!auth()->user()->can('property.view')) {
            abort(403, 'Unauthorized action.');
        }

        $this->property = $property->load(['floors.units']);
    }

    public function render()
    {
        return view('livewire.admin.properties.overview', [
            'property' => $this->property,
        ])->layout('layouts.admin.admin');
    }
}
