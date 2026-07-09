<?php

namespace App\Livewire\Admin\Notifications;

use App\Models\NotificationRecipient;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class Bell extends Component
{
    use WithPagination;

    public bool $modalOpen = false;

    public string $search = '';

    public string $filter = 'all';

    public ?int $selectedRecipientId = null;

    public function openInbox(): void
    {
        $this->modalOpen = true;
    }

    public function closeModal(): void
    {
        $this->modalOpen = false;
        $this->selectedRecipientId = null;
        $this->search = '';
        $this->filter = 'all';
        $this->resetPage();
    }

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedFilter(): void  { $this->resetPage(); }

    public function select(int $recipientId): void
    {
        NotificationRecipient::forUser(Auth::user())
            ->whereKey($recipientId)
            ->update(['is_read' => true, 'read_at' => now()]);

        $this->selectedRecipientId = $recipientId;
    }

    #[On('mark-all-notifications-read')]
    public function markAllAsRead(): void
    {
        NotificationRecipient::forUser(Auth::user())
            ->unread()
            ->update(['is_read' => true, 'read_at' => now()]);
    }

    public function render()
    {
        $unreadCount = NotificationRecipient::forUser(Auth::user())->unread()->count();

        $inboxRecipients = NotificationRecipient::forUser(Auth::user())
            ->with('notification')
            ->when($this->filter === 'unread', fn ($q) => $q->unread())
            ->when($this->search, fn ($q) => $q->whereHas('notification', function ($q2) {
                $q2->where('title', 'like', '%' . $this->search . '%')
                    ->orWhere('body', 'like', '%' . $this->search . '%');
            }))
            ->latest()
            ->paginate(8);

        $selected = $this->selectedRecipientId
            ? NotificationRecipient::with('notification')->find($this->selectedRecipientId)
            : null;

        return view('livewire.admin.notifications.bell', [
            'unreadCount'      => $unreadCount,
            'inboxRecipients'  => $inboxRecipients,
            'selected'         => $selected,
        ]);
    }
}
