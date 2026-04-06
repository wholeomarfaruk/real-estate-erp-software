<?php

namespace App\Livewire\Admin\Properties;

use Livewire\Component;
use App\Models\Property;
use App\Models\PropertyUnit;
use App\Models\PropertyFloor;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;

class UnitForm extends Component
{
    public Property $property;
    public ?PropertyUnit $unitRecord = null;
    public ?int $unitId = null;
    public bool $editMode = false;

    public $unit_number = '';
    public $unit_name = '';
    public $unit_type = '';
    public $purpose = '';
    public $size_sqft = null;
    public $sell_price = null;
    public $rent_amount = null;
    public $bedrooms = null;
    public $bathrooms = null;
    public $balcony = null;
    public $facing = '';
    public $availability_status = 'available';
    public $notes = null;
    public $property_floor_id = null;

    public function mount(Property $property, ?PropertyUnit $unit = null): void
    {
        $this->property = $property;

        if ($unit && $unit->exists) {
            if ($unit->property_id !== $property->id) {
                abort(404, 'Unit not found for property.');
            }

            $this->authorizeUpdate();

            $this->editMode = true;
            $this->unitRecord = $unit;
            $this->unitId = $unit->id;
            $this->property_floor_id = $unit->property_floor_id;
            $this->unit_number = $unit->unit_number;
            $this->unit_name = $unit->unit_name ?? '';
            $this->unit_type = $unit->unit_type ?? '';
            $this->purpose = $unit->purpose ?? '';
            $this->size_sqft = $unit->size_sqft;
            $this->sell_price = $unit->sell_price;
            $this->rent_amount = $unit->rent_amount;
            $this->bedrooms = $unit->bedrooms;
            $this->bathrooms = $unit->bathrooms;
            $this->balcony = $unit->balcony;
            $this->facing = $unit->facing;
            $this->availability_status = $unit->availability_status ?? 'available';
            $this->notes = $unit->notes;

            return;
        }

        $this->authorizeCreate();
    }

    public function rules(): array
    {
        return [
            'property_floor_id' => ['nullable', 'integer', 'exists:property_floors,id'],
            'unit_number' => ['required', 'string', 'max:50'],
            'unit_name' => ['nullable', 'string', 'max:255'],
            'unit_type' => ['nullable', 'string', 'max:100'],
            'purpose' => ['nullable', 'string', 'max:255'],
            'size_sqft' => ['nullable', 'numeric', 'min:0'],
            'sell_price' => ['nullable', 'numeric', 'min:0'],
            'rent_amount' => ['nullable', 'numeric', 'min:0'],
            'bedrooms' => ['nullable', 'integer', 'min:0'],
            'bathrooms' => ['nullable', 'integer', 'min:0'],
            'balcony' => ['nullable', 'integer', 'min:0'],
            'facing' => ['nullable', 'string', 'max:50'],
            'availability_status' => ['required', Rule::in(['available','sold','booked','reserved','handover'])],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function save()
    {
        if ($this->editMode) {
            $this->authorizeUpdate();
        } else {
            $this->authorizeCreate();
        }

        $validated = $this->validate();
        $data = array_merge($validated, ['property_id' => $this->property->id, 'property_floor_id' => $this->property_floor_id]);

        if ($this->editMode && $this->unitRecord) {
            $this->unitRecord->update($data);
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Unit updated successfully.']);
        } else {
            PropertyUnit::create($data);
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Unit created successfully.']);
        }

        return redirect()->route('admin.projects.properties.units', $this->property->id);
    }

    public function render(): View
    {
        return view('livewire.admin.properties.unit-form', [
            'property' => $this->property,
            'floors' => PropertyFloor::where('property_id', $this->property->id)->orderBy('floor_number')->get(),
        ])->layout('layouts.admin.admin');
    }

    protected function authorizeCreate(): void
    {
        abort_unless(auth()->user()?->can('property.create'), 403, 'Unauthorized action.');
    }

    protected function authorizeUpdate(): void
    {
        abort_unless(auth()->user()?->can('property.edit'), 403, 'Unauthorized action.');
    }
}
