<div x-data x-init="$store.pageName = { name: 'Notification Settings', slug: 'settings' }" class="space-y-6">

    {{-- ── Page Header ── --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Notification Settings</h1>
            <p class="text-sm text-gray-500 mt-0.5">Control which notification types are active and how they're delivered</p>
        </div>
        <nav>
            <ol class="flex items-center gap-1.5 text-sm text-gray-500">
                <li><a href="{{ route('admin.dashboard') }}" class="hover:text-gray-700">Dashboard</a></li>
                <li><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></li>
                <li class="text-gray-800 font-medium">Notification Settings</li>
            </ol>
        </nav>
    </div>

    {{-- ── Status Banner ── --}}
    <div class="flex items-center gap-3 px-4 py-3 rounded-xl bg-blue-50 border border-blue-200 text-blue-800 text-sm">
        <span class="w-2.5 h-2.5 rounded-full bg-blue-500 flex-shrink-0"></span>
        <span>
            <strong>{{ count($enabledTypes) }}</strong> of <strong>{{ collect($groupedTypes)->flatten()->count() }}</strong>
            notification types active · Web push is
            <strong>{{ $webPushEnabled ? 'enabled' : 'disabled' }}</strong>.
        </span>
    </div>

    {{-- ── Settings Card ── --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">

        {{-- Card header --}}
        <div class="px-6 py-5 border-b border-gray-100 flex items-center gap-3">
            <div class="w-9 h-9 rounded-lg bg-blue-50 flex items-center justify-center">
                <svg class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2a2 2 0 01-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
            </div>
            <div>
                <h2 class="text-base font-semibold text-gray-900">Notification Types</h2>
                <p class="text-xs text-gray-500">Turn off any type to stop it from firing system-wide</p>
            </div>
        </div>

        <div class="px-6 py-6 space-y-8">
            @foreach ($groups as $group)
                @continue(empty($groupedTypes[$group->value]))
                <div>
                    <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-4">{{ $group->label() }}</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @foreach ($groupedTypes[$group->value] as $type)
                            <label class="flex items-center gap-3 px-3.5 py-2.5 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition">
                                <input type="checkbox" wire:model="enabledTypes" value="{{ $type->value }}"
                                    class="w-4 h-4 text-blue-600 rounded">
                                <span class="text-sm text-gray-700">{{ $type->label() }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                @if (! $loop->last)
                    <div class="border-t border-gray-100"></div>
                @endif
            @endforeach

            <div class="border-t border-gray-100"></div>

            {{-- ── Section: Delivery Channels ── --}}
            <div>
                <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-4">Delivery Channels</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="flex items-center gap-3 px-3.5 py-2.5 border border-gray-200 rounded-lg bg-gray-50">
                        <input type="checkbox" checked disabled class="w-4 h-4 text-blue-600 rounded opacity-60">
                        <div>
                            <span class="text-sm text-gray-700">In-App</span>
                            <p class="text-xs text-gray-400">Always on — bell + client API feed</p>
                        </div>
                    </div>
                    <label class="flex items-center gap-3 px-3.5 py-2.5 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition">
                        <input type="checkbox" wire:model="webPushEnabled" class="w-4 h-4 text-blue-600 rounded">
                        <div>
                            <span class="text-sm text-gray-700">Web Push</span>
                            <p class="text-xs text-gray-400">Browser push notifications</p>
                        </div>
                    </label>
                </div>
            </div>
        </div>

        {{-- ── Card Footer ── --}}
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-end">
            @can('settings.notifications.view')
            <button type="button" wire:click="save"
                wire:loading.attr="disabled" wire:target="save"
                class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors disabled:opacity-70 shadow-sm">
                <span wire:loading.remove wire:target="save">
                    <svg class="w-4 h-4 inline -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Save Settings
                </span>
                <span wire:loading wire:target="save" class="inline-flex items-center gap-2">
                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    Saving…
                </span>
            </button>
            @endcan
        </div>
    </div>

</div>
