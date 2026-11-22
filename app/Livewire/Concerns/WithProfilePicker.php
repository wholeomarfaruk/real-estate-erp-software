<?php

namespace App\Livewire\Concerns;

use App\Models\MasterProfile;
use Illuminate\Support\Collection;

/**
 * "Profile" (party) picker for accounting entry forms.
 * Include the partial `livewire.admin.profiles.partials.profile-picker` in the view
 * (a searchable select bound to `$master_profile_id`) and save `$master_profile_id`
 * on the request / transaction.
 */
trait WithProfilePicker
{
    public ?int $master_profile_id = null;
    public string $profile_search = '';

    public function selectProfile(int $id): void
    {
        $this->master_profile_id = MasterProfile::whereKey($id)->value('id');
        $this->profile_search = '';
    }

    public function clearProfile(): void
    {
        $this->master_profile_id = null;
        $this->profile_search = '';
    }

    public function selectedProfile(): ?MasterProfile
    {
        return $this->master_profile_id
            ? MasterProfile::with('roles.type')->find($this->master_profile_id)
            : null;
    }

    /** Active profiles matching the search (name, code, phone, email). */
    public function profilePickerResults(): Collection
    {
        $term = trim($this->profile_search);
        if ($term === '') {
            return collect();
        }

        return MasterProfile::query()
            ->with('roles.type')
            ->where('status', 'active')
            ->where(fn ($q) => $q->where('name', 'like', "%{$term}%")
                ->orWhere('code', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%"))
            ->orderBy('name')
            ->limit(8)
            ->get();
    }

    /**
     * Options for the searchable profile select: id => "Name — CODE · phone (roles)".
     * Active profiles, plus the selected one even if it is no longer active.
     */
    public function profilePickerOptions(): array
    {
        $selected = $this->master_profile_id ?? null;

        return MasterProfile::query()
            ->with('roles.type:id,name')
            ->where(fn ($q) => $q->where('status', 'active')
                ->when($selected, fn ($q) => $q->orWhere('id', $selected)))
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'phone'])
            ->mapWithKeys(function (MasterProfile $p) {
                $meta  = collect([$p->code, $p->phone])->filter()->join(' · ');
                $roles = $p->roles->pluck('type.name')->filter()->unique()->join(', ');

                return [$p->id => $p->name . ($meta ? " — {$meta}" : '') . ($roles ? " ({$roles})" : '')];
            })
            ->all();
    }

    protected function profilePickerRules(): array
    {
        return ['master_profile_id' => ['nullable', 'integer', 'exists:master_profiles,id']];
    }
}
