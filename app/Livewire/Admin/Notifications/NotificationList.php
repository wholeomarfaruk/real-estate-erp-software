<?php

namespace App\Livewire\Admin\Notifications;

use App\Enums\Notification\NotificationType;
use App\Models\Notification;
use App\Models\NotificationTemplate;
use App\Models\User;
use App\Services\Notification\NotificationAudienceResolver;
use App\Services\Notification\NotificationDispatcher;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

class NotificationList extends Component
{
    use WithPagination;

    #[Url(as: 'q', history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $filterStatus = 'all';

    public bool $drawerOpen = false;
    public ?int $editingId  = null;

    // Form fields
    public string $fTitle        = '';
    public string $fBody         = '';
    public string $fActionUrl    = '';
    public string $fType         = 'system';
    public string $fBadge        = 'normal';
    public string $fTemplateId   = '';
    public string $fAudienceType = 'single';
    public string $fAudienceUserId = '';
    public array  $fAudienceUserIds = [];
    public string $fAudienceRole   = '';
    public string $fScheduleType   = 'now';
    public string $fScheduledAt    = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('notification.view'), 403);
    }

    public function updatedSearch(): void       { $this->resetPage(); }
    public function updatedFilterStatus(): void { $this->resetPage(); }

    public function updatedFTemplateId(): void
    {
        if (! $this->fTemplateId) {
            return;
        }

        $template = NotificationTemplate::find($this->fTemplateId);
        if ($template) {
            $this->fTitle = $template->title;
            $this->fBody  = $template->body;
            $this->fType  = $template->type->value;
        }
    }

    public function openCreate(): void
    {
        abort_unless(auth()->user()?->can('notification.create'), 403);
        $this->resetForm();
        $this->editingId  = null;
        $this->drawerOpen = true;
    }

    public function openEdit(int $id): void
    {
        abort_unless(auth()->user()?->can('notification.edit'), 403);

        $n = Notification::findOrFail($id);

        if (! in_array($n->status, ['draft', 'scheduled'])) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Only draft or scheduled notifications can be edited.']);
            return;
        }

        $this->editingId     = $n->id;
        $this->fTitle        = $n->title;
        $this->fBody         = $n->body;
        $this->fActionUrl    = $n->action_url ?? '';
        $this->fType         = $n->type->value;
        $this->fBadge        = $n->badge ?? 'normal';
        $this->fAudienceType = $n->audience_type ?? 'single';
        $this->fAudienceUserId  = (string) ($n->audience_value['user_id'] ?? '');
        $this->fAudienceUserIds = $n->audience_value['user_ids'] ?? [];
        $this->fAudienceRole    = $n->audience_value['role'] ?? '';
        $this->fScheduleType = $n->status === 'scheduled' ? 'scheduled' : 'now';
        $this->fScheduledAt  = $n->scheduled_at?->format('Y-m-d\TH:i') ?? '';
        $this->drawerOpen    = true;
    }

    public function save(): void
    {
        $this->validate([
            'fTitle'        => 'required|string|max:255',
            'fBody'         => 'required|string',
            'fActionUrl'    => 'nullable|string|max:255',
            'fType'         => 'required|in:' . implode(',', array_column(NotificationType::cases(), 'value')),
            'fBadge'        => 'required|in:normal,high,urgent',
            'fAudienceType' => 'required|in:single,multiple,role,all',
            'fAudienceUserId'  => 'required_if:fAudienceType,single|nullable|exists:users,id',
            'fAudienceUserIds' => 'required_if:fAudienceType,multiple|array',
            'fAudienceRole'    => 'required_if:fAudienceType,role|nullable|string',
            'fScheduledAt'     => 'required_if:fScheduleType,scheduled|nullable|date',
        ]);

        $audienceValue = match ($this->fAudienceType) {
            'single'   => ['user_id' => (int) $this->fAudienceUserId],
            'multiple' => ['user_ids' => array_map('intval', $this->fAudienceUserIds)],
            'role'     => ['role' => $this->fAudienceRole],
            'all'      => [],
        };

        $data = [
            'title'          => $this->fTitle,
            'body'           => $this->fBody,
            'action_url'     => $this->fActionUrl ?: null,
            'type'           => $this->fType,
            'badge'          => $this->fBadge,
            'audience_type'  => $this->fAudienceType,
            'audience_value' => $audienceValue,
            'scheduled_at'   => $this->fScheduleType === 'scheduled' ? ($this->fScheduledAt ?: null) : null,
            'status'         => $this->fScheduleType === 'scheduled' ? 'scheduled' : 'draft',
        ];

        if ($this->editingId) {
            abort_unless(auth()->user()?->can('notification.edit'), 403);
            Notification::findOrFail($this->editingId)->update($data);
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Notification updated.']);
        } else {
            abort_unless(auth()->user()?->can('notification.create'), 403);
            $data['created_by'] = auth()->id();
            Notification::create($data);
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Notification saved.']);
        }

        $this->closeDrawer();
    }

    public function sendNow(int $id, NotificationAudienceResolver $resolver, NotificationDispatcher $dispatcher): void
    {
        abort_unless(auth()->user()?->can('notification.send'), 403);

        $notification = Notification::findOrFail($id);

        if (! in_array($notification->status, ['draft', 'scheduled'])) {
            $this->dispatch('toast', ['type' => 'warning', 'message' => 'This notification was already sent.']);
            return;
        }

        $recipients = $resolver->resolve($notification->audience_type ?? '', $notification->audience_value ?? []);

        if ($recipients->isEmpty()) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'No recipients found for the selected audience.']);
            return;
        }

        $dispatcher->dispatchExisting($notification, $recipients);

        $this->dispatch('toast', ['type' => 'success', 'message' => "Sent to {$recipients->count()} recipient(s)."]);
    }

    public function delete(int $id): void
    {
        abort_unless(auth()->user()?->can('notification.delete'), 403);

        $n = Notification::findOrFail($id);

        if ($n->status === 'sent') {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Cannot delete an already-sent notification.']);
            return;
        }

        $n->delete();
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Notification deleted.']);
    }

    public function closeDrawer(): void
    {
        $this->drawerOpen = false;
        $this->editingId  = null;
        $this->resetValidation();
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->fTitle           = '';
        $this->fBody            = '';
        $this->fActionUrl       = '';
        $this->fType            = 'system';
        $this->fBadge           = 'normal';
        $this->fTemplateId      = '';
        $this->fAudienceType    = 'single';
        $this->fAudienceUserId  = '';
        $this->fAudienceUserIds = [];
        $this->fAudienceRole    = '';
        $this->fScheduleType    = 'now';
        $this->fScheduledAt     = '';
    }

    public function render()
    {
        abort_unless(auth()->user()?->can('notification.view'), 403);

        $notifications = Notification::with('createdByUser')
            ->when($this->search, fn ($q) => $q->where('title', 'like', '%' . $this->search . '%'))
            ->when($this->filterStatus !== 'all', fn ($q) => $q->where('status', $this->filterStatus))
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $kpi = [
            'total'     => Notification::count(),
            'draft'     => Notification::where('status', 'draft')->count(),
            'scheduled' => Notification::where('status', 'scheduled')->count(),
            'sent'      => Notification::where('status', 'sent')->count(),
        ];

        return view('livewire.admin.notifications.notification-list', [
            'notifications' => $notifications,
            'kpi'           => $kpi,
            'types'         => NotificationType::cases(),
            'templates'     => NotificationTemplate::where('is_active', true)->orderBy('name')->get(),
            'users'         => User::orderBy('name')->get(['id', 'name', 'email']),
            'roles'         => Role::orderBy('name')->get(),
        ])->layout('layouts.admin.admin');
    }
}
