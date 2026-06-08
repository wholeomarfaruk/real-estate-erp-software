<?php

namespace App\Livewire\Admin\Marketing\Audience;

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

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('marketing.audience.view'), 403);
    }

    public function updatedSearch(): void { $this->resetPage(); }

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
        $this->drawerOpen         = true;
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
            $audience->syncMemberCount();
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Audience updated.']);
        } else {
            abort_unless(auth()->user()?->can('marketing.audience.create'), 403);
            $data['created_by'] = auth()->id();
            $audience = MarketingAudience::create($data);
            $audience->syncMemberCount();
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Audience created.']);
        }

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
