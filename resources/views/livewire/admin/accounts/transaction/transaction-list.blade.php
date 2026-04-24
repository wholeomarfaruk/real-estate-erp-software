<div x-data x-init="$store.pageName = { name: 'Account Transactions', slug: 'accounts-transactions' }">
    <div class="flex flex-wrap justify-between gap-4">
        <div>
            <h1 class="text-lg font-bold text-gray-700">Transactions</h1>
            <p class="text-sm text-gray-500">Audit list of accounting transactions and line-level debit/credit details.</p>
        </div>

        <nav>
            <ol class="flex items-center gap-1.5 text-sm text-gray-500">
                <li><a href="{{ route('admin.dashboard') }}" class="hover:text-gray-700">Dashboard</a></li>
                <li>/</li>
                <li class="text-gray-700">Transactions</li>
            </ol>
        </nav>
    </div>

    <div class="mt-4 rounded-2xl border border-gray-200 bg-white">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-5">
                <div class="md:col-span-2">
                    <input
                        type="text"
                        wire:model.live.debounce.400ms="search"
                        placeholder="Search notes or reference"
                        class="h-11 w-full rounded-lg border border-gray-300 px-4 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none"
                    >
                </div>

                <div>
                    <select wire:model.live="typeFilter" class="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                        <option value="">All Types</option>
                        @foreach ($types as $transactionType)
                            <option value="{{ $transactionType->value }}">{{ $transactionType->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <input type="date" wire:model.live="dateFrom" class="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                </div>

                <div>
                    <input type="date" wire:model.live="dateTo" class="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                </div>
            </div>
        </div>

        <div class="border-t border-gray-100 p-5 sm:p-6">
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
                <div class="max-w-full overflow-x-auto min-h-[55vh]">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-gray-100">
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Date</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Type</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Reference</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Notes</th>
                                <th class="px-5 py-3 text-right text-xs font-medium text-gray-500">Debit</th>
                                <th class="px-5 py-3 text-right text-xs font-medium text-gray-500">Credit</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Balance</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Attachments</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Created By</th>
                                <th class="px-5 py-3 text-right text-xs font-medium text-gray-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($transactions as $transaction)
                                @php
                                    $debit = (float) ($transaction->total_debit ?? 0);
                                    $credit = (float) ($transaction->total_credit ?? 0);
                                    $balanced = abs($debit - $credit) < 0.0001;
                                @endphp
                                <tr>
                                    <td class="px-5 py-4 text-sm text-gray-700">{{ optional($transaction->date)->format('d M, Y') }}</td>

                                    <td class="px-5 py-4">
                                        <x-transaction-type-badge :type="$transaction->type" />
                                    </td>

                                    <td class="px-5 py-4 text-sm text-gray-700">
                                        @if ($transaction->reference_type)
                                            {{ $transaction->reference_type }}#{{ $transaction->reference_id ?: 'N/A' }}
                                        @else
                                            N/A
                                        @endif
                                    </td>

                                    <td class="px-5 py-4 text-sm text-gray-700">{{ $transaction->notes ? \Illuminate\Support\Str::limit($transaction->notes, 45) : 'N/A' }}</td>

                                    <td class="px-5 py-4 text-right text-sm text-gray-700">{{ number_format($debit, 3) }}</td>
                                    <td class="px-5 py-4 text-right text-sm text-gray-700">{{ number_format($credit, 3) }}</td>

                                    <td class="px-5 py-4">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $balanced ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                            {{ $balanced ? 'Balanced' : 'Unbalanced' }}
                                        </span>
                                    </td>

                                    <td class="px-5 py-4 text-sm text-gray-700">
                                        <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700">
                                            {{ $transaction->attachments_count ?? 0 }}
                                        </span>
                                    </td>

                                    <td class="px-5 py-4 text-sm text-gray-700">{{ $transaction->creator?->name ?? 'N/A' }}</td>

                                    <td class="px-5 py-4 text-right">
                                        @can('accounts.transaction.view')
                                            <button type="button" wire:click="viewTransaction({{ $transaction->id }})" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50">
                                                View
                                            </button>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="px-5 py-12 text-center">
                                        <p class="text-sm font-medium text-gray-700">No transactions found.</p>
                                        <p class="mt-1 text-xs text-gray-500">Try changing filters.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($transactions->hasPages())
                <div class="mt-6">
                    {{ $transactions->links() }}
                </div>
            @endif
        </div>
    </div>

    <div x-cloak x-data="{ open: @entangle('showViewModal') }" x-show="open" x-transition class="fixed inset-0 z-50 grid place-content-center bg-black/50 p-4" role="dialog" aria-modal="true">
        <div class="w-full max-w-4xl rounded-lg bg-white p-6 shadow-lg">
            <div class="flex items-start justify-between">
                <h2 class="text-xl font-bold text-gray-900">Transaction Details</h2>
                <button type="button" @click="open = false; $wire.closeViewModal()" class="-me-4 -mt-4 rounded-full p-2 text-gray-400 transition hover:bg-gray-50 hover:text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            @if ($viewTransaction)
                @php
                    $viewDebit = (float) ($viewTransaction->total_debit ?? 0);
                    $viewCredit = (float) ($viewTransaction->total_credit ?? 0);
                    $viewBalanced = abs($viewDebit - $viewCredit) < 0.0001;
                @endphp

                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2">
                        <p class="text-xs text-gray-500">Date</p>
                        <p class="text-sm font-medium text-gray-800">{{ optional($viewTransaction->date)->format('d M, Y') }}</p>
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2">
                        <p class="text-xs text-gray-500">Type</p>
                        <div class="mt-1"><x-transaction-type-badge :type="$viewTransaction->type" /></div>
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2">
                        <p class="text-xs text-gray-500">Reference</p>
                        <p class="text-sm font-medium text-gray-800">
                            {{ $viewTransaction->reference_type ? $viewTransaction->reference_type.'#'.$viewTransaction->reference_id : 'N/A' }}
                        </p>
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2">
                        <p class="text-xs text-gray-500">Created By</p>
                        <p class="text-sm font-medium text-gray-800">{{ $viewTransaction->creator?->name ?? 'N/A' }}</p>
                    </div>
                </div>

                <div class="mt-4 rounded-xl border border-gray-200">
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr class="border-b border-gray-100 bg-gray-50">
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Account</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Description</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">Debit</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">Credit</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($viewTransaction->lines as $line)
                                    <tr>
                                        <td class="px-4 py-2 text-sm text-gray-700">
                                            {{ $line->account?->name ?? 'N/A' }}
                                            <p class="text-xs text-gray-500">{{ $line->account?->code ?: 'No code' }}</p>
                                        </td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $line->description ?: 'N/A' }}</td>
                                        <td class="px-4 py-2 text-right text-sm text-gray-700">{{ number_format((float) $line->debit, 3) }}</td>
                                        <td class="px-4 py-2 text-right text-sm text-gray-700">{{ number_format((float) $line->credit, 3) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="border-t border-gray-200 bg-gray-50">
                                    <th colspan="2" class="px-4 py-2 text-right text-sm font-semibold text-gray-700">Total</th>
                                    <th class="px-4 py-2 text-right text-sm font-semibold text-gray-800">{{ number_format($viewDebit, 3) }}</th>
                                    <th class="px-4 py-2 text-right text-sm font-semibold text-gray-800">{{ number_format($viewCredit, 3) }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <div class="mt-3 flex items-center justify-between">
                    <p class="text-sm text-gray-600">{{ $viewTransaction->notes ?: 'No notes provided.' }}</p>
                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $viewBalanced ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                        {{ $viewBalanced ? 'Balanced Entry' : 'Unbalanced Entry' }}
                    </span>
                </div>

                <div class="mt-4 rounded-xl border border-gray-200 p-4">
                    <h3 class="text-sm font-semibold text-gray-700">Attachments</h3>
                    <div class="mt-3">
                        @include('livewire.admin.accounts.partials.attachment-list', [
                            'attachments' => $viewTransaction->attachments,
                            'fancyboxGroup' => 'transaction-attachments-'.$viewTransaction->id,
                            'canRemove' => false,
                            'emptyMessage' => 'No attachments available for this transaction.',
                        ])
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
