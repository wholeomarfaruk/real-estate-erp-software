<div x-data x-init="$store.pageName = { name: 'Collections', slug: 'accounts-collections' }">
    <div class="flex flex-wrap justify-between gap-4">
        <div>
            <h1 class="text-lg font-bold text-gray-700">Collections</h1>
            <p class="text-sm text-gray-500">Manage incoming collections with linked accounting transactions.</p>
        </div>

        <nav>
            <ol class="flex items-center gap-1.5 text-sm text-gray-500">
                <li><a href="{{ route('admin.dashboard') }}" class="hover:text-gray-700">Dashboard</a></li>
                <li>/</li>
                <li class="text-gray-700">Collections</li>
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
                        placeholder="Search collection no, payer, notes"
                        class="h-11 w-full rounded-lg border border-gray-300 px-4 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none"
                    >
                </div>

                <div class="lg:col-span-2">
                    <select wire:model.live="methodFilter" class="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                        <option value="">All Methods</option>
                        @foreach ($methods as $entryMethod)
                            <option value="{{ $entryMethod->value }}">{{ $entryMethod->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="lg:col-span-2">
                    <select wire:model.live="collectionTypeFilter" class="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                        <option value="">All Types</option>
                        @foreach ($collectionTypes as $type)
                            <option value="{{ $type->value }}">{{ $type->label() }}</option>
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
                    @can('accounts.collection.create')
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
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Collection No</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Date</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Method</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Type</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Accounts</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Payer</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Reference</th>
                                <th class="px-5 py-3 text-right text-xs font-medium text-gray-500">Amount</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Created By</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Attachments</th>
                                <th class="px-5 py-3 text-right text-xs font-medium text-gray-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($collections as $collection)
                                <tr>
                                    <td class="px-5 py-4 text-sm font-medium text-gray-800">{{ $collection->collection_no ?: 'Auto' }}</td>
                                    <td class="px-5 py-4 text-sm text-gray-700">{{ optional($collection->date)->format('d M, Y') }}</td>
                                    <td class="px-5 py-4 text-sm text-gray-700">{{ $collection->method?->label() ?? 'N/A' }}</td>
                                    <td class="px-5 py-4 text-sm text-gray-700">{{ $collection->collection_type?->label() ?? 'N/A' }}</td>
                                    <td class="px-5 py-4 text-sm text-gray-700">
                                        <p>Dr: {{ $collection->collectionAccount?->name ?? 'N/A' }}</p>
                                        <p class="text-xs text-gray-500">Cr: {{ $collection->targetAccount?->name ?? 'N/A' }}</p>
                                    </td>
                                    <td class="px-5 py-4 text-sm text-gray-700">{{ $collection->payer_name ?: 'N/A' }}</td>
                                    <td class="px-5 py-4 text-sm text-gray-700">
                                        @if ($collection->reference_type)
                                            {{ $collection->reference_type }}#{{ $collection->reference_id ?: 'N/A' }}
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 text-right text-sm font-medium text-gray-700">{{ number_format((float) $collection->amount, 2) }}</td>
                                    <td class="px-5 py-4 text-sm text-gray-700">{{ $collection->creator?->name ?? 'N/A' }}</td>
                                    <td class="px-5 py-4 text-sm text-gray-700">
                                        <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700">
                                            {{ $collection->transaction?->attachments?->count() ?? 0 }}
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
                                                @can('accounts.collection.print')
                                                    <a href="{{ route('admin.accounts.collections.print', $collection) }}" target="_blank" class="flex w-full items-center rounded-lg px-3 py-2 text-left text-sm text-zinc-700 transition hover:bg-zinc-100">
                                                        Print
                                                    </a>

                                                    <a href="{{ route('admin.accounts.collections.pdf', $collection) }}" class="flex w-full items-center rounded-lg px-3 py-2 text-left text-sm text-zinc-700 transition hover:bg-zinc-100">
                                                        Download PDF
                                                    </a>
                                                @endcan

                                                @can('accounts.transaction-attachment.view')
                                                    <button type="button" wire:click="openAttachmentModal({{ $collection->id }})" class="flex w-full items-center rounded-lg px-3 py-2 text-left text-sm text-zinc-700 transition hover:bg-zinc-100">
                                                        Attachments
                                                    </button>
                                                @endcan

                                                @can('accounts.collection.edit')
                                                    <button type="button" wire:click="openEditModal({{ $collection->id }})" class="flex w-full items-center rounded-lg px-3 py-2 text-left text-sm text-zinc-700 transition hover:bg-zinc-100">
                                                        Edit
                                                    </button>
                                                @endcan

                                                @can('accounts.collection.delete')
                                                    <button
                                                        type="button"
                                                        x-data="livewireConfirm"
                                                        @click="confirmAction({
                                                            id: {{ $collection->id }},
                                                            method: 'deleteCollection',
                                                            title: 'Delete collection?',
                                                            text: 'Linked transaction and lines will be removed safely.',
                                                            confirmText: 'Yes, delete collection'
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
                                    <td colspan="11" class="px-5 py-12 text-center">
                                        <p class="text-sm font-medium text-gray-700">No collections found.</p>
                                        <p class="mt-1 text-xs text-gray-500">Try changing filters or create a collection.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($collections->hasPages())
                <div class="mt-6">
                    {{ $collections->links() }}
                </div>
            @endif
        </div>
    </div>

    <div x-cloak x-data="{ open: @entangle('showFormModal') }" x-show="open" x-transition class="fixed inset-0 z-50 grid place-content-center bg-black/50 p-4" role="dialog" aria-modal="true">
        <div class="w-full max-w-3xl rounded-lg bg-white p-6 shadow-lg">
            <div class="flex items-start justify-between">
                <h2 class="text-xl font-bold text-gray-900">{{ $editingId ? 'Edit Collection' : 'Create Collection' }}</h2>
                <button type="button" @click="open = false; $wire.closeFormModal()" class="-me-4 -mt-4 rounded-full p-2 text-gray-400 transition hover:bg-gray-50 hover:text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form wire:submit.prevent="save" class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="text-sm font-medium text-gray-700">Collection No</label>
                    <input type="text" wire:model.defer="collection_no" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none" placeholder="Auto if empty">
                    @error('collection_no') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">Date <span class="text-rose-500">*</span></label>
                    <input type="date" wire:model.defer="date" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                    @error('date') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">Method <span class="text-rose-500">*</span></label>
                    <select wire:model.defer="method" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                        @foreach ($methods as $entryMethod)
                            <option value="{{ $entryMethod->value }}">{{ $entryMethod->label() }}</option>
                        @endforeach
                    </select>
                    @error('method') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">Collection Type <span class="text-rose-500">*</span></label>
                    <select wire:model.defer="collection_type" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                        @foreach ($collectionTypes as $type)
                            <option value="{{ $type->value }}">{{ $type->label() }}</option>
                        @endforeach
                    </select>
                    @error('collection_type') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">Collection Account (Debit) <span class="text-rose-500">*</span></label>
                    <select wire:model.defer="collection_account_id" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
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
                    @error('collection_account_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">Target Account (Credit) <span class="text-rose-500">*</span></label>
                    <select wire:model.live="target_account_id" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
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
                    @error('target_account_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">Amount <span class="text-rose-500">*</span></label>
                    <input type="number" step="0.01" min="0" wire:model.defer="amount" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none" placeholder="0.00">
                    @error('amount') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">Payer Name</label>
                    <input type="text" wire:model.defer="payer_name" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none" placeholder="Optional payer name">
                    @error('payer_name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">Reference Type</label>
                    <select wire:model.defer="reference_type" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                        <option value="">No reference</option>
                        @foreach ($availableReferenceOptions as $referenceKey => $referenceLabel)
                            <option value="{{ $referenceKey }}">{{ $referenceLabel }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500">
                        {{ $target_account_id && empty($availableReferenceOptions) ? 'No reference types are linked to the selected target account.' : 'Options are based on the selected target account.' }}
                    </p>
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
                        {{ $editingId ? 'Update Collection' : 'Save Collection' }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div x-cloak x-data="{ open: @entangle('showAttachmentModal') }" x-show="open" x-transition class="fixed inset-0 z-50 grid place-content-center bg-black/50 p-4" role="dialog" aria-modal="true">
        <div class="w-full max-w-3xl rounded-lg bg-white p-6 shadow-lg">
            <div class="flex items-start justify-between">
                <h2 class="text-xl font-bold text-gray-900">Collection Attachments</h2>
                <button type="button" @click="open = false; $wire.closeAttachmentModal()" class="-me-4 -mt-4 rounded-full p-2 text-gray-400 transition hover:bg-gray-50 hover:text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="mt-4">
                @if ($attachmentCollection && $attachmentCollection->transaction)
                    @include('livewire.admin.accounts.partials.attachment-list', [
                        'attachments' => $attachmentCollection->transaction->attachments,
                        'fancyboxGroup' => 'collection-attachments-'.$attachmentCollection->id,
                        'canRemove' => auth()->user()?->can('accounts.collection.edit'),
                        'removeMethod' => 'removeAttachment',
                        'emptyMessage' => 'No attachments found for this collection.',
                    ])
                @else
                    <p class="text-sm text-gray-500">No attachments found for this collection.</p>
                @endif
            </div>
        </div>
    </div>
</div>
