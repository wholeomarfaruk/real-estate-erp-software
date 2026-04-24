<?php

namespace App\Livewire\Admin\Hrm\Designation;

use App\Livewire\Admin\Hrm\Concerns\InteractsWithHrmAccess;
use App\Models\Department;
use App\Models\Designation;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class DesignationList extends Component
{
    use InteractsWithHrmAccess;
    use WithPagination;

    public string $search = '';

    public ?int $departmentFilter = null;

    public bool $showFormModal = false;

    public ?int $editingId = null;

    public ?int $department_id = null;

    public string $name = '';

    public ?string $code = null;

    public bool $status = true;

    protected string $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->authorizePermission('hrm.designations.view');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedDepartmentFilter(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->authorizePermission('hrm.designations.create');

        $this->resetForm();
        $this->showFormModal = true;
    }

    public function openEditModal(int $id): void
    {
        $this->authorizePermission('hrm.designations.update');

        $designation = Designation::query()->find($id);

        if (! $designation) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Designation not found.']);

            return;
        }

        $this->editingId = $designation->id;
        $this->department_id = $designation->department_id ? (int) $designation->department_id : null;
        $this->name = (string) $designation->name;
        $this->code = $designation->code;
        $this->status = (bool) $designation->status;
        $this->showFormModal = true;
    }

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
    }

    public function save(): void
    {
        $permission = $this->editingId ? 'hrm.designations.update' : 'hrm.designations.create';
        $this->authorizePermission($permission);

        $validated = $this->validate($this->rules());

        if ($this->editingId) {
            $designation = Designation::query()->find($this->editingId);

            if (! $designation) {
                $this->dispatch('toast', ['type' => 'error', 'message' => 'Designation not found.']);

                return;
            }

            $designation->update($validated);
        } else {
            Designation::query()->create($validated);
        }

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Designation saved successfully.']);
        $this->showFormModal = false;
        $this->resetForm();
    }

    public function deleteDesignation(int $id): void
    {
        $this->authorizePermission('hrm.designations.delete');

        $designation = Designation::query()->find($id);

        if (! $designation) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Designation not found.']);

            return;
        }

        if ($designation->employees()->exists()) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Designation cannot be deleted because it is in use.']);

            return;
        }

        $designation->delete();
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Designation deleted successfully.']);
    }

    public function render(): View
    {
        $this->authorizePermission('hrm.designations.view');

        $designations = Designation::query()
            ->with(['department:id,name'])
            ->withCount('employees')
            ->when($this->search !== '', function (Builder $query): void {
                $search = '%'.$this->search.'%';
                $query->where(function (Builder $subQuery) use ($search): void {
                    $subQuery->where('name', 'like', $search)
                        ->orWhere('code', 'like', $search);
                });
            })
            ->when($this->departmentFilter, fn (Builder $query): Builder => $query->where('department_id', $this->departmentFilter))
            ->orderBy('name')
            ->paginate(15);

        $departments = Department::query()->where('status', true)->orderBy('name')->get(['id', 'name']);

        return view('livewire.admin.hrm.designation.designation-list', [
            'designations' => $designations,
            'departments' => $departments,
        ])->layout('layouts.admin.admin');
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'department_id' => ['nullable', 'exists:departments,id'],
            'name' => ['required', 'string', 'max:150'],
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('designations', 'code')->ignore($this->editingId),
            ],
            'status' => ['required', 'boolean'],
        ];
    }

    protected function resetForm(): void
    {
        $this->reset(['editingId', 'department_id', 'name', 'code']);
        $this->status = true;
    }
}

