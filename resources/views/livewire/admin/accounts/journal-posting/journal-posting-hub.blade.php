{{-- Journal Posting Hub — category-wise entry points --}}
<div class="min-h-screen bg-slate-50 p-4 sm:p-6"
    x-data x-init="$store.pageName = { name: 'Journal Posting', slug: 'accounts' }">

@php
    $tones = [
        'rose'    => ['icon' => 'bg-rose-50 text-rose-600 ring-rose-100',          'bar' => 'bg-rose-500'],
        'sky'     => ['icon' => 'bg-sky-50 text-sky-600 ring-sky-100',             'bar' => 'bg-sky-500'],
        'emerald' => ['icon' => 'bg-emerald-50 text-emerald-600 ring-emerald-100', 'bar' => 'bg-emerald-500'],
        'amber'   => ['icon' => 'bg-amber-50 text-amber-600 ring-amber-100',       'bar' => 'bg-amber-500'],
        'violet'  => ['icon' => 'bg-violet-50 text-violet-600 ring-violet-100',    'bar' => 'bg-violet-500'],
        'slate'   => ['icon' => 'bg-slate-100 text-slate-600 ring-slate-200',      'bar' => 'bg-slate-500'],
        'indigo'  => ['icon' => 'bg-indigo-50 text-indigo-600 ring-indigo-100',    'bar' => 'bg-indigo-500'],
    ];

    $icons = [
        'expense'   => '<path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>',
        'contra'    => '<path d="M17 3l4 4-4 4"/><path d="M3 7h18"/><path d="M7 21l-4-4 4-4"/><path d="M21 17H3"/>',
        'sales'     => '<path d="M3 21h18"/><path d="M5 21V7l7-4 7 4v14"/><path d="M9 21v-6h6v6"/>',
        'payment'   => '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/><path d="M6 15h4"/>',
        'purchase'  => '<circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"/>',
        'journal'   => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/><path d="M9 7h7M9 11h5"/>',
        'custom'    => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M17.5 14v7M14 17.5h7"/>',
        'folder'    => '<path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>',
        'briefcase' => '<rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>',
        'building'  => '<path d="M4 21V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v16M16 9h2a2 2 0 0 1 2 2v10M3 21h18M8 7h4M8 11h4M8 15h4"/>',
        'megaphone' => '<path d="M3 11v2a1 1 0 0 0 1 1h3l6 4V6L7 10H4a1 1 0 0 0-1 1z"/><path d="M17 9a4 4 0 0 1 0 6"/>',
        'cog'       => '<circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3M4.9 4.9 7 7M17 17l2.1 2.1M4.9 19.1 7 17M17 7l2.1-2.1"/>',
        'tool'      => '<path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18l3 3 6.3-6.3a4 4 0 0 0 5.4-5.4l-2.5 2.5-2.4-.6-.6-2.4z"/>',
        'box'       => '<path d="M21 8 12 3 3 8v8l9 5 9-5z"/><path d="M3 8l9 5 9-5M12 13v8"/>',
        'package'   => '<path d="m7.5 4.3 9 5.2M21 8 12 3 3 8v8l9 5 9-5z"/><path d="M3.3 7 12 12l8.7-5M12 22V12"/>',
        'tag'       => '<path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L3 13V3h10l7.6 7.6a2 2 0 0 1 0 2.8z"/><circle cx="7.5" cy="7.5" r="1.5"/>',
        'layers'    => '<path d="m12 2 10 5-10 5L2 7z"/><path d="m2 17 10 5 10-5M2 12l10 5 10-5"/>',
    ];
    $svg = fn ($key, $class = 'h-4 w-4') => '<svg class="' . $class . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . ($icons[$key] ?? $icons['custom']) . '</svg>';
    $ui = [
        'up'    => '<path d="m18 15-6-6-6 6"/>',
        'down'  => '<path d="m6 9 6 6 6-6"/>',
        'edit'  => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/>',
        'trash' => '<path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6"/>',
        'x'     => '<path d="M18 6 6 18M6 6l12 12"/>',
        'plus'  => '<path d="M12 5v14M5 12h14"/>',
    ];
    $uiSvg = fn ($key, $class = 'h-3.5 w-3.5') => '<svg class="' . $class . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">' . $ui[$key] . '</svg>';

    $totalItems = $counts->sum();
    $hasFilters = trim($search) !== '' || $groupFilter !== '';
    $inputClass = 'h-10 w-full rounded-lg border border-gray-300 bg-white px-3! text-sm text-gray-800 transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/15';
    $lbl = 'mb-1 block text-xs font-medium text-gray-700';
    $err = fn ($k) => $errors->first($k);
@endphp

