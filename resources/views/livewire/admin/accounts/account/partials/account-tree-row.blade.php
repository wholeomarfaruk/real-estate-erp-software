{{-- Recursive account tree row.
     Expects: $node (Account with treeChildren relation + depth, own_balance, rollup_balance) --}}
@php
    $depth = (int) ($node->depth ?? 1);
    $indent = 16 + ($depth - 1) * 22;
    $hasChildren = $node->relationLoaded('treeChildren') && $node->treeChildren->isNotEmpty();
    $balance = $hasChildren ? $node->rollup_balance : $node->own_balance;
@endphp

<div x-data="{ open: true }">
    <div class="flex items-center gap-3 px-4 py-2.5 transition hover:bg-gray-50/70 sm:px-5">
        <div class="flex min-w-0 flex-1 items-center gap-2" style="padding-left: {{ $indent }}px;">
            {{-- connector --}}
            <span class="select-none font-mono text-gray-300">└─</span>

            @if ($hasChildren)
                <button type="button" @click="open = !open" class="shrink-0 text-gray-400 hover:text-gray-600">
                    <svg class="h-3.5 w-3.5 transition-transform" :class="open ? 'rotate-90' : ''" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M7.21 5.23a.75.75 0 0 1 1.06.02l4 4.25a.75.75 0 0 1 0 1.04l-4 4.25a.75.75 0 1 1-1.08-1.04L10.69 10 7.23 6.29a.75.75 0 0 1-.02-1.06Z" clip-rule="evenodd" />
                    </svg>
                </button>
            @endif

            <div class="min-w-0">
                <div class="flex items-center gap-1.5">
                    <span class="truncate text-sm font-medium text-gray-800">{{ $node->name }}</span>
                    @if ($node->is_locked)
                        <svg class="h-3 w-3 shrink-0 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    @endif
                </div>
                <div class="mt-0.5 flex items-center gap-1.5 font-mono text-[10px] text-gray-400">
                    <span>{{ $node->code ?: 'N/A' }}</span>
                    <span class="text-gray-300">·</span>
                    <span>{{ $node->type?->label() ?? '—' }}</span>
                </div>
            </div>
        </div>

        {{-- status --}}
        <span class="hidden shrink-0 rounded-full px-2 py-0.5 text-[10px] font-medium sm:inline {{ $node->is_active ? 'bg-green-100 text-green-700' : 'bg-rose-100 text-rose-700' }}">
            {{ $node->is_active ? 'Active' : 'Inactive' }}
        </span>

        {{-- balance --}}
        <div class="shrink-0 text-right">
            <p class="font-mono text-xs font-semibold {{ $balance < 0 ? 'text-rose-600' : 'text-gray-700' }}">{{ number_format((float) $balance, 2) }}</p>
        </div>

        @include('livewire.admin.accounts.account.partials.account-row-actions', ['account' => $node])
    </div>

    @if ($hasChildren)
        <div x-show="open" x-cloak x-transition.opacity class="divide-y divide-gray-50">
            @foreach ($node->treeChildren as $child)
                @include('livewire.admin.accounts.account.partials.account-tree-row', ['node' => $child])
            @endforeach
        </div>
    @endif
</div>
