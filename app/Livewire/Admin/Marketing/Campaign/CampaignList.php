<?php

namespace App\Livewire\Admin\Marketing\Campaign;

use App\Jobs\SendMessageJob;
use App\Models\Campaign;
use App\Models\CommunicationTemplate;
use App\Models\MarketingAudience;
use App\Models\Message;
use App\Models\SmtpConfiguration;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class CampaignList extends Component
{
    use WithPagination;

    #[Url(as: 'q', history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $filterStatus = 'all';

    public bool  $drawerOpen = false;
    public ?int  $editingId  = null;

    // Form fields
    public string $fName         = '';
    public string $fDescription  = '';
    public string $fType         = 'sms';
    public string $fAudienceId   = '';
    public string $fTemplateId   = '';
    public string $fScheduleType = 'now';
    public string $fScheduledAt  = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('marketing.campaign.view'), 403);
    }

    public function updatedSearch(): void       { $this->resetPage(); }
    public function updatedFilterStatus(): void { $this->resetPage(); }

    public function openCreate(): void
    {
        abort_unless(auth()->user()?->can('marketing.campaign.create'), 403);
        $this->resetForm();
        $this->editingId  = null;
        $this->drawerOpen = true;
    }

    public function openEdit(int $id): void
    {
        abort_unless(auth()->user()?->can('marketing.campaign.edit'), 403);
        $c = Campaign::findOrFail($id);
        $this->editingId      = $c->id;
        $this->fName          = $c->name;
        $this->fDescription   = $c->description ?? '';
        $this->fType          = $c->type;
        $this->fAudienceId    = (string) ($c->audience_id ?? '');
        $this->fTemplateId    = (string) ($c->template_id ?? '');
        $this->fScheduleType  = $c->schedule_type;
        $this->fScheduledAt   = $c->scheduled_at?->format('Y-m-d\TH:i') ?? '';
        $this->drawerOpen     = true;
    }

    public function save(): void
    {
        $this->validate([
            'fName'       => 'required|string|max:255',
            'fType'       => 'required|in:sms,email,both',
            'fAudienceId' => 'required|exists:marketing_audiences,id',
            'fTemplateId' => 'required|exists:communication_templates,id',
            'fScheduledAt' => 'nullable|date',
        ]);

        $data = [
            'name'          => $this->fName,
            'description'   => $this->fDescription ?: null,
            'type'          => $this->fType,
            'audience_id'   => $this->fAudienceId ?: null,
            'template_id'   => $this->fTemplateId ?: null,
            'schedule_type' => $this->fScheduleType,
            'scheduled_at'  => $this->fScheduleType === 'scheduled' ? ($this->fScheduledAt ?: null) : null,
            'updated_by'    => auth()->id(),
        ];

        if ($this->editingId) {
            abort_unless(auth()->user()?->can('marketing.campaign.edit'), 403);
            Campaign::findOrFail($this->editingId)->update($data);
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Campaign updated.']);
        } else {
            abort_unless(auth()->user()?->can('marketing.campaign.create'), 403);
            $data['created_by'] = auth()->id();
            $data['status']     = 'draft';
            Campaign::create($data);
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Campaign created.']);
        }

        $this->closeDrawer();
    }

    public function launch(int $id): void
    {
        abort_unless(auth()->user()?->can('marketing.campaign.send'), 403);

        $campaign = Campaign::with(['audience', 'template'])->findOrFail($id);

        if (! $campaign->audience || ! $campaign->template) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Campaign needs an audience and template.']);
            return;
        }

        if (! in_array($campaign->status, ['draft', 'paused'])) {
            $this->dispatch('toast', ['type' => 'warning', 'message' => 'Campaign already running or completed.']);
            return;
        }

        // Guard: email campaigns require SMTP to be configured
        if (in_array($campaign->type, ['email', 'both']) && ! SmtpConfiguration::exists()) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'SMTP is not configured. Go to Settings → SMTP Config first.']);
            return;
        }

        $messageIds = DB::transaction(function () use ($campaign) {
            $campaign->update(['status' => 'running', 'started_at' => now()]);

            $members  = $campaign->audience->resolveMembers();
            $template = $campaign->template;
            $queued   = 0;
            $failed   = 0;
            $ids      = [];

            foreach ($members as $member) {
                $renderData = [
                    'name'  => $member['name'],
                    'phone' => $member['phone'] ?? '',
                    'email' => $member['email'] ?? '',
                ];

                $body = $template->render($renderData);

                // For type='both' we create one message per channel per member
                $channels = match($campaign->type) {
                    'both'  => ['email', 'sms'],
                    default => [$campaign->type],
                };

                foreach ($channels as $channel) {
                    $recipient = $channel === 'email'
                        ? ($member['email'] ?? null)
                        : ($member['phone'] ?? null);

                    if (! $recipient) {
                        $failed++;
                        continue;
                    }

                    $msg = Message::create([
                        'type'        => $channel,
                        'campaign_id' => $campaign->id,
                        'member_type' => $member['type'],
                        'member_id'   => $member['id'],
                        'recipient'   => $recipient,
                        'subject'     => $template->subject,
                        'body'        => $body,
                        'status'      => 'queued',
                        'sent_by'     => auth()->id(),
                    ]);

                    $ids[] = $msg->id;
                    $queued++;
                }
            }

            $campaign->update([
                'stats' => [
                    'queued' => $queued,
                    'failed' => $failed,
                    'total'  => $members->count(),
                ],
            ]);

            return $ids;
        });

        // Dispatch one job per message outside the transaction so DB commits first
        foreach ($messageIds as $msgId) {
            SendMessageJob::dispatch($msgId);
        }

        $count = count($messageIds);
        $this->dispatch('toast', ['type' => 'success', 'message' => "Campaign launched — {$count} message(s) queued for sending."]);
    }

    public function delete(int $id): void
    {
        abort_unless(auth()->user()?->can('marketing.campaign.delete'), 403);
        $c = Campaign::findOrFail($id);
        if ($c->status === 'running') {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Cannot delete a running campaign.']);
            return;
        }
        $c->delete();
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Campaign deleted.']);
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
        $this->fName         = '';
        $this->fDescription  = '';
        $this->fType         = 'sms';
        $this->fAudienceId   = '';
        $this->fTemplateId   = '';
        $this->fScheduleType = 'now';
        $this->fScheduledAt  = '';
    }

    public function render()
    {
        abort_unless(auth()->user()?->can('marketing.campaign.view'), 403);

        $campaigns = Campaign::with(['audience', 'template', 'createdByUser'])
            ->when($this->search, fn($q) => $q->where('name', 'like', '%'.$this->search.'%'))
            ->when($this->filterStatus !== 'all', fn($q) => $q->where('status', $this->filterStatus))
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $kpi = [
            'total'     => Campaign::count(),
            'running'   => Campaign::where('status', 'running')->count(),
            'completed' => Campaign::where('status', 'completed')->count(),
            'draft'     => Campaign::where('status', 'draft')->count(),
        ];

        $audiences = MarketingAudience::where('is_active', true)->orderBy('name')->get();
        $templates = CommunicationTemplate::where('is_active', true)->orderBy('name')->get();

        return view('livewire.admin.marketing.campaign.campaign-list', compact('campaigns', 'kpi', 'audiences', 'templates'))
            ->layout('layouts.admin.admin');
    }
}
