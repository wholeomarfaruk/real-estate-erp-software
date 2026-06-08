<?php

namespace App\Livewire\Admin\Marketing\Template;

use App\Models\CommunicationTemplate;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class TemplateList extends Component
{
    use WithPagination;

    #[Url(as: 'q', history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $filterType = 'all';

    public bool  $drawerOpen = false;
    public ?int  $editingId  = null;

    // Form fields
    public string $fName      = '';
    public string $fType      = 'sms';
    public string $fSubject   = '';
    public string $fBody      = '';
    public bool   $fIsActive  = true;

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('marketing.template.view'), 403);
    }

    public function updatedSearch(): void   { $this->resetPage(); }
    public function updatedFilterType(): void { $this->resetPage(); }

    public function openCreate(): void
    {
        abort_unless(auth()->user()?->can('marketing.template.create'), 403);
        $this->resetForm();
        $this->editingId  = null;
        $this->drawerOpen = true;
    }

    public function openEdit(int $id): void
    {
        abort_unless(auth()->user()?->can('marketing.template.edit'), 403);
        $t = CommunicationTemplate::findOrFail($id);
        $this->editingId = $t->id;
        $this->fName     = $t->name;
        $this->fType     = $t->type;
        $this->fSubject  = $t->subject ?? '';
        $this->fBody     = $t->body;
        $this->fIsActive = $t->is_active;
        $this->drawerOpen = true;
    }

    public function save(): void
    {
        $this->validate([
            'fName' => 'required|string|max:255',
            'fType' => 'required|in:sms,email,both',
            'fBody' => 'required|string',
            'fSubject' => 'nullable|string|max:255',
        ]);

        // Extract {variable} placeholders from body
        preg_match_all('/\{(\w+)\}/', $this->fBody, $matches);
        $variables = array_unique($matches[1] ?? []);

        $data = [
            'name'       => $this->fName,
            'type'       => $this->fType,
            'subject'    => $this->fSubject ?: null,
            'body'       => $this->fBody,
            'variables'  => $variables ?: null,
            'is_active'  => $this->fIsActive,
            'updated_by' => auth()->id(),
        ];

        if ($this->editingId) {
            abort_unless(auth()->user()?->can('marketing.template.edit'), 403);
            CommunicationTemplate::findOrFail($this->editingId)->update($data);
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Template updated.']);
        } else {
            abort_unless(auth()->user()?->can('marketing.template.create'), 403);
            $data['created_by'] = auth()->id();
            CommunicationTemplate::create($data);
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Template created.']);
        }

        $this->closeDrawer();
    }

    public function delete(int $id): void
    {
        abort_unless(auth()->user()?->can('marketing.template.delete'), 403);
        CommunicationTemplate::findOrFail($id)->delete();
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Template deleted.']);
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
        $this->fName     = '';
        $this->fType     = 'sms';
        $this->fSubject  = '';
        $this->fBody     = '';
        $this->fIsActive = true;
    }

    public function render()
    {
        abort_unless(auth()->user()?->can('marketing.template.view'), 403);

        $templates = CommunicationTemplate::query()
            ->when($this->search, fn($q) => $q->where('name', 'like', '%'.$this->search.'%'))
            ->when($this->filterType !== 'all', fn($q) => $q->where('type', $this->filterType))
            ->orderBy('name')
            ->paginate(15);

        $kpi = [
            'total'  => CommunicationTemplate::count(),
            'sms'    => CommunicationTemplate::where('type', 'sms')->count(),
            'email'  => CommunicationTemplate::where('type', 'email')->count(),
            'active' => CommunicationTemplate::where('is_active', true)->count(),
        ];

        return view('livewire.admin.marketing.template.template-list', compact('templates', 'kpi'))
            ->layout('layouts.admin.admin');
    }
}
