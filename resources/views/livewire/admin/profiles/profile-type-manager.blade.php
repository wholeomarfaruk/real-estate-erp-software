@php
    use App\Models\ProfileType;
    $svg = fn ($name, $class = 'h-4 w-4') => view('livewire.admin.profiles.partials.icon', ['name' => $name, 'class' => $class])->render();
    $in   = 'h-10 w-full rounded-lg border border-gray-300 bg-white px-3! text-sm text-gray-800 transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/15';
    $lbl  = 'mb-1 block text-xs font-medium text-gray-700';
@endphp

<div class="min-h-screen bg-slate-50 p-4 sm:p-6" x-data x-init="$store.pageName = { name: 'Profile Types', slug: 'profiles' }">
<div class="mx-auto max-w-6xl">
    <a href="{{ route('admin.profiles.index') }}" class="mb-4 inline-flex items-center gap-1.5 text-sm font-medium text-gray-500 hover:text-gray-800">
        {!! $svg('arrow-left') !!} Master Profiles
    </a>

    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900">Profile Types</h1>
            <p class="text-sm text-gray-500">Roles a master profile can hold. Add your own with custom fields.</p>
        </div>
        <button type="button" wire:click="openModal" class="inline-flex h-10 items-center gap-2 rounded-lg bg-gray-900 px-4 text-sm font-semibold text-white shadow-sm hover:bg-gray-800">
            {!! $svg('plus') !!} New Type
        </button>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($types as $type)
            @php $t = $type->tones(); @endphp
            <article wire:key="type-{{ $type->id }}" class="flex flex-col rounded-xl border border-gray-200 bg-white shadow-sm {{ $type->is_active ? '' : 'opacity-60' }}">
                <div class="flex items-start gap-3 p-4">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl {{ $t['tile'] }}">{!! $svg($type->icon, 'h-5 w-5') !!}</span>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-1.5">
                            <h3 class="font-semibold text-gray-900">{{ $type->name }}</h3>
                            @if ($type->is_system)
                                <span class="rounded bg-gray-100 px-1.5 py-px text-[10px] font-semibold uppercase text-gray-500">System</span>
                            @endif
                            @unless ($type->is_active)
                                <span class="rounded bg-gray-100 px-1.5 py-px text-[10px] font-semibold uppercase text-gray-500">Hidden</span>
                            @endunless
                        </div>
                        <p class="line-clamp-2 text-xs text-gray-500">{{ $type->description ?: '—' }}</p>
                    </div>
                    <div class="flex flex-col">
                        <button type="button" wire:click="move({{ $type->id }}, -1)" class="rounded p-0.5 text-gray-300 hover:bg-gray-100 hover:text-gray-600" title="Move up">{!! $svg('chevron-up', 'h-3.5 w-3.5') !!}</button>
                        <button type="button" wire:click="move({{ $type->id }}, 1)" class="rounded p-0.5 text-gray-300 hover:bg-gray-100 hover:text-gray-600" title="Move down">{!! $svg('chevron-down', 'h-3.5 w-3.5') !!}</button>
                    </div>
                </div>

                <div class="flex flex-wrap gap-1.5 px-4 pb-4 text-[11px]">
                    <span class="rounded-md bg-gray-100 px-2 py-0.5 font-medium text-gray-600">{{ $type->roles_count }} {{ \Illuminate\Support\Str::plural('profile', $type->roles_count) }}</span>
                    @if ($type->module)
                        <span class="rounded-md px-2 py-0.5 font-medium ring-1 ring-inset {{ $t['badge'] }}">↔ {{ $modules[$type->module]['label'] }}</span>
                    @endif
                    @if ($type->login_role)
                        <span class="rounded-md bg-indigo-50 px-2 py-0.5 font-medium text-indigo-600">Login: {{ $type->login_role }}</span>
                    @endif
                    @if (count($type->fields ?? []))
                        <span class="rounded-md bg-slate-100 px-2 py-0.5 font-medium text-slate-600">{{ count($type->fields) }} custom {{ \Illuminate\Support\Str::plural('field', count($type->fields)) }}</span>
                    @endif
                </div>

                <div class="mt-auto flex items-center justify-between border-t border-gray-100 px-4 py-2">
                    <button type="button" wire:click="toggleActive({{ $type->id }})" class="text-xs font-medium text-gray-500 hover:text-gray-800">{{ $type->is_active ? 'Hide' : 'Show' }}</button>
                    <div class="flex items-center gap-1">
                        <button type="button" wire:click="openModal({{ $type->id }})" class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-700" title="Edit">{!! $svg('edit', 'h-3.5 w-3.5') !!}</button>
                        @unless ($type->is_system)
                            <button type="button" wire:click="delete({{ $type->id }})" wire:confirm="Delete the {{ $type->name }} type?" class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 hover:bg-red-50 hover:text-red-600" title="Delete">{!! $svg('trash', 'h-3.5 w-3.5') !!}</button>
                        @endunless
                    </div>
                </div>
            </article>
        @endforeach

        <button type="button" wire:click="openModal" class="flex min-h-40 flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed border-gray-300 text-gray-400 transition hover:border-indigo-400 hover:text-indigo-600">
            {!! $svg('plus', 'h-6 w-6') !!}
            <span class="text-sm font-semibold">Add profile type</span>
        </button>
    </div>
