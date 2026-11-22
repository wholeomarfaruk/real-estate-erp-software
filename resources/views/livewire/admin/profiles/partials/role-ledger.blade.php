{{--
    Balance strip + collapsible entries for one role (or the "other entries" bucket).
    Expects $bucket = ['received','paid','net','count','entries'].
--}}
@php
    $bucket ??= ['received' => 0, 'paid' => 0, 'net' => 0, 'count' => 0, 'entries' => collect()];
    $hasEntries = $bucket['count'] > 0;
@endphp
<div x-data="{ open: false, limit: 10 }" class="border-t border-gray-100">
    <div class="grid grid-cols-2 sm:grid-cols-4">
        <div class="px-5 py-3">
            <p class="text-[11px] font-medium uppercase tracking-wide text-gray-400">Received</p>
            <p class="text-sm font-bold {{ $bucket['received'] > 0 ? 'text-emerald-600' : 'text-gray-300' }}">{{ number_format($bucket['received'], 2) }}</p>
        </div>
        <div class="px-5 py-3">
            <p class="text-[11px] font-medium uppercase tracking-wide text-gray-400">Paid</p>
            <p class="text-sm font-bold {{ $bucket['paid'] > 0 ? 'text-rose-600' : 'text-gray-300' }}">{{ number_format($bucket['paid'], 2) }}</p>
        </div>
        <div class="bg-slate-50/70 px-5 py-3">
            <p class="text-[11px] font-medium uppercase tracking-wide text-gray-400">
                {{ $bucket['net'] < 0 ? 'Net paid' : 'Net received' }}
            </p>
            <p class="text-sm font-bold {{ $hasEntries ? 'text-gray-900' : 'text-gray-300' }}">{{ number_format(abs($bucket['net']), 2) }}</p>
        </div>
        <div class="flex items-center justify-end px-5 py-3">
            @if ($hasEntries)
                <button type="button" x-on:click="open = !open" class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-semibold text-indigo-600 hover:bg-indigo-50">
                    <span x-text="open ? 'Hide' : 'Entries'"></span>
                    <span class="rounded-full bg-indigo-100 px-1.5 text-[10px] text-indigo-700">{{ $bucket['count'] }}</span>
                    <svg class="h-3 w-3 transition" :class="open && 'rotate-180'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m6 9 6 6 6-6"/></svg>
                </button>
            @else
                <span class="text-[11px] text-gray-400">No entries</span>
            @endif
        </div>
    </div>

    @if ($hasEntries)
        <div x-show="open" x-collapse x-cloak class="overflow-x-auto border-t border-gray-100">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-[11px] uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-5 py-2 text-left font-medium">Date</th>
                        <th class="px-3 py-2 text-left font-medium">Type</th>
                        <th class="px-3 py-2 text-left font-medium">Voucher / Ref</th>
                        <th class="px-3 py-2 text-right font-medium">In</th>
                        <th class="px-5 py-2 text-right font-medium">Out</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach ($bucket['entries'] as $i => $entry)
                        <tr x-show="{{ $i }} < limit" class="hover:bg-gray-50/60">
                            <td class="whitespace-nowrap px-5 py-2 text-gray-600">{{ \Illuminate\Support\Carbon::parse($entry->datetime)->format('d M Y') }}</td>
                            <td class="px-3 py-2"><span class="inline-flex rounded-md border px-1.5 py-0.5 text-[11px] font-medium {{ $entry->badge }}">{{ $entry->type_label }}</span></td>
                            <td class="max-w-[220px] truncate px-3 py-2 text-gray-600" title="{{ $entry->notes }}">
                                <span class="font-mono text-xs">{{ $entry->voucher_no ?: ($entry->reference_no ?: '#' . $entry->id) }}</span>
                                @if ($entry->notes)<span class="text-xs text-gray-400"> · {{ $entry->notes }}</span>@endif
                            </td>
                            <td class="whitespace-nowrap px-3 py-2 text-right font-medium text-emerald-600">{{ $entry->direction === 'in' ? number_format($entry->amount, 2) : '' }}</td>
                            <td class="whitespace-nowrap px-5 py-2 text-right font-medium {{ $entry->direction === 'out' ? 'text-rose-600' : 'text-gray-400' }}">
                                {{ $entry->direction === 'out' ? number_format($entry->amount, 2) : ($entry->direction === 'neutral' ? number_format($entry->amount, 2) . ' ⇄' : '') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if ($bucket['count'] > 10)
                <button type="button" x-show="limit < {{ $bucket['count'] }}" x-on:click="limit += 20"
                    class="w-full border-t border-gray-100 py-2 text-xs font-semibold text-indigo-600 hover:bg-gray-50">Show more</button>
            @endif
        </div>
    @endif
</div>
