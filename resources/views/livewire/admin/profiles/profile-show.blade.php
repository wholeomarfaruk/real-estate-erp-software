@php
    use App\Models\ProfileType;
    $icon = fn ($name, $class = 'h-4 w-4') => view('livewire.admin.profiles.partials.icon', ['name' => $name, 'class' => $class])->render();
    $in   = 'h-10 w-full rounded-lg border border-gray-300 bg-white px-3! text-sm text-gray-800 transition placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/15';
    $lbl  = 'mb-1 block text-xs font-medium text-gray-700';
    $primaryTone = ProfileType::toneClasses($roles->first()?->type?->tone);
    $user = $profile->user;
    $fmt = function ($field, $value) {
        if ($value === null || $value === '') return '—';
        return match ($field['type'] ?? 'text') {
            'number' => is_numeric($value) ? rtrim(rtrim(number_format((float) $value, 2), '0'), '.') : $value,
            'date'   => \Illuminate\Support\Carbon::parse($value)->format('d M Y'),
            default  => $value,
        };
    };
@endphp

<div class="min-h-screen bg-slate-50 p-4 sm:p-6" x-data x-init="$store.pageName = { name: 'Master Profiles', slug: 'profiles' }">
<div class="mx-auto max-w-7xl">

    <a href="{{ route('admin.profiles.index') }}" class="mb-4 inline-flex items-center gap-1.5 text-sm font-medium text-gray-500 hover:text-gray-800">
        {!! $icon('arrow-left') !!} All profiles
    </a>

    {{-- ── Hero ───────────────────────────────────────────────── --}}
    <div class="mb-6 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="h-24 bg-linear-to-r {{ $primaryTone['bar'] }}"></div>
        <div class="flex flex-col gap-4 px-5 pb-5 pt-3 sm:flex-row sm:items-end sm:justify-between">
            <div class="flex min-w-0 items-end gap-4">
                {{-- Only the avatar overlaps the band; the name stays below it. --}}
                <span class="-mt-14 flex h-20 w-20 shrink-0 items-center justify-center rounded-2xl bg-white p-1 shadow-md ring-1 ring-black/5">
                    <span class="flex h-full w-full items-center justify-center rounded-xl text-2xl font-bold {{ $primaryTone['tile'] }}">
                        {{ $profile->initials() }}
                    </span>
                </span>
                <div class="min-w-0 pb-0.5">
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-xl font-bold text-gray-900">{{ $profile->name }}</h1>
                        <span class="rounded-md px-2 py-0.5 text-[11px] font-semibold {{ $profile->status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500' }}">
                            {{ ucfirst($profile->status) }}
                        </span>
                    </div>
                    <p class="text-sm text-gray-500">
                        <span class="font-mono">{{ $profile->code }}</span>
                        · {{ $profile->kind === 'company' ? 'Company' : 'Individual' }}
                        @if ($profile->designation || $profile->organization)
                            · {{ collect([$profile->designation, $profile->organization])->filter()->join(', ') }}
                        @endif
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @can('profile.edit')
                    <button type="button" wire:click="toggleStatus" class="inline-flex h-9 items-center rounded-lg border border-gray-200 px-3 text-sm font-medium text-gray-600 hover:bg-gray-50">
                        {{ $profile->status === 'active' ? 'Deactivate' : 'Activate' }}
                    </button>
                    <button type="button" wire:click="openEditModal" class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-gray-900 px-3.5 text-sm font-semibold text-white hover:bg-gray-800">
                        {!! $icon('edit', 'h-3.5 w-3.5') !!} Edit profile
                    </button>
                @endcan
                @can('profile.delete')
                    @if ($isLocked)
                        <span class="inline-flex h-9 w-9 cursor-not-allowed items-center justify-center rounded-lg border border-gray-100 text-gray-300"
                            title="Has linked business records — can't be deleted. Deactivate instead.">
                            {!! $icon('shield') !!}
                        </span>
                    @else
                        <button type="button" wire:click="deleteProfile" wire:confirm="Delete this master profile? Linked customer / supplier / employee records and login users are kept."
                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-400 hover:border-red-200 hover:bg-red-50 hover:text-red-600" title="Delete profile">
                            {!! $icon('trash') !!}
                        </button>
                    @endif
                @endcan
            </div>
        </div>

        {{-- Overall balance across all roles --}}
        <div class="grid grid-cols-2 border-t border-gray-100 sm:grid-cols-4">
            <div class="px-5 py-3">
                <p class="text-[11px] font-medium uppercase tracking-wide text-gray-400">Total received</p>
                <p class="text-lg font-bold {{ $ledger['received'] > 0 ? 'text-emerald-600' : 'text-gray-300' }}">{{ number_format($ledger['received'], 2) }}</p>
            </div>
            <div class="px-5 py-3 sm:border-l sm:border-gray-100">
                <p class="text-[11px] font-medium uppercase tracking-wide text-gray-400">Total paid</p>
                <p class="text-lg font-bold {{ $ledger['paid'] > 0 ? 'text-rose-600' : 'text-gray-300' }}">{{ number_format($ledger['paid'], 2) }}</p>
            </div>
            <div class="border-t border-gray-100 bg-slate-50 px-5 py-3 sm:border-l sm:border-t-0">
                <p class="text-[11px] font-medium uppercase tracking-wide text-gray-400">{{ $ledger['net'] < 0 ? 'Net paid' : 'Net received' }}</p>
                <p class="text-lg font-bold {{ $ledger['count'] ? 'text-gray-900' : 'text-gray-300' }}">{{ number_format(abs($ledger['net']), 2) }}</p>
            </div>
            <div class="border-t border-gray-100 bg-slate-50 px-5 py-3 sm:border-l sm:border-t-0">
                <p class="text-[11px] font-medium uppercase tracking-wide text-gray-400">Entries</p>
                <p class="text-lg font-bold text-gray-900">{{ number_format($ledger['count']) }}</p>
                @if ($ledger['last_at'])
                    <p class="text-[11px] text-gray-400">Last {{ \Illuminate\Support\Carbon::parse($ledger['last_at'])->format('d M Y') }}</p>
                @endif
            </div>
        </div>

        @if ($isLocked)
            <div class="flex items-start gap-3 border-t border-amber-100 bg-amber-50/60 px-5 py-3 text-sm">
                <span class="mt-0.5 text-amber-600">{!! $icon('shield') !!}</span>
                <p class="text-amber-800">
                    <b class="font-semibold">Protected profile.</b>
                    It's linked to accounting, sales, purchase or payroll records, so it can't be deleted.
                    @if ($profile->status === 'active')
                        Use <b class="font-semibold">Deactivate</b> to stop using it.
                    @endif
                </p>
            </div>
        @endif
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- ── Left column ─────────────────────────────────────── --}}
        <div class="space-y-6">
            {{-- Details --}}
            <section class="rounded-xl border border-gray-200 bg-white shadow-sm">
                <h2 class="border-b border-gray-100 px-5 py-3 text-sm font-semibold text-gray-900">Details</h2>
                <dl class="divide-y divide-gray-50 text-sm">
                    @foreach ([
                        ['phone', 'Phone', collect([$profile->phone, $profile->phone_alt])->filter()->join(' · ')],
                        ['mail', 'Email', $profile->email],
                        ['pin', 'Address', collect([$profile->address, $profile->district])->filter()->join(', ')],
                        ['id', $docTypes[$profile->doc_type] ?? 'Document', $profile->doc_no],
                        ['id', 'TIN', $profile->tin_no],
                        ['calendar', 'Date of birth', $profile->date_of_birth?->format('d M Y')],
                        ['user', 'Gender', $profile->gender ? ucfirst($profile->gender) : null],
                    ] as [$ic, $label, $value])
                        @if ($value)
                            <div class="flex gap-3 px-5 py-2.5">
                                <span class="mt-0.5 text-gray-400">{!! $icon($ic, 'h-4 w-4') !!}</span>
                                <div class="min-w-0">
                                    <dt class="text-[11px] font-medium uppercase tracking-wide text-gray-400">{{ $label }}</dt>
                                    <dd class="break-words text-gray-800">{{ $value }}</dd>
                                </div>
                            </div>
                        @endif
                    @endforeach
                    @if ($profile->notes)
                        <div class="px-5 py-3">
                            <dt class="text-[11px] font-medium uppercase tracking-wide text-gray-400">Notes</dt>
                            <dd class="whitespace-pre-line text-gray-700">{{ $profile->notes }}</dd>
                        </div>
                    @endif
                </dl>
                <p class="border-t border-gray-100 px-5 py-2.5 text-[11px] text-gray-400">Created {{ $profile->created_at?->format('d M Y') }}</p>
            </section>

            {{-- Login --}}
            <section class="rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-3">
                    <h2 class="flex items-center gap-2 text-sm font-semibold text-gray-900">{!! $icon('key') !!} Login Account</h2>
                    @if ($user)
                        <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-emerald-600"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>Enabled</span>
                    @endif
                </div>

                @if ($user)
                    <div class="space-y-3 px-5 py-4 text-sm">
                        <div>
                            <p class="text-[11px] font-medium uppercase tracking-wide text-gray-400">Username / email</p>
                            <p class="font-medium text-gray-800">{{ $user->email }}</p>
                        </div>
                        <div>
                            <p class="mb-1 text-[11px] font-medium uppercase tracking-wide text-gray-400">Role</p>
                            @if (auth()->user()->can('profile.login.manage') && $canManageUser)
                                <select wire:change="updateLoginRole($event.target.value)" class="{{ $in }}">
                                    <option value="">No role</option>
                                    @foreach ($spatieRoles as $r)
                                        <option value="{{ $r }}" @selected($user->roles->first()?->name === $r)>{{ ucfirst($r) }}</option>
                                    @endforeach
                                </select>
                            @else
                                <p class="text-gray-800">{{ ucfirst($user->roles->first()?->name ?? '—') }}</p>
                            @endif
                        </div>
                        @if ($user->panels->isNotEmpty())
                            <div>
                                <p class="text-[11px] font-medium uppercase tracking-wide text-gray-400">Panels</p>
                                <p class="text-gray-800">{{ $user->panels->pluck('name')->join(', ') }}</p>
                            </div>
                        @endif
                        @if ($newPassword)
                            <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2">
                                <p class="text-[11px] font-medium text-amber-700">New password — copy it now, it won't be shown again</p>
                                <p class="select-all font-mono text-sm font-semibold text-gray-900">{{ $newPassword }}</p>
                            </div>
                        @endif
                        @if (auth()->user()->can('profile.login.manage') && $canManageUser)
                            <div class="flex flex-wrap gap-2 pt-1">
                                <button type="button" wire:click="resetLoginPassword" wire:confirm="Generate a new password for {{ $user->email }}?"
                                    class="inline-flex h-8 items-center rounded-lg border border-gray-200 px-3 text-xs font-medium text-gray-600 hover:bg-gray-50">Reset password</button>
                                <button type="button" wire:click="unlinkLogin" wire:confirm="Unlink this login from the profile? The user account stays active."
                                    class="inline-flex h-8 items-center rounded-lg border border-gray-200 px-3 text-xs font-medium text-gray-600 hover:border-red-200 hover:bg-red-50 hover:text-red-600">Unlink</button>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="px-5 py-6 text-center">
                        <span class="mx-auto mb-2 flex h-10 w-10 items-center justify-center rounded-full bg-gray-100 text-gray-400">{!! $icon('key', 'h-5 w-5') !!}</span>
                        <p class="text-sm font-medium text-gray-700">No login yet</p>
                        <p class="mb-3 text-xs text-gray-500">Give this profile access to the system.</p>
                        @can('profile.login.manage')
                            <button type="button" wire:click="openLoginModal" class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-indigo-600 px-3.5 text-sm font-semibold text-white hover:bg-indigo-700">
                                {!! $icon('plus', 'h-3.5 w-3.5') !!} Set up login
                            </button>
                        @endcan
                    </div>
                @endif
            </section>
        </div>

        {{-- ── Right column ────────────────────────────────────── --}}
        <div class="space-y-6 lg:col-span-2">
        {{-- ── Roles ───────────────────────────────────────────── --}}
        <div>
            <div class="mb-3 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-semibold text-gray-900">Roles & linked profiles</h2>
                    <p class="text-xs text-gray-500">Every role this {{ $profile->kind === 'company' ? 'company' : 'person' }} holds in the business.</p>
                </div>
                @can('profile.edit')
                    <button type="button" wire:click="openRoleModal" class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50">
                        {!! $icon('plus', 'h-3.5 w-3.5') !!} Add role
                    </button>
                @endcan
            </div>

            <div class="space-y-4">
                @forelse ($roles as $role)
                    @php
                        $type = $role->type;
                        $t = $type?->tones() ?? ProfileType::toneClasses(null);
                        $record = $role->linkable;
                        $recordUrl = $service->recordUrl($record);
                    @endphp
                    <article wire:key="role-{{ $role->id }}" class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm {{ $role->status === 'inactive' ? 'opacity-70' : '' }}">
                        <div class="flex items-center gap-3 px-5 py-4">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl {{ $t['tile'] }}">{!! $icon($type?->icon ?? 'user', 'h-5 w-5') !!}</span>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="font-semibold text-gray-900">{{ $type?->name ?? 'Unknown' }}</h3>
                                    @if ($role->reference)
                                        <span class="rounded-md bg-gray-100 px-1.5 py-0.5 font-mono text-[11px] text-gray-600">{{ $role->reference }}</span>
                                    @endif
                                    @if ($role->status === 'inactive')
                                        <span class="rounded-md bg-gray-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-gray-500">Inactive</span>
                                    @endif
                                </div>
                                <p class="text-xs text-gray-500">{{ $role->since ? 'Since ' . $role->since->format('d M Y') : ($type?->description ?? '') }}</p>
                            </div>
                            @can('profile.edit')
                                <div class="flex items-center gap-1">
                                    <button type="button" wire:click="openRoleModal({{ $role->id }})" title="Edit role" class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-700">{!! $icon('edit', 'h-3.5 w-3.5') !!}</button>
                                    @if (empty($roleUsage[$role->id]))
                                        <button type="button" wire:click="removeRole({{ $role->id }})" wire:confirm="Remove the {{ $type?->name }} role from this profile?" title="Remove role" class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 hover:bg-red-50 hover:text-red-600">{!! $icon('trash', 'h-3.5 w-3.5') !!}</button>
                                    @else
                                        <span title="In use — set the role Inactive instead" class="flex h-8 w-8 cursor-not-allowed items-center justify-center text-gray-300">{!! $icon('shield', 'h-3.5 w-3.5') !!}</span>
                                    @endif
                                </div>
                            @endcan
                        </div>

                        @if ($type?->module)
                            <div class="mx-5 mb-4 flex items-center justify-between gap-3 rounded-lg border px-3 py-2.5 {{ $record ? 'border-gray-200 bg-slate-50' : 'border-dashed border-amber-300 bg-amber-50/50' }}">
                                @if ($record)
                                    <div class="flex min-w-0 items-center gap-2 text-sm">
                                        <span class="text-gray-400">{!! $icon('link', 'h-3.5 w-3.5') !!}</span>
                                        <span class="truncate text-gray-700">Linked {{ \Illuminate\Support\Str::singular(ProfileType::MODULES[$type->module]['label']) }}:
                                            <b class="font-semibold text-gray-900">{{ $record->name }}</b>
                                            <span class="font-mono text-xs text-gray-500">{{ $service->recordCode($record) }}</span>
                                        </span>
                                    </div>
                                    @if ($recordUrl)
                                        <a href="{{ $recordUrl }}" class="inline-flex shrink-0 items-center gap-1 text-xs font-semibold text-indigo-600 hover:text-indigo-800">Open {!! $icon('external', 'h-3 w-3') !!}</a>
                                    @endif
                                @else
                                    <p class="text-sm text-amber-700">Not linked to a {{ \Illuminate\Support\Str::singular(strtolower(ProfileType::MODULES[$type->module]['label'])) }} record.</p>
                                @endif
                            </div>
                            @if (! empty($roleUsage[$role->id]))
                                <div class="mx-5 -mt-2 mb-4 flex flex-wrap gap-1.5">
                                    @foreach ($roleUsage[$role->id] as $usage)
                                        <span class="rounded-md bg-amber-50 px-2 py-0.5 text-[11px] font-medium text-amber-700 ring-1 ring-inset ring-amber-600/20">{{ $usage }}</span>
                                    @endforeach
                                </div>
                            @endif
                        @endif

                        @if (! empty($roleDetails[$role->id]))
                            <dl class="grid gap-px border-t border-gray-100 bg-gray-100 sm:grid-cols-2 lg:grid-cols-3">
                                @foreach ($roleDetails[$role->id] as $label => $value)
                                    <div class="bg-white px-5 py-3">
                                        <dt class="text-[11px] font-medium uppercase tracking-wide text-gray-400">{{ $label }}</dt>
                                        <dd class="text-sm font-medium text-gray-800">{{ $value }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        @endif

                        @if (! empty($type?->fields))
                            <dl class="grid gap-px border-t border-gray-100 bg-gray-100 sm:grid-cols-2 lg:grid-cols-3">
                                @foreach ($type->fields as $field)
                                    <div class="bg-white px-5 py-3">
                                        <dt class="text-[11px] font-medium uppercase tracking-wide text-gray-400">{{ $field['label'] }}</dt>
                                        <dd class="text-sm font-medium text-gray-800">{{ $fmt($field, $role->data[$field['key']] ?? null) }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        @endif

                        {{-- This role's balance (entries of its linked record) --}}
                        @include('livewire.admin.profiles.partials.role-ledger', ['bucket' => $roleLedger['roles'][$role->id] ?? null])
                    </article>
                @empty
                    <div class="rounded-xl border border-dashed border-gray-300 bg-white px-6 py-12 text-center">
                        <span class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400">{!! $icon('link', 'h-6 w-6') !!}</span>
                        <p class="font-semibold text-gray-700">No roles yet</p>
                        <p class="mt-1 text-sm text-gray-500">Add a role — Customer, Supplier, Lender, Director… — to link this profile.</p>
                        @can('profile.edit')
                            <button type="button" wire:click="openRoleModal" class="mt-4 rounded-lg bg-gray-900 px-3.5 py-2 text-sm font-semibold text-white hover:bg-gray-800">Add role</button>
                        @endcan
                    </div>
                @endforelse

                {{-- Entries tagged with the profile but not tied to a role's record --}}
                @if ($roleLedger['other']['count'] > 0)
                    <article class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                        <div class="flex items-center gap-3 px-5 py-4">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-600">{!! $icon('link', 'h-5 w-5') !!}</span>
                            <div>
                                <h3 class="font-semibold text-gray-900">Other entries</h3>
                                <p class="text-xs text-gray-500">Entries where this profile was chosen manually (payment requests, journal postings, expenses).</p>
                            </div>
                        </div>
                        @include('livewire.admin.profiles.partials.role-ledger', ['bucket' => $roleLedger['other']])
                    </article>
                @endif
            </div>
        </div>
        </div>
    </div>
</div>

{{-- ── Edit profile modal ──────────────────────────────────────── --}}
@if ($showEditModal)
    <div class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/50 p-4" wire:click.self="$set('showEditModal', false)" x-on:keydown.escape.window="$wire.set('showEditModal', false)">
        <div class="mx-auto my-6 w-full max-w-2xl overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                <h3 class="text-lg font-semibold text-gray-900">Edit Profile</h3>
                <button type="button" wire:click="$set('showEditModal', false)" class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100">{!! $icon('x', 'h-5 w-5') !!}</button>
            </div>
            <form wire:submit="saveProfile">
                <div class="space-y-5 px-6 py-5">
                    @include('livewire.admin.profiles.partials.profile-fields')
                </div>
                <div class="flex justify-end gap-2 border-t border-gray-100 px-6 py-4">
                    <button type="button" wire:click="$set('showEditModal', false)" class="h-10 rounded-lg border border-gray-300 px-4 text-sm font-medium text-gray-600 hover:bg-gray-50">Cancel</button>
                    <button type="submit" wire:loading.attr="disabled" class="h-10 rounded-lg bg-gray-900 px-5 text-sm font-semibold text-white hover:bg-gray-800 disabled:opacity-60">Save changes</button>
                </div>
            </form>
        </div>
    </div>
@endif

{{-- ── Role modal ──────────────────────────────────────────────── --}}
@if ($showRoleModal)
    <div class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/50 p-4" wire:click.self="$set('showRoleModal', false)" x-on:keydown.escape.window="$wire.set('showRoleModal', false)">
        <div class="mx-auto my-6 w-full max-w-xl overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                <h3 class="text-lg font-semibold text-gray-900">{{ $editingRoleId ? 'Edit ' . ($roleType?->name ?? 'role') : 'Add Role' }}</h3>
                <button type="button" wire:click="$set('showRoleModal', false)" class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100">{!! $icon('x', 'h-5 w-5') !!}</button>
            </div>

            <form wire:submit="saveRole" class="space-y-5 px-6 py-5">
                @unless ($editingRoleId)
                    @if ($roleTypes->isEmpty())
                        <p class="rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-700">This profile already holds every active role type.</p>
                    @else
                        <div>
                            <label class="{{ $lbl }}">Role type <span class="text-red-500">*</span></label>
                            <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                                @foreach ($roleTypes as $type)
                                    @php $t = $type->tones(); $on = $role_type_id === (string) $type->id; @endphp
                                    <button type="button" wire:click="$set('role_type_id', '{{ $type->id }}')" wire:key="rt-{{ $type->id }}"
                                        class="flex items-center gap-2 rounded-lg border p-2.5 text-left text-sm font-medium transition {{ $on ? 'border-indigo-400 bg-indigo-50/40 text-gray-900 ring-2 ring-indigo-500/15' : 'border-gray-200 text-gray-600 hover:border-gray-300' }}">
                                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md {{ $t['tile'] }}">{!! $icon($type->icon, 'h-3.5 w-3.5') !!}</span>
                                        <span class="truncate">{{ $type->name }}</span>
                                    </button>
                                @endforeach
                            </div>
                            @error('role_type_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    @endif

                    {{-- Module link --}}
                    @if ($roleType?->module)
                        @php $single = \Illuminate\Support\Str::singular(ProfileType::MODULES[$roleType->module]['label']); @endphp
                        <div>
                            <label class="{{ $lbl }}">{{ $single }} record</label>
                            <div class="mb-3 inline-flex rounded-lg bg-gray-100 p-1">
                                @foreach (['create' => 'Create new', 'link' => 'Link existing', 'none' => 'Skip'] as $mode => $label)
                                    <button type="button" wire:click="$set('role_mode', '{{ $mode }}')"
                                        class="rounded-md px-3 py-1.5 text-xs font-semibold transition {{ $role_mode === $mode ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">{{ $label }}</button>
                                @endforeach
                            </div>

                            @if ($role_mode === 'create')
                                <p class="rounded-lg bg-slate-50 px-3 py-2 text-xs text-gray-600">A new {{ strtolower($single) }} record is created from this profile's name, phone, email and address.</p>
                            @elseif ($role_mode === 'link')
                                <div class="relative mb-2">
                                    <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">{!! $icon('search') !!}</span>
                                    <input type="text" wire:model.live.debounce.300ms="role_search" placeholder="Search {{ strtolower($single) }} by name, phone, email" class="{{ $in }} pl-9!">
                                </div>
                                <div class="max-h-56 divide-y divide-gray-100 overflow-y-auto rounded-lg border border-gray-200">
                                    @forelse ($linkResults as $r)
                                        <button type="button" wire:click="selectLinkRecord({{ $r['id'] }})" wire:key="lr-{{ $r['id'] }}"
                                            class="flex w-full items-center justify-between gap-2 px-3 py-2 text-left text-sm transition {{ $role_link_id === $r['id'] ? 'bg-indigo-50' : 'hover:bg-gray-50' }}">
                                            <span class="min-w-0">
                                                <span class="block truncate font-medium text-gray-800">{{ $r['name'] }}</span>
                                                <span class="block truncate text-xs text-gray-500"><span class="font-mono">{{ $r['code'] }}</span> {{ $r['phone'] ? '· ' . $r['phone'] : '' }}</span>
                                            </span>
                                            @if ($role_link_id === $r['id'])
                                                <span class="text-indigo-600">{!! $icon('check') !!}</span>
                                            @endif
                                        </button>
                                    @empty
                                        <p class="px-3 py-4 text-center text-xs text-gray-400">No unlinked {{ strtolower(ProfileType::MODULES[$roleType->module]['label']) }} found.</p>
                                    @endforelse
                                </div>
                                @error('role_link_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            @else
                                <p class="rounded-lg bg-slate-50 px-3 py-2 text-xs text-gray-600">The role is added without a linked {{ strtolower($single) }} record.</p>
                            @endif
                        </div>
                    @endif
                @endunless

                <div class="grid gap-3 sm:grid-cols-3">
                    <div>
                        <label class="{{ $lbl }}">Reference</label>
                        <input type="text" wire:model="role_reference" class="{{ $in }}" placeholder="{{ $roleType?->module ? 'Auto' : 'Optional' }}">
                    </div>
                    <div>
                        <label class="{{ $lbl }}">Since</label>
                        <input type="date" wire:model="role_since" class="{{ $in }}">
                    </div>
                    <div>
                        <label class="{{ $lbl }}">Status</label>
                        <select wire:model="role_status" class="{{ $in }}">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                @if ($showEmployeeForm)
                    <div class="rounded-xl border border-sky-100 bg-sky-50/40 p-4">
                        <p class="mb-3 flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wider text-sky-700">
                            {!! $icon('badge', 'h-3.5 w-3.5') !!} Employment details
                            <span class="font-normal normal-case tracking-normal text-sky-600/70">— saved to the HRM employee record</span>
                        </p>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="{{ $lbl }}">Department</label>
                                <select wire:model.live="emp.department_id" class="{{ $in }}">
                                    <option value="">—</option>
                                    @foreach ($departments as $d)
                                        <option value="{{ $d->id }}">{{ $d->name }}</option>
                                    @endforeach
                                </select>
                                @error('emp.department_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="{{ $lbl }}">Designation</label>
                                <select wire:model="emp.designation_id" class="{{ $in }}">
                                    <option value="">{{ $designations->isEmpty() ? 'No designations' : '—' }}</option>
                                    @foreach ($designations as $d)
                                        <option value="{{ $d->id }}">{{ $d->name }}</option>
                                    @endforeach
                                </select>
                                @error('emp.designation_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="{{ $lbl }}">Work station <span class="text-red-500">*</span></label>
                                <select wire:model="emp.work_station_id" class="{{ $in }}">
                                    <option value="">Select</option>
                                    @foreach ($workStations as $w)
                                        <option value="{{ $w->id }}">{{ $w->name }}</option>
                                    @endforeach
                                </select>
                                @error('emp.work_station_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="{{ $lbl }}">Joining date <span class="text-red-500">*</span></label>
                                <input type="date" wire:model="emp.joining_date" class="{{ $in }}">
                                @error('emp.joining_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="{{ $lbl }}">Employment type</label>
                                <select wire:model="emp.employment_type" class="{{ $in }}">
                                    <option value="">—</option>
                                    @foreach (\App\Services\Profiles\MasterProfileService::EMPLOYMENT_TYPES as $t)
                                        <option value="{{ $t }}">{{ ucfirst($t) }}</option>
                                    @endforeach
                                </select>
                                @error('emp.employment_type') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="{{ $lbl }}">Basic salary <span class="text-red-500">*</span></label>
                                <input type="number" step="0.01" min="0" wire:model="emp.basic_salary" class="{{ $in }}">
                                @error('emp.basic_salary') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="{{ $lbl }}">Employee status <span class="text-red-500">*</span></label>
                                <select wire:model="emp.status" class="{{ $in }}">
                                    @foreach (\App\Services\Profiles\MasterProfileService::EMPLOYEE_STATUSES as $s)
                                        <option value="{{ $s }}">{{ ucfirst($s) }}</option>
                                    @endforeach
                                </select>
                                @error('emp.status') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>
                @endif

                @if (! empty($roleType?->fields))
                    <div>
                        <p class="mb-2 text-[11px] font-semibold uppercase tracking-wider text-gray-400">{{ $roleType->name }} details</p>
                        <div class="grid gap-3 sm:grid-cols-2">
                            @foreach ($roleType->fields as $field)
                                <div class="{{ $field['type'] === 'textarea' ? 'sm:col-span-2' : '' }}" wire:key="rf-{{ $field['key'] }}">
                                    <label class="{{ $lbl }}">{{ $field['label'] }} @if (! empty($field['required']))<span class="text-red-500">*</span>@endif</label>
                                    @switch($field['type'])
                                        @case('select')
                                            <select wire:model="role_data.{{ $field['key'] }}" class="{{ $in }}">
                                                <option value="">—</option>
                                                @foreach (array_filter(array_map('trim', explode(',', $field['options'] ?? ''))) as $opt)
                                                    <option value="{{ $opt }}">{{ $opt }}</option>
                                                @endforeach
                                            </select>
                                            @break
                                        @case('textarea')
                                            <textarea wire:model="role_data.{{ $field['key'] }}" rows="2" class="w-full rounded-lg border border-gray-300 px-3! py-2! text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/15"></textarea>
                                            @break
                                        @default
                                            <input type="{{ $field['type'] === 'number' ? 'number' : ($field['type'] === 'date' ? 'date' : 'text') }}" @if ($field['type'] === 'number') step="any" @endif
                                                wire:model="role_data.{{ $field['key'] }}" class="{{ $in }}">
                                    @endswitch
                                    @error('role_data.' . $field['key']) <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="flex justify-end gap-2 border-t border-gray-100 pt-4">
                    <button type="button" wire:click="$set('showRoleModal', false)" class="h-10 rounded-lg border border-gray-300 px-4 text-sm font-medium text-gray-600 hover:bg-gray-50">Cancel</button>
                    <button type="submit" @disabled(! $editingRoleId && $roleTypes->isEmpty()) wire:loading.attr="disabled" wire:target="saveRole" class="h-10 rounded-lg bg-gray-900 px-5 text-sm font-semibold text-white hover:bg-gray-800 disabled:opacity-50">
                        {{ $editingRoleId ? 'Save role' : 'Add role' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endif

{{-- ── Login modal ─────────────────────────────────────────────── --}}
@if ($showLoginModal)
    <div class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/50 p-4" wire:click.self="$set('showLoginModal', false)" x-on:keydown.escape.window="$wire.set('showLoginModal', false)">
        <div class="mx-auto my-6 w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                <h3 class="text-lg font-semibold text-gray-900">Set up Login</h3>
                <button type="button" wire:click="$set('showLoginModal', false)" class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100">{!! $icon('x', 'h-5 w-5') !!}</button>
            </div>
            <form wire:submit="saveLogin" class="space-y-4 px-6 py-5">
                <div class="inline-flex rounded-lg bg-gray-100 p-1">
                    @foreach (['create' => 'Create new login', 'link' => 'Link existing user'] as $mode => $label)
                        <button type="button" wire:click="$set('login_mode', '{{ $mode }}')"
                            class="rounded-md px-3 py-1.5 text-xs font-semibold transition {{ $login_mode === $mode ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">{{ $label }}</button>
                    @endforeach
                </div>

                @if ($login_mode === 'create')
                    <div>
                        <label class="{{ $lbl }}">Email (username) <span class="text-red-500">*</span></label>
                        <input type="email" wire:model="login_email" class="{{ $in }}" placeholder="name@example.com">
                        @error('login_email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="{{ $lbl }}">Password <span class="text-red-500">*</span></label>
                        <div class="flex gap-2">
                            <input type="text" wire:model="login_password" class="{{ $in }} font-mono">
                            <button type="button" wire:click="generatePassword" title="Generate" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-gray-300 text-gray-500 hover:bg-gray-50">{!! $icon('refresh') !!}</button>
                        </div>
                        <p class="mt-1 text-[11px] text-gray-400">Copy it now and share it securely — it won't be shown again.</p>
                        @error('login_password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="{{ $lbl }}">Role</label>
                            <select wire:model="login_role" class="{{ $in }}">
                                <option value="">No role</option>
                                @foreach ($spatieRoles as $r)
                                    <option value="{{ $r }}">{{ ucfirst($r) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="{{ $lbl }}">Panels</label>
                            <div class="max-h-28 space-y-1 overflow-y-auto rounded-lg border border-gray-300 px-3 py-2">
                                @foreach ($panels as $panel)
                                    <label class="flex items-center gap-2 text-sm text-gray-700">
                                        <input type="checkbox" value="{{ $panel->id }}" wire:model="login_panels" class="h-4! w-4! rounded border-gray-300 p-0! text-indigo-600"> {{ $panel->name }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @else
                    <div>
                        <div class="relative mb-2">
                            <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">{!! $icon('search') !!}</span>
                            <input type="text" wire:model.live.debounce.300ms="login_user_search" placeholder="Search users by name or email" class="{{ $in }} pl-9!">
                        </div>
                        <div class="max-h-56 divide-y divide-gray-100 overflow-y-auto rounded-lg border border-gray-200">
                            @forelse ($userResults as $u)
                                <button type="button" wire:click="$set('login_user_id', {{ $u->id }})" wire:key="u-{{ $u->id }}"
                                    class="flex w-full items-center justify-between px-3 py-2 text-left text-sm {{ $login_user_id === $u->id ? 'bg-indigo-50' : 'hover:bg-gray-50' }}">
                                    <span class="min-w-0"><span class="block truncate font-medium text-gray-800">{{ $u->name }}</span><span class="block truncate text-xs text-gray-500">{{ $u->email }}</span></span>
                                    @if ($login_user_id === $u->id)<span class="text-indigo-600">{!! $icon('check') !!}</span>@endif
                                </button>
                            @empty
                                <p class="px-3 py-4 text-center text-xs text-gray-400">No free users found.</p>
                            @endforelse
                        </div>
                        @error('login_user_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                @endif

                <div class="flex justify-end gap-2 border-t border-gray-100 pt-4">
                    <button type="button" wire:click="$set('showLoginModal', false)" class="h-10 rounded-lg border border-gray-300 px-4 text-sm font-medium text-gray-600 hover:bg-gray-50">Cancel</button>
                    <button type="submit" wire:loading.attr="disabled" wire:target="saveLogin" class="h-10 rounded-lg bg-indigo-600 px-5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-60">
                        {{ $login_mode === 'create' ? 'Create login' : 'Link user' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endif
</div>
