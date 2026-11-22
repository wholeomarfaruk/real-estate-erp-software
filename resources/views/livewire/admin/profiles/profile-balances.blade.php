@php
    $icon = fn ($name, $class = 'h-4 w-4') => view('livewire.admin.profiles.partials.icon', ['name' => $name, 'class' => $class])->render();
    $select = 'h-10 rounded-lg border border-gray-200 bg-white px-3! text-sm text-gray-700 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/15';
    $money = fn ($v) => number_format((float) $v, 2);
    $th = function ($col, $label, $align = 'right') use ($sort, $dir) {
        $active = $sort === $col;
        $arrow = $active ? ($dir === 'asc' ? '↑' : '↓') : '';
        return '<button type="button" wire:click="sortBy(\'' . $col . '\')" class="inline-flex items-center gap-1 font-medium uppercase tracking-wide hover:text-gray-800 ' . ($active ? 'text-gray-900' : '') . '">'
            . e($label) . ' <span class="text-[10px]">' . $arrow . '</span></button>';
    };
@endphp

<div class="min-h-screen bg-slate-50 p-4 sm:p-6" x-data x-init="$store.pageName = { name: 'Profile Balances', slug: 'profiles' }">
<div class="mx-auto max-w-7xl">
    <a href="{{ route('admin.profiles.index') }}" class="mb-4 inline-flex items-center gap-1.5 text-sm font-medium text-gray-500 hover:text-gray-800">
        {!! $icon('arrow-left') !!} Master Profiles
    </a>

    <div class="mb-6">
        <h1 class="text-2xl font-bold tracking-tight text-gray-900">Profile Balances</h1>
        <p class="text-sm text-gray-500">Money received from and paid to each profile, from accounting entries.</p>
    </div>

    {{-- Totals --}}
    <div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach ([
            ['Profiles', number_format($totals->profiles), 'text-gray-900'],
            ['Total received', $money($totals->received), 'text-emerald-600'],
            ['Total paid', $money($totals->paid), 'text-rose-600'],
            [$totals->net >= 0 ? 'Net received' : 'Net paid', $money(abs($totals->net)), 'text-gray-900'],
        ] as [$label, $value, $color])
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-medium text-gray-500">{{ $label }}</p>
                <p class="mt-0.5 text-xl font-bold {{ $color }}">{{ $value }}</p>
            </div>
        @endforeach
    </div>

    {{-- Filters --}}
    <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center">
        <div class="relative min-w-0 flex-1">
            <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">{!! $icon('search') !!}</span>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search name, code or phone…"
                class="h-10 w-full rounded-lg border border-gray-200 bg-white pl-9! pr-3! text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/15">
        </div>
        <select wire:model.live="typeFilter" class="{{ $select }} w-full! sm:w-44! shrink-0">
            <option value="">All types</option>
            @foreach ($types as $type)
                <option value="{{ $type->slug }}">{{ $type->name }}</option>
            @endforeach
        </select>
        <select wire:model.live="show" class="{{ $select }} w-full! sm:w-48! shrink-0">
            <option value="with">With transactions</option>
            <option value="all">All profiles</option>
        </select>
    </div>

    {{-- Table --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm" wire:loading.class="opacity-60">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b border-gray-100 bg-gray-50 text-[11px] text-gray-500">
                    <tr>
                        <th class="px-5 py-3 text-left">{!! $th('name', 'Profile', 'left') !!}</th>
                        <th class="px-3 py-3 text-left font-medium uppercase tracking-wide">Roles</th>
                        <th class="px-3 py-3 text-right">{!! $th('received', 'Received') !!}</th>
                        <th class="px-3 py-3 text-right">{!! $th('paid', 'Paid') !!}</th>
                        <th class="px-3 py-3 text-right">{!! $th('net', 'Net') !!}</th>
                        <th class="px-3 py-3 text-right">{!! $th('entries', 'Entries') !!}</th>
                        <th class="px-5 py-3 text-right">{!! $th('last_at', 'Last entry') !!}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse ($rows as $row)
                        <tr wire:key="bal-{{ $row->id }}" class="hover:bg-gray-50/70 {{ $row->status === 'inactive' ? 'opacity-60' : '' }}">
                            <td class="px-5 py-3">
                                <a href="{{ route('admin.profiles.show', $row) }}" class="group flex items-center gap-2.5">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gray-100 text-[11px] font-bold text-gray-600">{{ $row->initials() }}</span>
                                    <span class="min-w-0">
                                        <span class="block truncate font-medium text-gray-900 group-hover:text-indigo-700">{{ $row->name }}</span>
                                        <span class="block font-mono text-[11px] text-gray-400">{{ $row->code }}</span>
                                    </span>
                                </a>
                            </td>
                            <td class="px-3 py-3">
                                <div class="flex flex-wrap gap-1">
                                    @foreach ($row->roles as $role)
                                        @php $t = $role->type?->tones() ?? \App\Models\ProfileType::toneClasses(null); @endphp
                                        <span class="rounded px-1.5 py-px text-[10px] font-medium ring-1 ring-inset {{ $t['badge'] }}">{{ $role->type?->name }}</span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-3 py-3 text-right font-medium {{ $row->received > 0 ? 'text-emerald-600' : 'text-gray-300' }}">{{ $money($row->received) }}</td>
                            <td class="whitespace-nowrap px-3 py-3 text-right font-medium {{ $row->paid > 0 ? 'text-rose-600' : 'text-gray-300' }}">{{ $money($row->paid) }}</td>
                            <td class="whitespace-nowrap px-3 py-3 text-right font-semibold text-gray-900">
                                {{ $money(abs($row->net)) }}
                                @if ((float) $row->net != 0)
                                    <span class="ml-1 text-[10px] font-medium {{ $row->net > 0 ? 'text-emerald-600' : 'text-rose-600' }}">{{ $row->net > 0 ? 'IN' : 'OUT' }}</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-right text-gray-500">{{ $row->entries }}</td>
                            <td class="whitespace-nowrap px-5 py-3 text-right text-gray-500">{{ $row->last_at ? \Illuminate\Support\Carbon::parse($row->last_at)->format('d M Y') : '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-14 text-center text-sm text-gray-400">
                                No profiles with accounting entries match these filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($rows->isNotEmpty())
                    <tfoot class="border-t-2 border-gray-200 bg-gray-50 text-sm font-semibold">
                        <tr>
                            <td class="px-5 py-3 text-gray-700" colspan="2">Total ({{ number_format($totals->profiles) }} profiles)</td>
                            <td class="whitespace-nowrap px-3 py-3 text-right text-emerald-700">{{ $money($totals->received) }}</td>
                            <td class="whitespace-nowrap px-3 py-3 text-right text-rose-700">{{ $money($totals->paid) }}</td>
                            <td class="whitespace-nowrap px-3 py-3 text-right text-gray-900">{{ $money(abs($totals->net)) }} <span class="text-[10px]">{{ $totals->net >= 0 ? 'IN' : 'OUT' }}</span></td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $rows->links() }}</div>
</div>
</div>
