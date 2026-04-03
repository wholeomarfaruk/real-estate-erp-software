<div x-data x-init="$store.pageName = { name: 'Fund Release', slug: 'purchase-orders-fund' }">
    <div class="flex flex-wrap justify-between gap-6">
        <h1 class="text-gray-500 text-lg font-bold">Purchase Order Fund Release</h1>

        <nav>
            <ol class="flex items-center gap-1.5">
                <li>
                    <a class="inline-flex items-center gap-1.5 text-sm text-gray-500" href="{{ route('admin.dashboard') }}">
                        Dashboard
                        <svg class="stroke-current" width="17" height="16" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m5.25 4.5 7.5 7.5-7.5 7.5m6-15 7.5 7.5-7.5 7.5" />
                        </svg>
                    </a>
                </li>
                <li>
                    <a class="inline-flex items-center gap-1.5 text-sm text-gray-500" href="{{ route('admin.inventory.purchase-orders.view', $purchaseOrder) }}">
                        {{ $purchaseOrder->po_no }}
                        <svg class="stroke-current" width="17" height="16" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m5.25 4.5 7.5 7.5-7.5 7.5m6-15 7.5 7.5-7.5 7.5" />
                        </svg>
                    </a>
                </li>
                <li class="text-sm text-gray-800">Fund Release</li>
            </ol>
        </nav>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-3">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6 xl:col-span-2">
            <h3 class="text-base font-semibold text-gray-800">Release Fund</h3>
            <p class="mt-1 text-xs text-gray-500">Use this form after accounts approval to release fund to purchase team.</p>

            <form wire:submit.prevent="save" class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label for="release_type" class="text-sm font-medium text-gray-700">Release Type *</label>
                    <select id="release_type" wire:model="release_type"
                        class="mt-1 h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                        @foreach ($releaseTypes as $type)
                            <option value="{{ $type->value }}">{{ $type->label() }}</option>
                        @endforeach
                    </select>
                    <x-input-error for="release_type" class="mt-1" />
                </div>

                <div>
                    <label for="amount" class="text-sm font-medium text-gray-700">Amount *</label>
                    <input id="amount" type="number" min="0.01" step="0.01" wire:model="amount"
                        class="mt-1 h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                    <x-input-error for="amount" class="mt-1" />
                </div>

                <div>
                    <label for="release_date" class="text-sm font-medium text-gray-700">Release Date *</label>
                    <input id="release_date" type="date" wire:model="release_date"
                        class="mt-1 h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                    <x-input-error for="release_date" class="mt-1" />
                </div>

                <div>
                    <label for="received_by" class="text-sm font-medium text-gray-700">Received By</label>
                    <select id="received_by" wire:model="received_by"
                        class="mt-1 h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                        <option value="">Select user</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error for="received_by" class="mt-1" />
                </div>

                <div class="md:col-span-2">
                    <label for="remarks" class="text-sm font-medium text-gray-700">Remarks</label>
                    <input id="remarks" type="text" wire:model="remarks"
                        class="mt-1 h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none"
                        placeholder="Cash handover / bank transfer note">
                    <x-input-error for="remarks" class="mt-1" />
                </div>

                <div class="md:col-span-2 flex items-center justify-end gap-3">
                    <a href="{{ route('admin.inventory.purchase-orders.view', $purchaseOrder) }}" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                        Cancel
                    </a>
                    <button type="submit" class="inline-flex items-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-emerald-700">
                        Save Fund Release
                    </button>
                </div>
            </form>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
            <h3 class="text-base font-semibold text-gray-800">PO Snapshot</h3>
            <div class="mt-4 space-y-3 text-sm text-gray-700">
                <div class="rounded-lg bg-gray-50 px-4 py-3">
                    <p class="text-xs text-gray-500">PO No</p>
                    <p class="font-medium text-gray-800">{{ $purchaseOrder->po_no }}</p>
                </div>
                <div class="rounded-lg bg-gray-50 px-4 py-3">
                    <p class="text-xs text-gray-500">Supplier</p>
                    <p class="font-medium text-gray-800">{{ $purchaseOrder->supplier?->name ?? 'N/A' }}</p>
                </div>
                <div class="rounded-lg bg-gray-50 px-4 py-3">
                    <p class="text-xs text-gray-500">Purchase Mode</p>
                    <p class="font-medium text-gray-800">{{ $purchaseOrder->purchase_mode?->label() ?? 'N/A' }}</p>
                </div>
                <div class="rounded-lg bg-gray-50 px-4 py-3">
                    <p class="text-xs text-gray-500">Approved Amount</p>
                    <p class="font-medium text-gray-800">{{ number_format((float) $purchaseOrder->approved_amount, 2) }}</p>
                </div>
                <div class="rounded-lg bg-gray-50 px-4 py-3">
                    <p class="text-xs text-gray-500">Total Released</p>
                    <p class="font-medium text-gray-800">{{ number_format($totalReleased, 2) }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4 rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
        <h3 class="text-base font-semibold text-gray-800">Fund Release History</h3>
        <div class="mt-4 overflow-hidden rounded-xl border border-gray-200">
            <div class="max-w-full overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-100 bg-gray-50">
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Type</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Amount</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Released By</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Received By</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Remarks</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($purchaseOrder->funds as $fund)
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ optional($fund->release_date)->format('d M, Y') }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $fund->release_type?->label() ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ number_format((float) $fund->amount, 2) }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $fund->releaser?->name ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $fund->receiver?->name ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $fund->remarks ?: 'N/A' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500">No fund release entry yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
