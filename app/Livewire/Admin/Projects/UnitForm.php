<?php

namespace App\Livewire\Admin\Projects;

use Livewire\Component;
use App\Models\Unit;
use App\Models\Project;
use App\Models\Floor;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;

class UnitForm extends Component
{
    public ?Unit $unitRecord = null;
    public ?int $unitId = null;
    public bool $editMode = false;

    public ?int $project_id = null;
    public ?int $floor_id = null;
    public $unit_number = '';
    public $unit_name = '';
    public $unit_type = '';
    public $size_sqft = null;
    public $price = null;
    public $facing = '';
    public $bedrooms = null;
    public $bathrooms = null;
    public $balcony = null;
    public $availability_status = 'available';
    public $notes = null;

    public function mount(?Unit $unit = null): void
    {
        if ($unit && $unit->exists) {
            $this->authorizeUpdate();

            $this->editMode = true;
            $this->unitRecord = $unit;
            $this->unitId = $unit->id;
            $this->project_id = $unit->project_id;
            $this->floor_id = $unit->floor_id;
            $this->unit_number = $unit->unit_number;
            $this->unit_name = $unit->unit_name ?? '';
            $this->unit_type = $unit->unit_type ?? '';
            $this->size_sqft = $unit->size_sqft;
            $this->price = $unit->price;
            $this->facing = $unit->facing;
            $this->bedrooms = $unit->bedrooms;
            $this->bathrooms = $unit->bathrooms;
            $this->balcony = $unit->balcony;
            $this->availability_status = $unit->availability_status ?? 'available';
            $this->notes = $unit->notes;

            return;
        }

        $this->authorizeCreate();
    }

    public function updatedProjectId()
    {
        $this->floor_id = null;
    }

    public function rules(): array
    {
        return [
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'floor_id' => ['nullable', 'integer', 'exists:floors,id'],
            'unit_number' => ['required', 'string', 'max:50'],
            'unit_name' => ['nullable', 'string', 'max:255'],
            'unit_type' => ['nullable', 'string', 'max:100'],
            'size_sqft' => ['nullable', 'numeric', 'min:0'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'facing' => ['nullable', 'string', 'max:50'],
            'bedrooms' => ['nullable', 'integer', 'min:0'],
            'bathrooms' => ['nullable', 'integer', 'min:0'],
            'balcony' => ['nullable', 'integer', 'min:0'],
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

        if ($this->editMode && $this->unitRecord) {
            $this->unitRecord->update($validated);
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Unit updated successfully.']);
        } else {
            Unit::create($validated);
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Unit created successfully.']);
        }

        return redirect()->route('admin.units.list');
    }

    public function render(): View
    {
        return view('livewire.admin.projects.unit-form', [
            'projects' => Project::query()->select('id', 'name')->orderBy('name')->get(),
            'floors' => $this->project_id ? Floor::where('project_id', $this->project_id)->orderBy('floor_name')->get() : collect(),
        ])->layout('layouts.admin.admin');
    }

    protected function authorizeCreate(): void
    {
        abort_unless(auth()->user()?->can('project.create'), 403, 'Unauthorized action.');
    }

    protected function authorizeUpdate(): void
    {
        abort_unless(auth()->user()?->can('project.edit'), 403, 'Unauthorized action.');
    }
}
