@php
    use App\Models\ProfileType;
    $icon = fn ($name, $class = 'h-4 w-4') => view('livewire.admin.profiles.partials.icon', ['name' => $name, 'class' => $class])->render();
    $hasFilters = trim($search) !== '' || $typeFilter !== '' || $statusFilter !== '' || $loginFilter !== '';
    $select = 'h-10 rounded-lg border border-gray-200 bg-white px-3! text-sm text-gray-700 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/15';
@endphp

<div class="min-h-screen bg-slate-50 p-4 sm:p-6" x-data x-init="$store.pageName = { name: 'Master Profiles', slug: 'profiles' }">
<div class="mx-auto max-w-7xl">

    {{-- ── Header ─────────────────────────────────────────────── --}}
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <nav class="mb-1 text-xs text-gray-400">
                <a href="{{ route('admin.dashboard') }}" class="hover:text-gray-600">Dashboard</a>
                <span class="mx-1">/</span><span class="text-gray-600">Master Profiles</span>
            </nav>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900">Master Profiles</h1>
            <p class="text-sm text-gray-500">One profile per person or company — linked to every role they play.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.profiles.balances') }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-gray-200 bg-white px-3.5 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50">
                {!! $icon('bank') !!} Balances
            </a>
            @can('profile.type.manage')
                <a href="{{ route('admin.profiles.types') }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-gray-200 bg-white px-3.5 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50">
                    {!! $icon('settings') !!} Profile Types
                </a>
            @endcan
            @can('profile.create')
                @if ($stats['unlinked'] > 0)
                    <button type="button" wire:click="importExisting"
                        wire:confirm="Import {{ $stats['unlinked'] }} existing customers / suppliers / employees into master profiles? Records with the same phone or email are merged into one profile."
                        class="inline-flex h-10 items-center gap-2 rounded-lg border border-indigo-200 bg-indigo-50 px-3.5 text-sm font-medium text-indigo-700 transition hover:bg-indigo-100">
                        <span wire:loading.remove wire:target="importExisting">{!! $icon('download') !!}</span>
                        <span wire:loading wire:target="importExisting">{!! $icon('refresh', 'h-4 w-4 animate-spin') !!}</span>
                        Import existing
                        <span class="rounded-full bg-indigo-600 px-1.5 text-[10px] font-bold text-white">{{ $stats['unlinked'] }}</span>
                    </button>
                @endif
                <button type="button" wire:click="openCreateModal" class="inline-flex h-10 items-center gap-2 rounded-lg bg-gray-900 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800">
                    {!! $icon('plus') !!} New Profile
                </button>
            @endcan
        </div>
    </div>

    {{-- ── Stats ──────────────────────────────────────────────── --}}
    <div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach ([
            ['Total profiles', $stats['total'], 'users', 'bg-gray-900 text-white'],
            ['Active', $stats['active'], 'check', 'bg-emerald-50 text-emerald-600'],
            ['With login', $stats['login'], 'key', 'bg-indigo-50 text-indigo-600'],
            ['Multiple roles', $stats['multi'], 'link', 'bg-amber-50 text-amber-600'],
        ] as [$label, $value, $ic, $tile])
            <div class="flex items-center gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg {{ $tile }}">{!! $icon($ic, 'h-5 w-5') !!}</span>
                <div>
                    <p class="text-xs font-medium text-gray-500">{{ $label }}</p>
                    <p class="text-xl font-bold text-gray-900">{{ number_format($value) }}</p>
                </div>
            </div>
        @endforeach
    </div>

    {{-- ── Type tabs ──────────────────────────────────────────── --}}
    <div class="mb-4 flex gap-2 overflow-x-auto pb-1">
        <button type="button" wire:click="$set('typeFilter', '')"
            class="inline-flex shrink-0 items-center gap-2 rounded-full border px-3.5 py-1.5 text-sm font-medium transition {{ $typeFilter === '' ? 'border-gray-900 bg-gray-900 text-white' : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300' }}">
            All <span class="text-xs opacity-70">{{ $stats['total'] }}</span>
        </button>
        @foreach ($types as $type)
            @php $t = $type->tones(); $active = $typeFilter === $type->slug; @endphp
            <button type="button" wire:click="setType('{{ $type->slug }}')" wire:key="tab-{{ $type->id }}"
                class="inline-flex shrink-0 items-center gap-2 rounded-full border px-3.5 py-1.5 text-sm font-medium transition {{ $active ? 'border-transparent ring-1 ring-inset ' . $t['badge'] : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300' }}">
                {!! $icon($type->icon, 'h-3.5 w-3.5') !!}
                {{ $type->name }}
                <span class="text-xs opacity-60">{{ $type->roles_count }}</span>
            </button>
        @endforeach
    </div>

    {{-- ── Toolbar ────────────────────────────────────────────── --}}
    <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center">
        <div class="relative min-w-0 flex-1">
            <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">{!! $icon('search') !!}</span>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search name, code, phone, email, NID…"
                class="h-10 w-full rounded-lg border border-gray-200 bg-white pl-9! pr-3! text-sm text-gray-800 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/15">
        </div>
        <select wire:model.live="statusFilter" class="{{ $select }} w-full! sm:w-40! shrink-0">
            <option value="">All status</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
        </select>
        <select wire:model.live="loginFilter" class="{{ $select }} w-full! sm:w-40! shrink-0">
            <option value="">Any login</option>
            <option value="yes">Has login</option>
            <option value="no">No login</option>
        </select>
        @if ($hasFilters)
            <button type="button" wire:click="clearFilters" class="h-10 rounded-lg px-3 text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-700">Reset</button>
        @endif
    </div>

    {{-- ── Profiles grid ──────────────────────────────────────── --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3" wire:loading.class="opacity-60" wire:target="search,typeFilter,statusFilter,loginFilter,setType,clearFilters,gotoPage,nextPage,previousPage">
        @forelse ($profiles as $profile)
            @php
                $primary = $profile->roles->first()?->type;
                $tone = ProfileType::toneClasses($primary?->tone);
            @endphp
            <a href="{{ route('admin.profiles.show', $profile) }}" wire:key="p-{{ $profile->id }}"
                class="group relative flex flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:border-gray-300 hover:shadow-md {{ $profile->status === 'inactive' ? 'opacity-70' : '' }}">
                <span class="h-1 w-full bg-linear-to-r {{ $tone['bar'] }}"></span>

                <div class="flex items-start gap-3 p-4">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full text-sm font-bold {{ $tone['tile'] }}">
                        {{ $profile->initials() }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <p class="truncate font-semibold text-gray-900 group-hover:text-indigo-700">{{ $profile->name }}</p>
                            @if ($profile->kind === 'company')
                                <span class="shrink-0 text-gray-400" title="Company">{!! $icon('building', 'h-3.5 w-3.5') !!}</span>
                            @endif
                        </div>
                        <p class="truncate text-xs text-gray-500">
                            <span class="font-mono">{{ $profile->code }}</span>
                            @if ($profile->designation || $profile->organization)
                                · {{ collect([$profile->designation, $profile->organization])->filter()->join(', ') }}
                            @endif
                        </p>
                    </div>
                    @if ($profile->status === 'inactive')
                        <span class="shrink-0 rounded-md bg-gray-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-gray-500">Inactive</span>
                    @endif
                </div>

                <div class="space-y-1.5 px-4 text-sm text-gray-600">
                    @if ($profile->phone)
                        <p class="flex items-center gap-2 truncate"><span class="text-gray-400">{!! $icon('phone', 'h-3.5 w-3.5') !!}</span>{{ $profile->phone }}</p>
                    @endif
                    @if ($profile->email)
                        <p class="flex items-center gap-2 truncate"><span class="text-gray-400">{!! $icon('mail', 'h-3.5 w-3.5') !!}</span><span class="truncate">{{ $profile->email }}</span></p>
                    @endif
                </div>

                <div class="mb-4 mt-3 flex flex-wrap gap-1.5 px-4">
                    @forelse ($profile->roles as $role)
                        @php $rt = $role->type?->tones() ?? ProfileType::toneClasses(null); @endphp
                        <span class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-[11px] font-medium ring-1 ring-inset {{ $rt['badge'] }} {{ $role->status === 'inactive' ? 'opacity-50' : '' }}">
                            {!! $icon($role->type?->icon ?? 'user', 'h-3 w-3') !!} {{ $role->type?->name }}
                        </span>
                    @empty
                        <span class="text-[11px] italic text-gray-400">No roles yet</span>
                    @endforelse
                </div>

                @php $bal = $balances[$profile->id] ?? null; @endphp
                <div class="mt-auto grid grid-cols-3 divide-x divide-gray-100 border-t border-gray-100 text-center">
                    <div class="px-2 py-2">
                        <p class="text-[10px] font-medium uppercase tracking-wide text-gray-400">Received</p>
                        <p class="text-xs font-semibold {{ $bal && $bal->received > 0 ? 'text-emerald-600' : 'text-gray-300' }}">{{ number_format((float) ($bal->received ?? 0), 2) }}</p>
                    </div>
                    <div class="px-2 py-2">
                        <p class="text-[10px] font-medium uppercase tracking-wide text-gray-400">Paid</p>
                        <p class="text-xs font-semibold {{ $bal && $bal->paid > 0 ? 'text-rose-600' : 'text-gray-300' }}">{{ number_format((float) ($bal->paid ?? 0), 2) }}</p>
                    </div>
                    <div class="bg-slate-50/70 px-2 py-2">
                        <p class="text-[10px] font-medium uppercase tracking-wide text-gray-400">Net</p>
                        <p class="text-xs font-bold {{ $bal ? 'text-gray-900' : 'text-gray-300' }}">{{ number_format(abs((float) ($bal->net ?? 0)), 2) }}</p>
                    </div>
                </div>
                <div class="flex items-center justify-between border-t border-gray-100 px-4 py-2.5">
                    @if ($profile->user_id)
                        <span class="inline-flex items-center gap-1.5 text-[11px] font-medium text-emerald-600">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Login enabled
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 text-[11px] font-medium text-gray-400">
                            <span class="h-1.5 w-1.5 rounded-full bg-gray-300"></span> No login
                        </span>
                    @endif
                    <span class="text-xs font-semibold text-gray-400 transition group-hover:text-indigo-600">View →</span>
                </div>
            </a>
        @empty
            <div class="rounded-xl border border-dashed border-gray-300 bg-white px-6 py-16 text-center sm:col-span-2 xl:col-span-3">
                <span class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400">{!! $icon('users', 'h-6 w-6') !!}</span>
                <p class="font-semibold text-gray-700">{{ $hasFilters ? 'No profiles match your filters' : 'No profiles yet' }}</p>
                <p class="mt-1 text-sm text-gray-500">
                    {{ $hasFilters ? 'Try a different search or clear the filters.' : 'Create your first profile, or import existing customers, suppliers and employees.' }}
                </p>
                <div class="mt-4 flex justify-center gap-2">
                    @if ($hasFilters)
                        <button type="button" wire:click="clearFilters" class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-600 hover:bg-gray-50">Clear filters</button>
                    @endif
                    @can('profile.create')
                        <button type="button" wire:click="openCreateModal" class="rounded-lg bg-gray-900 px-3 py-1.5 text-sm font-semibold text-white hover:bg-gray-800">New Profile</button>
                    @endcan
                </div>
            </div>
        @endforelse
    </div>

    <div class="mt-6">{{ $profiles->links() }}</div>
</div>

{{-- ── Create profile modal ────────────────────────────────────── --}}
@if ($showCreateModal)
    <div class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/50 p-4 backdrop-blur-[2px]" wire:click.self="closeCreateModal" x-on:keydown.escape.window="$wire.closeCreateModal()">
        <div class="mx-auto my-6 w-full max-w-4xl overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">New Master Profile</h3>
                    <p class="text-xs text-gray-500">Fill the identity once — pick every role this person or company plays.</p>
                </div>
                <button type="button" wire:click="closeCreateModal" class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600">{!! $icon('x', 'h-5 w-5') !!}</button>
            </div>

            <form wire:submit="createProfile">
                <div class="grid lg:grid-cols-[1fr_280px]">
                    <div class="space-y-5 px-6 py-5">
                        @include('livewire.admin.profiles.partials.profile-fields')
                    </div>

                    {{-- Roles --}}
                    <aside class="border-t border-gray-100 bg-slate-50 px-5 py-5 lg:border-l lg:border-t-0">
                        <p class="mb-1 text-sm font-semibold text-gray-900">Roles</p>
                        <p class="mb-3 text-xs text-gray-500">Select one or more. You can add more later.</p>
                        <div class="space-y-2">
                            @foreach ($types as $type)
                                @php $t = $type->tones(); $checked = in_array((string) $type->id, $selectedTypes, true); @endphp
                                <label wire:key="ct-{{ $type->id }}" class="flex cursor-pointer items-start gap-3 rounded-lg border bg-white p-3 transition {{ $checked ? 'border-indigo-400 ring-2 ring-indigo-500/15' : 'border-gray-200 hover:border-gray-300' }}">
                                    <input type="checkbox" value="{{ $type->id }}" wire:model.live="selectedTypes" class="mt-0.5 h-4! w-4! rounded border-gray-300 p-0! text-indigo-600">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $t['tile'] }}">{!! $icon($type->icon) !!}</span>
                                    <span class="min-w-0">
                                        <span class="block text-sm font-medium text-gray-800">{{ $type->name }}</span>
                                        @if ($type->module)
                                            <span class="block text-[11px] text-gray-500">Creates a new {{ \Illuminate\Support\Str::singular(ProfileType::MODULES[$type->module]['label']) }} record</span>
                                        @elseif ($type->description)
                                            <span class="block truncate text-[11px] text-gray-500">{{ $type->description }}</span>
                                        @endif
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        <p class="mt-3 text-[11px] leading-relaxed text-gray-400">To link an <b>existing</b> customer, supplier or employee instead, save first and use “Add role” on the profile page.</p>
                    </aside>
                </div>

                <div class="flex justify-end gap-2 border-t border-gray-100 bg-white px-6 py-4">
                    <button type="button" wire:click="closeCreateModal" class="h-10 rounded-lg border border-gray-300 px-4 text-sm font-medium text-gray-600 hover:bg-gray-50">Cancel</button>
                    <button type="submit" wire:loading.attr="disabled" wire:target="createProfile" class="inline-flex h-10 items-center gap-2 rounded-lg bg-gray-900 px-5 text-sm font-semibold text-white hover:bg-gray-800 disabled:opacity-60">
                        <span wire:loading.remove wire:target="createProfile">Create Profile</span>
                        <span wire:loading wire:target="createProfile">Creating…</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
@endif
</div>