<div class="mx-auto max-w-7xl">
    {{-- ── Header ─────────────────────────────────────────────── --}}
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <nav class="mb-1 text-xs text-gray-400">
                <a href="{{ route('admin.dashboard') }}" class="hover:text-gray-600">Dashboard</a>
                <span class="mx-1">/</span> Accounts <span class="mx-1">/</span>
                <span class="text-gray-600">Journal Posting</span>
            </nav>
            <h1 class="text-xl font-bold text-gray-900">Journal Posting</h1>
            <p class="text-sm text-gray-500">Choose a voucher category and start an entry.</p>
        </div>

        <div class="flex items-center gap-2">
            <button type="button" wire:click="openGroupsModal"
                class="inline-flex h-10 items-center gap-2 rounded-lg border border-gray-200 bg-white px-3.5 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50">
                {!! $svg('layers') !!} Categories
            </button>
            <button type="button" wire:click="openAddModal"
                class="inline-flex h-10 items-center gap-2 rounded-lg bg-gray-900 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800">
                {!! $uiSvg('plus', 'h-4 w-4') !!} Add Item
            </button>
        </div>
    </div>

    <div class="grid gap-5 lg:grid-cols-[220px_1fr]">
        {{-- ── Category rail ─────────────────────────────────── --}}
        <aside class="lg:sticky lg:top-4 lg:self-start">
            <div class="flex gap-1.5 overflow-x-auto rounded-xl border border-gray-200 bg-white p-1.5 lg:flex-col lg:overflow-visible">
                <button type="button" wire:click="$set('groupFilter', '')"
                    class="flex shrink-0 items-center justify-between gap-3 rounded-lg px-3 py-2 text-left text-sm transition {{ $groupFilter === '' ? 'bg-gray-900 font-semibold text-white' : 'text-gray-600 hover:bg-gray-50' }}">
                    <span>All categories</span>
                    <span class="font-mono text-[11px] {{ $groupFilter === '' ? 'text-white/70' : 'text-gray-400' }}">{{ $totalItems }}</span>
                </button>
                @foreach ($allGroups as $group)
                    @php $active = $groupFilter === $group['key']; $t = $tones[$group['tone']] ?? $tones['indigo']; @endphp
                    <button type="button" wire:click="setGroup('{{ $group['key'] }}')" wire:key="rail-{{ $group['key'] }}"
                        class="flex shrink-0 items-center justify-between gap-3 rounded-lg px-3 py-2 text-left text-sm transition {{ $active ? 'bg-gray-100 font-semibold text-gray-900' : 'text-gray-600 hover:bg-gray-50' }}">
                        <span class="flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full {{ $t['bar'] }}"></span>
                            {{ $group['name'] }}
                        </span>
                        <span class="font-mono text-[11px] text-gray-400">{{ $counts[$group['key']] }}</span>
                    </button>
                @endforeach
            </div>
        </aside>

        {{-- ── Main ──────────────────────────────────────────── --}}
        <div class="min-w-0">
            {{-- Search --}}
            <div class="mb-4 flex items-center gap-2">
                <div class="relative flex-1">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search entries — salary, transfer, sale…"
                        class="h-11 w-full rounded-xl border border-gray-200 bg-white pl-10! pr-10! text-sm text-gray-800 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/15">
                    @if (trim($search) !== '')
                        <button type="button" wire:click="$set('search', '')" class="absolute right-3 top-1/2 -translate-y-1/2 rounded p-0.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600" aria-label="Clear search">{!! $uiSvg('x', 'h-4 w-4') !!}</button>
                    @endif
                </div>
                @if ($hasFilters)
                    <button type="button" wire:click="clearFilters" class="h-11 shrink-0 rounded-xl border border-gray-200 bg-white px-3 text-sm font-medium text-gray-600 hover:bg-gray-50">Reset</button>
                @endif
            </div>

            {{-- Category panels --}}
            <div class="grid gap-4 xl:grid-cols-2" wire:loading.class="opacity-60" wire:target="search,setGroup,clearFilters">
                @forelse ($visibleGroups as $group)
                    @php $t = $tones[$group['tone']] ?? $tones['indigo']; @endphp
                    <section wire:key="group-{{ $group['key'] }}" class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                        <div class="flex items-center gap-3 border-b border-gray-100 px-4 py-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg ring-1 {{ $t['icon'] }}">{!! $svg($group['icon'], 'h-[18px] w-[18px]') !!}</span>
                            <div class="min-w-0 flex-1">
                                <h2 class="flex items-center gap-2 text-sm font-semibold text-gray-900">
                                    {{ $group['name'] }}
                                    <span class="rounded-full bg-gray-100 px-1.5 py-px font-mono text-[10px] font-semibold text-gray-500">{{ count($group['items']) }}</span>
                                </h2>
                                <p class="truncate text-xs text-gray-500">{{ $group['description'] }}</p>
                            </div>
                            <button type="button" wire:click="openAddModal('{{ $group['key'] }}')" title="Add item to {{ $group['name'] }}"
                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-700">{!! $uiSvg('plus', 'h-4 w-4') !!}</button>
                        </div>

                        <ul class="divide-y divide-gray-100">
                            @foreach ($group['items'] as $item)
                                @php
                                    $editable = in_array($item['source'], ['item', 'expense'], true) && ! ($item['locked'] ?? false);
                                    $movable  = trim($search) === '' && ($item['source'] === 'item'
                                        || ($item['source'] === 'expense' && auth()->user()?->can('accounts.expense.edit')));
                                    $tag = $item['url'] ? 'a' : 'div';
                                @endphp
                                <li wire:key="item-{{ $item['source'] }}-{{ $item['id'] ?? $group['key'] . $loop->index }}" class="group/item relative">
                                    <{{ $tag }} @if ($item['url']) href="{{ $item['url'] }}" @endif
                                        class="flex items-center gap-3 px-4 py-3 transition {{ $item['url'] ? 'hover:bg-gray-50' : '' }} {{ $editable || $movable ? 'pr-36' : '' }}">
                                        <div class="min-w-0 flex-1">
                                            <p class="flex items-center gap-1.5 truncate text-sm font-medium {{ $item['url'] ? 'text-gray-800' : 'text-gray-400' }}">
                                                <span class="truncate">{{ $item['label'] }}</span>
                                                @if ($item['locked'] ?? false)
                                                    <svg class="h-3 w-3 shrink-0 text-gray-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
                                                @endif
                                            </p>
                                            @if ($item['description'])
                                                <p class="truncate text-xs text-gray-500">{{ $item['description'] }}</p>
                                            @endif
                                            @php
                                                $badges = array_filter([
                                                    $item['type_label'] ? ['bg-gray-100 text-gray-600', $item['type_label']] : null,
                                                    $item['feature_label'] ? ['bg-indigo-50 text-indigo-600', $item['feature_label']] : null,
                                                    ! empty($item['dr']) ? ['bg-emerald-50 text-emerald-700', 'Dr: ' . $item['dr']] : null,
                                                    ! empty($item['cr']) ? ['bg-rose-50 text-rose-700', 'Cr: ' . $item['cr']] : null,
                                                    ! empty($item['direct']) ? ['bg-emerald-50 text-emerald-700', 'Direct post'] : null,
                                                    ! empty($item['voucher']) ? ['bg-sky-50 text-sky-700', $item['voucher']] : null,
                                                    ! empty($item['custom']) ? ['bg-violet-50 text-violet-700', $item['custom'] . ' custom ' . \Illuminate\Support\Str::plural('field', $item['custom'])] : null,
                                                    ! empty($item['restricted']) ? ['bg-amber-50 text-amber-700', 'Restricted'] : null,
                                                ]);
                                            @endphp
                                            @if ($badges)
                                                <div class="mt-1 flex flex-wrap gap-1">
                                                    @foreach ($badges as [$cls, $text])
                                                        <span class="rounded px-1.5 py-px text-[10px] font-medium {{ $cls }}">{{ $text }}</span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                        @unless ($item['url'])
                                            <span class="shrink-0 rounded-md bg-gray-100 px-2 py-1 text-[10px] font-semibold uppercase tracking-wide text-gray-400">{{ ! empty($item['no_access']) ? 'No access' : 'Soon' }}</span>
                                        @endunless
                                    </{{ $tag }}>

                                    @if ($editable || $movable)
                                        <div class="absolute right-3 top-1/2 flex -translate-y-1/2 items-center gap-0.5">
                                            @if ($movable)
                                                <button type="button" wire:click="moveItem('{{ $item['source'] }}', {{ $item['id'] }}, -1)" title="Move up" class="flex h-7 w-7 items-center justify-center rounded-md text-gray-300 hover:bg-gray-100 hover:text-gray-600">{!! $uiSvg('up') !!}</button>
                                                <button type="button" wire:click="moveItem('{{ $item['source'] }}', {{ $item['id'] }}, 1)" title="Move down" class="flex h-7 w-7 items-center justify-center rounded-md text-gray-300 hover:bg-gray-100 hover:text-gray-600">{!! $uiSvg('down') !!}</button>
                                            @endif
                                            @if ($editable)
                                                <button type="button" wire:click="openEditModal('{{ $item['source'] }}', {{ $item['id'] }})" title="Edit / settings"
                                                    class="flex h-7 w-7 items-center justify-center rounded-md text-gray-400 transition hover:bg-indigo-50 hover:text-indigo-600">{!! $uiSvg('edit') !!}</button>
                                                <button type="button" wire:click="deleteItem('{{ $item['source'] }}', {{ $item['id'] }})"
                                                    wire:confirm="Remove &quot;{{ $item['label'] }}&quot;?{{ $item['source'] === 'expense' ? ' This also removes it from the Expenses page.' : '' }}"
                                                    title="Remove item" class="flex h-7 w-7 items-center justify-center rounded-md text-gray-400 transition hover:bg-red-50 hover:text-red-600">{!! $uiSvg('trash') !!}</button>
                                            @endif
                                        </div>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @empty
                    <div class="rounded-xl border border-dashed border-gray-300 bg-white py-14 text-center xl:col-span-2">
                        <p class="text-sm font-medium text-gray-600">No entries match your search</p>
                        <p class="mt-1 text-xs text-gray-400">Try another keyword, or add it as a new item.</p>
                        <div class="mt-4 flex justify-center gap-2">
                            <button type="button" wire:click="clearFilters" class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-50">Clear filters</button>
                            <button type="button" wire:click="openAddModal" class="rounded-lg bg-gray-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-gray-800">Add Item</button>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- ── Add / edit item modal ─────────────────────────────────── --}}
