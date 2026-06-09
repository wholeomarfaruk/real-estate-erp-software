<div class="p-6 space-y-4">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">SMS Gateway Settings</h1>
            <p class="text-gray-600 text-sm mt-1">Configure SMS providers for sending messages</p>
        </div>
        <button type="button" @click="$wire.openCreate()"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
            + New Provider
        </button>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        @if ($gateways->isEmpty())
            <div class="p-8 text-center text-gray-500">
                <p>No SMS gateways configured.</p>
                <button type="button" @click="$wire.openCreate()"
                    class="text-blue-600 hover:underline mt-2">
                    Add one now
                </button>
            </div>
        @else
            <table class="w-full">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Name</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Provider</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Status</th>
                        <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach ($gateways as $gateway)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $gateway->name }}</td>
                            <td class="px-6 py-4 text-sm">
                                <span class="px-3 py-1 rounded-full text-xs font-semibold
                                    @if ($gateway->provider === 'bulk_sms_dhaka')
                                        bg-yellow-100 text-yellow-800
                                    @elseif ($gateway->provider === 'alpha_sms')
                                        bg-purple-100 text-purple-800
                                    @else
                                        bg-gray-100 text-gray-800
                                    @endif
                                ">
                                    {{ str_replace('_', ' ', ucfirst($gateway->provider)) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                @if ($gateway->is_active)
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                        ✓ Active
                                    </span>
                                @else
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-800">
                                        Inactive
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-right space-x-2">
                                @if (!$gateway->is_active)
                                    <button type="button" @click="$wire.setActive({{ $gateway->id }})"
                                        class="text-green-600 hover:text-green-800 font-medium">
                                        Set Active
                                    </button>
                                @endif

                                @if ($gateway->credentials && isset($gateway->credentials['website']))
                                    <a href="{{ $gateway->credentials['website'] }}" target="_blank" rel="noopener noreferrer"
                                        class="text-indigo-600 hover:text-indigo-800 font-medium">
                                        Website
                                    </a>
                                @endif

                                @if ($gateway->credentials && isset($gateway->credentials['dashboard']))
                                    <a href="{{ $gateway->credentials['dashboard'] }}" target="_blank" rel="noopener noreferrer"
                                        class="text-cyan-600 hover:text-cyan-800 font-medium">
                                        Dashboard
                                    </a>
                                @endif

                                @if ($gateway->provider === 'alpha_sms')
                                    <button type="button"
                                        @click="$wire.checkBalance({{ $gateway->id }})"
                                        :disabled="$wire.checkingBalanceId === {{ $gateway->id }}"
                                        class="font-medium transition
                                            {{ $gateway->is_active ? 'text-amber-600 hover:text-amber-800' : 'text-gray-400 cursor-not-allowed' }}"
                                        :class="$wire.checkingBalanceId === {{ $gateway->id }} ? 'opacity-50' : ''">
                                        <span x-show="$wire.checkingBalanceId !== {{ $gateway->id }}">💰 Balance</span>
                                        <span x-show="$wire.checkingBalanceId === {{ $gateway->id }}" class="inline-flex items-center">
                                            <svg class="animate-spin h-4 w-4 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            Checking...
                                        </span>
                                    </button>
                                @endif

                                <button type="button" @click="$wire.openEdit({{ $gateway->id }})"
                                    class="text-blue-600 hover:text-blue-800 font-medium">
                                    Edit
                                </button>
                                <button type="button" @click="if (confirm('Delete this gateway?')) $wire.delete({{ $gateway->id }})"
                                    class="text-red-600 hover:text-red-800 font-medium">
                                    Delete
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <!-- Drawer -->
    <div x-show="$wire.drawerOpen" x-transition
        class="fixed inset-0 overflow-hidden z-50"
        style="display: none;">

        <!-- Overlay -->
        <div class="absolute inset-0 bg-black opacity-50" @click="$wire.closeDrawer()"></div>

        <!-- Drawer Panel -->
        <div class="absolute right-0 top-0 h-full w-96 bg-white shadow-lg rounded-l-lg p-6 overflow-y-auto">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-gray-900">
                    @if ($editingId)
                        Edit Provider
                    @else
                        New Provider
                    @endif
                </h2>
                <button type="button" @click="$wire.closeDrawer()"
                    class="text-gray-500 hover:text-gray-900">
                    ✕
                </button>
            </div>

            <form @submit.prevent="$wire.save()" class="space-y-4">
                <!-- Name -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Gateway Name</label>
                    <input type="text" wire:model="fName"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="e.g., SSL Wireless Live">
                    @error('fName') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                </div>

                <!-- Provider Selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Provider</label>
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input type="radio" wire:model="fProvider" value="bulk_sms_dhaka"
                                class="w-4 h-4">
                            <span class="ml-2 text-sm text-gray-700">BulkSMS Dhaka</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" wire:model="fProvider" value="alpha_sms"
                                class="w-4 h-4">
                            <span class="ml-2 text-sm text-gray-700">Alpha SMS</span>
                        </label>
                    </div>
                    @error('fProvider') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                </div>


                <!-- BulkSMS Dhaka Credentials -->
                <div x-show="$wire.fProvider === 'bulk_sms_dhaka'" x-transition class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">API Token</label>
                        <input type="password" wire:model="fApiToken"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                            placeholder="Your BulkSMS Dhaka API token">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Sender ID</label>
                        <input type="text" wire:model="fSenderId"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                            placeholder="Your sender ID">
                    </div>
                </div>

                <!-- Alpha SMS Credentials -->
                <div x-show="$wire.fProvider === 'alpha_sms'" x-transition class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">API Key</label>
                        <input type="password" wire:model="fAlphaApiKey"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                            placeholder="Your Alpha SMS API key">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Message Type</label>
                        <select wire:model="fAlphaType"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            <option value="text">Text (Bangla & English)</option>
                            <option value="unicode">Unicode (Special Characters)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Send SMS Endpoint</label>
                        <input type="text" wire:model="fAlphaApiUrlSend"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                            placeholder="https://api.sms.net.bd/sendsms">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Check Balance Endpoint</label>
                        <input type="text" wire:model="fAlphaApiUrlBalance"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                            placeholder="https://api.sms.net.bd/user/balance/">
                    </div>
                </div>

                <!-- Active Toggle -->
                <div class="flex items-center space-x-3 pt-4 border-t">
                    <input type="checkbox" wire:model="fIsActive" id="fIsActive"
                        class="w-4 h-4 text-blue-600 rounded">
                    <label for="fIsActive" class="text-sm text-gray-700">
                        Set as active provider
                    </label>
                </div>

                <!-- Actions -->
                <div class="flex gap-2 pt-6 border-t">
                    <button type="submit"
                        class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium">
                        Save
                    </button>
                    <button type="button" @click="$wire.closeDrawer()"
                        class="flex-1 px-4 py-2 bg-gray-200 text-gray-900 rounded-lg hover:bg-gray-300 transition font-medium">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
