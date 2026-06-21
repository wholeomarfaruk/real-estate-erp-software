<?php

namespace App\Livewire\Admin\Properties;

use App\Models\Employee;
use App\Models\Project;
use App\Models\Property;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

class PropertyCatalog extends Component
{
    public string $search = '';
    public string $statusFilter = 'all';

    // ── form fields ──────────────────────────────────────────────────────────
    public bool $showForm = false;
    public ?int $editingId = null;
    public string $fName = '';
    public string $fCode = '';
    public string $fType = '';
    public string $fStatus = 'active';
    public string $fAddress = '';
    public string $fTotalArea = '';
    public string $fLandSize = '';
    public ?int   $fProjectId  = null;
    public ?int   $fEngineerId = null;
    public string $fRegisteredAt = '';
    public string $fRemarks = '';
    public ?object $selectedProject = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('property.view'), 403);
    }

    public function updatedSearch(): void {}
    public function updatedStatusFilter(): void {}

    public function updatedFProjectId(): void
    {
        // Auto-populate property data when project is selected (create mode only)
        if ($this->fProjectId && !$this->editingId) {
            $this->autoPopulateFromProject();
        }
    }

    private function autoPopulateFromProject(): void
    {
        $project = Project::find($this->fProjectId);

        if (!$project) {
            $this->selectedProject = null;
            return;
        }

        $this->selectedProject = $project;

        // Auto-fill name from project name
        if (!$this->fName) {
            $this->fName = $project->name ?? '';
        }

        // Auto-fill code from project code
        if (!$this->fCode) {
            $this->fCode = $project->code ?? '';
        }

        // Auto-fill address from project location
        if (!$this->fAddress) {
            $this->fAddress = $project->location ?? '';
        }

        // Auto-fill type from first project type
        if (!$this->fType && !empty($project->project_type)) {
            $projectTypes = is_array($project->project_type) ? $project->project_type : [$project->project_type];
            $this->fType = $projectTypes[0] ?? '';
        }

        // Auto-fill land size from project land_area
        if (!$this->fLandSize) {
            $this->fLandSize = $project->land_area ?? '';
        }

        // Auto-fill remarks from project description
        if (!$this->fRemarks) {
            $this->fRemarks = $project->description ?? '';
        }
    }

    // ── CRUD ─────────────────────────────────────────────────────────────────

    public function openCreate(): void
    {
        abort_unless(auth()->user()?->can('property.create'), 403);
        $this->resetForm();
        $this->showForm = true;
        $this->editingId = null;
    }

    public function openEdit(int $id): void
    {
        abort_unless(auth()->user()?->can('property.edit'), 403);
        $property = Property::findOrFail($id);
        $this->editingId    = $id;
        $this->fProjectId   = $property->project_id;
        $this->fName        = $property->name ?? '';
        $this->fCode        = $property->code ?? '';
        $this->fType        = $property->type ?? '';
        $this->fStatus      = $property->status ?? 'active';
        $this->fAddress     = $property->address ?? '';
        $this->fTotalArea   = $property->total_area ?? '';
        $this->fLandSize    = $property->land_size ?? '';
        $this->fEngineerId  = $property->engineer_id;
        $this->fRegisteredAt = $property->registered_at?->format('Y-m-d') ?? '';
        $this->fRemarks     = $property->remarks ?? '';
        $this->showForm     = true;
    }

    public function save(): void
    {
        $rules = [
            'fProjectId'    => 'nullable|integer|exists:projects,id',
            'fName'         => 'required|string|max:255',
            'fCode'         => 'nullable|string|max:50',
            'fType'         => 'nullable|string|max:100',
            'fStatus'       => 'required|in:active,inactive',
            'fAddress'      => 'nullable|string',
            'fTotalArea'    => 'nullable|numeric|min:0',
            'fLandSize'     => 'nullable|numeric|min:0',
            'fEngineerId'   => 'nullable|integer|exists:employees,id',
            'fRegisteredAt' => 'nullable|date',
            'fRemarks'      => 'nullable|string',
        ];

        if ($this->editingId) {
            $rules['fCode'] = 'nullable|string|max:50|unique:properties,code,' . $this->editingId;
        } else {
            $rules['fCode'] = 'nullable|string|max:50|unique:properties,code';
        }

        $this->validate($rules);

        $data = [
            'project_id'    => $this->fProjectId ?: null,
            'name'          => $this->fName,
            'code'          => $this->fCode ?: null,
            'type'          => $this->fType ?: null,
            'status'        => $this->fStatus,
            'address'       => $this->fAddress ?: null,
            'total_area'    => $this->fTotalArea !== '' ? $this->fTotalArea : null,
            'land_size'     => $this->fLandSize !== '' ? $this->fLandSize : null,
            'engineer_id'   => $this->fEngineerId ?: null,
            'registered_at' => $this->fRegisteredAt ?: null,
            'remarks'       => $this->fRemarks ?: null,
        ];

        if ($this->editingId) {
            Property::findOrFail($this->editingId)->update($data);
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Property updated.']);
        } else {
            Property::create($data);
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Property created.']);
        }

        $this->showForm = false;
        $this->resetForm();
    }

    public function delete(int $id): void
    {
        abort_unless(auth()->user()?->can('property.delete'), 403);
        $property = Property::findOrFail($id);

        if ($property->units()->count() > 0 || $property->floors()->count() > 0) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Cannot delete: property has floors or units.']);
            return;
        }

        $property->delete();
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Property deleted.']);
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->fName = $this->fCode = $this->fType = $this->fAddress = $this->fTotalArea = $this->fLandSize = $this->fRegisteredAt = $this->fRemarks = '';
        $this->fStatus = 'active';
        $this->fProjectId  = null;
        $this->fEngineerId = null;
        $this->editingId   = null;
        $this->selectedProject = null;
    }

    // ── render ───────────────────────────────────────────────────────────────

    public function render(): View
    {
        abort_unless(auth()->user()?->can('property.view'), 403);

        $properties = Property::query()
            ->withCount([
                'units as available_count' => fn (Builder $q) => $q->where('status', 'available'),
                'units as booked_count'    => fn (Builder $q) => $q->where('status', 'booked'),
                'units as sold_count'      => fn (Builder $q) => $q->where('status', 'sold'),
                'units as rented_count'    => fn (Builder $q) => $q->where('status', 'rented'),
                'units as total_units'     => fn (Builder $q) => $q,
            ])
            ->withSum(['units as available_value' => fn (Builder $q) => $q->where('status', 'available')], 'price')
            ->withSum(['units as booked_value'    => fn (Builder $q) => $q->where('status', 'booked')],    'price')
            ->withSum(['units as sold_value'      => fn (Builder $q) => $q->where('status', 'sold')],      'price')
            ->withSum(['units as rented_value'    => fn (Builder $q) => $q->where('status', 'rented')],    'price')
            ->withCount('floors as floor_count')
            ->with('engineer:id,name')
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->search !== '', function ($q) {
                $q->where(function ($sub) {
                    $sub->where('name',    'like', '%' . $this->search . '%')
                        ->orWhere('code',    'like', '%' . $this->search . '%')
                        ->orWhere('address', 'like', '%' . $this->search . '%');
                });
            })
            ->orderBy('created_at', 'desc')
            ->get();

        // KPI aggregates
        $kpi = [
            'properties' => $properties->count(),
            'active'     => $properties->where('status', 'active')->count(),
            'floors'     => $properties->sum('floor_count'),
            'total'      => $properties->sum('total_units'),
            'available'  => $properties->sum('available_count'),
            'booked'     => $properties->sum('booked_count'),
            'sold'       => $properties->sum('sold_count'),
            'rented'     => $properties->sum('rented_count'),
            'v_available'=> (float) $properties->sum('available_value'),
            'v_booked'   => (float) $properties->sum('booked_value'),
            'v_sold'     => (float) $properties->sum('sold_value'),
            'v_rented'   => (float) $properties->sum('rented_value'),
        ];

        $engineers = Employee::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);

        // Show all projects (allow multiple properties per project)
        $projects = Project::query()
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return view('livewire.admin.properties.property-catalog', compact('properties', 'kpi', 'engineers', 'projects'))
            ->layout('layouts.admin.admin');
    }
}
