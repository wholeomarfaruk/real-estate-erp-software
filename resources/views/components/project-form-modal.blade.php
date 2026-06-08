{{--
    Project Form Modal
    Props:
      mode      — 'create' | 'edit'
      engineers — collection of {id, name}
      autoLoad  — true (default, project-list): Alpine calls $wire.loadEditData(id) on open
                  false (project-details):       Livewire opens & populates then dispatches events itself

    Events when autoLoad=true (project-list):
      open-edit-modal { id } → modal opens instantly, loadEditData runs in background behind skeleton
    Events when autoLoad=false (project-details):
      open-edit-modal        → modal opens, edit-data-ready hides skeleton (both dispatched by Livewire together)

    Livewire → browser:
      edit-data-ready     — hide skeleton
      close-edit-modal    — close modal
      close-create-modal  — close modal
--}}
@props(['mode', 'engineers', 'autoLoad' => true])

@php
    $isEdit     = $mode === 'edit';
    $prefix     = $isEdit ? 'edit' : 'create';
    $openEvt    = $isEdit ? 'open-edit-modal'  : 'open-create-modal';
    $closeEvt   = $isEdit ? 'close-edit-modal' : 'close-create-modal';
    $saveMethod = $isEdit ? 'saveEdit' : 'saveCreate';
    $title      = $isEdit ? 'Edit Project' : 'Create New Project';
    $subtitle   = $isEdit ? 'Update project details' : 'Fill in the details to create a new project';
    $btnLabel   = $isEdit ? 'Save Changes' : 'Create Project';
    $btnLoading = $isEdit ? 'Saving…' : 'Creating…';
@endphp

