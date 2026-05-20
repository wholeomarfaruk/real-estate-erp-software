<div x-data x-init="$store.pageName = { name: 'Banking Management' }">

    {{-- ── Page header ─────────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-end justify-between gap-4 mb-4">
        <div>
            <h1 class="text-lg font-bold text-gray-800">Banking Management</h1>
            <p class="text-sm text-gray-500">Review and approve outgoing payment requests.</p>
        </div>
        <button type="button" wire:click="openCreateModal"
            class="inline-flex items-center gap-1.5 rounded-lg bg-gray-900 px-3.5 py-2 text-sm font-medium text-white transition hover:bg-gray-800">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            New Request
        </button>
    </div>

    {{-- ── Nav tabs ─────────────────────────────────────────────────────────── --}}
    @include('livewire.admin.accounts.banking.partials.nav-tabs', ['active' => 'payment-requests'])

    {{-- ── KPI strip ───────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 gap-3 mb-5 sm:grid-cols-3 xl:grid-cols-5">
        @foreach([
            ['key' => 'pending',   'label' => 'Pending',   'dot' => 'bg-amber-500',  'bg' => 'border-amber-200'],
            ['key' => 'approved',  'label' => 'Approved',  'dot' => 'bg-blue-500',   'bg' => 'border-blue-200'],
            ['key' => 'released',  'label' => 'Released',  'dot' => 'bg-violet-500', 'bg' => 'border-violet-200'],
            ['key' => 'completed', 'label' => 'Completed', 'dot' => 'bg-emerald-500','bg' => 'border-emerald-200'],
            ['key' => 'rejected',  'label' => 'Rejected',  'dot' => 'bg-rose-500',   'bg' => 'border-rose-200'],
        ] as $stat)
            <button type="button" wire:click="$set('statusFilter', '{{ $statusFilter === $stat['key'] ? '' : $stat['key'] }}')"
                class="rounded-xl border bg-white px-4 py-3 text-left transition hover:shadow-sm
                       {{ $statusFilter === $stat['key'] ? 'ring-2 ring-gray-900 ' . $stat['bg'] : 'border-gray-200' }}">
                <p class="flex items-center gap-1.5 text-[10px] font-semibold uppercase tracking-widest text-gray-400">
                    <span class="h-2 w-2 rounded-full {{ $stat['dot'] }}"></span>
                    {{ $stat['label'] }}
                </p>
                <p class="mt-2 text-2xl font-bold text-gray-900">{{ $kpi->{$stat['key']} ?? 0 }}</p>
            </button>
        @endforeach
    </div>

    {{-- ── Filter bar ──────────────────────────────────────────────────────── --}}
    <div class="mb-3 flex flex-wrap items-center gap-2">
        <div class="relative">
            <svg class="pointer-events-none absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            <input type="text" wire:model.live.debounce.400ms="search" placeholder="Search request no, description…"
                class="h-9 rounded-lg border border-gray-300 pl-8 pr-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none min-w-52">
        </div>

        <select wire:model.live="sourceFilter"
            class="h-9 rounded-lg border border-gray-300 px-3 text-sm text-gray-700 focus:border-indigo-500 focus:outline-none">
            <option value="">All Sources</option>
            @foreach($sourceTypes as $src)
                <option value="{{ $src->value }}">{{ $src->label() }}</option>
            @endforeach
        </select>

        <select wire:model.live="accountFilter"
            class="h-9 rounded-lg border border-gray-300 px-3 text-sm text-gray-700 focus:border-indigo-500 focus:outline-none">
            <option value="">All Accounts</option>
            @foreach($bankAccounts as $ba)
                <option value="{{ $ba->id }}">{{ $ba->bank_name }}</option>
            @endforeach
        </select>
    </div>

    {{-- ── Table ───────────────────────────────────────────────────────────── --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <div class="overflow-x-auto min-h-[50vh]">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50">
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-400">Request No</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-400">Source</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-400">Description</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-400">Account</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-400">Amount</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-400">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-400">Requested By</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-400">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($requests as $req)
                        @php
                            $statusClass = match($req->status) {
                                'pending'   => 'bg-amber-50 text-amber-700 border-amber-200',
                                'approved'  => 'bg-blue-50 text-blue-700 border-blue-200',
                                'released'  => 'bg-violet-50 text-violet-700 border-violet-200',
                                'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                'rejected'  => 'bg-rose-50 text-rose-700 border-rose-200',
                                default     => 'bg-gray-100 text-gray-600 border-gray-200',
                            };
                            $srcEnum = \App\Enums\Accounts\TransactionType::tryFrom($req->source_type);
                            $srcBadgeClass = $srcEnum?->badgeClass() ?? 'bg-gray-100 text-gray-600 border-gray-200';
                            $srcLabel = $srcEnum?->label() ?? ucfirst(str_replace('_', ' ', $req->source_type));
                        @endphp
                        <tr class="hover:bg-gray-50 cursor-pointer" wire:click="openDrawer({{ $req->id }})">
                            <td class="px-4 py-3">
                                <p class="font-mono text-xs font-semibold text-gray-700">{{ $req->request_no }}</p>
                                <p class="text-xs text-gray-400 mt-0.5">{{ $req->created_at->format('d M Y') }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide {{ $srcBadgeClass }}">
                                    {{ $srcLabel }}
                                </span>
                                @if($req->transactionCategory)
                                    <p class="mt-0.5 text-[10px] text-gray-400">{{ $req->transactionCategory->name }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-700 max-w-[180px]">
                                <p class="truncate">{{ $req->description ?: '—' }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-xs font-medium text-gray-700">{{ $req->bankAccount?->bank_name ?? '—' }}</p>
                                @if($req->bankAccount?->code)
                                    <p class="font-mono text-[10px] text-gray-400 mt-0.5">{{ $req->bankAccount->code }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right font-mono text-sm font-semibold text-gray-800">
                                {{ number_format($req->amount, 2) }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide {{ $statusClass }}">
                                    {{ ucfirst($req->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-600">{{ $req->requestedBy?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-right" onclick="event.stopPropagation()">
                                <div class="flex items-center justify-end gap-1">
                                    @if($req->status === 'pending')
                                        <button type="button" wire:click.stop="approve({{ $req->id }})"
                                            class="inline-flex items-center gap-1 rounded-md border border-blue-200 bg-blue-50 px-2 py-1 text-[10px] font-semibold text-blue-700 hover:bg-blue-100 transition">
                                            Approve
                                        </button>
                                        <button type="button" wire:click.stop="openRejectModal({{ $req->id }})"
                                            class="inline-flex items-center gap-1 rounded-md border border-rose-200 bg-rose-50 px-2 py-1 text-[10px] font-semibold text-rose-700 hover:bg-rose-100 transition">
                                            Reject
                                        </button>
                                    @elseif($req->status === 'approved')
                                        <button type="button" wire:click.stop="release({{ $req->id }})"
                                            class="inline-flex items-center gap-1 rounded-md border border-violet-200 bg-violet-50 px-2 py-1 text-[10px] font-semibold text-violet-700 hover:bg-violet-100 transition">
                                            Release
                                        </button>
                                        <button type="button" wire:click.stop="openRejectModal({{ $req->id }})"
                                            class="inline-flex items-center gap-1 rounded-md border border-rose-200 bg-rose-50 px-2 py-1 text-[10px] font-semibold text-rose-700 hover:bg-rose-100 transition">
                                            Reject
                                        </button>
                                    @elseif($req->status === 'released')
                                        <button type="button" wire:click.stop="markCompleted({{ $req->id }})"
                                            class="inline-flex items-center gap-1 rounded-md border border-emerald-200 bg-emerald-50 px-2 py-1 text-[10px] font-semibold text-emerald-700 hover:bg-emerald-100 transition">
                                            Complete
                                        </button>
                                    @endif
                                    <button type="button" wire:click.stop="openDrawer({{ $req->id }})"
                                        class="flex h-6 w-6 items-center justify-center rounded-md border border-gray-200 text-gray-400 hover:border-gray-400 hover:text-gray-700 transition">
                                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-16 text-center">
                                <p class="text-sm font-medium text-gray-600">No payment requests found.</p>
                                <p class="mt-1 text-xs text-gray-400">Try adjusting your filters.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($requests->hasPages())
        <div class="mt-4">{{ $requests->links() }}</div>
    @endif


    {{-- ═══════════════════════════════════════════════════════════════════════
         SIDE DRAWER
    ═══════════════════════════════════════════════════════════════════════════ --}}
    <div x-cloak x-data="{ open: @entangle('showDrawer') }" x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex justify-end bg-black/30" @click.self="open = false; $wire.closeDrawer()">

        <div x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
            class="w-full max-w-md overflow-y-auto bg-white shadow-2xl h-full">

            @if($viewingRequest)
            @php
                $r = $viewingRequest;
                $statusClass = match($r->status) {
                    'pending'   => 'bg-amber-50 text-amber-700 border-amber-200',
                    'approved'  => 'bg-blue-50 text-blue-700 border-blue-200',
                    'released'  => 'bg-violet-50 text-violet-700 border-violet-200',
                    'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                    'rejected'  => 'bg-rose-50 text-rose-700 border-rose-200',
                    default     => 'bg-gray-100 text-gray-600 border-gray-200',
                };
                $srcEnum = \App\Enums\Accounts\TransactionType::tryFrom($r->source_type);
                $srcBadgeClass = $srcEnum?->badgeClass() ?? 'bg-gray-100 text-gray-600 border-gray-200';
                $srcLabel = $srcEnum?->label() ?? ucfirst(str_replace('_', ' ', $r->source_type));
            @endphp

            <div class="sticky top-0 z-10 flex items-center justify-between border-b border-gray-100 bg-white px-5 py-4">
                <div>
                    <p class="font-mono text-sm font-semibold text-gray-800">{{ $r->request_no }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $r->created_at->format('d M Y, H:i') }}</p>
                </div>
                <button type="button" @click="open = false; $wire.closeDrawer()"
                    class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 transition">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="p-5 space-y-5">

                {{-- Amount + Status --}}
                <div class="rounded-xl border border-gray-200 bg-gray-50 px-5 py-4 flex items-center justify-between">
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wide font-semibold">Amount</p>
                        <p class="mt-1 text-3xl font-bold text-gray-900">{{ number_format($r->amount, 2) }}</p>
                    </div>
                    <span class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold uppercase tracking-wide {{ $statusClass }}">
                        {{ ucfirst($r->status) }}
                    </span>
                </div>

                {{-- Details --}}
                <div>
                    <h4 class="mb-3 text-[10px] font-bold uppercase tracking-widest text-gray-400">Request Details</h4>
                    <dl class="space-y-2.5 text-sm">
                        <div class="flex gap-3">
                            <dt class="w-28 shrink-0 text-xs text-gray-400">Source Type</dt>
                            <dd>
                                <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide {{ $srcBadgeClass }}">
                                    {{ $srcLabel }}
                                </span>
                            </dd>
                        </div>
                        @if($r->transactionCategory)
                            <div class="flex gap-3">
                                <dt class="w-28 shrink-0 text-xs text-gray-400">Category</dt>
                                <dd class="text-gray-700">{{ $r->transactionCategory->name }}</dd>
                            </div>
                        @endif
                        <div class="flex gap-3">
                            <dt class="w-28 shrink-0 text-xs text-gray-400">Description</dt>
                            <dd class="text-gray-700 break-words">{{ $r->description ?: '—' }}</dd>
                        </div>
                        <div class="flex gap-3">
                            <dt class="w-28 shrink-0 text-xs text-gray-400">Bank Account</dt>
                            <dd class="text-gray-700">{{ $r->bankAccount?->bank_name ?? '—' }}</dd>
                        </div>
                        @if($r->bankAccount?->account)
                            <div class="flex gap-3">
                                <dt class="w-28 shrink-0 text-xs text-gray-400">Balance</dt>
                                <dd class="font-mono text-sm font-semibold text-gray-800">
                                    {{ number_format($r->bankAccount->account->balance, 2) }} BDT
                                </dd>
                            </div>
                        @endif
                        @if($r->notes)
                            <div class="flex gap-3">
                                <dt class="w-28 shrink-0 text-xs text-gray-400">Notes</dt>
                                <dd class="text-gray-600 italic">{{ $r->notes }}</dd>
                            </div>
                        @endif
                        @if($r->rejection_reason)
                            <div class="flex gap-3">
                                <dt class="w-28 shrink-0 text-xs text-rose-500">Reason</dt>
                                <dd class="text-rose-700">{{ $r->rejection_reason }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>

                {{-- Approval Timeline --}}
                <div>
                    <h4 class="mb-3 text-[10px] font-bold uppercase tracking-widest text-gray-400">Approval Timeline</h4>
                    <ol class="relative border-l border-gray-200 pl-5 space-y-4">
                        <li>
                            <div class="absolute -left-1.5 mt-1 h-3 w-3 rounded-full border-2 border-white bg-gray-400"></div>
                            <p class="text-xs font-semibold text-gray-700">Requested by {{ $r->requestedBy?->name ?? '—' }}</p>
                            <p class="text-[10px] text-gray-400 font-mono">{{ $r->created_at->format('d M Y, H:i') }}</p>
                        </li>
                        @if($r->approved_at)
                            <li>
                                <div class="absolute -left-1.5 mt-1 h-3 w-3 rounded-full border-2 border-white bg-blue-500"></div>
                                <p class="text-xs font-semibold text-gray-700">Approved by {{ $r->approvedBy?->name ?? '—' }}</p>
                                <p class="text-[10px] text-gray-400 font-mono">{{ $r->approved_at->format('d M Y, H:i') }}</p>
                            </li>
                        @endif
                        @if($r->released_at)
                            <li>
                                <div class="absolute -left-1.5 mt-1 h-3 w-3 rounded-full border-2 border-white bg-violet-500"></div>
                                <p class="text-xs font-semibold text-gray-700">Released by {{ $r->releasedBy?->name ?? '—' }}</p>
                                <p class="text-[10px] text-gray-400 font-mono">{{ $r->released_at->format('d M Y, H:i') }}</p>
                            </li>
                        @endif
                        @if($r->completed_at)
                            <li>
                                <div class="absolute -left-1.5 mt-1 h-3 w-3 rounded-full border-2 border-white bg-emerald-500"></div>
                                <p class="text-xs font-semibold text-gray-700">Completed by {{ $r->completedBy?->name ?? '—' }}</p>
                                <p class="text-[10px] text-gray-400 font-mono">{{ $r->completed_at->format('d M Y, H:i') }}</p>
                            </li>
                        @endif
                        @if($r->rejected_at)
                            <li>
                                <div class="absolute -left-1.5 mt-1 h-3 w-3 rounded-full border-2 border-white bg-rose-500"></div>
                                <p class="text-xs font-semibold text-gray-700">Rejected by {{ $r->rejectedBy?->name ?? '—' }}</p>
                                <p class="text-[10px] text-gray-400 font-mono">{{ $r->rejected_at->format('d M Y, H:i') }}</p>
                            </li>
                        @endif
                    </ol>
                </div>

                {{-- Action buttons --}}
                @if(in_array($r->status, ['pending', 'approved', 'released']))
                    <div class="flex flex-wrap gap-2 border-t border-gray-100 pt-4">
                        @if($r->status === 'pending')
                            <button type="button" wire:click="approve({{ $r->id }})"
                                class="flex-1 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 transition">
                                Approve
                            </button>
                        @elseif($r->status === 'approved')
                            <button type="button" wire:click="release({{ $r->id }})"
                                class="flex-1 rounded-lg bg-violet-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-violet-700 transition">
                                Release
                            </button>
                        @elseif($r->status === 'released')
                            <button type="button" wire:click="markCompleted({{ $r->id }})"
                                class="flex-1 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 transition">
                                Mark Completed
                            </button>
                        @endif
                        <button type="button" wire:click="openRejectModal({{ $r->id }})"
                            class="rounded-lg border border-rose-200 bg-white px-4 py-2.5 text-sm font-semibold text-rose-600 hover:bg-rose-50 transition">
                            Reject
                        </button>
                    </div>
                @endif
            </div>
            @endif
        </div>
    </div>


    {{-- ═══════════════════════════════════════════════════════════════════════
         CREATE REQUEST MODAL
    ═══════════════════════════════════════════════════════════════════════════ --}}
    <div x-cloak x-data="{ open: @entangle('showCreateModal') }" x-show="open" x-transition
        class="fixed inset-0 z-50 grid place-content-center bg-black/50 p-4" role="dialog" aria-modal="true">
        <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl">
            <div class="flex items-start justify-between mb-4">
                <h2 class="text-lg font-bold text-gray-900">New Payment Request</h2>
                <button type="button" @click="open = false; $wire.closeCreateModal()"
                    class="rounded-full p-1.5 text-gray-400 hover:bg-gray-100 transition">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form wire:submit.prevent="createRequest" class="space-y-4">
                <div>
                    <label class="text-sm font-medium text-gray-700">Source Type <span class="text-rose-500">*</span></label>
                    <select wire:model.live="source_type"
                        class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                        @foreach($sourceTypes as $src)
                            <option value="{{ $src->value }}">{{ $src->label() }}</option>
                        @endforeach
                    </select>
                    @error('source_type') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                @if($source_type === 'advance')
                <div>
                    <label class="text-sm font-medium text-gray-700">Advance Category <span class="text-rose-500">*</span></label>
                    <select wire:model.defer="transaction_category_id"
                        class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                        <option value="">— Select Category —</option>
                        @foreach($advanceCategories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                    @error('transaction_category_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                @endif

                <div>
                    <label class="text-sm font-medium text-gray-700">Bank Account <span class="text-rose-500">*</span></label>
                    <select wire:model.defer="bank_account_id"
                        class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                        <option value="">— Select Account —</option>
                        @foreach($bankAccounts as $ba)
                            <option value="{{ $ba->id }}">{{ $ba->bank_name }} ({{ $ba->code ?: $ba->ac_number }})</option>
                        @endforeach
                    </select>
                    @error('bank_account_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">Amount <span class="text-rose-500">*</span></label>
                    <input type="number" step="0.001" wire:model.defer="amount" placeholder="0.000"
                        class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                    @error('amount') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">Description <span class="text-rose-500">*</span></label>
                    <textarea wire:model.defer="description" rows="2" placeholder="Purpose of this payment"
                        class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none"></textarea>
                    @error('description') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">Notes</label>
                    <textarea wire:model.defer="notes" rows="2" placeholder="Optional notes"
                        class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none"></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="open = false; $wire.closeCreateModal()"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                        Cancel
                    </button>
                    <button type="submit"
                        class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800 transition">
                        Submit Request
                    </button>
                </div>
            </form>
        </div>
    </div>


    {{-- ═══════════════════════════════════════════════════════════════════════
         REJECT MODAL
    ═══════════════════════════════════════════════════════════════════════════ --}}
    <div x-cloak x-data="{ open: @entangle('showRejectModal') }" x-show="open" x-transition
        class="fixed inset-0 z-50 grid place-content-center bg-black/50 p-4" role="dialog" aria-modal="true">
        <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
            <h2 class="text-lg font-bold text-gray-900 mb-4">Reject Request</h2>
            <form wire:submit.prevent="confirmReject" class="space-y-4">
                <div>
                    <label class="text-sm font-medium text-gray-700">Rejection Reason <span class="text-rose-500">*</span></label>
                    <textarea wire:model.defer="rejection_reason" rows="3" placeholder="Reason for rejection (min. 5 characters)"
                        class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-rose-500 focus:outline-none"></textarea>
                    @error('rejection_reason') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="open = false; $wire.closeRejectModal()"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                        Cancel
                    </button>
                    <button type="submit"
                        class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-700 transition">
                        Confirm Reject
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
