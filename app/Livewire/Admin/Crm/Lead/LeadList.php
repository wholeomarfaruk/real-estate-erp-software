<?php

namespace App\Livewire\Admin\Crm\Lead;

use App\Livewire\Traits\WithMediaPicker;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadSource;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class LeadList extends Component
{
    use WithPagination, WithMediaPicker;

    // ── Filters ──────────────────────────────────────────────────────────────
    #[Url(as: 'q', history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $filterStatus = 'all';

    #[Url(history: true)]
    public string $filterSource = 'all';

    #[Url(history: true)]
    public string $filterAssigned = 'all';

    // ── Drawer state ─────────────────────────────────────────────────────────
    public bool   $drawerOpen = false;
    public ?int   $editingId  = null;

    // ── Form fields ───────────────────────────────────────────────────────────
    public string $fName          = '';
    public string $fPhone         = '';
    public string $fEmail         = '';
    public string $fAddress       = '';
    public string $fLeadSourceId  = '';
    public string $fProjectId     = '';
    public string $fAssignedTo    = '';
    public string $fBudgetMin     = '';
    public string $fBudgetMax     = '';
    public string $fStatus        = 'new';
    public string $fClosedReason  = '';
    public string $fNotes         = '';

    // Social profiles
    public string $fFacebook   = '';
    public string $fWhatsapp   = '';
    public string $fInstagram  = '';
    public string $fLinkedin   = '';

    // Extra data
    public string $fOccupation        = '';
    public string $fCompany           = '';
    public string $fIncomeRange       = '';
    public string $fFamilySize        = '';
    public string $fPreferredLocation = '';
    public string $fUnitType          = '';
    public string $fRemarks           = '';

    // File IDs selected via MediaPicker
    public array $attachments = [];

    public int $formStep = 1;

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('crm.lead.view'), 403);
    }

    public function updatedSearch(): void         { $this->resetPage(); }
    public function updatedFilterStatus(): void   { $this->resetPage(); }
    public function updatedFilterSource(): void   { $this->resetPage(); }
    public function updatedFilterAssigned(): void { $this->resetPage(); }

    public function openCreate(): void
    {
        abort_unless(auth()->user()?->can('crm.lead.create'), 403);
        $this->resetForm();
        $this->editingId  = null;
        $this->drawerOpen = true;
    }

    public function openEdit(int $id): void
    {
        abort_unless(auth()->user()?->can('crm.lead.edit'), 403);

        $lead = Lead::findOrFail($id);

        $this->editingId            = $lead->id;
        $this->fName               = $lead->name;
        $this->fPhone              = $lead->phone;
        $this->fEmail              = $lead->email ?? '';
        $this->fAddress            = $lead->address ?? '';
        $this->fLeadSourceId       = (string) ($lead->lead_source_id ?? '');
        $this->fProjectId          = (string) ($lead->project_id ?? '');
        $this->fAssignedTo         = (string) ($lead->assigned_to ?? '');
        $this->fBudgetMin          = (string) ($lead->budget_min ?? '');
        $this->fBudgetMax          = (string) ($lead->budget_max ?? '');
        $this->fStatus             = $lead->status;
        $this->fClosedReason       = $lead->closed_reason ?? '';
        $this->fNotes              = $lead->notes ?? '';
        $this->fFacebook           = $lead->social_profiles['facebook'] ?? '';
        $this->fWhatsapp           = $lead->social_profiles['whatsapp'] ?? '';
        $this->fInstagram          = $lead->social_profiles['instagram'] ?? '';
        $this->fLinkedin           = $lead->social_profiles['linkedin'] ?? '';
        $this->fOccupation         = $lead->extra_data['occupation'] ?? '';
        $this->fCompany            = $lead->extra_data['company'] ?? '';
        $this->fIncomeRange        = $lead->extra_data['income_range'] ?? '';
        $this->fFamilySize         = (string) ($lead->extra_data['family_size'] ?? '');
        $this->fPreferredLocation  = $lead->extra_data['preferred_location'] ?? '';
        $this->fUnitType           = $lead->extra_data['unit_type'] ?? '';
        $this->fRemarks            = $lead->extra_data['remarks'] ?? '';
        $this->attachments         = $lead->fileables()->pluck('file_id')->toArray();
        $this->formStep            = 1;
        $this->drawerOpen          = true;
    }

    public function saveLead(): void
    {
        $this->validate([
            'fName'  => 'required|string|max:255',
            'fPhone' => 'required|string|max:50',
        ], [
            'fName.required'  => 'Lead name is required.',
            'fPhone.required' => 'Phone number is required.',
        ]);

        $socialProfiles = array_filter([
            'facebook'  => $this->fFacebook,
            'whatsapp'  => $this->fWhatsapp,
            'instagram' => $this->fInstagram,
            'linkedin'  => $this->fLinkedin,
        ]);

        $extraData = array_filter([
            'occupation'         => $this->fOccupation,
            'company'            => $this->fCompany,
            'income_range'       => $this->fIncomeRange,
            'family_size'        => $this->fFamilySize ? (int) $this->fFamilySize : null,
            'preferred_location' => $this->fPreferredLocation,
            'unit_type'          => $this->fUnitType,
            'remarks'            => $this->fRemarks,
        ]);

        $data = [
            'name'            => $this->fName,
            'phone'           => $this->fPhone,
            'email'           => $this->fEmail ?: null,
            'address'         => $this->fAddress ?: null,
            'lead_source_id'  => $this->fLeadSourceId ?: null,
            'project_id'      => $this->fProjectId ?: null,
            'assigned_to'     => $this->fAssignedTo ?: null,
            'budget_min'      => $this->fBudgetMin ?: null,
            'budget_max'      => $this->fBudgetMax ?: null,
            'status'          => $this->fStatus,
            'closed_reason'   => $this->fClosedReason ?: null,
            'notes'           => $this->fNotes ?: null,
            'social_profiles' => $socialProfiles ?: null,
            'extra_data'      => $extraData ?: null,
        ];

        DB::transaction(function () use ($data) {
            if ($this->editingId) {
                abort_unless(auth()->user()?->can('crm.lead.edit'), 403);
                $lead = Lead::findOrFail($this->editingId);
                $oldStatus = $lead->status;
                $data['updated_by'] = auth()->id();
                $lead->update($data);

                if ($oldStatus !== $this->fStatus) {
                    LeadActivity::create([
                        'lead_id'     => $lead->id,
                        'type'        => 'status_change',
                        'description' => "Status changed from {$oldStatus} to {$this->fStatus}",
                        'old_value'   => $oldStatus,
                        'new_value'   => $this->fStatus,
                        'created_by'  => auth()->id(),
                    ]);
                }

                $this->syncLeadFiles($lead);
                $this->dispatch('toast', ['type' => 'success', 'message' => 'Lead updated successfully.']);
            } else {
                abort_unless(auth()->user()?->can('crm.lead.create'), 403);
                $data['created_by'] = auth()->id();
                $data['updated_by'] = auth()->id();
                $lead = Lead::create($data);

                LeadActivity::create([
                    'lead_id'     => $lead->id,
                    'type'        => 'note',
                    'description' => 'Lead created.',
                    'created_by'  => auth()->id(),
                ]);

                $this->syncLeadFiles($lead);
                $this->dispatch('toast', ['type' => 'success', 'message' => 'Lead created successfully.']);
            }
        });

        $this->closeDrawer();
        $this->resetPage();
    }

    private function syncLeadFiles(Lead $lead): void
    {
        $existing = $lead->fileables()->pluck('file_id')->toArray();
        $toAdd    = array_diff($this->attachments, $existing);
        $toRemove = array_diff($existing, $this->attachments);

        foreach ($toAdd as $fileId) {
            $lead->attachFile((int) $fileId, 'document');
        }

        if ($toRemove) {
            $lead->fileables()->whereIn('file_id', $toRemove)->delete();
        }
    }

    public function deleteLead(int $id): void
    {
        abort_unless(auth()->user()?->can('crm.lead.delete'), 403);

        $lead = Lead::findOrFail($id);
        $lead->delete();
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Lead deleted.']);
        $this->resetPage();
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
        $this->fName              = '';
        $this->fPhone             = '';
        $this->fEmail             = '';
        $this->fAddress           = '';
        $this->fLeadSourceId      = '';
        $this->fProjectId         = '';
        $this->fAssignedTo        = '';
        $this->fBudgetMin         = '';
        $this->fBudgetMax         = '';
        $this->fStatus            = 'new';
        $this->fClosedReason      = '';
        $this->fNotes             = '';
        $this->fFacebook          = '';
        $this->fWhatsapp          = '';
        $this->fInstagram         = '';
        $this->fLinkedin          = '';
        $this->fOccupation        = '';
        $this->fCompany           = '';
        $this->fIncomeRange       = '';
        $this->fFamilySize        = '';
        $this->fPreferredLocation = '';
        $this->fUnitType          = '';
        $this->fRemarks           = '';
        $this->attachments        = [];
        $this->formStep           = 1;
    }

    public function render()
    {
        abort_unless(auth()->user()?->can('crm.lead.view'), 403);

        $leads = Lead::query()
            ->with(['source', 'assignedUser', 'project'])
            ->when($this->search, function ($q) {
                $s = '%' . $this->search . '%';
                $q->where(fn ($i) => $i
                    ->where('name', 'like', $s)
                    ->orWhere('lead_no', 'like', $s)
                    ->orWhere('phone', 'like', $s)
                    ->orWhere('email', 'like', $s)
                );
            })
            ->when($this->filterStatus !== 'all', fn ($q) => $q->where('status', $this->filterStatus))
            ->when($this->filterSource !== 'all', fn ($q) => $q->where('lead_source_id', $this->filterSource))
            ->when($this->filterAssigned !== 'all', fn ($q) => $q->where('assigned_to', $this->filterAssigned))
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $kpi = [
            'total'     => Lead::count(),
            'new'       => Lead::where('status', 'new')->count(),
            'qualified' => Lead::whereIn('status', ['qualified', 'site_visit', 'negotiation'])->count(),
            'won'       => Lead::where('status', 'won')->count(),
            'lost'      => Lead::where('status', 'lost')->count(),
        ];

        $sources  = LeadSource::where('is_active', true)->orderBy('name')->get();
        $projects = Project::orderBy('name')->get();
        $users    = User::orderBy('name')->get();

        return view('livewire.admin.crm.lead.lead-list', compact('leads', 'kpi', 'sources', 'projects', 'users'))
            ->layout('layouts.admin.admin');
    }
}
