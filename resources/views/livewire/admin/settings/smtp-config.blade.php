<div x-data x-init="$store.pageName = { name: 'SMTP Configuration', slug: 'settings' }" class="space-y-6">

    {{-- ── Page Header ── --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">SMTP Configuration</h1>
            <p class="text-sm text-gray-500 mt-0.5">Configure your outgoing email server</p>
        </div>
        <nav>
            <ol class="flex items-center gap-1.5 text-sm text-gray-500">
                <li><a href="{{ route('admin.dashboard') }}" class="hover:text-gray-700">Dashboard</a></li>
                <li><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></li>
                <li class="text-gray-800 font-medium">SMTP Config</li>
            </ol>
        </nav>
    </div>

    {{-- ── Status Banner ── --}}
    @if ($isNew)
        <div class="flex items-center gap-3 px-4 py-3 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-sm">
            <svg class="w-5 h-5 flex-shrink-0 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
            <span><strong>Not configured yet.</strong> Fill in the details below and save to enable outgoing email.</span>
        </div>
    @else
        <div class="flex items-center gap-3 px-4 py-3 rounded-xl bg-green-50 border border-green-200 text-green-800 text-sm">
            <span class="w-2.5 h-2.5 rounded-full bg-green-500 flex-shrink-0 animate-pulse"></span>
            <span><strong>SMTP is configured.</strong> Outgoing email is set up for <span class="font-mono">{{ $fFromAddress ?: '—' }}</span> via <span class="font-mono">{{ $fHost ?: '—' }}:{{ $fPort }}</span>.</span>
        </div>
    @endif

    {{-- ── Settings Card ── --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">

        {{-- Card header --}}
        <div class="px-6 py-5 border-b border-gray-100 flex items-center gap-3">
            <div class="w-9 h-9 rounded-lg bg-blue-50 flex items-center justify-center">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            </div>
            <div>
                <h2 class="text-base font-semibold text-gray-900">Mail Server Settings</h2>
                <p class="text-xs text-gray-500">All fields are required for the first save</p>
            </div>
        </div>

        <div class="px-6 py-6 space-y-8">

            {{-- ── Section: Server ── --}}
            <div>
                <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-4">Server</h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">

                    {{-- Host --}}
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                            SMTP Host <span class="text-red-500">*</span>
                        </label>
                        <input type="text" wire:model="fHost"
                            class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition placeholder-gray-400"
                            placeholder="smtp.gmail.com">
                        @error('fHost') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    {{-- Port --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                            Port <span class="text-red-500">*</span>
                        </label>
                        <input type="number" wire:model="fPort"
                            class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                            placeholder="587">
                        @error('fPort') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                </div>

                {{-- Encryption --}}
                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Encryption <span class="text-red-500">*</span>
                    </label>
                    <div class="flex gap-3">
                        {{-- TLS --}}
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" wire:model.live="fEncryption" value="tls" class="sr-only peer">
                            <div class="border-2 rounded-xl p-3 text-center transition-all select-none
                                border-gray-200 hover:border-gray-300
                                peer-checked:border-blue-500 peer-checked:bg-blue-50">
                                <p class="text-sm font-semibold text-gray-700 peer-checked:text-blue-700">TLS</p>
                                <p class="text-xs text-gray-400 mt-0.5">Port 587 — recommended</p>
                            </div>
                        </label>
                        {{-- SSL --}}
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" wire:model.live="fEncryption" value="ssl" class="sr-only peer">
                            <div class="border-2 rounded-xl p-3 text-center transition-all select-none
                                border-gray-200 hover:border-gray-300
                                peer-checked:border-purple-500 peer-checked:bg-purple-50">
                                <p class="text-sm font-semibold text-gray-700 peer-checked:text-purple-700">SSL</p>
                                <p class="text-xs text-gray-400 mt-0.5">Port 465</p>
                            </div>
                        </label>
                        {{-- None --}}
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" wire:model.live="fEncryption" value="none" class="sr-only peer">
                            <div class="border-2 rounded-xl p-3 text-center transition-all select-none
                                border-gray-200 hover:border-gray-300
                                peer-checked:border-gray-500 peer-checked:bg-gray-100">
                                <p class="text-sm font-semibold text-gray-700 peer-checked:text-gray-800">None</p>
                                <p class="text-xs text-gray-400 mt-0.5">Unencrypted</p>
                            </div>
                        </label>
                    </div>
                    @error('fEncryption') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="border-t border-gray-100"></div>

            {{-- ── Section: Authentication ── --}}
            <div>
                <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-4">Authentication</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                    {{-- Username --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                            Username / Email <span class="text-red-500">*</span>
                        </label>
                        <input type="text" wire:model="fUsername" autocomplete="off"
                            class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition placeholder-gray-400"
                            placeholder="you@gmail.com">
                        @error('fUsername') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    {{-- Password --}}
                    <div x-data="{ show: false }">
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                            Password / App Password
                            @if (! $isNew)
                                <span class="text-gray-400 font-normal text-xs">(leave blank to keep current)</span>
                            @else
                                <span class="text-red-500">*</span>
                            @endif
                        </label>
                        <div class="relative">
                            <input :type="show ? 'text' : 'password'" wire:model="fPassword" autocomplete="new-password"
                                class="w-full px-3.5 py-2.5 pr-10 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition placeholder-gray-400"
                                placeholder="{{ $isNew ? 'Enter password or app password' : '••••••••' }}">
                            <button type="button" @click="show = !show"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors">
                                <svg x-show="!show" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                <svg x-show="show" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                </svg>
                            </button>
                        </div>
                        @error('fPassword') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                </div>
            </div>

            <div class="border-t border-gray-100"></div>

            {{-- ── Section: Sender Identity ── --}}
            <div>
                <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-4">Sender Identity</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                            From Address <span class="text-red-500">*</span>
                        </label>
                        <input type="email" wire:model="fFromAddress"
                            class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition placeholder-gray-400"
                            placeholder="no-reply@yourdomain.com">
                        @error('fFromAddress') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                            From Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" wire:model="fFromName"
                            class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition placeholder-gray-400"
                            placeholder="Your Company Name">
                        @error('fFromName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                </div>
            </div>

        </div>

        {{-- ── Card Footer ── --}}
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex flex-wrap items-center justify-between gap-3">

            {{-- Test connection --}}
            <button type="button" wire:click="testConnection"
                wire:loading.attr="disabled" wire:target="testConnection"
                @if ($isNew) disabled title="Save your configuration first" @endif
                class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium rounded-lg border transition-colors
                    {{ $isNew
                        ? 'border-gray-200 text-gray-300 cursor-not-allowed bg-white'
                        : 'border-gray-300 text-gray-700 bg-white hover:bg-gray-50' }}">
                <span wire:loading.remove wire:target="testConnection">
                    <svg class="w-4 h-4 inline -mt-0.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    Test Connection
                </span>
                <span wire:loading wire:target="testConnection" class="inline-flex items-center gap-2">
                    <svg class="w-4 h-4 animate-spin text-amber-500" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    Testing…
                </span>
            </button>

            {{-- Save --}}
            @can('settings.smtp.view')
            <button type="button" wire:click="save"
                wire:loading.attr="disabled" wire:target="save"
                class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors disabled:opacity-70 shadow-sm">
                <span wire:loading.remove wire:target="save">
                    <svg class="w-4 h-4 inline -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    {{ $isNew ? 'Save Configuration' : 'Update Configuration' }}
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

    {{-- ── Help Tips ── --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
        <div class="bg-white rounded-xl border border-gray-200 p-4 space-y-1">
            <p class="font-semibold text-gray-700">Gmail / Google Workspace</p>
            <p class="text-gray-400 text-xs">Host: <span class="font-mono">smtp.gmail.com</span></p>
            <p class="text-gray-400 text-xs">Port: <span class="font-mono">587</span> · TLS</p>
            <p class="text-gray-400 text-xs">Use an App Password, not your main password</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 space-y-1">
            <p class="font-semibold text-gray-700">Outlook / Microsoft 365</p>
            <p class="text-gray-400 text-xs">Host: <span class="font-mono">smtp.office365.com</span></p>
            <p class="text-gray-400 text-xs">Port: <span class="font-mono">587</span> · TLS</p>
            <p class="text-gray-400 text-xs">Use your full email as the username</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 space-y-1">
            <p class="font-semibold text-gray-700">SendGrid / Mailgun</p>
            <p class="text-gray-400 text-xs">Host: <span class="font-mono">smtp.sendgrid.net</span></p>
            <p class="text-gray-400 text-xs">Port: <span class="font-mono">587</span> · TLS</p>
            <p class="text-gray-400 text-xs">Username: <span class="font-mono">apikey</span> · Password: your API key</p>
        </div>
    </div>

</div>
