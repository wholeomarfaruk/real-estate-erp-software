<?php

namespace App\Livewire\Admin\Hrm\Department;

use App\Livewire\Admin\Hrm\Concerns\InteractsWithHrmAccess;
use App\Models\Department;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class DepartmentList extends Component
{
    use InteractsWithHrmAccess;
    use WithPagination;

    public string $search = '';

    public bool $showFormModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public ?string $code = null;

    public bool $status = true;

    protected string $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->authorizePermission('hrm.departments.view');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->authorizePermission('hrm.departments.create');

        $this->resetForm();
        $this->showFormModal = true;
    }

    public function openEditModal(int $id): void
    {
        $this->authorizePermission('hrm.departments.update');

        $department = Department::query()->find($id);

        if (! $department) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Department not found.']);

            return;
        }

        $this->editingId = $department->id;
        $this->name = (string) $department->name;
        $this->code = $department->code;
        $this->status = (bool) $department->status;
        $this->showFormModal = true;
    }

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
    }

    public function save(): void
    {
        $permission = $this->editingId ? 'hrm.departments.update' : 'hrm.departments.create';
        $this->authorizePermission($permission);

        $validated = $this->validate($this->rules());

        if ($this->editingId) {
            $department = Department::query()->find($this->editingId);

            if (! $department) {
                $this->dispatch('toast', ['type' => 'error', 'message' => 'Department not found.']);

                return;
            }

            $department->update($validated);
        } else {
            Department::query()->create($validated);
        }

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Department saved successfully.']);
        $this->showFormModal = false;
        $this->resetForm();
    }

    public function deleteDepartment(int $id): void
    {
        $this->authorizePermission('hrm.departments.delete');

        $department = Department::query()->find($id);

        if (! $department) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Department not found.']);

            return;
        }

        if ($department->designations()->exists() || $department->employees()->exists()) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Department cannot be deleted because it is in use.']);

            return;
        }

        $department->delete();
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Department deleted successfully.']);
    }

    public function render(): View
    {
        $this->authorizePermission('hrm.departments.view');

        $departments = Department::query()
            ->withCount(['designations', 'employees'])
            ->when($this->search !== '', function (Builder $query): void {
                $search = '%'.$this->search.'%';
                $query->where(function (Builder $subQuery) use ($search): void {
                    $subQuery->where('name', 'like', $search)
                        ->orWhere('code', 'like', $search);
                });
            })
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.admin.hrm.department.department-list', [
            'departments' => $departments,
        ])->layout('layouts.admin.admin');
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('departments', 'code')->ignore($this->editingId),
            ],
            'status' => ['required', 'boolean'],
        ];
    }

    protected function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'code']);
        $this->status = true;
    }
}

