<div wire:poll.30s>
    {{-- Floating trigger --}}
    <div class="fixed bottom-6 right-6 z-40">
        <div wire:click="openInbox"
            class="relative flex items-center justify-center w-12 h-12 rounded-full bg-gray-800 text-gray-200 hover:bg-gray-700 shadow-lg cursor-pointer transition">

            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2a2 2 0 01-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>

            @if ($unreadCount > 0)
                <span class="absolute -top-1 -right-1 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-semibold text-white">
                    {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                </span>
            @endif
        </div>
    </div>

    {{-- Inbox modal --}}
    @if ($modalOpen)
        <div x-data x-on:keydown.escape.window="$wire.closeModal()"
            class="fixed inset-0 z-[60] flex items-center justify-center p-4" style="background:rgba(0,0,0,.5);">
            <div @click.outside="$wire.closeModal()"
                class="w-full max-w-4xl h-[80vh] max-h-[640px] bg-gray-800 border border-gray-700 rounded-xl shadow-2xl overflow-hidden flex">

                {{-- Left: list --}}
                <div class="w-80 shrink-0 border-r border-gray-700 flex flex-col">
                    <div class="p-4 border-b border-gray-700 space-y-3">
                        <h3 class="text-sm font-semibold text-white">Inbox</h3>

                        <div class="relative">
                            <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-500" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search notifications…"
                                class="w-full pl-8 pr-3 py-2 text-sm rounded-lg bg-gray-900 border border-gray-700 text-gray-200 placeholder-gray-500 outline-none focus:border-gray-500">
                        </div>

                        <div class="flex gap-2">
                            <button type="button" wire:click="$set('filter', 'all')"
                                class="px-3 py-1 text-xs font-medium rounded-full {{ $filter === 'all' ? 'bg-blue-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">
                                All
                            </button>
                            <button type="button" wire:click="$set('filter', 'unread')"
                                class="px-3 py-1 text-xs font-medium rounded-full {{ $filter === 'unread' ? 'bg-blue-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">
                                Unread @if($unreadCount > 0)({{ $unreadCount }})@endif
                            </button>
                        </div>
                    </div>

                    <div class="flex-1 overflow-y-auto divide-y divide-gray-700">
                        @forelse ($inboxRecipients as $recipient)
                            <div wire:key="row-{{ $recipient->id }}" wire:click="select({{ $recipient->id }})"
                                class="px-4 py-3 cursor-pointer hover:bg-gray-700 transition
                                    {{ $selected?->id === $recipient->id ? 'bg-gray-700' : '' }}
                                    {{ $recipient->is_read ? 'opacity-60' : '' }}">
                                <div class="flex items-start justify-between gap-2">
                                    <p class="text-sm font-medium text-gray-200 truncate">{{ $recipient->notification->title }}</p>
                                    @if (! $recipient->is_read)
                                        <span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-blue-500"></span>
                                    @endif
                                </div>
                                <p class="text-xs text-gray-400 mt-1 line-clamp-2">{{ $recipient->notification->body }}</p>
                                <p class="text-[11px] text-gray-500 mt-1">{{ $recipient->created_at->diffForHumans() }}</p>
                            </div>
                        @empty
                            <p class="px-4 py-10 text-center text-xs text-gray-500">No notifications found.</p>
                        @endforelse
                    </div>

                    @if ($inboxRecipients->hasPages())
                        <div class="p-2 border-t border-gray-700 [&_button]:text-gray-300 [&_span]:text-gray-500">
                            {{ $inboxRecipients->links() }}
                        </div>
                    @endif
                </div>

                {{-- Right: detail --}}
                <div class="flex-1 flex flex-col min-w-0">
                    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-700">
                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-400">
                            {{ $selected ? $selected->notification->type->label() : 'Details' }}
                        </span>
                        <div class="flex items-center gap-4">
                            @if ($unreadCount > 0)
                                <button type="button" wire:click="markAllAsRead" class="text-xs text-blue-400 hover:text-blue-300">Mark all as read</button>
                            @endif
                            <button type="button" wire:click="closeModal" class="text-gray-400 hover:text-white">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </div>

                    <div class="flex-1 overflow-y-auto px-6 py-5">
                        @if ($selected)
                            <h3 class="text-lg font-semibold text-white">{{ $selected->notification->title }}</h3>
                            <p class="text-sm text-gray-300 mt-3 whitespace-pre-line leading-relaxed">{{ $selected->notification->body }}</p>
                            <p class="text-xs text-gray-500 mt-5">{{ $selected->created_at->format('d M Y, h:i A') }}</p>

                            @if ($selected->notification->action_url)
                                <a href="{{ $selected->notification->action_url }}"
                                    class="inline-block mt-6 px-4 py-2 text-sm font-medium rounded-lg bg-blue-600 hover:bg-blue-700 text-white">
                                    Open
                                </a>
                            @endif
                        @else
                            <div class="h-full flex flex-col items-center justify-center text-center text-gray-500">
                                <svg class="w-10 h-10 mb-3 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2a2 2 0 01-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                </svg>
                                <p class="text-sm">Select a notification to view details</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
