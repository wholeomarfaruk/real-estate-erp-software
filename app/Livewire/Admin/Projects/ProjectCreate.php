<?php

namespace App\Livewire\Admin\Projects;

use App\Livewire\Traits\WithMediaPicker;
use Livewire\Component;
use App\Models\Project;
use Illuminate\Http\Request;
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
    public $status;
    public $description;
    public $image;
    public $documents = [];
    public $editMode = false;
    public $project_id;

    public function mount(Request $request)
    {

        if (!auth()->user()->can('project.create')) {
            abort(403, 'Unauthorized action.');
        } elseif (!auth()->user()->can('project.edit')) {
            abort(403, 'Unauthorized action.');
        }

        if ($request->has('project_id')) {
            $project_id = $request->project_id;
            $this->project_id = $project_id;
            $project = Project::find($project_id);
            if (!$project) {
                return redirect()->back()->with('toast', ['type' => 'error', 'message' => 'Project not found.']);
            }
            $this->editMode = true;
            $this->name = $project->name;
            $this->code = $project->code;
            $this->project_type = $project->project_type;
            $this->location = $project->location;

            $this->budget = $project->budget;
            $this->status = $project->status;
            $this->description = $project->description;
            $this->image = $project->image;
            $this->documents = $project->documents;
            $this->start_date = optional($project->start_date)->format('Y-m-d');
            $this->end_date = optional($project->end_date)->format('Y-m-d');
        }
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'project_type' => 'required',
            'location' => 'required|string|max:500',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'budget' => 'nullable|numeric|min:0',
            'status' => 'required',
            'description' => 'nullable|string|max:1000',
        ];
    }

    public function updated($propertyName)
    {
        if (in_array($propertyName, ['name', 'code', 'location', 'start_date', 'end_date', 'budget'])) {
            $this->validateOnly($propertyName, $this->rules());
        }
    }
    public function generateCode()
    {
        $project = Project::latest()->first();

        $codeValue = intval(preg_replace('/[^0-9]/', '', $project->code ?? ''));

        $code = 'SUDP' . ($project ? $codeValue + 1 : 1);
        $this->code = $code;
    }

    public function save()
    {
        if (!auth()->user()->can('project.create')) {
            abort(403, 'Unauthorized action.');
        }

         
        
        $this->validate();
try {
        $data = [
            'name' => $this->name,
            'code' => $this->code,
            'project_type' => $this->project_type,
            'location' => $this->location,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'budget' => $this->budget,
            'status' => $this->status,
            'description' => $this->description,
            'image' => $this->image,
            'documents' => $this->documents
        ];
        if ($this->editMode) {
           
            $project = Project::find($this->project_id);
            $project->update($data);
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Project updated successfully.']);
            return redirect()->route('admin.projects.list');
        } else {
            
            $this->validate([
                'code' => 'required|string|max:50|unique:projects,code',
            ]);
            $project = Project::create($data);
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Project created successfully.']);
        }
    //code...
         } catch (\Throwable $th) {
            //throw $th;
            dd($th->getMessage());
             $this->dispatch('toast', ['type' => 'error', 'message' => 'An error occurred while saving the project.']);
            
         }

        return redirect()->route('admin.projects.list');
    }

    public function render()
    {
        return view('livewire.admin.projects.project-create')
            ->layout('layouts.admin.admin');
    }
}