</div>

{{-- ── Type modal ──────────────────────────────────────────────── --}}
@if ($showModal)
    @php $isSystem = $editingId && $types->firstWhere('id', $editingId)?->is_system; @endphp
    <div class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/50 p-4" wire:click.self="$set('showModal', false)" x-on:keydown.escape.window="$wire.set('showModal', false)">
        <div class="mx-auto my-6 w-full max-w-2xl overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                <h3 class="text-lg font-semibold text-gray-900">{{ $editingId ? 'Edit Profile Type' : 'New Profile Type' }}</h3>
                <button type="button" wire:click="$set('showModal', false)" class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100">{!! $svg('x', 'h-5 w-5') !!}</button>
            </div>

            <form wire:submit="save" class="space-y-5 px-6 py-5">
                {{-- Preview + name --}}
                <div class="flex items-start gap-4">
                    @php $pt = ProfileType::toneClasses($tone); @endphp
                    <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl {{ $pt['tile'] }}">{!! $svg($this->icon, 'h-7 w-7') !!}</span>
                    <div class="grid flex-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label class="{{ $lbl }}">Name <span class="text-red-500">*</span></label>
                            <input type="text" wire:model.blur="name" class="{{ $in }}" placeholder="e.g. Investor">
                            @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="{{ $lbl }}">Slug <span class="text-red-500">*</span></label>
                            <input type="text" wire:model.blur="slug" class="{{ $in }} font-mono" @disabled($isSystem)>
                            @error('slug') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label class="{{ $lbl }}">Description</label>
                            <input type="text" wire:model="description" class="{{ $in }}" placeholder="Short explanation shown in lists">
                        </div>
                    </div>
                </div>

                {{-- Icon --}}
                <div>
                    <label class="{{ $lbl }}">Icon</label>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach ($icons as $key => $label)
                            <button type="button" wire:click="$set('icon', '{{ $key }}')" title="{{ $label }}"
                                class="flex h-9 w-9 items-center justify-center rounded-lg border transition {{ $this->icon === $key ? 'border-indigo-500 bg-indigo-50 text-indigo-600' : 'border-gray-200 text-gray-500 hover:border-gray-300' }}">
                                {!! $svg($key) !!}
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Tone --}}
                <div>
                    <label class="{{ $lbl }}">Color</label>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($tones as $tn)
                            @php $c = ProfileType::toneClasses($tn); @endphp
                            <button type="button" wire:click="$set('tone', '{{ $tn }}')" title="{{ ucfirst($tn) }}"
                                class="h-7 w-7 rounded-full {{ $c['dot'] }} ring-offset-2 transition {{ $tone === $tn ? 'ring-2 ring-gray-900' : 'hover:scale-110' }}"></button>
                        @endforeach
                    </div>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="{{ $lbl }}">Linked module</label>
                        <select wire:model="module" class="{{ $in }}" @disabled($isSystem)>
                            <option value="">None — profile data only</option>
                            @foreach ($modules as $key => $m)
                                <option value="{{ $key }}">{{ $m['label'] }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-[11px] text-gray-400">Links the role to a record in that module.</p>
                        @error('module') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="{{ $lbl }}">Default login role</label>
                        <select wire:model="login_role" class="{{ $in }}">
                            <option value="">—</option>
                            @foreach ($roles as $r)
                                <option value="{{ $r }}">{{ ucfirst($r) }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-[11px] text-gray-400">Pre-selected when creating a login.</p>
                    </div>
                </div>

                {{-- Custom fields --}}
                <div>
                    <div class="mb-2 flex items-center justify-between">
                        <label class="text-xs font-medium text-gray-700">Custom fields</label>
                        <button type="button" wire:click="addField" class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 hover:text-indigo-800">{!! $svg('plus', 'h-3.5 w-3.5') !!} Add field</button>
                    </div>
                    <div class="space-y-2">
                        @forelse ($fields as $i => $field)
                            <div wire:key="field-{{ $i }}" class="rounded-lg border border-gray-200 bg-slate-50 p-3">
                                <div class="flex items-start gap-2">
                                    <div class="grid flex-1 gap-2 sm:grid-cols-[1fr_130px_auto]">
                                        <div>
                                            <input type="text" wire:model="fields.{{ $i }}.label" placeholder="Field label" class="{{ $in }}">
                                            @error("fields.$i.label") <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                        </div>
                                        <select wire:model.live="fields.{{ $i }}.type" class="{{ $in }}">
                                            @foreach ($fieldTypes as $ft => $ftl)
                                                <option value="{{ $ft }}">{{ $ftl }}</option>
                                            @endforeach
                                        </select>
                                        <label class="flex h-10 items-center gap-1.5 text-xs text-gray-600">
                                            <input type="checkbox" wire:model="fields.{{ $i }}.required" class="h-4! w-4! rounded border-gray-300 p-0! text-indigo-600"> Required
                                        </label>
                                    </div>
                                    <div class="flex items-center">
                                        <button type="button" wire:click="moveField({{ $i }}, -1)" class="rounded p-1 text-gray-400 hover:bg-white hover:text-gray-700">{!! $svg('chevron-up', 'h-3.5 w-3.5') !!}</button>
                                        <button type="button" wire:click="moveField({{ $i }}, 1)" class="rounded p-1 text-gray-400 hover:bg-white hover:text-gray-700">{!! $svg('chevron-down', 'h-3.5 w-3.5') !!}</button>
                                        <button type="button" wire:click="removeField({{ $i }})" class="rounded p-1 text-gray-400 hover:bg-red-50 hover:text-red-600">{!! $svg('trash', 'h-3.5 w-3.5') !!}</button>
                                    </div>
                                </div>
                                @if (($field['type'] ?? '') === 'select')
                                    <input type="text" wire:model="fields.{{ $i }}.options" placeholder="Options, comma separated — e.g. Bank, Individual, NBFI" class="{{ $in }} mt-2">
                                    @error("fields.$i.options") <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                @endif
                            </div>
                        @empty
                            <p class="rounded-lg border border-dashed border-gray-300 px-3 py-4 text-center text-xs text-gray-400">No custom fields. Add fields like “Loan amount” or “Share %” to capture role-specific data.</p>
                        @endforelse
                    </div>
                </div>

                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" wire:model="is_active" class="h-4! w-4! rounded border-gray-300 p-0! text-indigo-600"> Active (shown when adding roles)
                </label>

                <div class="flex justify-end gap-2 border-t border-gray-100 pt-4">
                    <button type="button" wire:click="$set('showModal', false)" class="h-10 rounded-lg border border-gray-300 px-4 text-sm font-medium text-gray-600 hover:bg-gray-50">Cancel</button>
                    <button type="submit" wire:loading.attr="disabled" wire:target="save" class="h-10 rounded-lg bg-gray-900 px-5 text-sm font-semibold text-white hover:bg-gray-800 disabled:opacity-60">
                        {{ $editingId ? 'Save changes' : 'Create type' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endif
</div>
