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

                    {{-- ── SECTION 1: Advance ───────────────────────────────── --}}
                    <div class="rounded-xl border border-indigo-100 bg-indigo-50/60 p-4">
                        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-indigo-500">1. Advance</p>
                        <div class="flex items-center gap-3">
                            <span class="inline-grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-indigo-500 text-white">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3M3.75 19.5h16.5A2.25 2.25 0 0 0 22.5 17.25V6.75A2.25 2.25 0 0 0 20.25 4.5H3.75A2.25 2.25 0 0 0 1.5 6.75v10.5A2.25 2.25 0 0 0 3.75 19.5Z"/></svg>
                            </span>
                            <div>
                                <p class="text-sm font-semibold text-indigo-900">Supplier Advance</p>
                                <p class="mt-0.5 text-xs text-indigo-600/80">
                                    Advance against PO for
                                    <span class="font-semibold">{{ $purchaseOrder->supplier?->name ?? '— no supplier —' }}</span>.
                                    Posted as <span class="font-mono">Dr Supplier Advance / Cr {payment account}</span> on banking approval.
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- ── SECTION 2: Source & Payment ─────────────────────── --}}
                    <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                        <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-500">2. Source Account & Payment</p>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

                            {{-- Account Type filter --}}
                            <div>
                                <label class="text-sm font-medium text-gray-700">
                                    Account Type <span class="text-red-500">*</span>
                                </label>
                                <select wire:model.live="account_type"
                                    class="mt-1 h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                                    <option value="">Select type</option>
                                    @foreach ($accountTypes as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                <x-input-error for="account_type" class="mt-1" />
                            </div>

                            {{-- Source COA money account (filtered by type) --}}
                            <div>
                                <label class="text-sm font-medium text-gray-700">
                                    Source Account <span class="text-red-500">*</span>
                                </label>
                                <select wire:model="payment_account_id" @disabled(! $account_type)
                                    class="mt-1 h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none disabled:bg-gray-100 disabled:text-gray-400">
                                    <option value="">{{ $account_type ? 'Select account' : 'Select a type first' }}</option>
                                    @foreach ($sourceAccounts as $acc)
                                        <option value="{{ $acc->id }}">
                                            {{ $acc->code ? $acc->code.' · ' : '' }}{{ $acc->name }}@if ($acc->bankAccount) — {{ $acc->bankAccount->bank_name }}{{ $acc->bankAccount->ac_number ? ' ('.$acc->bankAccount->ac_number.')' : '' }}@endif
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error for="payment_account_id" class="mt-1" />
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
                                    class="mt-1 h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none flatpickr-only-date">
                                <x-input-error for="release_date" class="mt-1" />
                            </div>

                            {{-- Reference No --}}
                            <div>
                                <label class="text-sm font-medium text-gray-700">Reference / Voucher No.</label>
                                <input type="text" wire:model="reference_no"
                                    class="mt-1 h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none"
                                    placeholder="Cheque / voucher / slip no. (optional)">
                                <x-input-error for="reference_no" class="mt-1" />
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

                    {{-- ── SECTION 3: Paid To ──────────────────────────────── --}}
                    <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                        <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-500">3. Paid To</p>

                        {{-- Direct supplier vs through an employee --}}
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <label wire:click="$set('receiver_mode', 'supplier_direct')"
                                class="flex cursor-pointer items-start gap-3 rounded-xl border-2 p-4 transition
                                    {{ $receiver_mode === 'supplier_direct' ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 bg-white hover:border-gray-300' }}">
                                <div class="mt-0.5 flex h-4 w-4 shrink-0 items-center justify-center rounded-full border-2 {{ $receiver_mode === 'supplier_direct' ? 'border-indigo-500 bg-indigo-500' : 'border-gray-300' }}">
                                    @if ($receiver_mode === 'supplier_direct')<div class="h-1.5 w-1.5 rounded-full bg-white"></div>@endif
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-800">Directly to supplier</p>
                                    <p class="mt-0.5 text-xs text-gray-500">Paid straight to {{ $purchaseOrder->supplier?->name ?? 'the supplier' }}.</p>
                                </div>
                            </label>

                            <label wire:click="$set('receiver_mode', 'via_employee')"
                                class="flex cursor-pointer items-start gap-3 rounded-xl border-2 p-4 transition
                                    {{ $receiver_mode === 'via_employee' ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 bg-white hover:border-gray-300' }}">
                                <div class="mt-0.5 flex h-4 w-4 shrink-0 items-center justify-center rounded-full border-2 {{ $receiver_mode === 'via_employee' ? 'border-indigo-500 bg-indigo-500' : 'border-gray-300' }}">
                                    @if ($receiver_mode === 'via_employee')<div class="h-1.5 w-1.5 rounded-full bg-white"></div>@endif
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-800">Through an employee</p>
                                    <p class="mt-0.5 text-xs text-gray-500">Cash handed to an employee who pays the supplier.</p>
                                </div>
                            </label>
                        </div>

                        {{-- Employee picker — only when paying through an employee --}}
                        @if ($receiver_mode === 'via_employee')
                            <div class="mt-3">
                                <label class="text-sm font-medium text-gray-700">Employee <span class="text-red-500">*</span></label>
                                <select wire:model="receiver_id"
                                    class="mt-1 h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                                    <option value="">Select employee</option>
                                    @foreach ($employees as $r)
                                        <option value="{{ $r->id }}" @selected($receiver_id == $r->id)>{{ $r->name }}</option>
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
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Paid To</th>
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
                                // Paid via an employee or directly to the supplier.
                                $isEmployee = $fund->payto === 'employee';

                                // Source: the payment (credit) ledger line's account from the
                                // posted transaction, else the fund's own bank account.
                                $paymentLine = $fund->transaction?->lines->firstWhere(fn ($l) => (float) $l->credit > 0);
                                $sourceName = null;
                                if ($paymentLine?->account) {
                                    $sourceName = $paymentLine->account->name;
                                    if ($paymentLine->account->bankAccount) {
                                        $sourceName .= ' (' . $paymentLine->account->bankAccount->bank_name . ')';
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
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                                        {{ $isEmployee ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">
                                        {{ $isEmployee ? 'Via employee' : 'Supplier (direct)' }}
                                    </span>
                                    @if ($fund->reference_no)
                                        <span class="mt-0.5 block font-mono text-[10px] text-gray-400">Ref: {{ $fund->reference_no }}</span>
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
