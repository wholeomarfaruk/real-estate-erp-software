<?php

namespace App\Livewire\Admin\Marketing\Message;

use App\Jobs\CheckAlphaSmsDeliveryStatusJob;
use App\Jobs\SendMessageJob;
use App\Models\CommunicationTemplate;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Message;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class MessageList extends Component
{
    use WithPagination;

    #[Url(as: 'q', history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $filterType   = 'all';

    #[Url(history: true)]
    public string $filterStatus = 'all';

    // Bulk selection (delivery status check / resend)
    public array $selected = [];
    public bool  $selectAll = false;

    // Details view modal
    public bool $viewModal = false;
    public ?int $viewingMessageId = null;

    // Individual send modal
    public bool   $sendModal    = false;
    public string $sType        = 'sms';
    public string $sMemberType  = 'lead';
    public string $sMemberId    = '';
    public string $sRecipient   = '';
    public string $sSubject     = '';
    public string $sBody        = '';
    public string $sTemplateId  = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('marketing.message.view'), 403);
    }

    public function updatedSearch(): void       { $this->resetPage(); $this->clearSelection(); }
    public function updatedFilterType(): void   { $this->resetPage(); $this->clearSelection(); }
    public function updatedFilterStatus(): void { $this->resetPage(); $this->clearSelection(); }

    public function updatedSelectAll(bool $value): void
    {
        $this->selected = $value ? $this->actionableMessageIdsOnPage() : [];
    }

    public function clearSelection(): void
    {
        $this->selected  = [];
        $this->selectAll = false;
    }

    /** IDs of currently-listed messages eligible for at least one bulk action (check status or resend). */
    private function actionableMessageIdsOnPage(): array
    {
        return $this->currentPageQuery()
            ->where(fn($q) => $q
                ->where(fn($i) => $i->where('type', 'sms')->where('status', 'sent')->whereNotNull('provider_message_id'))
                ->orWhere('status', 'failed')
            )
            ->pluck('id')
            ->all();
    }

    private function currentPageQuery()
    {
        return Message::query()
            ->when($this->search, fn($q) => $q->where(fn($i) =>
                $i->where('recipient', 'like', '%'.$this->search.'%')
                  ->orWhere('body', 'like', '%'.$this->search.'%')
            ))
            ->when($this->filterType !== 'all', fn($q) => $q->where('type', $this->filterType))
            ->when($this->filterStatus !== 'all', fn($q) => $q->where('status', $this->filterStatus))
            ->orderBy('created_at', 'desc')
            ->forPage($this->getPage(), 20);
    }

    public function openSendModal(): void
    {
        abort_unless(auth()->user()?->can('marketing.message.send'), 403);
        $this->resetSendForm();
        $this->sendModal = true;
    }

    public function updatedSMemberId(): void
    {
        if (! $this->sMemberId) return;

        $model = $this->sMemberType === 'lead'
            ? Lead::find($this->sMemberId)
            : Customer::find($this->sMemberId);

        if ($model) {
            $this->sRecipient = $this->sType === 'email' ? ($model->email ?? '') : $model->phone;
        }
    }

    public function updatedSTemplateId(): void
    {
        if (! $this->sTemplateId) return;
        $tpl = CommunicationTemplate::find($this->sTemplateId);
        if ($tpl) {
            $this->sBody    = $tpl->body;
            $this->sSubject = $tpl->subject ?? '';
            $this->sType    = in_array($tpl->type, ['sms','email']) ? $tpl->type : $this->sType;
        }
    }

    public function sendMessage(): void
    {
        abort_unless(auth()->user()?->can('marketing.message.send'), 403);

        $this->validate([
            'sType'      => 'required|in:sms,email',
            'sRecipient' => 'required|string',
            'sBody'      => 'required|string',
        ]);

        // Replace {name} and other placeholders if a member is selected
        $body    = $this->sBody;
        $subject = $this->sSubject ?: null;

        if ($this->sMemberId) {
            $member = $this->sMemberType === 'lead'
                ? Lead::find($this->sMemberId)
                : Customer::find($this->sMemberId);

            if ($member) {
                $vars = [
                    'name'  => $member->name,
                    'phone' => $member->phone,
                    'email' => $member->email ?? '',
                ];
                foreach ($vars as $key => $value) {
                    $body    = str_replace('{' . $key . '}', $value, $body);
                    $subject = $subject ? str_replace('{' . $key . '}', $value, $subject) : $subject;
                }
            }
        }

        $message = Message::create([
            'type'        => $this->sType,
            'member_type' => $this->sMemberId ? $this->sMemberType : null,
            'member_id'   => $this->sMemberId ?: null,
            'recipient'   => $this->sRecipient,
            'subject'     => $subject,
            'body'        => $body,
            'status'      => 'queued',
            'sent_by'     => auth()->id(),
        ]);

        SendMessageJob::dispatch($message->id);

        $this->sendModal = false;
        $this->resetSendForm();
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Message queued for sending.']);
    }

    public function checkDeliveryStatus(int $messageId): void
    {
        abort_unless(auth()->user()?->can('marketing.message.view'), 403);

        $message = Message::find($messageId);

        if (!$message || $message->type !== 'sms' || !$message->provider_message_id) {
            $this->dispatch('toast', [
                'type' => 'warning',
                'message' => 'SMS message ID not found or already delivered.',
            ]);
            return;
        }

        CheckAlphaSmsDeliveryStatusJob::dispatch($messageId);

        $this->dispatch('toast', [
            'type' => 'info',
            'message' => 'Checking delivery status...',
        ]);
    }

    /** Check delivery status for every currently-selected message. */
    public function bulkCheckDeliveryStatus(): void
    {
        abort_unless(auth()->user()?->can('marketing.message.view'), 403);

        $ids = Message::query()
            ->whereIn('id', $this->selected)
            ->where('type', 'sms')
            ->whereNotNull('provider_message_id')
            ->pluck('id');

        if ($ids->isEmpty()) {
            $this->dispatch('toast', [
                'type' => 'warning',
                'message' => 'No checkable SMS messages selected.',
            ]);
            return;
        }

        foreach ($ids as $id) {
            CheckAlphaSmsDeliveryStatusJob::dispatch((int) $id);
        }

        $this->dispatch('toast', [
            'type' => 'info',
            'message' => "Checking delivery status for {$ids->count()} message(s)...",
        ]);

        $this->clearSelection();
    }

    /** Requeue a single failed message for sending again. */
    public function resendMessage(int $messageId): void
    {
        abort_unless(auth()->user()?->can('marketing.message.send'), 403);

        $message = Message::find($messageId);

        if (!$message || $message->status !== 'failed') {
            $this->dispatch('toast', [
                'type' => 'warning',
                'message' => 'Only failed messages can be resent.',
            ]);
            return;
        }

        $this->requeue($message);

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Message requeued for sending.']);
    }

    /** Requeue every currently-selected failed message for sending again. */
    public function bulkResendMessages(): void
    {
        abort_unless(auth()->user()?->can('marketing.message.send'), 403);

        $messages = Message::query()
            ->whereIn('id', $this->selected)
            ->where('status', 'failed')
            ->get();

        if ($messages->isEmpty()) {
            $this->dispatch('toast', [
                'type' => 'warning',
                'message' => 'No failed messages selected.',
            ]);
            return;
        }

        foreach ($messages as $message) {
            $this->requeue($message);
        }

        $this->dispatch('toast', [
            'type' => 'success',
            'message' => "{$messages->count()} message(s) requeued for sending.",
        ]);

        $this->clearSelection();
    }

    public function viewMessage(int $messageId): void
    {
        abort_unless(auth()->user()?->can('marketing.message.view'), 403);

        $this->viewingMessageId = $messageId;
        $this->viewModal = true;
    }

    public function closeViewModal(): void
    {
        $this->viewModal = false;
        $this->viewingMessageId = null;
    }

    private function requeue(Message $message): void
    {
        $message->update([
            'status'              => 'queued',
            'provider_response'   => null,
            'provider_error_code' => null,
            'validation_error'    => null,
            'response_snapshot'   => null,
        ]);
        $message->addTimelineEvent('requeued', ['by' => auth()->id()]);

        SendMessageJob::dispatch($message->id);
    }

    private function resetSendForm(): void
    {
        $this->sType       = 'sms';
        $this->sMemberType = 'lead';
        $this->sMemberId   = '';
        $this->sRecipient  = '';
        $this->sSubject    = '';
        $this->sBody       = '';
        $this->sTemplateId = '';
    }

    public function render()
    {
        abort_unless(auth()->user()?->can('marketing.message.view'), 403);

        $messages = Message::with(['campaign', 'sentByUser'])
            ->when($this->search, fn($q) => $q->where(fn($i) =>
                $i->where('recipient', 'like', '%'.$this->search.'%')
                  ->orWhere('body', 'like', '%'.$this->search.'%')
            ))
            ->when($this->filterType !== 'all', fn($q) => $q->where('type', $this->filterType))
            ->when($this->filterStatus !== 'all', fn($q) => $q->where('status', $this->filterStatus))
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $kpi = [
            'total'   => Message::count(),
            'sent'    => Message::where('status', 'sent')->count(),
            'failed'  => Message::where('status', 'failed')->count(),
            'sms'     => Message::where('type', 'sms')->count(),
            'email'   => Message::where('type', 'email')->count(),
        ];

        $templates = CommunicationTemplate::where('is_active', true)->orderBy('name')->get();
        $leads     = Lead::orderBy('name')->get(['id','name','phone','email']);
        $customers = Customer::orderBy('name')->get(['id','name','phone','email']);

        $viewingMessage = $this->viewingMessageId
            ? Message::with(['campaign', 'sentByUser'])->find($this->viewingMessageId)
            : null;

        return view('livewire.admin.marketing.message.message-list', compact('messages', 'kpi', 'templates', 'leads', 'customers', 'viewingMessage'))
            ->layout('layouts.admin.admin');
    }
}
