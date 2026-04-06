<?php

namespace App\Livewire\Admin\Properties;

use App\Livewire\Traits\WithMediaPicker;
use Livewire\Component;
use App\Models\Property;
use App\Models\Project;
use Illuminate\Http\Request;

class PropertyCreate extends Component
{
    use WithMediaPicker;

    public $project_id;
    public $name;
    public $code;
    public $property_type;
    public $purpose;
    public $address;
    public $description;
    public $total_floors;
    public $status = 'active';
    public $image;
    public $documents = [];
    public $editMode = false;
    public $property_id_param;

    public $projects = [];

    public function mount(Request $request)
    {
        if (!auth()->user()->can('property.create')) {
            abort(403, 'Unauthorized action.');
        }

        $this->projects = Project::orderBy('name')->get();

        if ($request->has('property_id')) {
            $this->property_id_param = $request->property_id;
            $property = Property::find($this->property_id_param);
            if (!$property) {
                return redirect()->back()->with('toast', ['type' => 'error', 'message' => 'Property not found.']);
            }
            $this->editMode = true;
            $this->project_id = $property->project_id;
            $this->name = $property->name;
            $this->code = $property->code;
            $this->property_type = $property->property_type;
            $this->purpose = $property->purpose;
            $this->address = $property->address;
            $this->description = $property->description;
            $this->total_floors = $property->total_floors;
            $this->status = $property->status;
            $this->image = $property->image;
            $this->documents = $property->documents ?? [];
        }
    }

    public function rules()
    {
        return [
            'project_id' => 'required|exists:projects,id',
            'name' => 'required|string|max:255',
            'property_type' => 'nullable|string|max:255',
            'purpose' => 'required|string',
            'address' => 'nullable|string|max:1000',
            'total_floors' => 'nullable|integer|min:0',
            'status' => 'required|string',
        ];
    }

    public function updated($propertyName)
    {
        if (in_array($propertyName, ['name', 'code', 'address', 'total_floors'])) {
            $this->validateOnly($propertyName, $this->rules());
        }
    }

    public function generateCode()
    {
        $last = Property::latest()->first();
        $codeValue = intval(preg_replace('/[^0-9]/', '', $last->code ?? ''));
        $code = 'PROP' . ($last ? $codeValue + 1 : 1);
        $this->code = $code;
    }

    public function save()
    {
        if (!auth()->user()->can('property.create')) {
            abort(403, 'Unauthorized action.');
        }

        $this->validate();

        $data = [
            'project_id' => $this->project_id,
            'name' => $this->name,
            'code' => $this->code,
            'property_type' => $this->property_type,
            'purpose' => $this->purpose,
            'address' => $this->address,
            'description' => $this->description,
            'total_floors' => $this->total_floors,
            'status' => $this->status,
            'image' => $this->image,
            'documents' => $this->documents,
        ];

        if ($this->editMode) {
            $property = Property::find($this->property_id_param);
            $property->update($data);
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Property updated successfully.']);
            return redirect()->route('admin.projects.properties');
        }

        $this->validate([
            'code' => 'nullable|string|max:50|unique:properties,code',
        ]);

        Property::create($data);
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Property created successfully.']);

        return redirect()->route('admin.projects.properties');
    }

    public function render()
    {
        return view('livewire.admin.properties.property-create')
            ->layout('layouts.admin.admin');
    }
}
