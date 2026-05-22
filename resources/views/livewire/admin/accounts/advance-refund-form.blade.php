<div x-data x-init="$store.pageName = { name: 'Advance Refund', slug: 'advance-refund' }">

    {{-- Breadcrumb --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <nav>
            <ol class="flex items-center gap-1.5 text-sm text-gray-500">
                <li><a href="{{ route('admin.dashboard') }}" class="hover:text-gray-800">Dashboard</a></li>
                <li>/</li>
                <li><a href="{{ route('admin.accounts.transactions.index') }}" class="hover:text-gray-800">Transactions</a></li>
                <li>/</li>
                <li class="text-gray-800">Advance Refund</li>
            </ol>
        </nav>
    </div>

    <div class="mt-6 mx-auto max-w-xl">
        <div class="rounded-2xl border border-gray-200 bg-white px-6 py-6 shadow-sm">

            <div class="mb-5">
                <h2 class="text-base font-semibold text-gray-800">Return Unused Advance</h2>
                <p class="mt-1 text-xs text-gray-500">
                    Record cash returned by an employee or supplier against a previously released advance.
                </p>
            </div>

            <div class="space-y-4">

                {{-- Select Fund --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600">
                        Advance Fund <span class="text-red-500">*</span>
                    </label>
                    <select wire:model.live="fund_id"
                        class="mt-1 h-9 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                        <option value="">— Select advance fund —</option>
                        @foreach ($availableFunds as $fund)
                            <option value="{{ $fund['id'] }}">{{ $fund['label'] }}</option>
                        @endforeach
                    </select>
                    @error('fund_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                @if ($fund_id && $selectedFundRemaining > 0)
                <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm">
                    <span class="text-amber-700">Available to refund:</span>
                    <span class="ml-2 font-semibold text-amber-900">{{ number_format($selectedFundRemaining, 2) }}</span>
                </div>
                @endif

                {{-- Refund Amount --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600">
                        Refund Amount <span class="text-red-500">*</span>
                    </label>
                    <input type="number" step="0.01" min="0.01"
                        max="{{ $selectedFundRemaining }}"
                        wire:model.lazy="refund_amount"
                        placeholder="0.00"
                        class="mt-1 h-9 w-full rounded-lg border border-gray-300 px-3 text-right text-sm focus:border-indigo-500 focus:outline-none">
                    @error('refund_amount') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Cash / Bank Account --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600">
                        DR — Cash / Bank Account <span class="text-red-500">*</span>
                    </label>
                    <p class="mt-0.5 text-xs text-gray-400">Where the returned cash is deposited.</p>
                    <select wire:model.live="cash_account_id"
                        class="mt-1 h-9 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                        <option value="">— Select account —</option>
                        @foreach ($cashBankAccounts as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->name }}</option>
                        @endforeach
                    </select>
                    @error('cash_account_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Payment Method --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600">
                        Payment Method <span class="text-red-500">*</span>
                    </label>
                    <select wire:model.live="method"
                        class="mt-1 h-9 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                        <option value="">— Select method —</option>
                        @foreach ($paymentMethods as $m)
                            <option value="{{ $m->value }}">{{ $m->label() }}</option>
                        @endforeach
                    </select>
                    @error('method') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Refund Date --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600">
                        Refund Date <span class="text-red-500">*</span>
                    </label>
                    <input type="date" wire:model.lazy="refund_date"
                        class="mt-1 h-9 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                    @error('refund_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Remarks --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600">Remarks</label>
                    <textarea wire:model.lazy="remarks" rows="2"
                        class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none"
                        placeholder="Optional notes…"></textarea>
                    @error('remarks') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Journal preview --}}
                @if ($fund_id && $refund_amount > 0)
                <div class="rounded-lg bg-gray-50 p-4 text-xs font-mono">
                    <p class="mb-2 font-sans text-xs font-semibold text-gray-500">Journal Preview</p>
                    <div class="space-y-1">
                        <div class="flex justify-between">
                            <span class="text-indigo-700">DR Cash / Bank</span>
                            <span class="font-semibold">{{ number_format($refund_amount, 2) }}</span>
                        </div>
                        <div class="flex justify-between pl-4 text-amber-700">
                            <span>CR Advance Account</span>
                            <span>{{ number_format($refund_amount, 2) }}</span>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Submit --}}
                <button type="button" wire:click="save"
                    wire:confirm="Record this advance refund?"
                    class="flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-indigo-700">
                    Record Refund
                </button>

            </div>
        </div>
    </div>
</div>
