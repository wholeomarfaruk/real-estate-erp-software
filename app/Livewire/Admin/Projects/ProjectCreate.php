<?php

namespace App\Livewire\Admin\Projects;

use App\Livewire\Traits\WithMediaPicker;
use Livewire\Component;
use App\Models\Project;
use Illuminate\Validation\Rule;

class ProjectCreate extends Component
{
    use WithMediaPicker;
    public $name;
    public $code;
    public $project_type;
    public $location;
    public $start_date;
    public $end_date;
    public $budget;
    public $status = 'planned';
    public $description;
    public $image;

    public function mount()
    {
        if (!auth()->user()->can('project.create')) {
            abort(403, 'Unauthorized action.');
        }
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:projects,code',
            'project_type' => 'required|string|max:100',
            'location' => 'required|string|max:500',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'budget' => 'nullable|numeric|min:0',
            'status' => ['required', Rule::in(['planned', 'ongoing', 'on_hold', 'completed'])],
            'description' => 'nullable|string|max:1000',
        ];
    }

    public function updated($propertyName)
    {
        if (in_array($propertyName, ['name', 'code', 'project_type', 'location', 'start_date', 'end_date', 'budget'])) {
            $this->validateOnly($propertyName, $this->rules());
        }
    }
    public function generateCode(){
        $project = Project::latest()->first();
        $code = 'SUDP'.($project ? intval($project->code) + 1 : 1);
        $this->code = $code;
    }

    public function save()
    {
        if (!auth()->user()->can('project.create')) {
            abort(403, 'Unauthorized action.');
        }

        $validatedData = $this->validate();


        $project = Project::create($validatedData);

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Project created successfully.']);

        return redirect()->route('admin.projects.list');
    }

    public function render()
    {
        return view('livewire.admin.projects.project-create')
            ->layout('layouts.admin.admin');
    }
}
