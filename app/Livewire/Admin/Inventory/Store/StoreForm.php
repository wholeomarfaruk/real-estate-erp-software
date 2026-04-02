<?php

namespace App\Livewire\Admin\Inventory\Store;

use App\Enums\Inventory\StoreType;
use App\Models\Project;
use App\Models\Store;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class StoreForm extends Component
{
    public ?Store $storeRecord = null;

    public ?int $storeId = null;

    public bool $editMode = false;

    public string $name = '';

    public string $code = '';

    public string $type = 'office';

    public ?int $project_id = null;

    public ?string $address = null;

    public ?string $description = null;

    public bool $status = true;

    public function mount(?Store $store = null): void
    {
        if ($store && $store->exists) {
            $this->authorizeUpdate();

            $this->editMode = true;
            $this->storeRecord = $store;
            $this->storeId = $store->id;
            $this->name = $store->name;
            $this->code = $store->code;
            $this->type = $store->type?->value ?? (string) $store->getRawOriginal('type');
            $this->project_id = $store->project_id;
            $this->address = $store->address;
            $this->description = $store->description;
            $this->status = (bool) $store->status;

            return;
        }

        $this->authorizeCreate();
    }

    public function updatedType(string $value): void
    {
        if ($value === StoreType::OFFICE->value) {
            $this->project_id = null;
        }
    }

    public function save()
    {
        if ($this->editMode) {
            $this->authorizeUpdate();
        } else {
            $this->authorizeCreate();
        }

        $validated = $this->validate($this->rules());

        if ($validated['type'] === StoreType::OFFICE->value) {
            $validated['project_id'] = null;
        }

        $validated['status'] = (bool) $validated['status'];

        DB::transaction(function () use ($validated): void {
            if ($this->editMode && $this->storeRecord) {
                $this->storeRecord->update($validated);

                return;
            }

            $this->storeRecord = Store::query()->create($validated);
        });

        $this->dispatch('toast', [
            'type' => 'success',
            'message' => $this->editMode ? 'Store updated successfully.' : 'Store created successfully.',
        ]);

        return redirect()->route('admin.inventory.stores.index');
    }

    public function render(): View
    {
        if (! $this->editMode) {
            $this->authorizeCreate();
        }

        return view('livewire.admin.inventory.store.store-form', [
            'projects' => Project::query()->select('id', 'name', 'code')->orderBy('name')->get(),
            'types' => StoreType::cases(),
        ])->layout('layouts.admin.admin');
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:100', Rule::unique('stores', 'code')->ignore($this->storeId)],
            'type' => ['required', Rule::enum(StoreType::class)],
            'project_id' => [
                'nullable',
                'integer',
                'exists:projects,id',
                Rule::requiredIf(fn (): bool => $this->type === StoreType::PROJECT->value),
                Rule::prohibitedIf(fn (): bool => $this->type === StoreType::OFFICE->value),
            ],
            'address' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'boolean'],
        ];
    }

    protected function authorizeCreate(): void
    {
        abort_unless(auth()->user()?->can('inventory.store.create'), 403, 'Unauthorized action.');
    }

    protected function authorizeUpdate(): void
    {
        abort_unless(auth()->user()?->can('inventory.store.update'), 403, 'Unauthorized action.');
    }
}