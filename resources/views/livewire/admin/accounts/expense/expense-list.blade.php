<div x-data x-init="$store.pageName = { name: 'Expenses', slug: 'accounts-expenses' }">
    <div class="flex flex-wrap justify-between gap-4">
        <div>
            <h1 class="text-lg font-bold text-gray-700">Expenses</h1>
            <p class="text-sm text-gray-500">Manage expense entries and related payment accounts.</p>
        </div>

        <nav>
            <ol class="flex items-center gap-1.5 text-sm text-gray-500">
                <li><a href="{{ route('admin.dashboard') }}" class="hover:text-gray-700">Dashboard</a></li>
                <li>/</li>
                <li class="text-gray-700">Expenses</li>
            </ol>
        </nav>
    </div>

    <div class="mt-4 rounded-2xl border border-gray-200 bg-white">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <div class="grid grid-cols-1 gap-3 lg:grid-cols-12">
                <div class="lg:col-span-3">
                    <input
                        type="text"
                        wire:model.live.debounce.400ms="search"
                        placeholder="Search expense no, title, notes"
                        class="h-11 w-full rounded-lg border border-gray-300 px-4 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none"
                    >
                </div>

                <div class="lg:col-span-2">
                    <select wire:model.live="expenseAccountFilter" class="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                        <option value="">Expense Account</option>
                        @foreach ($accounts as $account)
                            <option value="{{ $account->id }}">{{ $account->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="lg:col-span-2">
                    <select wire:model.live="paymentAccountFilter" class="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                        <option value="">Payment Account</option>
                        @foreach ($accounts as $account)
                            <option value="{{ $account->id }}">{{ $account->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="lg:col-span-2">
                    <input type="date" wire:model.live="dateFrom" class="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                </div>

                <div class="lg:col-span-2">
                    <input type="date" wire:model.live="dateTo" class="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                </div>

                <div class="lg:col-span-1">
                    @can('accounts.expense.create')
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
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Expense No</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Date</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Title</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Accounts</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Reference</th>
                                <th class="px-5 py-3 text-right text-xs font-medium text-gray-500">Amount</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Created By</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Attachments</th>
                                <th class="px-5 py-3 text-right text-xs font-medium text-gray-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($expenses as $expense)
                                <tr>
                                    <td class="px-5 py-4 text-sm font-medium text-gray-800">{{ $expense->expense_no ?: 'Auto' }}</td>
                                    <td class="px-5 py-4 text-sm text-gray-700">{{ optional($expense->date)->format('d M, Y') }}</td>
                                    <td class="px-5 py-4 text-sm text-gray-700">{{ $expense->title }}</td>
                                    <td class="px-5 py-4 text-sm text-gray-700">
                                        <p>Dr: {{ $expense->expenseAccount?->name ?? 'N/A' }}</p>
                                        <p class="text-xs text-gray-500">Cr: {{ $expense->paymentAccount?->name ?? 'N/A' }}</p>
                                    </td>
                                    <td class="px-5 py-4 text-sm text-gray-700">
                                        @if ($expense->reference_type)
                                            {{ $expense->reference_type }}#{{ $expense->reference_id ?: 'N/A' }}
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 text-right text-sm font-medium text-gray-700">{{ number_format((float) $expense->amount, 2) }}</td>
                                    <td class="px-5 py-4 text-sm text-gray-700">{{ $expense->creator?->name ?? 'N/A' }}</td>
                                    <td class="px-5 py-4 text-sm text-gray-700">
                                        <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700">
                                            {{ $expense->transaction?->attachments?->count() ?? 0 }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="relative flex justify-end" x-data="{ open: false }">
                                            <button type="button" @click="open = !open" class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-zinc-200 bg-white text-zinc-600 shadow-sm transition hover:bg-zinc-50 hover:text-zinc-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                                <span class="sr-only">Open actions</span>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path d="M10 6a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3ZM10 11.5A1.5 1.5 0 1 0 10 8.5a1.5 1.5 0 0 0 0 3ZM10 17a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z" />
                                                </svg>
                                            </button>

                                            <div x-show="open" @click.away="open = false" style="display: none;" x-transition class="absolute right-0 z-40 mt-10 w-56 origin-top-right rounded-md border border-zinc-200 bg-white p-1 shadow-lg">
                                                @can('accounts.expense.print')
                                                    <a href="{{ route('admin.accounts.expenses.print', $expense) }}" target="_blank" class="flex w-full items-center rounded-lg px-3 py-2 text-left text-sm text-zinc-700 transition hover:bg-zinc-100">
                                                        Print
                                                    </a>

                                                    <a href="{{ route('admin.accounts.expenses.pdf', $expense) }}" class="flex w-full items-center rounded-lg px-3 py-2 text-left text-sm text-zinc-700 transition hover:bg-zinc-100">
                                                        Download PDF
                                                    </a>
                                                @endcan

                                                @can('accounts.transaction-attachment.view')
                                                    <button type="button" wire:click="openAttachmentModal({{ $expense->id }})" class="flex w-full items-center rounded-lg px-3 py-2 text-left text-sm text-zinc-700 transition hover:bg-zinc-100">
                                                        Attachments
                                                    </button>
                                                @endcan

                                                @can('accounts.expense.edit')
                                                    <button type="button" wire:click="openEditModal({{ $expense->id }})" class="flex w-full items-center rounded-lg px-3 py-2 text-left text-sm text-zinc-700 transition hover:bg-zinc-100">
                                                        Edit
                                                    </button>
                                                @endcan

                                                @can('accounts.expense.delete')
                                                    <button
                                                        type="button"
                                                        x-data="livewireConfirm"
                                                        @click="confirmAction({
                                                            id: {{ $expense->id }},
                                                            method: 'deleteExpense',
                                                            title: 'Delete expense?',
                                                            text: 'Linked transaction and lines will be removed safely.',
                                                            confirmText: 'Yes, delete expense'
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
                                    <td colspan="9" class="px-5 py-12 text-center">
                                        <p class="text-sm font-medium text-gray-700">No expenses found.</p>
                                        <p class="mt-1 text-xs text-gray-500">Try changing filters or create an expense.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($expenses->hasPages())
                <div class="mt-6">
                    {{ $expenses->links() }}
                </div>
            @endif
        </div>
    </div>

    <div x-cloak x-data="{ open: @entangle('showFormModal') }" x-show="open" x-transition class="fixed inset-0 z-50 grid place-content-center bg-black/50 p-4" role="dialog" aria-modal="true">
        <div class="w-full max-w-3xl rounded-lg bg-white p-6 shadow-lg">
            <div class="flex items-start justify-between">
                <h2 class="text-xl font-bold text-gray-900">{{ $editingId ? 'Edit Expense' : 'Create Expense' }}</h2>
                <button type="button" @click="open = false; $wire.closeFormModal()" class="-me-4 -mt-4 rounded-full p-2 text-gray-400 transition hover:bg-gray-50 hover:text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form wire:submit.prevent="save" class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="text-sm font-medium text-gray-700">Expense No</label>
                    <input type="text" wire:model.defer="expense_no" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none" placeholder="Auto if empty">
                    @error('expense_no') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">Date <span class="text-rose-500">*</span></label>
                    <input type="date" wire:model.defer="date" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                    @error('date') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="text-sm font-medium text-gray-700">Title <span class="text-rose-500">*</span></label>
                    <input type="text" wire:model.defer="title" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none" placeholder="Expense title">
                    @error('title') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">Expense Account (Debit) <span class="text-rose-500">*</span></label>
                    <select wire:model.defer="expense_account_id" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
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
                    @error('expense_account_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">Payment Account (Credit) <span class="text-rose-500">*</span></label>
                    <select wire:model.defer="payment_account_id" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
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
                    @error('payment_account_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">Amount <span class="text-rose-500">*</span></label>
                    <input type="number" step="0.01" min="0" wire:model.defer="amount" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none" placeholder="0.00">
                    @error('amount') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">Reference Type</label>
                    <input type="text" wire:model.defer="reference_type" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none" placeholder="Optional reference type">
                    @error('reference_type') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">Reference ID</label>
                    <input type="number" min="1" wire:model.defer="reference_id" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none" placeholder="Optional">
                    @error('reference_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2">
                    <x-media-picker-field
                        field="attachment_ids"
                        :value="$attachment_ids"
                        placeholder="Add attachments"
                        :multiple="true"
                        type="all"
                        label="Attachments"
                        required="false"
                    />
                    @error('attachment_ids') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    @error('attachment_ids.*') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="text-sm font-medium text-gray-700">Notes</label>
                    <textarea wire:model.defer="notes" rows="3" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none" placeholder="Optional notes"></textarea>
                    @error('notes') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2 mt-2 flex justify-end gap-2">
                    <button type="button" @click="open = false; $wire.closeFormModal()" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" class="inline-flex items-center rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-gray-800">
                        {{ $editingId ? 'Update Expense' : 'Save Expense' }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div x-cloak x-data="{ open: @entangle('showAttachmentModal') }" x-show="open" x-transition class="fixed inset-0 z-50 grid place-content-center bg-black/50 p-4" role="dialog" aria-modal="true">
        <div class="w-full max-w-3xl rounded-lg bg-white p-6 shadow-lg">
            <div class="flex items-start justify-between">
                <h2 class="text-xl font-bold text-gray-900">Expense Attachments</h2>
                <button type="button" @click="open = false; $wire.closeAttachmentModal()" class="-me-4 -mt-4 rounded-full p-2 text-gray-400 transition hover:bg-gray-50 hover:text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="mt-4">
                @if ($attachmentExpense && $attachmentExpense->transaction)
                    @include('livewire.admin.accounts.partials.attachment-list', [
                        'attachments' => $attachmentExpense->transaction->attachments,
                        'fancyboxGroup' => 'expense-attachments-'.$attachmentExpense->id,
                        'canRemove' => auth()->user()?->can('accounts.expense.edit'),
                        'removeMethod' => 'removeAttachment',
                        'emptyMessage' => 'No attachments found for this expense.',
                    ])
                @else
                    <p class="text-sm text-gray-500">No attachments found for this expense.</p>
                @endif
            </div>
        </div>
    </div>
</div>