<div
    x-data="{
        show: false,
        loading: {{ $isEdit ? 'true' : 'false' }},
        init() {
            window.addEventListener('{{ $openEvt }}', () => {
                this.show    = true;
                this.loading = {{ $isEdit ? 'true' : 'false' }};
            });
            @if($isEdit)
            window.addEventListener('edit-data-ready', () => { this.loading = false; });
            @endif
            window.addEventListener('{{ $closeEvt }}', () => { this.show = false; });
        }
    }"
    @keydown.escape.window="show = false"
    x-show="show"
    x-cloak
    class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0"
    style="display:none;">

    {{-- Backdrop --}}
    <div x-show="show" @click="show = false"
        x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-gray-500/75"></div>

    {{-- Panel --}}
    <div x-show="show"
        x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0 scale-100" x-transition:leave-end="opacity-0 translate-y-2 scale-95"
        class="relative bg-white rounded-lg shadow-xl sm:max-w-2xl sm:mx-auto overflow-hidden mb-6">

        {{-- Header --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">{{ $title }}</h3>
                <p class="text-sm text-gray-500 mt-0.5">{{ $subtitle }}</p>
            </div>
            <button @click="show = false" type="button"
                class="rounded-md p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        {{-- Skeleton (edit only) --}}
        @if($isEdit)
        <div x-show="loading" class="px-6 py-8">
            <div class="grid grid-cols-2 gap-4">
                @foreach(range(1,8) as $n)
                    <div class="{{ $n === 1 ? 'col-span-2' : '' }} space-y-2">
                        <div class="h-3 w-24 bg-gray-200 rounded animate-pulse"></div>
                        <div class="h-9 w-full bg-gray-100 rounded-md animate-pulse"></div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Form body --}}
        <div @if($isEdit) x-show="!loading" @endif class="px-6 py-5 overflow-y-auto max-h-[72vh]">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                {{-- Name --}}
                <div class="sm:col-span-2">
                    <x-label for="{{ $prefix }}_name" value="Project Name *" />
                    <x-input wire:model="{{ $prefix }}_name" id="{{ $prefix }}_name" type="text"
                        class="mt-1 block w-full" placeholder="Enter project name" />
                    <x-input-error for="{{ $prefix }}_name" class="mt-1" />
                </div>

                {{-- Code --}}
                <div>
                    <x-label for="{{ $prefix }}_code" value="Project Code" />
                    <x-input wire:model="{{ $prefix }}_code" id="{{ $prefix }}_code" type="text"
                        class="mt-1 block w-full" placeholder="e.g. SUDP001" />
                    <x-input-error for="{{ $prefix }}_code" class="mt-1" />
                </div>

                {{-- Progress (edit only) --}}
                @if($isEdit)
                <div>
                    <x-label for="edit_progress_pct" value="Construction Progress (%)" />
                    <x-input wire:model="edit_progress_pct" id="edit_progress_pct" type="number"
                        min="0" max="100" class="mt-1 block w-full" placeholder="0–100" />
                    <x-input-error for="edit_progress_pct" class="mt-1" />
                </div>
                @endif

                {{-- Status --}}
                <div>
                    <x-label for="{{ $prefix }}_status" value="Status *" />
                    <select wire:model="{{ $prefix }}_status" id="{{ $prefix }}_status"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">Select Status</option>
                        @foreach(\App\Enums\Project\Status::cases() as $s)
                            <option value="{{ $s->value }}">{{ $s->label() }}</option>
                        @endforeach
                    </select>
                    <x-input-error for="{{ $prefix }}_status" class="mt-1" />
                </div>

                {{-- Project Type --}}
                <div class="sm:col-span-2">
                    <x-label value="Project Type * (select one or more)" />
                    <div class="mt-2 flex flex-wrap gap-3">
                        @foreach(\App\Enums\Project\Type::cases() as $type)
                            <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                                <input type="checkbox"
                                    wire:model="{{ $prefix }}_project_type"
                                    value="{{ $type->value }}"
                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" />
                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ $type->label() }}</span>
                            </label>
                        @endforeach
                    </div>
                    <x-input-error for="{{ $prefix }}_project_type" class="mt-1" />
                </div>

                {{-- Location --}}
                <div class="sm:col-span-2">
                    <x-label for="{{ $prefix }}_location" value="Location *" />
                    <x-input wire:model="{{ $prefix }}_location" id="{{ $prefix }}_location" type="text"
                        class="mt-1 block w-full" placeholder="Full address" />
                    <x-input-error for="{{ $prefix }}_location" class="mt-1" />
                </div>

                {{-- Land Area --}}
                <div>
                    <x-label for="{{ $prefix }}_land_area" value="Land Area (sft)" />
                    <x-input wire:model="{{ $prefix }}_land_area" id="{{ $prefix }}_land_area" type="number"
                        step="0.01" class="mt-1 block w-full" placeholder="e.g. 12500" />
                    <x-input-error for="{{ $prefix }}_land_area" class="mt-1" />
                </div>

                {{-- Building Area --}}
                <div>
                    <x-label for="{{ $prefix }}_building_area" value="Building Area (sft)" />
                    <x-input wire:model="{{ $prefix }}_building_area" id="{{ $prefix }}_building_area" type="number"
                        step="0.01" class="mt-1 block w-full" placeholder="e.g. 96400" />
                    <x-input-error for="{{ $prefix }}_building_area" class="mt-1" />
                </div>

                {{-- Start Date --}}
                <div>
                    <x-label for="{{ $prefix }}_start_date" value="Start Date *" />
                    <x-input wire:model="{{ $prefix }}_start_date" id="{{ $prefix }}_start_date" type="date"
                        class="mt-1 block w-full flatpickr-only-date" />
                    <x-input-error for="{{ $prefix }}_start_date" class="mt-1" />
                </div>

                {{-- End Date --}}
                <div>
                    <x-label for="{{ $prefix }}_end_date" value="End Date *" />
                    <x-input wire:model="{{ $prefix }}_end_date" id="{{ $prefix }}_end_date" type="date"
                        class="mt-1 block w-full flatpickr-only-date" />
                    <x-input-error for="{{ $prefix }}_end_date" class="mt-1" />
                </div>

                {{-- Handover Date --}}
                <div>
                    <x-label for="{{ $prefix }}_handover_date" value="Handover Date" />
                    <x-input wire:model="{{ $prefix }}_handover_date" id="{{ $prefix }}_handover_date" type="date"
                        class="mt-1 block w-full flatpickr-only-date" />
                    <x-input-error for="{{ $prefix }}_handover_date" class="mt-1" />
                </div>

                {{-- Budget --}}
                <div>
                    <x-label for="{{ $prefix }}_budget" value="Budget (BDT)" />
                    <x-input wire:model="{{ $prefix }}_budget" id="{{ $prefix }}_budget" type="number"
                        step="0.01" class="mt-1 block w-full" placeholder="e.g. 42000000" />
                    <x-input-error for="{{ $prefix }}_budget" class="mt-1" />
                </div>

                {{-- Chief Engineer --}}
                <div>
                    <x-label for="{{ $prefix }}_chief_engineer_id" value="Chief Engineer" />
                    <select wire:model="{{ $prefix }}_chief_engineer_id" id="{{ $prefix }}_chief_engineer_id"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">— None —</option>
                        @foreach($engineers as $eng)
                            <option value="{{ $eng->id }}">{{ $eng->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error for="{{ $prefix }}_chief_engineer_id" class="mt-1" />
                </div>

                {{-- Site Engineer --}}
                <div>
                    <x-label for="{{ $prefix }}_site_engineer_id" value="Site Engineer" />
                    <select wire:model="{{ $prefix }}_site_engineer_id" id="{{ $prefix }}_site_engineer_id"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">— None —</option>
                        @foreach($engineers as $eng)
                            <option value="{{ $eng->id }}">{{ $eng->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error for="{{ $prefix }}_site_engineer_id" class="mt-1" />
                </div>

                {{-- Description --}}
                <div class="sm:col-span-2">
                    <x-label for="{{ $prefix }}_description" value="Description" />
                    <textarea wire:model="{{ $prefix }}_description" id="{{ $prefix }}_description" rows="3"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                        placeholder="Project description (optional)"></textarea>
                    <x-input-error for="{{ $prefix }}_description" class="mt-1" />
                </div>

                {{-- Cover Image --}}
                <div class="sm:col-span-2">
                    @if($isEdit)
                        <x-media-picker-field field="edit_image" :value="$edit_image ?? null"
                            placeholder="Click to upload cover image" :multiple="false"
                            type="image" label="Cover Image" required="false" />
                    @else
                        <x-media-picker-field field="create_image" :value="$create_image ?? null"
                            placeholder="Click to upload cover image" :multiple="false"
                            type="image" label="Cover Image" required="false" />
                    @endif
                </div>

            </div>
        </div>

        {{-- Footer --}}
        <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 bg-gray-50">
            <button @click="show = false" type="button"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 transition">
                Cancel
            </button>
            <button wire:click="{{ $saveMethod }}" type="button"
                @if($isEdit) :disabled="loading" @endif
                wire:loading.attr="disabled" wire:loading.class="opacity-60 cursor-not-allowed"
                class="inline-flex items-center gap-2 rounded-lg bg-[#0d2a4a] px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-[#0a2240] transition disabled:opacity-50">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                </svg>
                <span wire:loading.remove wire:target="{{ $saveMethod }}">{{ $btnLabel }}</span>
                <span wire:loading wire:target="{{ $saveMethod }}">{{ $btnLoading }}</span>
            </button>
        </div>

    </div>
</div>
