<div x-data x-init="$store.pageName = { name: 'Fund Release', slug: 'purchase-orders-fund' }">

    {{-- Breadcrumb --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <h1 class="text-lg font-bold text-gray-500">Fund Release</h1>
        <nav>
            <ol class="flex items-center gap-1.5 text-sm">
                <li>
                    <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center gap-1 text-gray-500 hover:text-gray-700">
                        Dashboard
                        <svg class="h-4 w-4 stroke-current" fill="none" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m5.25 4.5 7.5 7.5-7.5 7.5m6-15 7.5 7.5-7.5 7.5"/></svg>
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.inventory.purchase-orders.view', $purchaseOrder) }}" class="inline-flex items-center gap-1 text-gray-500 hover:text-gray-700">
                        {{ $purchaseOrder->po_no }}
                        <svg class="h-4 w-4 stroke-current" fill="none" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m5.25 4.5 7.5 7.5-7.5 7.5m6-15 7.5 7.5-7.5 7.5"/></svg>
                    </a>
                </li>
                <li class="text-gray-800">Fund Release</li>
            </ol>
        </nav>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-3">

        {{-- ------------------------------------------------------------------ --}}
        {{-- FORM                                                                --}}
        {{-- ------------------------------------------------------------------ --}}
        <div class="xl:col-span-2 space-y-4">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
                <h3 class="text-base font-semibold text-gray-800">Release Details</h3>
                <p class="mt-0.5 text-xs text-gray-500">
                    Submits an advance request for banking approval. The ledger entry is created once banking confirms the release.
                </p>

                <form wire:submit.prevent="save" class="mt-5 space-y-5">

                    {{-- ── SECTION 1: Advance Type ──────────────────────────── --}}
                    <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                        <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-500">1. Advance Type</p>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            @foreach ($advanceCategories as $category)
                                <label wire:click="$set('transaction_category_id', '{{ $category->id }}')"
                                    class="flex cursor-pointer items-start gap-3 rounded-xl border-2 p-4 transition
                                        {{ (string) $transaction_category_id === (string) $category->id
                                            ? 'border-indigo-500 bg-indigo-50'
                                            : 'border-gray-200 bg-white hover:border-gray-300' }}">
                                    <div class="mt-0.5 flex h-4 w-4 shrink-0 items-center justify-center rounded-full border-2
                                        {{ (string) $transaction_category_id === (string) $category->id
                                            ? 'border-indigo-500 bg-indigo-500'
                                            : 'border-gray-300' }}">
                                        @if ((string) $transaction_category_id === (string) $category->id)
                                            <div class="h-1.5 w-1.5 rounded-full bg-white"></div>
                                        @endif
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-800">{{ $category->name }}</p>
                                        <p class="mt-0.5 text-xs text-gray-500">
                                            @if ($category->slug === 'employee-advance')
                                                Cash given to an employee for purchasing
                                            @elseif ($category->slug === 'supplier-advance')
                                                Advance payment to supplier before invoice
                                            @else
                                                {{ $category->name }}
                                            @endif
                                        </p>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                        <x-input-error for="transaction_category_id" class="mt-2" />
                    </div>

                    {{-- ── SECTION 2: Source & Payment ─────────────────────── --}}
                    <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                        <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-500">2. Source Account & Payment</p>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

                            {{-- Source Bank Account --}}
                            <div class="sm:col-span-2">
                                <label class="text-sm font-medium text-gray-700">
                                    Source Account <span class="text-red-500">*</span>
                                </label>
                                <select wire:model="bank_account_id"
                                    class="mt-1 h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                                    <option value="">Select source account</option>
                                    @foreach ($sourceAccounts as $ba)
                                        <option value="{{ $ba->id }}">
                                            {{ $ba->bank_name }}
                                            @if ($ba->type)
                                                ({{ strtoupper($ba->type) }})
                                            @endif
                                            @if ($ba->code || $ba->ac_number)
                                                — {{ $ba->code ?: $ba->ac_number }}
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error for="bank_account_id" class="mt-1" />
                            </div>

                            {{-- Payment Method --}}
                            <div>
                                <label class="text-sm font-medium text-gray-700">
                                    Payment Method <span class="text-red-500">*</span>
                                </label>
                                <select wire:model="method"
                                    class="mt-1 h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                                    <option value="">Select method</option>
                                    @foreach ($paymentMethods as $m)
                                        <option value="{{ $m->value }}">{{ $m->label() }}</option>
                                    @endforeach
                                </select>
                                <x-input-error for="method" class="mt-1" />
                            </div>

                            {{-- Amount --}}
                            <div>
                                <label class="text-sm font-medium text-gray-700">
                                    Amount <span class="text-red-500">*</span>
                                </label>
                                <input type="number" min="0.01" step="0.01" wire:model.lazy="amount"
                                    class="mt-1 h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none"
                                    placeholder="0.00">
                                <x-input-error for="amount" class="mt-1" />
                            </div>

                            {{-- Release Date --}}
                            <div>
                                <label class="text-sm font-medium text-gray-700">
                                    Release Date <span class="text-red-500">*</span>
                                </label>
                                <input type="date" wire:model="release_date"
                                    class="mt-1 h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                                <x-input-error for="release_date" class="mt-1" />
                            </div>

                            {{-- Remarks --}}
                            <div>
                                <label class="text-sm font-medium text-gray-700">Remarks</label>
                                <input type="text" wire:model="remarks"
                                    class="mt-1 h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none"
                                    placeholder="Bank transfer note, cash handover…">
                                <x-input-error for="remarks" class="mt-1" />
                            </div>
                        </div>
                    </div>

                    {{-- ── SECTION 3: Receiver ─────────────────────────────── --}}
                    <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                        <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-500">3. Receiver</p>

                        @if (! $transaction_category_id)
                            <p class="text-sm text-gray-400">Select an advance type above to set the receiver.</p>
                        @elseif ($payee_type === 'supplier')
                            <div class="flex items-center gap-3 rounded-lg border border-blue-100 bg-blue-50 px-4 py-3">
                                <svg class="h-5 w-5 shrink-0 text-blue-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 0 0 3.75-.615A2.993 2.993 0 0 0 9.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 0 0 2.25 1.016 2.993 2.993 0 0 0 2.25-1.016 3.001 3.001 0 0 0 3.75.614m-16.5 0a3.004 3.004 0 0 1-.621-4.72l1.189-1.19A1.5 1.5 0 0 1 5.378 3h13.243a1.5 1.5 0 0 1 1.06.44l1.19 1.189a3 3 0 0 1-.621 4.72M6.75 18h3.75a.75.75 0 0 0 .75-.75V13.5a.75.75 0 0 0-.75-.75H6.75a.75.75 0 0 0-.75.75v3.75c0 .414.336.75.75.75Z"/>
                                </svg>
                                <div>
                                    <p class="text-xs text-blue-500">Supplier (from PO)</p>
                                    <p class="text-sm font-semibold text-blue-900">
                                        {{ $purchaseOrder->supplier?->name ?? 'No supplier on this PO' }}
                                    </p>
                                </div>
                            </div>
                            <x-input-error for="receiver_id" class="mt-2" />
                        @elseif ($payee_type === 'employee')
                            <div>
                                <label class="text-sm font-medium text-gray-700">
                                    Employee <span class="text-red-500">*</span>
                                </label>
                                <select wire:model="receiver_id"
                                    class="mt-1 h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                                    <option value="">Select employee</option>
                                    @foreach ($receivers as $r)
                                        <option value="{{ $r->id }}" @selected($receiver_id == $r->id)>
                                            {{ $r->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error for="receiver_id" class="mt-1" />
                            </div>
                        @endif
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center justify-end gap-3">
                        <a href="{{ route('admin.inventory.purchase-orders.view', $purchaseOrder) }}"
                            class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Cancel
                        </a>
                        <button type="submit"
                            class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                            </svg>
                            Submit Fund Request
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ------------------------------------------------------------------ --}}
        {{-- SIDEBAR: PO Snapshot                                               --}}
        {{-- ------------------------------------------------------------------ --}}
        <div class="space-y-4">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
                <h3 class="text-base font-semibold text-gray-800">PO Snapshot</h3>
                <div class="mt-4 space-y-2.5 text-sm">
                    <div class="rounded-lg bg-gray-50 px-4 py-3">
                        <p class="text-xs text-gray-500">PO No</p>
                        <p class="font-semibold text-gray-800">{{ $purchaseOrder->po_no }}</p>
                    </div>
                    <div class="rounded-lg bg-gray-50 px-4 py-3">
                        <p class="text-xs text-gray-500">Supplier</p>
                        <p class="font-semibold text-gray-800">{{ $purchaseOrder->supplier?->name ?? 'N/A' }}</p>
                    </div>
                    <div class="rounded-lg bg-gray-50 px-4 py-3">
                        <p class="text-xs text-gray-500">Purchase Mode</p>
                        <p class="font-semibold text-gray-800">{{ $purchaseOrder->purchase_mode?->label() ?? 'N/A' }}</p>
                    </div>
                    <div class="rounded-lg bg-blue-50 px-4 py-3">
                        <p class="text-xs text-blue-600">Approved Amount</p>
                        <p class="font-bold text-blue-800">{{ number_format((float) ($purchaseOrder->approved_amount ?? 0), 2) }}</p>
                    </div>
                    <div class="rounded-lg bg-amber-50 px-4 py-3">
                        <p class="text-xs text-amber-600">Total Committed</p>
                        <p class="font-bold text-amber-800">{{ number_format($totalCommitted, 2) }}</p>
                        <p class="mt-0.5 text-[10px] text-amber-500">Pending + completed</p>
                    </div>
                    <div class="rounded-lg bg-emerald-50 px-4 py-3">
                        <p class="text-xs text-emerald-600">Remaining</p>
                        <p class="font-bold text-emerald-800">{{ number_format($unreleased, 2) }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- -------------------------------------------------------------------- --}}
    {{-- Fund Release History                                                  --}}
    {{-- -------------------------------------------------------------------- --}}
    <div class="mt-4 rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
        <h3 class="text-base font-semibold text-gray-800">Fund Release History</h3>
        <div class="mt-4 overflow-hidden rounded-xl border border-gray-200">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-100 bg-gray-50">
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Advance Type</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Source Account</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Method</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">Amount</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Released By</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Received By</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Remarks</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($purchaseOrder->funds as $fund)
                            @php
                                // Category: prefer completed transaction's category, fallback to fund's own
                                $advanceCategory = $fund->transaction?->transactionCategory ?? $fund->transactionCategory;
                                $catSlug  = $advanceCategory?->slug ?? '';
                                $isEmployee = str_contains($catSlug, 'employee');

                                // Source: prefer completed transaction's account, fallback to fund's bank account
                                $sourceName = null;
                                if ($fund->transaction?->account) {
                                    $sourceName = $fund->transaction->account->name;
                                    if ($fund->transaction->account->bankAccount) {
                                        $sourceName .= ' (' . $fund->transaction->account->bankAccount->bank_name . ')';
                                    }
                                } elseif ($fund->bankAccount) {
                                    $sourceName = $fund->bankAccount->bank_name;
                                }

                                // Method: prefer transaction's, fallback to fund's
                                $methodRaw = $fund->transaction?->method ?? $fund->method;
                                $methodLabel = $methodRaw
                                    ? (\App\Enums\Accounts\EntryMethod::tryFrom($methodRaw)?->label() ?? $methodRaw)
                                    : '—';

                                $statusClass = match($fund->status ?? 'completed') {
                                    'pending'   => 'bg-amber-100 text-amber-700',
                                    'completed' => 'bg-emerald-100 text-emerald-700',
                                    'rejected'  => 'bg-rose-100 text-rose-700',
                                    default     => 'bg-gray-100 text-gray-600',
                                };
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide {{ $statusClass }}">
                                        {{ ucfirst($fund->status ?? 'completed') }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    {{ $fund->release_date?->format('d M Y') ?? '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    @if ($advanceCategory)
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                                            {{ $isEmployee ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">
                                            {{ $advanceCategory->name }}
                                        </span>
                                    @else
                                        <span class="text-xs text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    {{ $sourceName ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $methodLabel }}</td>
                                <td class="px-4 py-3 text-right text-sm font-semibold text-gray-800">
                                    {{ number_format((float) $fund->amount, 2) }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $fund->releaser?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $fund->receiver?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-500">{{ $fund->remarks ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-8 text-center text-sm text-gray-400">
                                    No fund releases yet for this purchase order.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
