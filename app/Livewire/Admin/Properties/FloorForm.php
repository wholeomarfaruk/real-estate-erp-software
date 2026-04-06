<?php

namespace App\Livewire\Admin\Properties;

use Livewire\Component;
use App\Models\Property;
use App\Models\PropertyFloor;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;

class FloorForm extends Component
{
    public Property $property;
    public ?PropertyFloor $floorRecord = null;
    public ?int $floorId = null;
    public bool $editMode = false;

    public ?int $floor_number = null;
    public string $floor_name = '';
    public string $floor_type = '';
    public string $status = 'planned';
    public ?string $notes = null;

    public function mount(Property $property, ?PropertyFloor $floor = null): void
    {
        $this->property = $property;

        if ($floor && $floor->exists) {
            if ($floor->property_id !== $property->id) {
                abort(404, 'Floor not found for property.');
            }

            $this->authorizeUpdate();

            $this->editMode = true;
            $this->floorRecord = $floor;
            $this->floorId = $floor->id;
            $this->floor_number = $floor->floor_number;
            $this->floor_name = $floor->floor_name;
            $this->floor_type = $floor->floor_type;
            $this->status = $floor->status;
            $this->notes = $floor->notes;

            return;
        }

        $this->authorizeCreate();
    }

    public function rules(): array
    {
        return [
            'floor_number' => ['nullable', 'integer'],
            'floor_name' => ['required', 'string', 'max:255'],
            'floor_type' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['planned', 'ongoing', 'completed'])],
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

        $data = array_merge($validated, ['property_id' => $this->property->id]);

        if ($this->editMode && $this->floorRecord) {
            $this->floorRecord->update($data);
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Floor updated successfully.']);
        } else {
            PropertyFloor::create($data);
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Floor created successfully.']);
        }

        return redirect()->route('admin.projects.properties.floors', $this->property->id);
    }

    public function render(): View
    {
        return view('livewire.admin.properties.floor-form', [
            'property' => $this->property,
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