@if ($showModal)
    @php
        $NEW = \App\Livewire\Admin\Accounts\JournalPosting\JournalPostingHub::NEW_GROUP;
        $isExpense = $this->isExpenseForm();
        $tabs = $isExpense ? ['general' => 'General'] : ['general' => 'General', 'form' => 'Entry form', 'behaviour' => 'Behaviour'];
        $tabErrors = [
            'general'   => collect($errors->keys())->contains(fn ($k) => ! str_starts_with($k, 'form_fields') && ! str_starts_with($k, 'form_custom_fields') && ! str_starts_with($k, 'form_behaviour')),
            'form'      => collect($errors->keys())->contains(fn ($k) => str_starts_with($k, 'form_fields') || str_starts_with($k, 'form_custom_fields')),
            'behaviour' => collect($errors->keys())->contains(fn ($k) => str_starts_with($k, 'form_behaviour')),
        ];
    @endphp
    <div class="fixed inset-0 z-50 overflow-y-auto bg-black/50 p-4" wire:click.self="closeModal" x-on:keydown.escape.window="$wire.closeModal()">
        <div class="mx-auto my-6 w-full max-w-2xl overflow-hidden rounded-xl bg-white shadow-xl">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                <div>
                    <h3 class="text-base font-semibold text-gray-900">{{ $editingId ? 'Edit Item' : 'Add Item' }}</h3>
                    @if ($form_label)<p class="text-xs text-gray-500">{{ $form_label }}</p>@endif
                </div>
                <button type="button" wire:click="closeModal" class="rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600" aria-label="Close">{!! $uiSvg('x', 'h-5 w-5') !!}</button>
            </div>

            @if (count($tabs) > 1)
                <div class="flex gap-1 border-b border-gray-100 px-5">
                    @foreach ($tabs as $key => $label)
                        <button type="button" wire:click="$set('modalTab', '{{ $key }}')"
                            class="relative -mb-px border-b-2 px-3 py-2.5 text-sm font-medium transition {{ $modalTab === $key ? 'border-gray-900 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                            {{ $label }}
                            @if ($tabErrors[$key])<span class="absolute right-0.5 top-2 h-1.5 w-1.5 rounded-full bg-red-500"></span>@endif
                        </button>
                    @endforeach
                </div>
            @endif

            <form wire:submit="saveItem">
                <div class="max-h-[65vh] space-y-4 overflow-y-auto px-5 py-4">

                {{-- ── General ───────────────────────────── --}}
                @if ($modalTab === 'general' || $isExpense)
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="{{ $form_group === $NEW ? '' : 'sm:col-span-2' }}">
                            <label class="{{ $lbl }}">Category <span class="text-red-500">*</span></label>
                            <select wire:model.live="form_group" class="{{ $inputClass }}" @disabled($editingType === 'expense')>
                                @foreach ($categoryOptions as $cat)
                                    <option value="{{ $cat->key }}">{{ $cat->name }}{{ $cat->is_active ? '' : ' (hidden)' }}</option>
                                @endforeach
                                @unless ($editingId)
                                    <option value="{{ $NEW }}">+ New category…</option>
                                @endunless
                            </select>
                            @if ($form_group === 'expense' && ! $editingId)
                                <div class="mt-2 inline-flex rounded-md bg-gray-100 p-0.5">
                                    @foreach (['category' => 'Simple expense category', 'item' => 'Journal item (full settings)'] as $kind => $kindLabel)
                                        <button type="button" wire:click="$set('form_kind', '{{ $kind }}')"
                                            class="rounded px-2.5 py-1 text-xs font-medium transition {{ $form_kind === $kind ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">{{ $kindLabel }}</button>
                                    @endforeach
                                </div>
                            @endif
                            @if ($isExpense)
                                <p class="mt-1 text-[11px] text-indigo-500">Saved as an expense category — it also appears on the Expenses page.</p>
                            @elseif ($form_group === 'expense')
                                <p class="mt-1 text-[11px] text-indigo-500">Journal item — field rules, approval, voucher numbering, custom fields and project link are configurable.</p>
                            @endif
                            @if ($err('form_group')) <p class="mt-1 text-xs text-red-600">{{ $err('form_group') }}</p> @endif
                        </div>
                        @if ($form_group === $NEW)
                            <div>
                                <label class="{{ $lbl }}">New category name <span class="text-red-500">*</span></label>
                                <input type="text" wire:model="form_group_name" placeholder="e.g. Receipts" class="{{ $inputClass }}">
                                @if ($err('form_group_name')) <p class="mt-1 text-xs text-red-600">{{ $err('form_group_name') }}</p> @endif
                            </div>
                        @endif
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="{{ $lbl }}">Name <span class="text-red-500">*</span></label>
                            <input type="text" wire:model.blur="form_label" maxlength="150" placeholder="e.g. Loan Repayment" class="{{ $inputClass }}">
                            @if ($err('form_label')) <p class="mt-1 text-xs text-red-600">{{ $err('form_label') }}</p> @endif
                        </div>
                        <div>
                            <div class="mb-1 flex items-center justify-between">
                                <label class="block text-xs font-medium text-gray-700">Slug <span class="text-red-500">*</span></label>
                                @if (! $slugManuallyEdited && $form_slug)<span class="text-[11px] font-medium text-indigo-500">✦ auto</span>@endif
                            </div>
                            <input type="text" wire:model.blur="form_slug" maxlength="80" placeholder="e.g. loan-repayment" class="{{ $inputClass }} font-mono">
                            @if ($err('form_slug')) <p class="mt-1 text-xs text-red-600">{{ $err('form_slug') }}</p> @endif
                        </div>
                    </div>

                    <div>
                        <label class="{{ $lbl }}">Description</label>
                        <textarea wire:model="form_description" rows="2" maxlength="500" placeholder="Short note shown under the name"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3! py-2! text-sm text-gray-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/15"></textarea>
                    </div>

                    @if ($isExpense)
                        {{-- Expense categories: Dr expense head (feature) · Cr cash/bank (expense form) --}}
                        <div>
                            <label class="{{ $lbl }}">Feature account <span class="font-normal text-gray-400">(expense heads)</span></label>
                            <select wire:model="form_feature_type" class="{{ $inputClass }}">
                                <option value="">— All active accounts —</option>
                                @foreach ($featureAccounts as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-[11px] text-gray-400">The expense form debits one of these and credits a cash / bank account.</p>
                        </div>
                    @else
                        <div>
                            <label class="{{ $lbl }}">Posting type <span class="text-red-500">*</span></label>
                            <select wire:model.live="form_transaction_type" class="{{ $inputClass }}">
                                @foreach ($transactionTypes as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-[11px] text-gray-400">Recorded on the ledger entry{{ $editingId ? '' : ' — also suggests the account sides below' }}.</p>
                        </div>

                        {{-- Double entry: where each side's accounts come from --}}
                        <div class="rounded-lg border border-gray-200 p-3">
                            <p class="mb-2 text-xs font-medium text-gray-700">Double entry — account lists</p>
                            <div class="grid gap-3 sm:grid-cols-2">
                                @foreach ([
                                    ['form_debit_source',  'Debit (Dr)',  'bg-emerald-50 text-emerald-700', 'border-emerald-400'],
                                    ['form_credit_source', 'Credit (Cr)', 'bg-rose-50 text-rose-700',       'border-rose-400'],
                                ] as [$prop, $title, $chip, $bar])
                                    <div class="border-l-4 {{ $bar }} pl-2.5">
                                        <label class="mb-1 flex items-center gap-1.5 text-xs font-medium text-gray-700">
                                            <span class="rounded px-1.5 py-px text-[10px] font-bold {{ $chip }}">{{ \Illuminate\Support\Str::before($title, ' ') === 'Debit' ? 'Dr' : 'Cr' }}</span>
                                            {{ $title }} accounts from <span class="text-red-500">*</span>
                                        </label>
                                        <select wire:model="{{ $prop }}" class="{{ $inputClass }}">
                                            <option value="{{ \App\Models\JournalPostingItem::SOURCE_ALL }}">All active accounts</option>
                                            <option value="{{ \App\Models\JournalPostingItem::SOURCE_CASHBANK }}">Cash &amp; bank accounts</option>
                                            @php
                                                $current = $this->{$prop};
                                                $currentFeature = \App\Models\JournalPostingItem::sourceFeature($current);
                                            @endphp
                                            @if ($allFeatures->isNotEmpty())
                                                <optgroup label="Feature accounts">
                                                    @foreach ($allFeatures as $feature)
                                                        <option value="{{ \App\Models\JournalPostingItem::SOURCE_FEATURE_PREFIX . $feature->key }}">{{ $feature->label }}{{ $feature->is_active ? '' : ' (inactive)' }}</option>
                                                    @endforeach
                                                </optgroup>
                                            @endif
                                            {{-- Keep a stored value visible even if its feature was deleted --}}
                                            @if ($currentFeature !== null && ! $allFeatures->contains('key', $currentFeature))
                                                <option value="{{ $current }}">{{ $currentFeature }} (missing feature)</option>
                                            @endif
                                        </select>
                                        @if ($err($prop)) <p class="mt-1 text-xs text-red-600">{{ $err($prop) }}</p> @endif
                                    </div>
                                @endforeach
                            </div>
                            <p class="mt-2 text-[11px] text-gray-400">
                                e.g. Loan received → Dr: Cash &amp; bank · Cr: Loans feature. Salary paid → Dr: Salary feature · Cr: Cash &amp; bank.
                                Manage feature lists in <a href="{{ route('admin.accounts.settings.features') }}" class="text-indigo-600 hover:underline" target="_blank">Feature Accounts</a>.
                            </p>
                        </div>
                    @endif

                    @unless ($isExpense)
                        <div>
                            <label class="{{ $lbl }}">Custom link <span class="font-normal text-gray-400">(optional)</span></label>
                            <input type="text" wire:model="form_url" placeholder="/admin/..." class="{{ $inputClass }}">
                            <p class="mt-1 text-[11px] text-gray-400">Empty = built-in entry form. A link opens another page instead (form &amp; behaviour settings then don't apply).</p>
                            @if ($err('form_url')) <p class="mt-1 text-xs text-red-600">{{ $err('form_url') }}</p> @endif
                        </div>
                    @endunless
                @endif

                {{-- ── Entry form ────────────────────────── --}}
                @if ($modalTab === 'form' && ! $isExpense)
                    <div>
                        <div class="mb-1.5 flex items-center justify-between">
                            <label class="block text-xs font-medium text-gray-700">Standard fields</label>
                            <span class="text-[11px] text-gray-400">Accounts, amount, date &amp; title are always required</span>
                        </div>
                        <div class="divide-y divide-gray-100 overflow-hidden rounded-lg border border-gray-200">
                            @foreach ($formFieldLabels as $key => $label)
                                @php
                                    $mode = $form_fields[$key] ?? 'optional';
                                    $autoVoucher = $key === 'voucher_no' && trim((string) ($form_behaviour['voucher_format'] ?? '')) !== '';
                                @endphp
                                <div class="flex items-center justify-between gap-3 px-3 py-2" wire:key="ff-{{ $key }}">
                                    <span class="text-sm {{ $mode === 'hidden' ? 'text-gray-400 line-through' : 'text-gray-700' }}">
                                        {{ $label }}
                                        @if ($autoVoucher)<span class="ml-1 rounded bg-sky-50 px-1.5 py-px text-[10px] font-medium text-sky-700 no-underline">auto-numbered</span>@endif
                                    </span>
                                    @if ($autoVoucher)
                                        <span class="text-[11px] text-gray-400">set by Behaviour → Voucher format</span>
                                    @else
                                        <div class="inline-flex shrink-0 rounded-md bg-gray-100 p-0.5">
                                            @foreach (['hidden' => 'Hidden', 'optional' => 'Optional', 'required' => 'Required'] as $m => $mLabel)
                                                <button type="button" wire:click="$set('form_fields.{{ $key }}', '{{ $m }}')"
                                                    class="rounded px-2 py-1 text-[11px] font-semibold transition {{ $mode === $m
                                                        ? ($m === 'required' ? 'bg-white text-rose-600 shadow-sm' : 'bg-white text-gray-900 shadow-sm')
                                                        : 'text-gray-500 hover:text-gray-700' }}">{{ $mLabel }}</button>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <div class="mb-1.5 flex items-center justify-between">
                            <label class="block text-xs font-medium text-gray-700">Custom fields</label>
                            <button type="button" wire:click="addCustomField" class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 hover:text-indigo-800">{!! $uiSvg('plus') !!} Add field</button>
                        </div>
                        <div class="space-y-2">
                            @forelse ($form_custom_fields as $i => $field)
                                <div wire:key="cf-{{ $i }}" class="rounded-lg border border-gray-200 bg-slate-50 p-3">
                                    <div class="flex items-start gap-2">
                                        <div class="grid flex-1 gap-2 sm:grid-cols-[1fr_130px_auto]">
                                            <div>
                                                <input type="text" wire:model="form_custom_fields.{{ $i }}.label" placeholder="Field label — e.g. Cheque date" class="{{ $inputClass }}">
                                                @if ($err("form_custom_fields.$i.label")) <p class="mt-1 text-xs text-red-600">{{ $err("form_custom_fields.$i.label") }}</p> @endif
                                            </div>
                                            <select wire:model.live="form_custom_fields.{{ $i }}.type" class="{{ $inputClass }}">
                                                @foreach ($customFieldTypes as $ft => $ftl)
                                                    <option value="{{ $ft }}">{{ $ftl }}</option>
                                                @endforeach
                                            </select>
                                            <label class="flex h-10 items-center gap-1.5 text-xs text-gray-600">
                                                <input type="checkbox" wire:model="form_custom_fields.{{ $i }}.required" class="h-4! w-4! rounded border-gray-300 p-0! text-indigo-600"> Required
                                            </label>
                                        </div>
                                        <div class="flex items-center">
                                            <button type="button" wire:click="moveCustomField({{ $i }}, -1)" class="rounded p-1 text-gray-400 hover:bg-white hover:text-gray-700">{!! $uiSvg('up') !!}</button>
                                            <button type="button" wire:click="moveCustomField({{ $i }}, 1)" class="rounded p-1 text-gray-400 hover:bg-white hover:text-gray-700">{!! $uiSvg('down') !!}</button>
                                            <button type="button" wire:click="removeCustomField({{ $i }})" class="rounded p-1 text-gray-400 hover:bg-red-50 hover:text-red-600">{!! $uiSvg('trash') !!}</button>
                                        </div>
                                    </div>
                                    @if (($field['type'] ?? '') === 'select')
                                        <input type="text" wire:model="form_custom_fields.{{ $i }}.options" placeholder="Options, comma separated — e.g. Bank, Cash, Cheque" class="{{ $inputClass }} mt-2">
                                        @if ($err("form_custom_fields.$i.options")) <p class="mt-1 text-xs text-red-600">{{ $err("form_custom_fields.$i.options") }}</p> @endif
                                    @endif
                                </div>
                            @empty
                                <p class="rounded-lg border border-dashed border-gray-300 px-3 py-4 text-center text-xs text-gray-400">No custom fields. Add extra inputs like “Cheque date” or “Project” — values are saved with the entry.</p>
                            @endforelse
                        </div>
                    </div>
                @endif

                {{-- ── Behaviour ─────────────────────────── --}}
                @if ($modalTab === 'behaviour' && ! $isExpense)
                    {{-- Approval --}}
                    <div class="rounded-lg border border-gray-200 p-3">
                        <p class="mb-2 text-xs font-medium text-gray-700">Posting</p>
                        <div class="grid gap-2 sm:grid-cols-2">
                            @foreach ([true => ['Needs approval', 'Goes to Payment Requests; posts after approval.'], false => ['Post directly', 'Posts to the ledger as soon as it is submitted.']] as $val => [$title, $hint])
                                @php $on = (bool) ($form_behaviour['requires_approval'] ?? true) === (bool) $val; @endphp
                                <button type="button" wire:click="$set('form_behaviour.requires_approval', {{ $val ? 'true' : 'false' }})"
                                    class="rounded-lg border p-3 text-left transition {{ $on ? 'border-indigo-400 bg-indigo-50/40 ring-2 ring-indigo-500/15' : 'border-gray-200 hover:border-gray-300' }}">
                                    <span class="block text-sm font-semibold text-gray-900">{{ $title }}</span>
                                    <span class="block text-[11px] text-gray-500">{{ $hint }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Voucher numbering --}}
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="{{ $lbl }}">Voucher format <span class="font-normal text-gray-400">(auto number)</span></label>
                            <input type="text" wire:model.live.debounce.400ms="form_behaviour.voucher_format" placeholder="e.g. JV-{YYYY}-{####}" class="{{ $inputClass }} font-mono">
                            <p class="mt-1 text-[11px] text-gray-400">Tokens: {YYYY} {YY} {MM} {####}. Empty = typed by hand. Resets yearly when it has a year.</p>
                            @if ($err('form_behaviour.voucher_format')) <p class="mt-1 text-xs text-red-600">{{ $err('form_behaviour.voucher_format') }}</p> @endif
                        </div>
                        <div>
                            <label class="{{ $lbl }}">Preview</label>
                            <div class="flex h-10 items-center rounded-lg border border-dashed border-gray-300 bg-slate-50 px-3 font-mono text-sm {{ $voucherPreview ? 'text-gray-800' : 'text-gray-400' }}">
                                {{ $voucherPreview ?? 'Manual voucher no' }}
                            </div>
                        </div>
                    </div>

                    {{-- Amount limits --}}
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="{{ $lbl }}">Minimum amount</label>
                            <input type="number" step="0.01" min="0" wire:model="form_behaviour.min_amount" placeholder="No minimum" class="{{ $inputClass }}">
                            @if ($err('form_behaviour.min_amount')) <p class="mt-1 text-xs text-red-600">{{ $err('form_behaviour.min_amount') }}</p> @endif
                        </div>
                        <div>
                            <label class="{{ $lbl }}">Maximum amount</label>
                            <input type="number" step="0.01" min="0" wire:model="form_behaviour.max_amount" placeholder="No maximum" class="{{ $inputClass }}">
                            @if ($err('form_behaviour.max_amount')) <p class="mt-1 text-xs text-red-600">{{ $err('form_behaviour.max_amount') }}</p> @endif
                        </div>
                    </div>

                    {{-- Defaults --}}
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="{{ $lbl }}">Default method</label>
                            <select wire:model="form_behaviour.default_method" class="{{ $inputClass }}">
                                @foreach ($methods as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <label class="mt-1.5 flex items-center gap-1.5 text-xs text-gray-600">
                                <input type="checkbox" wire:model="form_behaviour.lock_method" class="h-4! w-4! rounded border-gray-300 p-0! text-indigo-600"> Lock (users can't change it)
                            </label>
                        </div>
                        <div>
                            <label class="{{ $lbl }}">Default title</label>
                            <input type="text" wire:model="form_behaviour.title_template" placeholder="e.g. {label} — {date}" class="{{ $inputClass }}">
                            <p class="mt-1 text-[11px] text-gray-400">Tokens: {label} {date}. Empty = item name.</p>
                        </div>
                        <div>
                            <label class="{{ $lbl }}">Default debit account (Dr)</label>
                            <select wire:model="form_behaviour.default_debit_account_id" class="{{ $inputClass }}">
                                <option value="">— None —</option>
                                @foreach ($debitOptions as $acc)
                                    <option value="{{ $acc->id }}">{{ $acc->code }} — {{ $acc->name }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-[11px] text-gray-400">From the Dr list set on the General tab.</p>
                            @if ($err('form_behaviour.default_debit_account_id')) <p class="mt-1 text-xs text-red-600">This account isn't in the Dr list — pick another or change the Dr source.</p> @endif
                        </div>
                        <div>
                            <label class="{{ $lbl }}">Default credit account (Cr)</label>
                            <select wire:model="form_behaviour.default_credit_account_id" class="{{ $inputClass }}">
                                <option value="">— None —</option>
                                @foreach ($creditOptions as $acc)
                                    <option value="{{ $acc->id }}">{{ $acc->code }} — {{ $acc->name }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-[11px] text-gray-400">From the Cr list set on the General tab.</p>
                            @if ($err('form_behaviour.default_credit_account_id')) <p class="mt-1 text-xs text-red-600">This account isn't in the Cr list — pick another or change the Cr source.</p> @endif
                        </div>
                    </div>

                    {{-- Access --}}
                    <div>
                        <label class="{{ $lbl }}">Who can use it</label>
                        <div class="flex flex-wrap gap-1.5 rounded-lg border border-gray-200 p-2.5">
                            @foreach ($roles as $role)
                                @php $on = in_array($role, $form_behaviour['allowed_roles'] ?? [], true); @endphp
                                <label wire:key="role-{{ $role }}" class="inline-flex cursor-pointer items-center gap-1.5 rounded-md border px-2 py-1 text-xs transition {{ $on ? 'border-indigo-300 bg-indigo-50 text-indigo-700' : 'border-gray-200 text-gray-600 hover:border-gray-300' }}">
                                    <input type="checkbox" value="{{ $role }}" wire:model.live="form_behaviour.allowed_roles" class="h-3.5! w-3.5! rounded border-gray-300 p-0! text-indigo-600">
                                    {{ ucfirst($role) }}
                                </label>
                            @endforeach
                        </div>
                        <p class="mt-1 text-[11px] text-gray-400">None selected = everyone with Journal Posting access. Superadmin can always use it.</p>
                    </div>
                @endif
                </div>

                <div class="flex justify-end gap-2 border-t border-gray-100 px-5 py-4">
                    <button type="button" wire:click="closeModal" class="h-10 rounded-lg border border-gray-300 px-4 text-sm font-medium text-gray-600 hover:bg-gray-50">Cancel</button>
                    <button type="submit" class="inline-flex h-10 items-center rounded-lg bg-gray-900 px-4 text-sm font-semibold text-white hover:bg-gray-800 disabled:opacity-60" wire:loading.attr="disabled" wire:target="saveItem">
                        <span wire:loading.remove wire:target="saveItem">{{ $editingId ? 'Update Item' : 'Create Item' }}</span>
                        <span wire:loading wire:target="saveItem">Saving…</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
@endif

{{-- ── Category manager ──────────────────────────────────────── --}}
@if ($showGroupsModal)
    <div class="fixed inset-0 z-50 overflow-y-auto bg-black/50 p-4" wire:click.self="$set('showGroupsModal', false)" x-on:keydown.escape.window="$wire.set('showGroupsModal', false)">
        <div class="mx-auto my-6 w-full max-w-xl overflow-hidden rounded-xl bg-white shadow-xl">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                <div>
                    <h3 class="text-base font-semibold text-gray-900">Categories</h3>
                    <p class="text-xs text-gray-500">Rename, recolour, reorder or hide. Built-in categories can't be deleted.</p>
                </div>
                <button type="button" wire:click="$set('showGroupsModal', false)" class="rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600">{!! $uiSvg('x', 'h-5 w-5') !!}</button>
            </div>

            @if ($showGroupForm)
                <form wire:submit="saveGroup" class="space-y-4 border-b border-gray-100 bg-slate-50 px-5 py-4">
                    <p class="text-sm font-semibold text-gray-900">{{ $editingGroupId ? 'Edit category' : 'New category' }}</p>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="{{ $lbl }}">Name <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="g_name" class="{{ $inputClass }}" placeholder="e.g. Receipts">
                            @if ($err('g_name')) <p class="mt-1 text-xs text-red-600">{{ $err('g_name') }}</p> @endif
                        </div>
                        <div>
                            <label class="{{ $lbl }}">Description</label>
                            <input type="text" wire:model="g_description" class="{{ $inputClass }}">
                        </div>
                    </div>
                    <div>
                        <label class="{{ $lbl }}">Icon</label>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach (\App\Models\JournalPostingGroup::ICONS as $key => $label)
                                <button type="button" wire:click="$set('g_icon', '{{ $key }}')" title="{{ $label }}"
                                    class="flex h-9 w-9 items-center justify-center rounded-lg border bg-white transition {{ $g_icon === $key ? 'border-indigo-500 text-indigo-600 ring-2 ring-indigo-500/15' : 'border-gray-200 text-gray-500 hover:border-gray-300' }}">{!! $svg($key) !!}</button>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <label class="{{ $lbl }}">Colour</label>
                        <div class="flex flex-wrap gap-2">
                            @foreach (\App\Models\JournalPostingGroup::TONES as $tn)
                                <button type="button" wire:click="$set('g_tone', '{{ $tn }}')" title="{{ ucfirst($tn) }}"
                                    class="h-7 w-7 rounded-full {{ $tones[$tn]['bar'] }} ring-offset-2 transition {{ $g_tone === $tn ? 'ring-2 ring-gray-900' : 'hover:scale-110' }}"></button>
                            @endforeach
                        </div>
                    </div>
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" wire:model="g_active" class="h-4! w-4! rounded border-gray-300 p-0! text-indigo-600"> Visible on Journal Posting
                    </label>
                    <div class="flex justify-end gap-2">
                        <button type="button" wire:click="$set('showGroupForm', false)" class="h-9 rounded-lg border border-gray-300 px-3 text-sm font-medium text-gray-600 hover:bg-white">Cancel</button>
                        <button type="submit" class="h-9 rounded-lg bg-gray-900 px-4 text-sm font-semibold text-white hover:bg-gray-800">Save category</button>
                    </div>
                </form>
            @endif

            <ul class="max-h-[55vh] divide-y divide-gray-100 overflow-y-auto">
                @foreach ($managedGroups as $g)
                    @php $t = $tones[$g->tone] ?? $tones['indigo']; @endphp
                    <li wire:key="mg-{{ $g->id }}" class="flex items-center gap-3 px-5 py-3 {{ $g->is_active ? '' : 'opacity-60' }}">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg ring-1 {{ $t['icon'] }}">{!! $svg($g->icon) !!}</span>
                        <div class="min-w-0 flex-1">
                            <p class="flex items-center gap-1.5 text-sm font-medium text-gray-900">
                                {{ $g->name }}
                                @if ($g->is_system)<span class="rounded bg-gray-100 px-1.5 py-px text-[10px] font-semibold uppercase text-gray-500">Built-in</span>@endif
                                @unless ($g->is_active)<span class="rounded bg-gray-100 px-1.5 py-px text-[10px] font-semibold uppercase text-gray-500">Hidden</span>@endunless
                            </p>
                            <p class="truncate text-xs text-gray-500">{{ $g->description ?: '—' }} · {{ $g->items_count }} custom {{ \Illuminate\Support\Str::plural('item', $g->items_count) }}</p>
                        </div>
                        <div class="flex items-center gap-0.5">
                            <button type="button" wire:click="moveGroup({{ $g->id }}, -1)" title="Move up" class="flex h-7 w-7 items-center justify-center rounded-md text-gray-400 hover:bg-gray-100 hover:text-gray-700">{!! $uiSvg('up') !!}</button>
                            <button type="button" wire:click="moveGroup({{ $g->id }}, 1)" title="Move down" class="flex h-7 w-7 items-center justify-center rounded-md text-gray-400 hover:bg-gray-100 hover:text-gray-700">{!! $uiSvg('down') !!}</button>
                            <button type="button" wire:click="toggleGroup({{ $g->id }})" class="rounded-md px-2 py-1 text-[11px] font-semibold text-gray-500 hover:bg-gray-100 hover:text-gray-800">{{ $g->is_active ? 'Hide' : 'Show' }}</button>
                            <button type="button" wire:click="openGroupForm({{ $g->id }})" title="Edit" class="flex h-7 w-7 items-center justify-center rounded-md text-gray-400 hover:bg-indigo-50 hover:text-indigo-600">{!! $uiSvg('edit') !!}</button>
                            @unless ($g->is_system)
                                <button type="button" wire:click="deleteGroup({{ $g->id }})" wire:confirm="Delete the {{ $g->name }} category?" title="Delete" class="flex h-7 w-7 items-center justify-center rounded-md text-gray-400 hover:bg-red-50 hover:text-red-600">{!! $uiSvg('trash') !!}</button>
                            @endunless
                        </div>
                    </li>
                @endforeach
            </ul>

            <div class="flex justify-between border-t border-gray-100 px-5 py-3">
                <button type="button" wire:click="openGroupForm" class="inline-flex items-center gap-1.5 text-sm font-semibold text-indigo-600 hover:text-indigo-800">{!! $uiSvg('plus') !!} New category</button>
                <button type="button" wire:click="$set('showGroupsModal', false)" class="h-9 rounded-lg border border-gray-300 px-4 text-sm font-medium text-gray-600 hover:bg-gray-50">Done</button>
            </div>
        </div>
    </div>
@endif
</div>
