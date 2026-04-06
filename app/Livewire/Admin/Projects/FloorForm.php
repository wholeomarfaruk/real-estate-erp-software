<?php

namespace App\Livewire\Admin\Projects;

use Livewire\Component;
use App\Models\Floor;
use App\Models\Project;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;

class FloorForm extends Component
{
    public ?Floor $floorRecord = null;
    public ?int $floorId = null;
    public bool $editMode = false;

    public ?int $project_id = null;
    public string $floor_name = '';
    public string $floor_type = '';
    public string $status = 'planned';

    public function mount(?Floor $floor = null): void
    {
        if ($floor && $floor->exists) {
            $this->authorizeUpdate();

            $this->editMode = true;
            $this->floorRecord = $floor;
            $this->floorId = $floor->id;
            $this->project_id = $floor->project_id;
            $this->floor_name = $floor->floor_name;
            $this->floor_type = $floor->floor_type;
            $this->status = $floor->status;

            return;
        }

        $this->authorizeCreate();
    }

    public function rules(): array
    {
        return [
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'floor_name' => ['required', 'string', 'max:255'],
            'floor_type' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(['planned', 'ongoing', 'completed'])],
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

        if ($this->editMode && $this->floorRecord) {
            $this->floorRecord->update($validated);
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Floor updated successfully.']);
        } else {
            $this->floorRecord = Floor::create($validated);
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Floor created successfully.']);
        }

        return redirect()->route('admin.floors.list');
    }

    public function render(): View
    {
        return view('livewire.admin.projects.floor-form', [
            'projects' => Project::query()->select('id', 'name')->orderBy('name')->get(),
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
