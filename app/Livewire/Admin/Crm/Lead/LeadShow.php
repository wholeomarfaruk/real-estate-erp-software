<?php

namespace App\Livewire\Admin\Crm\Lead;

use App\Livewire\Traits\WithMediaPicker;
use App\Models\CrmTask;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadFollowup;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class LeadShow extends Component
{
    use WithMediaPicker;

    public Lead $lead;
    public string $activeTab = 'timeline';

    // ── Activity log ─────────────────────────────────────────────────────────
    public string $activityType = 'note';
    public string $activityDesc = '';

    // ── Follow-up form ───────────────────────────────────────────────────────
    public bool   $followupModal     = false;
    public ?int   $editingFollowupId = null;
    public string $fuType            = 'call';
    public string $fuScheduledAt     = '';
    public string $fuAssignedTo      = '';
    public string $fuNotes           = '';
    public string $fuStatus          = 'pending';
    public string $fuOutcome         = '';

    // ── Task form ────────────────────────────────────────────────────────────
    public bool   $taskModal     = false;
    public ?int   $editingTaskId = null;
    public string $tTitle        = '';
    public string $tType         = 'other';
    public string $tPriority     = 'medium';
    public string $tStatus       = 'todo';
    public string $tDueAt        = '';
    public string $tAssignedTo   = '';
    public string $tDescription  = '';

    // ── Files via MediaPicker ─────────────────────────────────────────────────
    public array $leadFiles = [];

    // ── Convert to Customer ──────────────────────────────────────────────────
    public bool $convertModal = false;

    public function mount(Lead $lead): void
    {
        abort_unless(auth()->user()?->can('crm.lead.view'), 403);
        $this->lead        = $lead;
        $this->leadFiles   = $lead->fileables()->pluck('file_id')->toArray();
        $this->fuScheduledAt = now()->addDay()->format('Y-m-d\TH:i');
    }

    // ─── Activity ────────────────────────────────────────────────────────────

    public function logActivity(): void
    {
        $this->validate([
            'activityDesc' => 'required|string|min:3',
            'activityType' => 'required',
        ]);

        LeadActivity::create([
            'lead_id'     => $this->lead->id,
            'type'        => $this->activityType,
            'description' => $this->activityDesc,
            'created_by'  => auth()->id(),
        ]);

        $this->activityDesc = '';
        $this->activityType = 'note';
        $this->lead->refresh();
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Activity logged.']);
    }

    // ─── Follow-ups ──────────────────────────────────────────────────────────

    public function openFollowupCreate(): void
    {
        $this->editingFollowupId = null;
        $this->fuType        = 'call';
        $this->fuScheduledAt = now()->addDay()->format('Y-m-d\TH:i');
        $this->fuAssignedTo  = (string) auth()->id();
        $this->fuNotes       = '';
        $this->fuStatus      = 'pending';
        $this->fuOutcome     = '';
        $this->followupModal = true;
    }

    public function openFollowupEdit(int $id): void
    {
        $fu = LeadFollowup::findOrFail($id);
        $this->editingFollowupId = $fu->id;
        $this->fuType        = $fu->type;
        $this->fuScheduledAt = $fu->scheduled_at->format('Y-m-d\TH:i');
        $this->fuAssignedTo  = (string) ($fu->assigned_to ?? '');
        $this->fuNotes       = $fu->notes ?? '';
        $this->fuStatus      = $fu->status;
        $this->fuOutcome     = $fu->outcome ?? '';
        $this->followupModal = true;
    }

    public function saveFollowup(): void
    {
        $this->validate([
            'fuScheduledAt' => 'required|date',
            'fuType'        => 'required',
        ]);

        $data = [
            'lead_id'      => $this->lead->id,
            'type'         => $this->fuType,
            'scheduled_at' => $this->fuScheduledAt,
            'assigned_to'  => $this->fuAssignedTo ?: null,
            'notes'        => $this->fuNotes ?: null,
            'status'       => $this->fuStatus,
            'outcome'      => $this->fuOutcome ?: null,
            'completed_at' => in_array($this->fuStatus, ['done', 'cancelled']) ? now() : null,
            'created_by'   => auth()->id(),
        ];

        if ($this->editingFollowupId) {
            LeadFollowup::findOrFail($this->editingFollowupId)->update($data);
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Follow-up updated.']);
        } else {
            LeadFollowup::create($data);
            LeadActivity::create([
                'lead_id'     => $this->lead->id,
                'type'        => $this->fuType,
                'description' => "Follow-up scheduled: {$this->fuType} on {$this->fuScheduledAt}",
                'created_by'  => auth()->id(),
            ]);
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Follow-up scheduled.']);
        }

        $this->followupModal = false;
        $this->lead->refresh();
    }

    public function deleteFollowup(int $id): void
    {
        LeadFollowup::findOrFail($id)->delete();
        $this->lead->refresh();
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Follow-up deleted.']);
    }

    // ─── Tasks ────────────────────────────────────────────────────────────────

    public function openTaskCreate(): void
    {
        $this->editingTaskId = null;
        $this->tTitle       = '';
        $this->tType        = 'other';
        $this->tPriority    = 'medium';
        $this->tStatus      = 'todo';
        $this->tDueAt       = now()->addDay()->format('Y-m-d\TH:i');
        $this->tAssignedTo  = (string) auth()->id();
        $this->tDescription = '';
        $this->taskModal    = true;
    }

    public function openTaskEdit(int $id): void
    {
        $task = CrmTask::findOrFail($id);
        $this->editingTaskId = $task->id;
        $this->tTitle       = $task->title;
        $this->tType        = $task->type;
        $this->tPriority    = $task->priority;
        $this->tStatus      = $task->status;
        $this->tDueAt       = $task->due_at?->format('Y-m-d\TH:i') ?? '';
        $this->tAssignedTo  = (string) ($task->assigned_to ?? '');
        $this->tDescription = $task->description ?? '';
        $this->taskModal    = true;
    }

    public function saveTask(): void
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
            'related_type' => 'lead',
            'related_id'   => $this->lead->id,
            'completed_at' => $this->tStatus === 'done' ? now() : null,
            'created_by'   => auth()->id(),
        ];

        if ($this->editingTaskId) {
            CrmTask::findOrFail($this->editingTaskId)->update($data);
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Task updated.']);
        } else {
            CrmTask::create($data);
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Task created.']);
        }

        $this->taskModal = false;
        $this->lead->refresh();
    }

    public function markDoneTask(int $id): void
    {
        CrmTask::findOrFail($id)->update(['status' => 'done', 'completed_at' => now()]);
        $this->lead->refresh();
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Task marked as done.']);
    }

    public function deleteTask(int $id): void
    {
        CrmTask::findOrFail($id)->delete();
        $this->lead->refresh();
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Task deleted.']);
    }

    // ─── Files via existing files/fileables system ────────────────────────────

    public function attachSelectedFiles(): void
    {
        $existing = $this->lead->fileables()->pluck('file_id')->toArray();

        foreach ($this->leadFiles as $fileId) {
            if (! in_array($fileId, $existing)) {
                $this->lead->attachFile((int) $fileId, 'document');
            }
        }

        $this->lead->refresh();
        $this->leadFiles = $this->lead->fileables()->pluck('file_id')->toArray();
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Files attached.']);
    }

    public function detachFile(int $fileableId): void
    {
        $this->lead->fileables()->where('id', $fileableId)->delete();
        $this->lead->refresh();
        $this->leadFiles = $this->lead->fileables()->pluck('file_id')->toArray();
        $this->dispatch('toast', ['type' => 'success', 'message' => 'File removed.']);
    }

    // ─── Convert to Customer ─────────────────────────────────────────────────

    public function convertToCustomer(): void
    {
        abort_unless(auth()->user()?->can('crm.lead.convert'), 403);

        if ($this->lead->converted_customer_id) {
            $this->dispatch('toast', ['type' => 'warning', 'message' => 'Lead already converted.']);
            return;
        }

        DB::transaction(function () {
            $customer = Customer::create([
                'name'       => $this->lead->name,
                'phone'      => $this->lead->phone,
                'email'      => $this->lead->email,
                'address'    => $this->lead->address,
                'source'     => 'referral',
                'notes'      => "Converted from lead {$this->lead->lead_no}",
                'status'     => 'active',
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            $this->lead->update([
                'status'                => 'won',
                'converted_customer_id' => $customer->id,
                'converted_at'          => now(),
                'updated_by'            => auth()->id(),
            ]);

            LeadActivity::create([
                'lead_id'     => $this->lead->id,
                'type'        => 'converted',
                'description' => "Lead converted to customer #{$customer->customer_id}",
                'created_by'  => auth()->id(),
            ]);
        });

        $this->convertModal = false;
        $this->lead->refresh();
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Lead converted to customer successfully.']);
    }

    public function render()
    {
        $this->lead->load([
            'source', 'assignedUser', 'project', 'convertedCustomer',
            'activities.createdByUser',
            'followups.assignedUser',
            'tasks.assignedUser',
            'fileables.file',
        ]);

        $users = User::orderBy('name')->get();

        return view('livewire.admin.crm.lead.lead-show', [
            'lead'  => $this->lead,
            'users' => $users,
        ])->layout('layouts.admin.admin');
    }
}
