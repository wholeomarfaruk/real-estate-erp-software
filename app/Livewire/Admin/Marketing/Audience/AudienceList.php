<?php

namespace App\Livewire\Admin\Marketing\Audience;

use App\Models\AudienceMember;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\MarketingAudience;
use App\Models\Project;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class AudienceList extends Component
{
    use WithPagination;

    #[Url(as: 'q', history: true)]
    public string $search = '';

    public bool  $drawerOpen = false;
    public ?int  $editingId  = null;

    // Form fields
    public string $fName        = '';
    public string $fDescription = '';
    public string $fType        = 'dynamic';
    public bool   $fIsActive    = true;

    // Dynamic filter fields
    public array  $fLeadStatus       = [];
    public string $fProjectId        = '';
    public string $fSourceId         = '';
    public string $fBudgetMin        = '';
    public bool   $fIncludeCustomers = false;
    public string $fCustomerStatus   = '';

    // Preview
    public int $previewCount = 0;

    // Static member management
    public string $memberSearch  = '';
    public array  $searchResults = [];
    public array  $staticMembers = [];

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('marketing.audience.view'), 403);
    }

    public function updatedSearch(): void { $this->resetPage(); }

    public function updatedMemberSearch(): void
    {
        $q = trim($this->memberSearch);
        if (strlen($q) < 1) {
            $this->searchResults = [];
            return;
        }

        $addedIds = array_column($this->staticMembers, 'id');
        $like     = '%' . $q . '%';

        $this->searchResults = Lead::where(fn ($query) =>
                $query->where('name', 'like', $like)
                      ->orWhere('phone', 'like', $like)
                      ->orWhere('lead_no', 'like', $like)
            )
            ->when($addedIds, fn ($query) => $query->whereNotIn('id', $addedIds))
            ->limit(8)
            ->get()
            ->map(fn (Lead $lead) => [
                'id'           => $lead->id,
                'name'         => $lead->name,
                'lead_no'      => $lead->lead_no,
                'phone'        => $lead->phone,
                'status'       => $lead->status_label,
                'status_color' => $lead->status_color,
            ])
            ->toArray();
    }

    public function addMember(int $leadId): void
    {
        if (collect($this->staticMembers)->contains('id', $leadId)) {
            return;
        }

        $lead = Lead::findOrFail($leadId);
        $this->staticMembers[] = [
            'id'           => $lead->id,
            'name'         => $lead->name,
            'lead_no'      => $lead->lead_no,
            'phone'        => $lead->phone,
            'status'       => $lead->status_label,
            'status_color' => $lead->status_color,
        ];

        $this->memberSearch  = '';
        $this->searchResults = [];
    }

    public function removeMember(int $leadId): void
    {
        $this->staticMembers = array_values(
            array_filter($this->staticMembers, fn ($m) => $m['id'] !== $leadId)
        );
    }

    public function previewAudience(): void
    {
        $filters = $this->buildFilters();
        $audience = new MarketingAudience(['type' => $this->fType, 'filters' => $filters]);
        $this->previewCount = $audience->resolveMembers()->count();
        $this->dispatch('toast', ['type' => 'info', 'message' => "Preview: {$this->previewCount} recipients"]);
    }

    public function openCreate(): void
    {
        abort_unless(auth()->user()?->can('marketing.audience.create'), 403);
        $this->resetForm();
        $this->editingId  = null;
        $this->drawerOpen = true;
    }

    public function openEdit(int $id): void
    {
        abort_unless(auth()->user()?->can('marketing.audience.edit'), 403);
        $a = MarketingAudience::findOrFail($id);
        $filters = $a->filters ?? [];

        $this->editingId          = $a->id;
        $this->fName              = $a->name;
        $this->fDescription       = $a->description ?? '';
        $this->fType              = $a->type;
        $this->fIsActive          = $a->is_active;
        $this->fLeadStatus        = $filters['lead_status'] ?? [];
        $this->fProjectId         = (string) ($filters['project_id'] ?? '');
        $this->fSourceId          = (string) ($filters['source_id'] ?? '');
        $this->fBudgetMin         = (string) ($filters['budget_min'] ?? '');
        $this->fIncludeCustomers  = $filters['include_customers'] ?? false;
        $this->fCustomerStatus    = $filters['customer_status'] ?? '';

        if ($a->type === 'static') {
            $this->staticMembers = $a->members()
                ->where('member_type', 'lead')
                ->get()
                ->map(function (AudienceMember $m) {
                    $lead = Lead::find($m->member_id);
                    if (! $lead) return null;
                    return [
                        'id'           => $lead->id,
                        'name'         => $lead->name,
                        'lead_no'      => $lead->lead_no,
                        'phone'        => $lead->phone,
                        'status'       => $lead->status_label,
                        'status_color' => $lead->status_color,
                    ];
                })
                ->filter()
                ->values()
                ->toArray();
        }

        $this->drawerOpen = true;
    }

    public function save(): void
    {
        $this->validate([
            'fName' => 'required|string|max:255',
            'fType' => 'required|in:static,dynamic',
        ]);

        $data = [
            'name'        => $this->fName,
            'description' => $this->fDescription ?: null,
            'type'        => $this->fType,
            'filters'     => $this->fType === 'dynamic' ? $this->buildFilters() : null,
            'is_active'   => $this->fIsActive,
            'updated_by'  => auth()->id(),
        ];

        if ($this->editingId) {
            abort_unless(auth()->user()?->can('marketing.audience.edit'), 403);
            $audience = MarketingAudience::findOrFail($this->editingId);
            $audience->update($data);
        } else {
            abort_unless(auth()->user()?->can('marketing.audience.create'), 403);
            $data['created_by'] = auth()->id();
            $audience = MarketingAudience::create($data);
        }

        if ($this->fType === 'static') {
            $audience->members()->delete();
            foreach ($this->staticMembers as $m) {
                $audience->members()->create([
                    'member_type' => 'lead',
                    'member_id'   => $m['id'],
                ]);
            }
        }

        $audience->syncMemberCount();
        $this->dispatch('toast', ['type' => 'success', 'message' => $this->editingId ? 'Audience updated.' : 'Audience created.']);

        $this->closeDrawer();
    }

    public function delete(int $id): void
    {
        abort_unless(auth()->user()?->can('marketing.audience.delete'), 403);
        MarketingAudience::findOrFail($id)->delete();
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Audience deleted.']);
    }

    public function refresh(int $id): void
    {
        $audience = MarketingAudience::findOrFail($id);
        $audience->syncMemberCount();
        $this->dispatch('toast', ['type' => 'success', 'message' => "Count refreshed: {$audience->member_count}"]);
    }

    public function closeDrawer(): void
    {
        $this->drawerOpen = false;
        $this->editingId  = null;
        $this->resetValidation();
        $this->resetForm();
    }

    private function buildFilters(): array
    {
        return array_filter([
            'lead_status'       => $this->fLeadStatus ?: null,
            'project_id'        => $this->fProjectId ?: null,
            'source_id'         => $this->fSourceId ?: null,
            'budget_min'        => $this->fBudgetMin ?: null,
            'include_customers' => $this->fIncludeCustomers ?: null,
            'customer_status'   => $this->fCustomerStatus ?: null,
        ]);
    }

    private function resetForm(): void
    {
        $this->fName             = '';
        $this->fDescription      = '';
        $this->fType             = 'dynamic';
        $this->fIsActive         = true;
        $this->fLeadStatus       = [];
        $this->fProjectId        = '';
        $this->fSourceId         = '';
        $this->fBudgetMin        = '';
        $this->fIncludeCustomers = false;
        $this->fCustomerStatus   = '';
        $this->previewCount      = 0;
        $this->memberSearch      = '';
        $this->searchResults     = [];
        $this->staticMembers     = [];
    }

    public function render()
    {
        abort_unless(auth()->user()?->can('marketing.audience.view'), 403);

        $audiences = MarketingAudience::query()
            ->when($this->search, fn($q) => $q->where('name', 'like', '%'.$this->search.'%'))
            ->withCount('members')
            ->orderBy('name')
            ->paginate(15);

        $projects = Project::orderBy('name')->get();
        $sources  = LeadSource::where('is_active', true)->orderBy('name')->get();

        return view('livewire.admin.marketing.audience.audience-list', compact('audiences', 'projects', 'sources'))
            ->layout('layouts.admin.admin');
    }
}
