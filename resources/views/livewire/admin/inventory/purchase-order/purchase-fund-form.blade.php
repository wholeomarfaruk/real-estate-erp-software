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
                    This creates a double-entry journal:
                    <span class="font-medium text-indigo-600">DR Advance Account</span> /
                    <span class="font-medium text-rose-600">CR Cash/Bank</span>
                </p>

                <form wire:submit.prevent="save" class="mt-5 space-y-5">

                    {{-- ── SECTION 1: Advance Type ──────────────────────────── --}}
                    <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                        <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-500">1. Advance Type</p>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            @foreach ($advanceTypes as $type)
                                <label wire:click="$set('advance_type', '{{ $type->value }}')"
                                    class="flex cursor-pointer items-start gap-3 rounded-xl border-2 p-4 transition
                                        {{ $advance_type === $type->value
                                            ? 'border-indigo-500 bg-indigo-50'
                                            : 'border-gray-200 bg-white hover:border-gray-300' }}">
                                    <div class="mt-0.5 flex h-4 w-4 shrink-0 items-center justify-center rounded-full border-2
                                        {{ $advance_type === $type->value ? 'border-indigo-500 bg-indigo-500' : 'border-gray-300' }}">
                                        @if ($advance_type === $type->value)
                                            <div class="h-1.5 w-1.5 rounded-full bg-white"></div>
                                        @endif
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-800">{{ $type->label() }}</p>
                                        <p class="mt-0.5 text-xs text-gray-500">
                                            {{ $type === \App\Enums\Inventory\FundReleaseType::EMPLOYEE_ADVANCE
                                                ? 'Cash given to an employee for purchasing'
                                                : 'Advance payment to supplier before invoice' }}
                                        </p>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                        <x-input-error for="advance_type" class="mt-2" />
                    </div>

                    {{-- ── SECTION 2: Accounting ────────────────────────────── --}}
                    <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                        <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-500">2. Accounting Entries</p>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            {{-- DR: Advance Account --}}
                            <div>
                                <label class="text-sm font-medium text-gray-700">
                                    <span class="inline-flex items-center gap-1">
                                        <span class="rounded bg-indigo-100 px-1.5 py-0.5 text-xs font-bold text-indigo-700">DR</span>
                                        Advance Account <span class="text-red-500">*</span>
                                    </span>
                                </label>
                                <select wire:model="advance_account_id"
                                    class="mt-1 h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                                    <option value="">Select advance account</option>
                                    @foreach ($advanceAccounts as $acc)
                                        <option value="{{ $acc->id }}">
                                            {{ $acc->name }}{{ $acc->code ? ' (' . $acc->code . ')' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error for="advance_account_id" class="mt-1" />
                            </div>

                            {{-- CR: Cash/Bank — two-level cascading --}}
                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-gray-700">
                                    <span class="inline-flex items-center gap-1">
                                        <span class="rounded bg-rose-100 px-1.5 py-0.5 text-xs font-bold text-rose-700">CR</span>
                                        Cash / Bank Group
                                    </span>
                                </label>
                                <select wire:model.live="payment_account_group_id"
                                    class="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                                    <option value="">Select head account</option>
                                    @foreach ($cashBankGroups as $grp)
                                        <option value="{{ $grp->id }}">{{ $grp->name }}</option>
                                    @endforeach
                                </select>

                                <label class="block text-sm font-medium text-gray-700">
                                    Cash / Bank Account <span class="text-red-500">*</span>
                                </label>
                                <select wire:model="payment_account_id"
                                    @disabled(! $payment_account_group_id)
                                    class="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none disabled:bg-gray-50 disabled:text-gray-400">
                                    <option value="">
                                        {{ $payment_account_group_id ? 'Select account' : 'Select group first' }}
                                    </option>
                                    @foreach ($payment_account_children as $child)
                                        <option value="{{ $child['id'] }}">{{ $child['name'] }}</option>
                                    @endforeach
                                </select>
                                <x-input-error for="payment_account_id" class="mt-1" />
                            </div>
                        </div>

                        {{-- Live journal preview --}}
                        @if ($advance_account_id && $payment_account_id && $amount > 0)
                            @php
                                $drName = $advanceAccounts->firstWhere('id', $advance_account_id)?->name ?? '—';
                                $crName = collect($payment_account_children)->firstWhere('id', $payment_account_id)['name'] ?? '—';
                            @endphp
                            <div class="mt-4 rounded-lg border border-indigo-100 bg-indigo-50 p-3">
                                <p class="mb-2 text-xs font-semibold text-indigo-700">Journal Preview</p>
                                <div class="space-y-1 text-xs font-mono text-gray-700">
                                    <div class="flex justify-between">
                                        <span class="text-indigo-700">DR {{ $drName }}</span>
                                        <span class="font-semibold">{{ number_format($amount, 2) }}</span>
                                    </div>
                                    <div class="flex justify-between pl-6">
                                        <span class="text-rose-700">CR {{ $crName }}</span>
                                        <span class="font-semibold">{{ number_format($amount, 2) }}</span>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- ── SECTION 3: Payment Details ──────────────────────── --}}
                    <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                        <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-500">3. Payment Details</p>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="text-sm font-medium text-gray-700">Payment Method <span class="text-red-500">*</span></label>
                                <select wire:model="payment_method"
                                    class="mt-1 h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                                    <option value="">Select method</option>
                                    @foreach ($paymentMethods as $m)
                                        <option value="{{ $m->value }}">{{ $m->label() }}</option>
                                    @endforeach
                                </select>
                                <x-input-error for="payment_method" class="mt-1" />
                            </div>

                            <div>
                                <label class="text-sm font-medium text-gray-700">Amount <span class="text-red-500">*</span></label>
                                <input type="number" min="0.01" step="0.01" wire:model.lazy="amount"
                                    class="mt-1 h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none"
                                    placeholder="0.00">
                                <x-input-error for="amount" class="mt-1" />
                            </div>

                            <div>
                                <label class="text-sm font-medium text-gray-700">Release Date <span class="text-red-500">*</span></label>
                                <input type="date" wire:model="release_date"
                                    class="mt-1 h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                                <x-input-error for="release_date" class="mt-1" />
                            </div>

                            <div>
                                <label class="text-sm font-medium text-gray-700">Remarks</label>
                                <input type="text" wire:model="remarks"
                                    class="mt-1 h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none"
                                    placeholder="Bank transfer note, cash handover…">
                                <x-input-error for="remarks" class="mt-1" />
                            </div>
                        </div>
                    </div>

                    {{-- ── SECTION 4: Receiver ─────────────────────────────── --}}
                    <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                        <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-500">4. Receiver</p>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="text-sm font-medium text-gray-700">Payee Type <span class="text-red-500">*</span></label>
                                <select wire:model.live="payee_type"
                                    class="mt-1 h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                                    <option value="">Select type</option>
                                    <option value="supplier">Supplier</option>
                                    <option value="employee">Employee</option>
                                </select>
                                <x-input-error for="payee_type" class="mt-1" />
                            </div>

                            <div>
                                <label class="text-sm font-medium text-gray-700">Received By <span class="text-red-500">*</span></label>
                                <select wire:model="receiver_id"
                                    @disabled($payee_type === 'supplier')
                                    class="mt-1 h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none disabled:bg-gray-50 disabled:text-gray-500">
                                    <option value="">Select receiver</option>
                                    @foreach ($receivers as $r)
                                        <option value="{{ $r->id }}" @selected($receiver_id == $r->id)>{{ $r->name }}</option>
                                    @endforeach
                                </select>
                                <x-input-error for="receiver_id" class="mt-1" />
                            </div>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center justify-end gap-3">
                        <a href="{{ route('admin.inventory.purchase-orders.view', $purchaseOrder) }}"
                            class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Cancel
                        </a>
                        <button type="submit"
                            class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-5 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                            </svg>
                            Post Fund Release
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
                        <p class="text-xs text-amber-600">Total Released</p>
                        <p class="font-bold text-amber-800">{{ number_format($totalReleased, 2) }}</p>
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
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Advance Type</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Method</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">DR Account</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">CR Account</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">Amount</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Released By</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Received By</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Remarks</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($purchaseOrder->funds as $fund)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    {{ $fund->release_date?->format('d M Y') ?? '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    @if ($fund->advance_type)
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                                            {{ $fund->advance_type === \App\Enums\Inventory\FundReleaseType::EMPLOYEE_ADVANCE
                                                ? 'bg-purple-100 text-purple-700'
                                                : 'bg-blue-100 text-blue-700' }}">
                                            {{ $fund->advance_type->label() }}
                                        </span>
                                    @else
                                        <span class="text-xs text-gray-400">Legacy</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    {{ $fund->release_type?->label() ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    {{ $fund->advanceAccount?->name ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    {{ $fund->paymentAccount?->name ?? '—' }}
                                </td>
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
