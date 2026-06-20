<div x-data x-init="$store.pageName = { name: 'Accounting Settings', slug: 'accounts-settings' }">
    {{-- Header --}}
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-lg font-bold text-gray-800">Accounting Settings</h1>
            <p class="text-sm text-gray-500">Configure the default debit/credit accounts each business event posts to —
                so users never pick accounting accounts manually.</p>
        </div>
        <nav>
            <ol class="flex items-center gap-1.5 text-sm text-gray-500">
                <li><a href="{{ route('admin.dashboard') }}" class="hover:text-gray-700">Dashboard</a></li>
                <li>/</li>
                <li class="text-gray-700">Accounting Settings</li>
            </ol>
        </nav>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-5">
        {{-- ── Event list ─────────────────────────────────────────────── --}}
        <div class="space-y-4 lg:col-span-3">
            @forelse ($eventsByModule as $module => $events)
                <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                    <div class="flex items-center gap-2 border-b border-gray-100 bg-gray-50 px-4 py-2.5">
                        <span class="text-[11px] font-bold uppercase tracking-widest text-gray-500">{{ ucfirst($module) }}</span>
                        <span class="rounded-full bg-gray-200 px-1.5 py-0.5 font-mono text-[10px] text-gray-600">{{ $events->count() }}</span>
                    </div>

                    <div class="divide-y divide-gray-100">
                        @foreach ($events as $event)
                            @php
                                $dr = $event->rules->firstWhere('leg', \App\Enums\Accounts\PostingLeg::DEBIT);
                                $cr = $event->rules->firstWhere('leg', \App\Enums\Accounts\PostingLeg::CREDIT);
                                $legLabel = fn ($rule) => $rule
                                    ? ($rule->account_source === \App\Enums\Accounts\AccountSource::FIXED
                                        ? ($rule->account?->name ?? '— unset —')
                                        : 'User-selected account')
                                    : '—';
                                $isSel = $editingEvent && $editingEvent->id === $event->id;
                            @endphp
                            <button type="button" wire:click="selectEvent({{ $event->id }})"
                                class="flex w-full items-center gap-3 px-4 py-3 text-left transition hover:bg-gray-50 {{ $isSel ? 'bg-indigo-50/60' : '' }}">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="truncate text-sm font-semibold text-gray-800">{{ $event->name }}</span>
                                        @unless ($event->is_active)
                                            <span class="rounded-full bg-gray-100 px-1.5 py-0.5 text-[9px] font-semibold uppercase text-gray-500">Off</span>
                                        @endunless
                                        @unless ($event->isBalancedRecipe())
                                            <span class="rounded-full bg-amber-50 px-1.5 py-0.5 text-[9px] font-semibold uppercase text-amber-700">Incomplete</span>
                                        @endunless
                                    </div>
                                    <div class="mt-1 flex flex-wrap items-center gap-1.5 text-[11px]">
                                        <span class="rounded bg-emerald-50 px-1.5 py-0.5 font-medium text-emerald-700">Dr {{ $legLabel($dr) }}</span>
                                        <span class="rounded bg-rose-50 px-1.5 py-0.5 font-medium text-rose-700">Cr {{ $legLabel($cr) }}</span>
                                    </div>
                                </div>
                                <svg class="h-4 w-4 shrink-0 text-gray-300" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.21 5.23a.75.75 0 0 1 1.06.02l4 4.25a.75.75 0 0 1 0 1.04l-4 4.25a.75.75 0 1 1-1.08-1.04L10.69 10 7.23 6.29a.75.75 0 0 1-.02-1.06Z" clip-rule="evenodd"/></svg>
                            </button>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="rounded-2xl border border-dashed border-gray-200 bg-white px-5 py-16 text-center">
                    <p class="text-sm font-medium text-gray-700">No accounting events configured.</p>
                    <p class="mt-1 text-xs text-gray-500">Run the AccountingEventSeeder to populate defaults.</p>
                </div>
            @endforelse
        </div>

        {{-- ── Editor panel ───────────────────────────────────────────── --}}
        <div class="lg:col-span-2">
            <div class="sticky top-4 rounded-2xl border border-gray-200 bg-white shadow-sm">
                @if ($editingEvent)
                    <div class="flex items-start justify-between gap-3 border-b border-gray-100 px-5 py-4">
                        <div>
                            <h2 class="text-sm font-bold text-gray-800">{{ $editingEvent->name }}</h2>
                            <p class="mt-0.5 font-mono text-[10px] text-gray-400">{{ $editingEvent->key }} · {{ $editingEvent->transaction_type->value }}</p>
                        </div>
                        <button type="button" wire:click="cancelEdit" class="rounded-md p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </button>
                    </div>

                    <div class="px-5 py-4">
                        @if ($editingEvent->description)
                            <p class="mb-3 text-xs leading-relaxed text-gray-500">{{ $editingEvent->description }}</p>
                        @endif

                        {{-- active toggle --}}
                        <label class="mb-4 flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-2">
                            <input type="checkbox" wire:model="isActive" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="text-sm text-gray-700">Event active (auto-posts when triggered)</span>
                        </label>

                        @error('legs') <p class="mb-2 rounded bg-rose-50 px-2 py-1 text-xs text-rose-600">{{ $message }}</p> @enderror

                        {{-- legs --}}
                        <div class="space-y-3">
                            @foreach ($legs as $i => $leg)
                                <div class="rounded-xl border border-gray-200 p-3">
                                    <div class="flex items-center justify-between gap-2">
                                        <div class="inline-flex rounded-lg bg-gray-100 p-0.5">
                                            <button type="button" wire:click="$set('legs.{{ $i }}.leg', 'debit')"
                                                class="rounded-md px-2.5 py-1 text-xs font-semibold transition {{ ($leg['leg'] ?? '') === 'debit' ? 'bg-emerald-600 text-white' : 'text-gray-500' }}">Debit</button>
                                            <button type="button" wire:click="$set('legs.{{ $i }}.leg', 'credit')"
                                                class="rounded-md px-2.5 py-1 text-xs font-semibold transition {{ ($leg['leg'] ?? '') === 'credit' ? 'bg-rose-600 text-white' : 'text-gray-500' }}">Credit</button>
                                        </div>
                                        <button type="button" wire:click="removeLeg({{ $i }})" class="text-gray-300 transition hover:text-rose-500">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                        </button>
                                    </div>

                                    {{-- account source --}}
                                    <div class="mt-3 flex gap-2">
                                        <button type="button" wire:click="$set('legs.{{ $i }}.account_source', 'fixed')"
                                            class="flex-1 rounded-lg border px-2.5 py-1.5 text-xs font-medium transition {{ ($leg['account_source'] ?? '') === 'fixed' ? 'border-indigo-400 bg-indigo-50 text-indigo-700' : 'border-gray-200 text-gray-500' }}">Fixed account</button>
                                        <button type="button" wire:click="$set('legs.{{ $i }}.account_source', 'runtime')"
                                            @disabled(empty($slots))
                                            class="flex-1 rounded-lg border px-2.5 py-1.5 text-xs font-medium transition disabled:cursor-not-allowed disabled:opacity-40 {{ ($leg['account_source'] ?? '') === 'runtime' ? 'border-indigo-400 bg-indigo-50 text-indigo-700' : 'border-gray-200 text-gray-500' }}">User-selected</button>
                                    </div>

                                    {{-- account / slot picker --}}
                                    @if (($leg['account_source'] ?? '') === 'runtime')
                                        <select wire:model="legs.{{ $i }}.runtime_slot"
                                            class="mt-2 h-9 w-full rounded-lg border border-gray-300 px-2.5 text-xs text-gray-700 focus:border-indigo-500 focus:outline-none">
                                            <option value="">Select runtime slot…</option>
                                            @foreach ($slots as $slotKey => $slotLabel)
                                                <option value="{{ $slotKey }}">{{ $slotLabel }}</option>
                                            @endforeach
                                        </select>
                                        @error("legs.$i.runtime_slot") <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p> @enderror
                                    @else
                                        <select wire:model="legs.{{ $i }}.account_id"
                                            class="mt-2 h-9 w-full rounded-lg border border-gray-300 px-2.5 text-xs text-gray-700 focus:border-indigo-500 focus:outline-none">
                                            <option value="">Select account…</option>
                                            @foreach ($accounts as $acc)
                                                <option value="{{ $acc->id }}">{{ $acc->code ? $acc->code.' · ' : '' }}{{ $acc->name }}{{ $acc->group ? ' ('.$acc->group->label().')' : '' }}</option>
                                            @endforeach
                                        </select>
                                        @error("legs.$i.account_id") <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p> @enderror
                                    @endif

                                    <input type="text" wire:model="legs.{{ $i }}.description" placeholder="Line memo (optional)"
                                        class="mt-2 h-8 w-full rounded-lg border border-gray-200 px-2.5 text-[11px] text-gray-600 focus:border-indigo-400 focus:outline-none">
                                </div>
                            @endforeach
                        </div>

                        <button type="button" wire:click="addLeg"
                            class="mt-3 inline-flex items-center gap-1.5 rounded-lg border border-dashed border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-600 transition hover:bg-gray-50">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            Add leg
                        </button>

                        {{-- balance preview --}}
                        <div class="mt-4 rounded-lg px-3 py-2 text-xs font-medium {{ $this->recipeBalanced ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                            {{ $this->recipeBalanced ? '✓ Balanced recipe (has a debit and a credit leg)' : '⚠ Needs at least one debit and one credit leg' }}
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 border-t border-gray-100 px-5 py-3">
                        <button type="button" wire:click="cancelEdit"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">Cancel</button>
                        <button type="button" wire:click="save"
                            class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-gray-800">Save Rule</button>
                    </div>
                @else
                    <div class="grid place-items-center px-6 py-20 text-center text-gray-400">
                        <div>
                            <p class="font-serif text-5xl text-gray-200">⚙</p>
                            <p class="mt-3 text-sm text-gray-500">Select an event to configure its journal recipe.</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
