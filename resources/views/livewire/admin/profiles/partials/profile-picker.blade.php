{{--
    Optional party picker. Requires the WithProfilePicker trait on the component.
    @include('livewire.admin.profiles.partials.profile-picker', ['label' => 'Paid to (profile)'])
--}}
@php
    $picked  = $this->selectedProfile();
    $results = $this->profilePickerResults();
@endphp
<div>
    <label class="mb-1 block text-sm font-medium text-gray-700">
        {{ $label ?? 'Profile' }} <span class="font-normal text-gray-400">(optional)</span>
    </label>

    @if ($picked)
        <div class="flex items-center justify-between gap-3 rounded-lg border border-indigo-200 bg-indigo-50/50 px-3 py-2">
            <div class="flex min-w-0 items-center gap-2.5">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-xs font-bold text-indigo-700">{{ $picked->initials() }}</span>
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-gray-900">{{ $picked->name }}</p>
                    <p class="truncate text-xs text-gray-500">
                        <span class="font-mono">{{ $picked->code }}</span>
                        @if ($picked->roles->isNotEmpty()) · {{ $picked->roles->pluck('type.name')->filter()->join(', ') }} @endif
                    </p>
                </div>
            </div>
            <button type="button" wire:click="clearProfile" class="shrink-0 rounded-md px-2 py-1 text-xs font-medium text-gray-500 hover:bg-white hover:text-red-600">Change</button>
        </div>
    @else
        <div class="relative">
            <input type="text" wire:model.live.debounce.300ms="profile_search" autocomplete="off"
                placeholder="Search customer, supplier, employee, lender… by name, code or phone"
                class="block w-full rounded-md border border-gray-300 px-3! py-2! text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/15">

            @if (trim($profile_search) !== '')
                <div class="absolute z-30 mt-1 max-h-64 w-full overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-lg">
                    @forelse ($results as $p)
                        <button type="button" wire:click="selectProfile({{ $p->id }})" wire:key="pp-{{ $p->id }}"
                            class="flex w-full items-center gap-2.5 px-3 py-2 text-left hover:bg-gray-50">
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-gray-100 text-[11px] font-bold text-gray-600">{{ $p->initials() }}</span>
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-medium text-gray-800">{{ $p->name }}</span>
                                <span class="block truncate text-xs text-gray-500">
                                    <span class="font-mono">{{ $p->code }}</span>{{ $p->phone ? ' · ' . $p->phone : '' }}
                                    @if ($p->roles->isNotEmpty()) · {{ $p->roles->pluck('type.name')->filter()->join(', ') }} @endif
                                </span>
                            </span>
                        </button>
                    @empty
                        <p class="px-3 py-3 text-center text-xs text-gray-400">No active profile found.</p>
                    @endforelse
                </div>
            @endif
        </div>
    @endif
    @error('master_profile_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
</div>
