<div x-data x-init="$store.pageName = { name: '{{ $editMode ? 'Update Unit' : 'Create New Unit' }}' }">
    <div class="flex flex-wrap justify-between gap-6 ">
        <h1 class="text-gray-500 text-lg font-bold" x-cloak x-text="$store.pageName?.name ?? ''"></h1>
    </div>

    <div class="flex-1 w-full bg-white rounded-lg min-h-[60vh]">
        <div class="rounded-2xl border border-gray-200 bg-white">
            <div class="px-5 py-4 sm:px-6 sm:py-5">
                <x-form-section submit="save">
                    <x-slot name="title">{{ $editMode ? 'Update Unit' : 'Create New Unit' }}</x-slot>
                    <x-slot name="description">Provide unit details for {{ $property->name }}.</x-slot>

                    <x-slot name="form">
                        {{-- Floor --}}
                        <div class="col-span-6 sm:col-span-3">
                            <x-label for="property_floor_id" value="Floor" />
                            <select wire:model="property_floor_id" id="property_floor_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                <option value="">Select Floor</option>
                                @foreach($floors as $f)
                                    <option value="{{ $f->id }}">{{ $f->floor_name }}</option>
                                @endforeach
                            </select>
                            <x-input-error for="property_floor_id" class="mt-2" />
                        </div>

                        {{-- Unit Number --}}
                        <div class="col-span-6 sm:col-span-3">
                            <x-label for="unit_number" value="Unit Number *" />
                            <x-input wire:model="unit_number" id="unit_number" type="text" class="mt-1 block w-full" placeholder="e.g. 101" />
                            <x-input-error for="unit_number" class="mt-2" />
                        </div>

                        {{-- Unit Name --}}
                        <div class="col-span-6 sm:col-span-3">
                            <x-label for="unit_name" value="Unit Name" />
                            <x-input wire:model="unit_name" id="unit_name" type="text" class="mt-1 block w-full" placeholder="Optional" />
                            <x-input-error for="unit_name" class="mt-2" />
                        </div>

                        {{-- Unit Type --}}
                        <div class="col-span-6 sm:col-span-3">
                            <x-label for="unit_type" value="Type" />
                            <x-input wire:model="unit_type" id="unit_type" type="text" class="mt-1 block w-full" />
                            <x-input-error for="unit_type" class="mt-2" />
                        </div>

                        {{-- Purpose --}}
                        <div class="col-span-6 sm:col-span-3" x-data="{ purpose: $wire.entangle('purpose') }">
                            <x-label for="purpose" value="Purpose" />
                            <select wire:model.live="purpose" x-model="purpose" id="purpose" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                <option value="">Not specified</option>
                                <option value="sell">Sell</option>
                                <option value="rent">Rent</option>
                            </select>
                            <x-input-error for="purpose" class="mt-2" />

                            {{-- Sell-only: Down Payment % --}}
                            <div x-show="purpose === 'sell'" x-cloak class="mt-4">
                                <x-label for="down_payment_percentage" value="Down Payment (%)" />
                                <x-input wire:model="down_payment_percentage" id="down_payment_percentage" type="number" step="0.01" min="0" max="100" class="mt-1 block w-full" placeholder="e.g. 20" />
                                <x-input-error for="down_payment_percentage" class="mt-2" />
                            </div>

                            {{-- Rent-only: Deposit Amount --}}
                            <div x-show="purpose === 'rent'" x-cloak class="mt-4">
                                <x-label for="deposit_amount" value="Security Deposit" />
                                <x-input wire:model="deposit_amount" id="deposit_amount" type="number" step="0.01" min="0" class="mt-1 block w-full" placeholder="e.g. 50000" />
                                <x-input-error for="deposit_amount" class="mt-2" />
                            </div>
                        </div>

                        {{-- Size --}}
                        <div class="col-span-6 sm:col-span-3">
                            <x-label for="size_sqft" value="Size (sqft)" />
                            <x-input wire:model="size_sqft" id="size_sqft" type="number" step="0.01" class="mt-1 block w-full" />
                            <x-input-error for="size_sqft" class="mt-2" />
                        </div>

                        {{-- Sell Price (always visible) --}}
                        <div class="col-span-6 sm:col-span-3">
                            <x-label for="sell_price" value="Sell Price" />
                            <x-input wire:model="sell_price" id="sell_price" type="number" step="0.01" class="mt-1 block w-full" />
                            <x-input-error for="sell_price" class="mt-2" />
                        </div>

                        {{-- Rent Amount (always visible) --}}
                        <div class="col-span-6 sm:col-span-3">
                            <x-label for="rent_amount" value="Rent Amount" />
                            <x-input wire:model="rent_amount" id="rent_amount" type="number" step="0.01" class="mt-1 block w-full" />
                            <x-input-error for="rent_amount" class="mt-2" />
                        </div>

                        {{-- Notes --}}
                        <div class="col-span-6">
                            <x-label for="notes" value="Notes" />
                            <textarea wire:model="notes" id="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></textarea>
                            <x-input-error for="notes" class="mt-2" />
                        </div>
                    </x-slot>

                    <x-slot name="actions">
                        <x-button type="submit" class="ml-3">{{ $editMode ? 'Update Unit' : 'Create Unit' }}</x-button>
                        <a href="{{ route('admin.projects.properties.units', $property->id) }}" class="ml-3 inline-flex items-center px-4 py-2 border rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">Cancel</a>
                    </x-slot>
                </x-form-section>
            </div>
        </div>
    </div>
</div>
