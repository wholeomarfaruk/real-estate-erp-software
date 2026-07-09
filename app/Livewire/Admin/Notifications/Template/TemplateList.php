<?php

namespace App\Livewire\Admin\Notifications\Template;

use App\Enums\Notification\NotificationType;
use App\Models\NotificationTemplate;
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
    public string $fName     = '';
    public string $fType     = 'system';
    public string $fTitle    = '';
    public string $fBody     = '';
    public bool   $fIsActive = true;

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('notification.template.view'), 403);
    }

    public function updatedSearch(): void     { $this->resetPage(); }
    public function updatedFilterType(): void { $this->resetPage(); }

    public function openCreate(): void
    {
        abort_unless(auth()->user()?->can('notification.template.create'), 403);
        $this->resetForm();
        $this->editingId  = null;
        $this->drawerOpen = true;
    }

    public function openEdit(int $id): void
    {
        abort_unless(auth()->user()?->can('notification.template.edit'), 403);
        $t = NotificationTemplate::findOrFail($id);
        $this->editingId  = $t->id;
        $this->fName      = $t->name;
        $this->fType      = $t->type->value;
        $this->fTitle     = $t->title;
        $this->fBody      = $t->body;
        $this->fIsActive  = $t->is_active;
        $this->drawerOpen = true;
    }

    public function save(): void
    {
        $this->validate([
            'fName'  => 'required|string|max:255',
            'fType'  => 'required|in:' . implode(',', array_column(NotificationType::cases(), 'value')),
            'fTitle' => 'required|string|max:255',
            'fBody'  => 'required|string',
        ]);

        // Extract {variable} placeholders from title + body
        preg_match_all('/\{(\w+)\}/', $this->fTitle . ' ' . $this->fBody, $matches);
        $variables = array_unique($matches[1] ?? []);

        $data = [
            'name'       => $this->fName,
            'type'       => $this->fType,
            'title'      => $this->fTitle,
            'body'       => $this->fBody,
            'variables'  => $variables ?: null,
            'is_active'  => $this->fIsActive,
            'updated_by' => auth()->id(),
        ];

        if ($this->editingId) {
            abort_unless(auth()->user()?->can('notification.template.edit'), 403);
            NotificationTemplate::findOrFail($this->editingId)->update($data);
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Template updated.']);
        } else {
            abort_unless(auth()->user()?->can('notification.template.create'), 403);
            $data['created_by'] = auth()->id();
            NotificationTemplate::create($data);
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Template created.']);
        }

        $this->closeDrawer();
    }

    public function delete(int $id): void
    {
        abort_unless(auth()->user()?->can('notification.template.delete'), 403);
        NotificationTemplate::findOrFail($id)->delete();
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
        $this->fType     = 'system';
        $this->fTitle    = '';
        $this->fBody     = '';
        $this->fIsActive = true;
    }

    public function render()
    {
        abort_unless(auth()->user()?->can('notification.template.view'), 403);

        $templates = NotificationTemplate::query()
            ->when($this->search, fn ($q) => $q->where('name', 'like', '%' . $this->search . '%'))
            ->when($this->filterType !== 'all', fn ($q) => $q->where('type', $this->filterType))
            ->orderBy('name')
            ->paginate(15);

        $kpi = [
            'total'  => NotificationTemplate::count(),
            'active' => NotificationTemplate::where('is_active', true)->count(),
        ];

        return view('livewire.admin.notifications.template.template-list', [
            'templates' => $templates,
            'kpi'       => $kpi,
            'types'     => NotificationType::cases(),
        ])->layout('layouts.admin.admin');
    }
}
