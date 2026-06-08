<?php

namespace App\Livewire\Admin\Crm\LeadSource;

use App\Models\LeadSource;
use Livewire\Component;
use Livewire\WithPagination;

class LeadSourceList extends Component
{
    use WithPagination;

    public bool   $drawerOpen = false;
    public ?int   $editingId  = null;
    public string $fName      = '';
    public string $fColor     = '#6B7280';
    public bool   $fIsActive  = true;

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('crm.lead_source.view'), 403);
    }

    public function openCreate(): void
    {
        $this->editingId  = null;
        $this->fName      = '';
        $this->fColor     = '#6B7280';
        $this->fIsActive  = true;
        $this->drawerOpen = true;
    }

    public function openEdit(int $id): void
    {
        $src = LeadSource::findOrFail($id);
        $this->editingId  = $src->id;
        $this->fName      = $src->name;
        $this->fColor     = $src->color;
        $this->fIsActive  = (bool) $src->is_active;
        $this->drawerOpen = true;
    }

    public function save(): void
    {
        $this->validate(['fName' => 'required|string|max:100']);

        $data = [
            'name'       => $this->fName,
            'color'      => $this->fColor,
            'is_active'  => $this->fIsActive,
            'created_by' => auth()->id(),
        ];

        if ($this->editingId) {
            LeadSource::findOrFail($this->editingId)->update($data);
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Lead source updated.']);
        } else {
            LeadSource::create($data);
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Lead source created.']);
        }

        $this->drawerOpen = false;
        $this->editingId  = null;
        $this->resetPage();
    }

    public function delete(int $id): void
    {
        LeadSource::findOrFail($id)->delete();
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Lead source deleted.']);
        $this->resetPage();
    }

    public function closeDrawer(): void
    {
        $this->drawerOpen = false;
        $this->editingId  = null;
        $this->resetValidation();
    }

    public function render()
    {
        $sources = LeadSource::withCount('leads')
            ->orderBy('name')
            ->paginate(20);

        return view('livewire.admin.crm.lead-source.lead-source-list', compact('sources'))
            ->layout('layouts.admin.admin');
    }
}
