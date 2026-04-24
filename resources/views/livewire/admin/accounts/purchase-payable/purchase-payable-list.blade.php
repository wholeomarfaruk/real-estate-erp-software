<div x-data x-init="$store.pageName = { name: 'Purchase Payables', slug: 'accounts-purchase-payables' }">
    <div class="flex flex-wrap justify-between gap-4">
        <div>
            <h1 class="text-lg font-bold text-gray-700">Purchase Payables</h1>
            <p class="text-sm text-gray-500">Track purchase order payables, due status, and settlement entries.</p>
        </div>

        <nav>
            <ol class="flex items-center gap-1.5 text-sm text-gray-500">
                <li><a href="{{ route('admin.dashboard') }}" class="hover:text-gray-700">Dashboard</a></li>
                <li>/</li>
                <li class="text-gray-700">Purchase Payables</li>
            </ol>
        </nav>
    </div>

    <div class="mt-4 rounded-2xl border border-gray-200 bg-white">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                <div class="md:col-span-2">
                    <input
                        type="text"
                        wire:model.live.debounce.400ms="search"
                        placeholder="Search by PO or supplier"
                        class="h-11 w-full rounded-lg border border-gray-300 px-4 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none"
                    >
                </div>

                <div>
                    <select wire:model.live="statusFilter" class="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                        <option value="">All Status</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}">{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    @can('accounts.purchase-payable.create')
                        <button
                            type="button"
                            wire:click="openCreateModal"
                            class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-lg bg-gray-900 px-4 text-sm font-medium text-white transition hover:bg-gray-800"
                        >
                            Create
                        </button>
                    @endcan
                </div>
            </div>
        </div>

        <div class="border-t border-gray-100 p-5 sm:p-6">
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
                <div class="max-w-full overflow-x-auto min-h-[55vh]">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-gray-100">
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">PO</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Supplier</th>
                                <th class="px-5 py-3 text-right text-xs font-medium text-gray-500">Payable</th>
                                <th class="px-5 py-3 text-right text-xs font-medium text-gray-500">Paid</th>
                                <th class="px-5 py-3 text-right text-xs font-medium text-gray-500">Due</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Status</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Transaction</th>
                                <th class="px-5 py-3 text-right text-xs font-medium text-gray-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($payables as $payable)
                                <tr>
                                    <td class="px-5 py-4 text-sm text-gray-700">{{ $payable->purchaseOrder?->po_no ?? 'N/A' }}</td>
                                    <td class="px-5 py-4 text-sm text-gray-700">
                                        <p>{{ $payable->supplier?->name ?? 'N/A' }}</p>
                                        <p class="text-xs text-gray-500">{{ $payable->supplier?->code ?? '' }}</p>
                                    </td>
                                    <td class="px-5 py-4 text-right text-sm text-gray-700">{{ number_format((float) $payable->payable_amount, 2) }}</td>
                                    <td class="px-5 py-4 text-right text-sm text-gray-700">{{ number_format((float) $payable->paid_amount, 2) }}</td>
                                    <td class="px-5 py-4 text-right text-sm font-medium text-gray-700">{{ number_format((float) $payable->due_amount, 2) }}</td>
                                    <td class="px-5 py-4">
                                        <x-purchase-payable-status-badge :status="$payable->status" />
                                    </td>
                                    <td class="px-5 py-4 text-sm text-gray-700">
                                        @if ($payable->transaction)
                                            {{ optional($payable->transaction->date)->format('d M, Y') }}
                                            <p class="text-xs text-gray-500">{{ $payable->transaction->type?->label() ?? 'Transaction' }}</p>
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="relative flex justify-end" x-data="{ open: false }">
                                            <button type="button" @click="open = !open" class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-zinc-200 bg-white text-zinc-600 shadow-sm transition hover:bg-zinc-50 hover:text-zinc-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                                <span class="sr-only">Open actions</span>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path d="M10 6a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3ZM10 11.5A1.5 1.5 0 1 0 10 8.5a1.5 1.5 0 0 0 0 3ZM10 17a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z" />
                                                </svg>
                                            </button>

                                            <div x-show="open" @click.away="open = false" style="display: none;" x-transition class="absolute right-0 z-40 mt-10 w-52 origin-top-right rounded-md border border-zinc-200 bg-white p-1 shadow-lg">
                                                @can('accounts.purchase-payable.edit')
                                                    <button type="button" wire:click="openEditModal({{ $payable->id }})" class="flex w-full items-center rounded-lg px-3 py-2 text-left text-sm text-zinc-700 transition hover:bg-zinc-100">
                                                        Edit
                                                    </button>
                                                @endcan

                                                @can('accounts.purchase-payable.settle')
                                                    @if ((float) $payable->due_amount > 0)
                                                        <button type="button" wire:click="openSettlementModal({{ $payable->id }})" class="flex w-full items-center rounded-lg px-3 py-2 text-left text-sm text-emerald-700 transition hover:bg-emerald-50">
                                                            Settle Payment
                                                        </button>
                                                    @endif
                                                @endcan

                                                @can('accounts.purchase-payable.delete')
                                                    <button
                                                        type="button"
                                                        x-data="livewireConfirm"
                                                        @click="confirmAction({
                                                            id: {{ $payable->id }},
                                                            method: 'deletePayable',
                                                            title: 'Delete payable?',
                                                            text: 'Payables with settlements cannot be deleted.',
                                                            confirmText: 'Yes, delete payable'
                                                        })"
                                                        class="flex w-full items-center rounded-lg px-3 py-2 text-left text-sm text-rose-600 transition hover:bg-rose-50"
                                                    >
                                                        Delete
                                                    </button>
                                                @endcan
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-5 py-12 text-center">
                                        <p class="text-sm font-medium text-gray-700">No payables found.</p>
                                        <p class="mt-1 text-xs text-gray-500">Try changing filters or create a payable record.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($payables->hasPages())
                <div class="mt-6">
                    {{ $payables->links() }}
                </div>
            @endif
        </div>
    </div>

    <div x-cloak x-data="{ open: @entangle('showFormModal') }" x-show="open" x-transition class="fixed inset-0 z-50 grid place-content-center bg-black/50 p-4" role="dialog" aria-modal="true">
        <div class="w-full max-w-2xl rounded-lg bg-white p-6 shadow-lg">
            <div class="flex items-start justify-between">
                <h2 class="text-xl font-bold text-gray-900">{{ $editingId ? 'Edit Purchase Payable' : 'Create Purchase Payable' }}</h2>
                <button type="button" @click="open = false; $wire.closeFormModal()" class="-me-4 -mt-4 rounded-full p-2 text-gray-400 transition hover:bg-gray-50 hover:text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form wire:submit.prevent="save" class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="text-sm font-medium text-gray-700">Purchase Order <span class="text-rose-500">*</span></label>
                    <select wire:model.defer="purchase_order_id" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                        <option value="">Select purchase order</option>
                        @foreach ($purchaseOrders as $purchaseOrder)
                            <option value="{{ $purchaseOrder->id }}">{{ $purchaseOrder->po_no }} - {{ optional($purchaseOrder->order_date)->format('d M, Y') }}</option>
                        @endforeach
                    </select>
                    @error('purchase_order_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="text-sm font-medium text-gray-700">Supplier</label>
                    <select wire:model.defer="supplier_id" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                        <option value="">Select supplier</option>
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->name }}{{ $supplier->code ? ' ('.$supplier->code.')' : '' }}</option>
                        @endforeach
                    </select>
                    @error('supplier_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">Payable Amount <span class="text-rose-500">*</span></label>
                    <input type="number" step="0.01" min="0" wire:model.defer="payable_amount" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none" placeholder="0.00">
                    @error('payable_amount') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">Already Paid</label>
                    <input type="number" step="0.01" min="0" wire:model.defer="paid_amount" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none" placeholder="0.00">
                    @error('paid_amount') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2 mt-2 flex justify-end gap-2">
                    <button type="button" @click="open = false; $wire.closeFormModal()" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" class="inline-flex items-center rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-gray-800">
                        {{ $editingId ? 'Update Payable' : 'Save Payable' }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div x-cloak x-data="{ open: @entangle('showSettlementModal') }" x-show="open" x-transition class="fixed inset-0 z-50 grid place-content-center bg-black/50 p-4" role="dialog" aria-modal="true">
        <div class="w-full max-w-3xl rounded-lg bg-white p-6 shadow-lg">
            <div class="flex items-start justify-between">
                <h2 class="text-xl font-bold text-gray-900">Settle Payable</h2>
                <button type="button" @click="open = false; $wire.closeSettlementModal()" class="-me-4 -mt-4 rounded-full p-2 text-gray-400 transition hover:bg-gray-50 hover:text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form wire:submit.prevent="settlePayable" class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <input type="hidden" wire:model.defer="settlementPayableId">

                <div>
                    <label class="text-sm font-medium text-gray-700">Date <span class="text-rose-500">*</span></label>
                    <input type="date" wire:model.defer="settlement_date" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                    @error('settlement_date') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">Method <span class="text-rose-500">*</span></label>
                    <select wire:model.defer="settlement_method" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                        @foreach ($methods as $entryMethod)
                            <option value="{{ $entryMethod->value }}">{{ $entryMethod->label() }}</option>
                        @endforeach
                    </select>
                    @error('settlement_method') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">Payment Account (Credit) <span class="text-rose-500">*</span></label>
                    <select wire:model.defer="settlement_payment_account_id" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                        <option value="">Select account</option>
                        @foreach ($types as $accountType)
                            @if (($groupedAccounts[$accountType->value] ?? collect())->count())
                                <optgroup label="{{ $accountType->label() }}">
                                    @foreach ($groupedAccounts[$accountType->value] as $account)
                                        <option value="{{ $account->id }}">{{ $account->name }}{{ $account->code ? ' ('.$account->code.')' : '' }}</option>
                                    @endforeach
                                </optgroup>
                            @endif
                        @endforeach
                    </select>
                    @error('settlement_payment_account_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">Payable Account (Debit) <span class="text-rose-500">*</span></label>
                    <select wire:model.defer="settlement_payable_account_id" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                        <option value="">Select account</option>
                        @foreach ($types as $accountType)
                            @if (($groupedAccounts[$accountType->value] ?? collect())->count())
                                <optgroup label="{{ $accountType->label() }}">
                                    @foreach ($groupedAccounts[$accountType->value] as $account)
                                        <option value="{{ $account->id }}">{{ $account->name }}{{ $account->code ? ' ('.$account->code.')' : '' }}</option>
                                    @endforeach
                                </optgroup>
                            @endif
                        @endforeach
                    </select>
                    @error('settlement_payable_account_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">Amount <span class="text-rose-500">*</span></label>
                    <input type="number" step="0.01" min="0" wire:model.defer="settlement_amount" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none" placeholder="0.00">
                    @error('settlement_amount') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">Payee Name</label>
                    <input type="text" wire:model.defer="settlement_payee_name" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none" placeholder="Optional payee">
                    @error('settlement_payee_name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="text-sm font-medium text-gray-700">Notes</label>
                    <textarea wire:model.defer="settlement_notes" rows="3" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none" placeholder="Optional notes"></textarea>
                    @error('settlement_notes') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2 mt-2 flex justify-end gap-2">
                    <button type="button" @click="open = false; $wire.closeSettlementModal()" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" class="inline-flex items-center rounded-lg bg-emerald-700 px-4 py-2 text-sm font-medium text-white transition hover:bg-emerald-600">
                        Confirm Settlement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
