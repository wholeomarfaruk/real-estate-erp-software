<div class="min-h-screen bg-gray-100 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Header --}}
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Feature Account Configuration</h1>
            <p class="text-gray-600 mt-2">Enable or disable accounts for different business features</p>
        </div>

        {{-- Main Container --}}
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            {{-- Feature List Sidebar --}}
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden sticky top-4">
                    <div class="bg-gray-50 border-b border-gray-200 px-4 py-3 flex items-center justify-between">
                        <h2 class="font-semibold text-sm text-gray-900">Features</h2>
                        <button type="button" wire:click="$toggle('showAddFeature')"
                            class="text-xs font-medium text-indigo-600 hover:text-indigo-800">
                            {{ $showAddFeature ? 'Cancel' : '+ Add' }}
                        </button>
                    </div>

                    {{-- Add Feature form --}}
                    @if ($showAddFeature)
                        <div class="border-b border-gray-200 px-4 py-3 bg-indigo-50/40">
                            <label for="newFeatureLabel" class="block text-xs font-medium text-gray-700 mb-1">New feature name</label>
                            <input type="text" id="newFeatureLabel" wire:model="newFeatureLabel"
                                wire:keydown.enter="createFeature"
                                placeholder="e.g. Travel Expense"
                                class="block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            @error('newFeatureLabel')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                            <button type="button" wire:click="createFeature"
                                class="mt-2 w-full px-3 py-1.5 bg-indigo-600 text-white rounded-md text-xs font-medium hover:bg-indigo-700">
                                Add Feature
                            </button>
                        </div>
                    @endif

                    <div class="divide-y divide-gray-100">
                        @forelse ($features as $feature)
                            <div class="group flex items-center {{ $this->selectedFeature === $feature->key ? 'bg-indigo-50 border-l-4 border-l-indigo-600' : '' }}">
                                <button type="button" wire:click="selectFeature('{{ $feature->key }}')"
                                    class="flex-1 text-left px-4 py-3 transition hover:bg-gray-50 {{ $this->selectedFeature === $feature->key ? 'text-indigo-700 font-semibold' : 'text-gray-700' }} {{ $feature->is_active ? '' : 'opacity-50' }}">
                                    {{ $feature->label }}
                                    @unless ($feature->is_active)
                                        <span class="ml-1 text-[10px] uppercase text-gray-400">(inactive)</span>
                                    @endunless
                                </button>
                                @unless ($feature->is_locked)
                                    <button type="button" wire:click="toggleFeatureActive({{ $feature->id }})"
                                        title="{{ $feature->is_active ? 'Deactivate' : 'Activate' }}"
                                        class="px-2 text-gray-400 hover:text-gray-600 opacity-0 group-hover:opacity-100 transition">
                                        @if ($feature->is_active)
                                            &#9210;
                                        @else
                                            &#9211;
                                        @endif
                                    </button>
                                    <button type="button" wire:click="deleteFeature({{ $feature->id }})"
                                        wire:confirm="Delete this feature and its account mappings?"
                                        title="Delete"
                                        class="pr-3 pl-1 text-gray-400 hover:text-red-600 opacity-0 group-hover:opacity-100 transition">
                                        &times;
                                    </button>
                                @endunless
                            </div>
                        @empty
                            <div class="px-4 py-6 text-center text-xs text-gray-500">No features yet. Click “+ Add”.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Content Area --}}
            <div class="lg:col-span-3">
                @if ($this->selectedFeature)
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                        {{-- Header --}}
                        <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 px-6 py-4">
                            <h2 class="text-white font-bold text-lg">Account Configuration</h2>
                            <p class="text-indigo-100 text-sm mt-1">Check the accounts to enable them for this feature</p>
                        </div>

                        {{-- Content --}}
                        <div class="p-6">
                            @if (count($accountTree) > 0)
                                <div class="space-y-4">
                            @include('livewire.admin.accounts.features.account-tree', [
                                'accounts' => $accountTree,
                                'depth' => 0,
                                'enabledMappings' => $this->enabledMappings
                            ])
                        </div>

                                {{-- Save Button --}}
                                <div class="mt-8 pt-6 border-t border-gray-200 flex justify-end gap-3">
                                    <button type="button"
                                        class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium transition">
                                        Cancel
                                    </button>
                                    <button type="button" wire:click="saveAll"
                                        class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium transition shadow-md">
                                        Save Changes
                                    </button>
                                </div>
                            @else
                                <div class="text-center py-12">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <p class="text-gray-600 mt-4">No accounts available for this feature</p>
                                </div>
                            @endif
                        </div>
                    </div>
                @else
                    {{-- Empty State --}}
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 border-dashed p-12">
                        <div class="text-center">
                            <svg class="mx-auto h-16 w-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                            </svg>
                            <h3 class="text-lg font-semibold text-gray-900 mt-4">Select a Feature</h3>
                            <p class="text-gray-600 mt-2">Choose a feature from the list on the left to configure its accounts</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
