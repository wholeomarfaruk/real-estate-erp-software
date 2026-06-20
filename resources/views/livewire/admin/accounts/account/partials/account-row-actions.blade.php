{{-- Shared edit / toggle-status / delete dropdown for an account row.
     Expects: $account --}}
@if (auth()->user()?->can('accounts.chart.edit') || auth()->user()?->can('accounts.chart.delete'))
    <div class="relative shrink-0" x-data="{ open: false }">
        <button type="button" @click="open = !open"
            class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-zinc-200 bg-white text-zinc-600 shadow-sm transition hover:bg-zinc-50 hover:text-zinc-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <span class="sr-only">Open actions</span>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path d="M10 6a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3ZM10 11.5A1.5 1.5 0 1 0 10 8.5a1.5 1.5 0 0 0 0 3ZM10 17a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z" />
            </svg>
        </button>

        <div x-show="open" @click.away="open = false" style="display: none;" x-transition
            class="absolute right-0 z-40 mt-1 w-52 origin-top-right rounded-md border border-zinc-200 bg-white p-1 shadow-lg">
            @can('accounts.chart.edit')
                <button type="button" wire:click="openEditModal({{ $account->id }})"
                    class="flex w-full items-center rounded-lg px-3 py-2 text-left text-sm text-zinc-700 transition hover:bg-zinc-100">
                    Edit
                </button>

                <button type="button" x-data="livewireConfirm"
                    @click="confirmAction({
                        id: {{ $account->id }},
                        method: 'toggleStatus',
                        title: 'Change account status?',
                        text: 'Account status will be updated immediately.',
                        confirmText: 'Yes, update status'
                    })"
                    class="flex w-full items-center rounded-lg px-3 py-2 text-left text-sm text-zinc-700 transition hover:bg-zinc-100">
                    {{ $account->is_active ? 'Mark Inactive' : 'Mark Active' }}
                </button>
            @endcan

            @can('accounts.chart.delete')
                @unless ($account->is_locked)
                    <button type="button" x-data="livewireConfirm"
                        @click="confirmAction({
                            id: {{ $account->id }},
                            method: 'deleteAccount',
                            title: 'Delete account?',
                            text: 'Used accounts cannot be deleted and this action is permanent.',
                            confirmText: 'Yes, delete account'
                        })"
                        class="flex w-full items-center rounded-lg px-3 py-2 text-left text-sm text-rose-600 transition hover:bg-rose-50">
                        Delete
                    </button>
                @endunless
            @endcan
        </div>
    </div>
@endif
