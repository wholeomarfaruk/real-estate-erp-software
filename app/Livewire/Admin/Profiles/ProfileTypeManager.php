<?php

namespace App\Livewire\Admin\Profiles;

use App\Models\ProfileType;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class ProfileTypeManager extends Component
{
    public bool $showModal = false;
    public ?int $editingId = null;

    public string $name = '';
    public string $slug = '';
    public string $description = '';
    public string $icon = 'user';
    public string $tone = 'slate';
    public string $module = '';
    public string $login_role = '';
    public bool $is_active = true;
    public array $fields = [];
    public bool $slugManuallyEdited = false;

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('profile.type.manage'), 403);
    }

    public function openModal(?int $id = null): void
    {
        $this->authorizeManage();
        $this->resetValidation();
        $this->reset('editingId', 'name', 'slug', 'description', 'icon', 'tone', 'module', 'login_role', 'is_active', 'fields', 'slugManuallyEdited');

        if ($id) {
            $type = ProfileType::findOrFail($id);
            $this->editingId = $type->id;
            $this->fill($type->only('name', 'slug', 'icon', 'tone', 'is_active'));
            $this->description = (string) $type->description;
            $this->module = (string) $type->module;
            $this->login_role = (string) $type->login_role;
            $this->fields = collect($type->fields ?? [])->map(fn ($f) => [
                'key' => $f['key'], 'label' => $f['label'], 'type' => $f['type'],
                'options' => $f['options'] ?? '', 'required' => (bool) ($f['required'] ?? false),
            ])->all();
            $this->slugManuallyEdited = true;
        }

        $this->showModal = true;
    }

    public function updatedName(string $value): void
    {
        if (! $this->slugManuallyEdited) {
            $this->slug = Str::slug($value);
        }
    }

    public function updatedSlug(string $value): void
    {
        $this->slug = Str::slug($value);
        $this->slugManuallyEdited = $value !== '';
    }

    public function addField(): void
    {
        $this->fields[] = ['key' => '', 'label' => '', 'type' => 'text', 'options' => '', 'required' => false];
    }

    public function removeField(int $index): void
    {
        unset($this->fields[$index]);
        $this->fields = array_values($this->fields);
    }

    public function moveField(int $index, int $direction): void
    {
        $target = $index + $direction;
        if (! isset($this->fields[$index], $this->fields[$target])) {
            return;
        }
        [$this->fields[$index], $this->fields[$target]] = [$this->fields[$target], $this->fields[$index]];
    }

    public function save(): void
    {
        $this->authorizeManage();
        $type = $this->editingId ? ProfileType::findOrFail($this->editingId) : null;

        $this->validate([
            'name'              => ['required', 'string', 'max:100'],
            'slug'              => ['required', 'string', 'max:80', Rule::unique('profile_types', 'slug')->ignore($this->editingId)],
            'description'       => ['nullable', 'string', 'max:255'],
            'icon'              => ['required', Rule::in(array_keys(ProfileType::ICONS))],
            'tone'              => ['required', Rule::in(ProfileType::TONES)],
            'module'            => ['nullable', Rule::in(array_keys(ProfileType::MODULES))],
            'login_role'        => ['nullable', 'exists:roles,name'],
            'fields'            => ['array', 'max:20'],
            'fields.*.label'    => ['required', 'string', 'max:60'],
            'fields.*.type'     => ['required', Rule::in(array_keys(ProfileType::FIELD_TYPES))],
            'fields.*.options'  => ['nullable', 'required_if:fields.*.type,select', 'string', 'max:500'],
        ], [
            'fields.*.label.required'      => 'Field label is required.',
            'fields.*.options.required_if' => 'Add options (comma separated) for a dropdown.',
        ]);

        // A system type's module link and slug are fixed.
        if ($type?->is_system && ($this->module !== (string) $type->module || $this->slug !== $type->slug)) {
            $this->addError('module', 'System types cannot change their module or slug.');
            return;
        }

        // Build stable field keys (keep existing keys so saved values remain attached).
        $used = [];
        $fields = collect($this->fields)->map(function ($f) use (&$used) {
            $key = $f['key'] ?: Str::slug(Str::ascii($f['label']), '_');
            $key = $key ?: 'field';
            $base = $key;
            $i = 2;
            while (in_array($key, $used, true)) {
                $key = $base . '_' . $i++;
            }
            $used[] = $key;

            return [
                'key'      => $key,
                'label'    => trim($f['label']),
                'type'     => $f['type'],
                'options'  => $f['type'] === 'select' ? trim((string) $f['options']) : null,
                'required' => (bool) $f['required'],
            ];
        })->values()->all();

        $data = [
            'name'        => trim($this->name),
            'slug'        => $this->slug,
            'description' => $this->description ?: null,
            'icon'        => $this->icon,
            'tone'        => $this->tone,
            'module'      => $this->module ?: null,
            'login_role'  => $this->login_role ?: null,
            'is_active'   => $this->is_active,
            'fields'      => $fields,
        ];

        if ($type) {
            $type->update($data);
        } else {
            ProfileType::create($data + ['sort_order' => (int) ProfileType::max('sort_order') + 1]);
        }

        $this->showModal = false;
        $this->dispatch('toast', type: 'success', message: $type ? 'Profile type updated' : 'Profile type created');
    }

    public function move(int $id, int $direction): void
    {
        $this->authorizeManage();
        $types = ProfileType::ordered()->get()->values();
        $index = $types->search(fn ($t) => $t->id === $id);
        $target = $index + $direction;
        if ($index === false || ! isset($types[$target])) {
            return;
        }
        // Swap positions, then renumber 1..n so orders stay unique.
        $ordered = $types->all();
        [$ordered[$index], $ordered[$target]] = [$ordered[$target], $ordered[$index]];
        foreach ($ordered as $i => $t) {
            $t->update(['sort_order' => $i + 1]);
        }
    }

    public function toggleActive(int $id): void
    {
        $this->authorizeManage();
        $type = ProfileType::findOrFail($id);
        $type->update(['is_active' => ! $type->is_active]);
    }

    public function delete(int $id): void
    {
        $this->authorizeManage();
        $type = ProfileType::withCount('roles')->findOrFail($id);

        if ($type->is_system) {
            $this->dispatch('toast', type: 'error', message: 'System types cannot be deleted.');
            return;
        }
        if ($type->roles_count > 0) {
            $this->dispatch('toast', type: 'error', message: "{$type->roles_count} profiles use this type. Deactivate it instead, or remove it from those profiles first.");
            return;
        }

        $type->delete();
        $this->dispatch('toast', type: 'success', message: 'Profile type deleted');
    }

    protected function authorizeManage(): void
    {
        abort_unless(auth()->user()?->can('profile.type.manage'), 403);
    }

    public function render(): View
    {
        return view('livewire.admin.profiles.profile-type-manager', [
            'types'      => ProfileType::ordered()->withCount('roles')->get(),
            'modules'    => ProfileType::MODULES,
            'icons'      => ProfileType::ICONS,
            'tones'      => ProfileType::TONES,
            'fieldTypes' => ProfileType::FIELD_TYPES,
            'roles'      => Role::orderBy('name')->pluck('name'),
        ])->layout('layouts.admin.admin');
    }
}
