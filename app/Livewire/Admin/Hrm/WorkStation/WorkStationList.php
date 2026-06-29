<?php

namespace App\Livewire\Admin\Hrm\WorkStation;

use App\Livewire\Admin\Hrm\Concerns\InteractsWithHrmAccess;
use App\Models\WorkStation;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class WorkStationList extends Component
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
        $this->authorizePermission('hrm.work-stations.view');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->authorizePermission('hrm.work-stations.create');

        $this->resetForm();
        $this->showFormModal = true;
    }

    public function openEditModal(int $id): void
    {
        $this->authorizePermission('hrm.work-stations.update');

        $workStation = WorkStation::query()->find($id);

        if (! $workStation) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Work station not found.']);

            return;
        }

        $this->editingId = $workStation->id;
        $this->name = (string) $workStation->name;
        $this->code = $workStation->code;
        $this->status = (bool) $workStation->status;
        $this->showFormModal = true;
    }

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
    }

    public function save(): void
    {
        $permission = $this->editingId ? 'hrm.work-stations.update' : 'hrm.work-stations.create';
        $this->authorizePermission($permission);

        $validated = $this->validate($this->rules());

        if ($this->editingId) {
            $workStation = WorkStation::query()->find($this->editingId);

            if (! $workStation) {
                $this->dispatch('toast', ['type' => 'error', 'message' => 'Work station not found.']);

                return;
            }

            $workStation->update($validated);
        } else {
            WorkStation::query()->create($validated);
        }

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Work station saved successfully.']);
        $this->showFormModal = false;
        $this->resetForm();
    }

    public function deleteWorkStation(int $id): void
    {
        $this->authorizePermission('hrm.work-stations.delete');

        $workStation = WorkStation::query()->find($id);

        if (! $workStation) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Work station not found.']);

            return;
        }

        if ($workStation->employees()->exists()) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Work station cannot be deleted because it has employees assigned.']);

            return;
        }

        $workStation->delete();
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Work station deleted successfully.']);
    }

    public function render(): View
    {
        $this->authorizePermission('hrm.work-stations.view');

        $workStations = WorkStation::query()
            ->withCount('employees')
            ->when($this->search !== '', function (Builder $query): void {
                $search = '%'.$this->search.'%';
                $query->where(function (Builder $subQuery) use ($search): void {
                    $subQuery->where('name', 'like', $search)
                        ->orWhere('code', 'like', $search);
                });
            })
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.admin.hrm.work-station.work-station-list', [
            'workStations' => $workStations,
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
                Rule::unique('work_stations', 'code')->ignore($this->editingId),
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
