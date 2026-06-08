<?php

namespace App\Livewire\Admin\Marketing\Automation;

use App\Models\Automation;
use App\Models\CommunicationTemplate;
use Livewire\Component;
use Livewire\WithPagination;

class AutomationList extends Component
{
    use WithPagination;

    public bool  $drawerOpen = false;
    public ?int  $editingId  = null;

    public string $fName          = '';
    public string $fDescription   = '';
    public string $fTriggerEvent  = 'lead.created';
    public string $fActionType    = 'send_sms';
    public string $fTemplateId    = '';
    public string $fDelayMinutes  = '0';
    public string $fStatus        = 'active';

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('marketing.automation.view'), 403);
    }

    public function openCreate(): void
    {
        abort_unless(auth()->user()?->can('marketing.automation.create'), 403);
        $this->resetForm();
        $this->editingId  = null;
        $this->drawerOpen = true;
    }

    public function openEdit(int $id): void
    {
        abort_unless(auth()->user()?->can('marketing.automation.edit'), 403);
        $a = Automation::findOrFail($id);
        $this->editingId       = $a->id;
        $this->fName           = $a->name;
        $this->fDescription    = $a->description ?? '';
        $this->fTriggerEvent   = $a->trigger_event;
        $this->fActionType     = $a->action_type;
        $this->fTemplateId     = (string) ($a->template_id ?? '');
        $this->fDelayMinutes   = (string) $a->delay_minutes;
        $this->fStatus         = $a->status;
        $this->drawerOpen      = true;
    }

    public function save(): void
    {
        $this->validate([
            'fName'         => 'required|string|max:255',
            'fTriggerEvent' => 'required|string',
            'fActionType'   => 'required|in:send_sms,send_email,send_both',
            'fTemplateId'   => 'required|exists:communication_templates,id',
            'fDelayMinutes' => 'nullable|integer|min:0',
        ]);

        $data = [
            'name'          => $this->fName,
            'description'   => $this->fDescription ?: null,
            'trigger_event' => $this->fTriggerEvent,
            'action_type'   => $this->fActionType,
            'template_id'   => $this->fTemplateId ?: null,
            'delay_minutes' => (int) $this->fDelayMinutes,
            'status'        => $this->fStatus,
            'updated_by'    => auth()->id(),
        ];

        if ($this->editingId) {
            abort_unless(auth()->user()?->can('marketing.automation.edit'), 403);
            Automation::findOrFail($this->editingId)->update($data);
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Automation updated.']);
        } else {
            abort_unless(auth()->user()?->can('marketing.automation.create'), 403);
            $data['created_by'] = auth()->id();
            Automation::create($data);
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Automation created.']);
        }

        $this->closeDrawer();
    }

    public function toggle(int $id): void
    {
        abort_unless(auth()->user()?->can('marketing.automation.edit'), 403);
        $a = Automation::findOrFail($id);
        $a->update(['status' => $a->status === 'active' ? 'inactive' : 'active']);
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Automation '.($a->fresh()->status === 'active' ? 'activated' : 'deactivated').'.']);
    }

    public function delete(int $id): void
    {
        abort_unless(auth()->user()?->can('marketing.automation.delete'), 403);
        Automation::findOrFail($id)->delete();
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Automation deleted.']);
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
        $this->fTriggerEvent = 'lead.created';
        $this->fActionType   = 'send_sms';
        $this->fTemplateId   = '';
        $this->fDelayMinutes = '0';
        $this->fStatus       = 'active';
    }

    public function render()
    {
        abort_unless(auth()->user()?->can('marketing.automation.view'), 403);

        $automations = Automation::with(['template', 'createdByUser'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $kpi = [
            'total'    => Automation::count(),
            'active'   => Automation::where('status', 'active')->count(),
            'inactive' => Automation::where('status', 'inactive')->count(),
        ];

        $templates = CommunicationTemplate::where('is_active', true)->orderBy('name')->get();

        return view('livewire.admin.marketing.automation.automation-list', compact('automations', 'kpi', 'templates'))
            ->layout('layouts.admin.admin');
    }
}
