<?php

namespace App\Livewire\Admin\Crm\Task;

use App\Models\CrmTask;
use App\Models\Lead;
use App\Models\User;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class CrmTaskList extends Component
{
    use WithPagination;

    #[Url(as: 'q', history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $filterStatus   = 'all';

    #[Url(history: true)]
    public string $filterPriority = 'all';

    #[Url(history: true)]
    public string $filterAssigned = 'all';

    public bool   $drawerOpen    = false;
    public ?int   $editingId     = null;
    public string $tTitle        = '';
    public string $tType         = 'other';
    public string $tPriority     = 'medium';
    public string $tStatus       = 'todo';
    public string $tDueAt        = '';
    public string $tAssignedTo   = '';
    public string $tDescription  = '';
    public string $tRelatedType  = 'lead';
    public string $tRelatedId    = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('crm.task.view'), 403);
    }

    public function updatedSearch(): void        { $this->resetPage(); }
    public function updatedFilterStatus(): void  { $this->resetPage(); }
    public function updatedFilterPriority(): void { $this->resetPage(); }

    public function openCreate(): void
    {
        $this->editingId    = null;
        $this->tTitle       = '';
        $this->tType        = 'other';
        $this->tPriority    = 'medium';
        $this->tStatus      = 'todo';
        $this->tDueAt       = now()->addDay()->format('Y-m-d\TH:i');
        $this->tAssignedTo  = (string) auth()->id();
        $this->tDescription = '';
        $this->tRelatedType = 'lead';
        $this->tRelatedId   = '';
        $this->drawerOpen   = true;
    }

    public function openEdit(int $id): void
    {
        $task = CrmTask::findOrFail($id);
        $this->editingId    = $task->id;
        $this->tTitle       = $task->title;
        $this->tType        = $task->type;
        $this->tPriority    = $task->priority;
        $this->tStatus      = $task->status;
        $this->tDueAt       = $task->due_at?->format('Y-m-d\TH:i') ?? '';
        $this->tAssignedTo  = (string) ($task->assigned_to ?? '');
        $this->tDescription = $task->description ?? '';
        $this->tRelatedType = $task->related_type ?? 'lead';
        $this->tRelatedId   = (string) ($task->related_id ?? '');
        $this->drawerOpen   = true;
    }

    public function save(): void
    {
        $this->validate(['tTitle' => 'required|string|max:255']);

        $data = [
            'title'        => $this->tTitle,
            'type'         => $this->tType,
            'priority'     => $this->tPriority,
            'status'       => $this->tStatus,
            'due_at'       => $this->tDueAt ?: null,
            'assigned_to'  => $this->tAssignedTo ?: null,
            'description'  => $this->tDescription ?: null,
            'related_type' => $this->tRelatedType ?: null,
            'related_id'   => $this->tRelatedId ?: null,
            'completed_at' => $this->tStatus === 'done' ? now() : null,
            'created_by'   => auth()->id(),
        ];

        if ($this->editingId) {
            CrmTask::findOrFail($this->editingId)->update($data);
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Task updated.']);
        } else {
            CrmTask::create($data);
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Task created.']);
        }

        $this->closeDrawer();
        $this->resetPage();
    }

    public function markDone(int $id): void
    {
        CrmTask::findOrFail($id)->update(['status' => 'done', 'completed_at' => now()]);
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Task marked as done.']);
    }

    public function delete(int $id): void
    {
        CrmTask::findOrFail($id)->delete();
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Task deleted.']);
        $this->resetPage();
    }

    public function closeDrawer(): void
    {
        $this->drawerOpen = false;
        $this->editingId  = null;
        $this->resetValidation();
    }

    public function render()
    {
        $tasks = CrmTask::query()
            ->with(['assignedUser'])
            ->when($this->search, function ($q) {
                $s = '%' . $this->search . '%';
                $q->where('title', 'like', $s)->orWhere('description', 'like', $s);
            })
            ->when($this->filterStatus !== 'all', fn ($q) => $q->where('status', $this->filterStatus))
            ->when($this->filterPriority !== 'all', fn ($q) => $q->where('priority', $this->filterPriority))
            ->when($this->filterAssigned !== 'all', fn ($q) => $q->where('assigned_to', $this->filterAssigned))
            ->orderByRaw("FIELD(priority, 'urgent', 'high', 'medium', 'low')")
            ->orderBy('due_at', 'asc')
            ->paginate(20);

        $kpi = [
            'todo'        => CrmTask::where('status', 'todo')->count(),
            'in_progress' => CrmTask::where('status', 'in_progress')->count(),
            'done'        => CrmTask::where('status', 'done')->count(),
            'overdue'     => CrmTask::where('status', '!=', 'done')
                ->where('due_at', '<', now())
                ->count(),
        ];

        $users = User::orderBy('name')->get();
        $leads = Lead::orderBy('created_at', 'desc')->limit(100)->get();

        return view('livewire.admin.crm.task.crm-task-list', compact('tasks', 'kpi', 'users', 'leads'))
            ->layout('layouts.admin.admin');
    }
}
