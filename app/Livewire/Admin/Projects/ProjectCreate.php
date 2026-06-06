<?php

namespace App\Livewire\Admin\Projects;

use App\Livewire\Traits\WithMediaPicker;
use App\Models\Project;
use Illuminate\Http\Request;
use Livewire\Component;

class ProjectCreate extends Component
{
    use WithMediaPicker;

    public $name;
    public $code;
    public array $project_type = [];
    public $location;
    public $start_date;
    public $end_date;
    public $budget;
    public $status;
    public $description;
    public $image;
    public $documents = [];
    public bool $editMode = false;
    public $project_id;

    public function mount(Request $request)
    {
        if (!auth()->user()->can('project.create') && !auth()->user()->can('project.edit')) {
            abort(403, 'Unauthorized action.');
        }

        if ($request->has('project_id')) {
            $this->project_id = $request->project_id;
            $project = Project::find($this->project_id);

            if (!$project) {
                return redirect()->back()->with('toast', ['type' => 'error', 'message' => 'Project not found.']);
            }

            $this->editMode       = true;
            $this->name           = $project->name;
            $this->code           = $project->code;
            $this->project_type   = (array) ($project->project_type ?? []);
            $this->location       = $project->location;
            $this->budget         = $project->budget;
            $this->status         = $project->status?->value ?? $project->status;
            $this->description    = $project->description;
            $this->image          = $project->image;
            $this->documents      = $project->documents ?? [];
            $this->start_date     = optional($project->start_date)->format('Y-m-d');
            $this->end_date       = optional($project->end_date)->format('Y-m-d');
        }
    }

    public function rules(): array
    {
        return [
            'name'          => 'required|string|max:255',
            'project_type'  => 'required|array|min:1',
            'project_type.*'=> 'string|in:residential,commercial,luxury,classic',
            'location'      => 'required|string|max:500',
            'start_date'    => 'required|date',
            'end_date'      => 'required|date|after:start_date',
            'budget'        => 'nullable|numeric|min:0',
            'status'        => 'required',
            'description'   => 'nullable|string|max:1000',
        ];
    }

    public function updated(string $propertyName): void
    {
        if (in_array($propertyName, ['name', 'code', 'location', 'start_date', 'end_date', 'budget'])) {
            $this->validateOnly($propertyName, $this->rules());
        }
    }

    public function generateCode(): void
    {
        $project = Project::latest()->first();
        $codeValue = intval(preg_replace('/[^0-9]/', '', $project->code ?? ''));
        $this->code = 'SUDP' . ($project ? $codeValue + 1 : 1);
    }

    public function save()
    {
        if (!auth()->user()->can('project.create') && !auth()->user()->can('project.edit')) {
            abort(403, 'Unauthorized action.');
        }

        $this->validate();

        try {
            $data = [
                'name'         => $this->name,
                'code'         => $this->code,
                'project_type' => $this->project_type,
                'location'     => $this->location,
                'start_date'   => $this->start_date,
                'end_date'     => $this->end_date,
                'budget'       => $this->budget,
                'status'       => $this->status,
                'description'  => $this->description,
                'image'        => $this->image,
                'documents'    => $this->documents,
            ];

            if ($this->editMode) {
                Project::findOrFail($this->project_id)->update($data);
                $this->dispatch('toast', ['type' => 'success', 'message' => 'Project updated successfully.']);
            } else {
                $this->validate(['code' => 'required|string|max:50|unique:projects,code']);
                Project::create($data);
                $this->dispatch('toast', ['type' => 'success', 'message' => 'Project created successfully.']);
            }
        } catch (\Throwable $th) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'An error occurred: ' . $th->getMessage()]);
            return;
        }

        return redirect()->route('admin.projects.list');
    }

    public function render()
    {
        return view('livewire.admin.projects.project-create')
            ->layout('layouts.admin.admin');
    }
}
