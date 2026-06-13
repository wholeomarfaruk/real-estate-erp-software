@php
    // Resolve the Livewire property bound via wire:model (any modifier).
    $wireModelKey = collect($attributes->getAttributes())
        ->keys()
        ->first(fn ($key) => \Illuminate\Support\Str::startsWith($key, 'wire:model'));
    $wireModel = $wireModelKey ? $attributes->get($wireModelKey) : null;

    // Expose to the PHP component instance so errorKey() works.
    $wireModel = $wireModel ?: $name;
    $errorKey = $error ?? $wireModel;

    // $errors is shared on every real request, but guard for Blade::render() / tests.
    $errorBag = $errors ?? new \Illuminate\Support\MessageBag();
    $hasError = $errorKey && $errorBag->has($errorKey);

    $alpineConfig = [
        'multiple' => $multiple,
        'liveSearch' => $liveSearch,
        'searchMethod' => $searchMethod,
        'placeholder' => $placeholder,
        'searchPlaceholder' => $searchPlaceholder,
        'noResultsText' => $noResultsText,
        'loadingText' => $loadingText,
        'disabled' => $disabled,
        'minSearchChars' => $minSearchChars,
        'debounceMs' => $debounceMs,
        'options' => $normalizedOptions,
    ];
@endphp

{{--
    State lives in Alpine (selectComponent). The selected value is exposed via
    x-modelable="selected" and bound to the Livewire property with wire:model.live,
    so Livewire entangles the two automatically. Alpine x-data roots survive
    Livewire morphs, so no wire:ignore is required.
--}}
<div
    x-data="selectComponent(@js($alpineConfig))"
    @if($wireModel)
        x-modelable="selected"
        wire:model.live="{{ $wireModel }}"
    @endif
    x-id="['select-listbox']"
    class="relative w-full"
    @keydown="onKeydown($event)"
    @click.outside="closeDropdown()"
>
    {{-- ============================ CONTROL ============================ --}}
    {{-- Uses role="button" (not <button>) so the inner remove/clear buttons are
         not nested inside a button, which is invalid HTML. --}}
    <div
        x-ref="trigger"
        role="combobox"
        :tabindex="disabled ? -1 : 0"
        @click="toggleDropdown()"
        @keydown.enter.prevent="toggleDropdown()"
        @keydown.space.prevent="toggleDropdown()"
        :aria-expanded="open"
        :aria-controls="$id('select-listbox')"
        :aria-disabled="disabled"
        aria-haspopup="listbox"
        @class([
            'flex w-full cursor-pointer items-center gap-2 rounded-lg border bg-white px-3 py-2 text-left text-sm transition',
            'min-h-[2.5rem]',
            'focus:outline-none focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500',
            'border-red-400 ring-1 ring-red-300' => $hasError,
            'border-slate-300' => ! $hasError,
        ])
        :class="disabled && 'cursor-not-allowed bg-slate-50 opacity-60'"
    >
        {{-- Single-select display --}}
        <template x-if="!isMultiple">
            <span class="flex min-w-0 flex-1 items-center gap-2">
                <span
                    x-show="hasSelection"
                    class="truncate text-slate-800"
                    x-text="displayLabel"
                ></span>
                <span
                    x-show="!hasSelection"
                    class="truncate text-slate-400"
                    x-text="placeholder"
                ></span>
            </span>
        </template>

        {{-- Multi-select badges --}}
        <template x-if="isMultiple">
            <span class="flex min-w-0 flex-1 flex-wrap items-center gap-1">
                <template x-for="opt in selectedOptions" :key="opt.value">
                    <span class="inline-flex max-w-full items-center gap-1 rounded bg-indigo-50 px-2 py-0.5 text-xs text-indigo-700">
                        <span class="truncate" x-text="opt.label"></span>
                        <button
                            type="button"
                            x-show="!disabled"
                            @click.stop="remove(opt.value)"
                            class="shrink-0 rounded-full text-indigo-400 hover:text-indigo-700"
                            aria-label="Remove"
                        >
                            <svg viewBox="0 0 20 20" fill="currentColor" class="h-3 w-3">
                                <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/>
                            </svg>
                        </button>
                    </span>
                </template>
                <span
                    x-show="!hasSelection"
                    class="truncate text-slate-400"
                    x-text="placeholder"
                ></span>
            </span>
        </template>

        {{-- Clear all --}}
        @if($clearable)
            <button
                type="button"
                x-show="hasSelection && !disabled"
                @click.stop="clearAll()"
                class="shrink-0 rounded text-slate-400 hover:text-slate-600"
                aria-label="Clear all"
            >
                <svg viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4">
                    <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/>
                </svg>
            </button>
        @endif

        {{-- Chevron --}}
        <svg
            class="h-4 w-4 shrink-0 text-slate-400 transition-transform"
            :class="open && 'rotate-180'"
            viewBox="0 0 20 20" fill="currentColor"
        >
            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.17l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd"/>
        </svg>
    </div>

    {{-- ============================ DROPDOWN ============================ --}}
    <div
        x-show="open"
        x-cloak
        x-transition.opacity.duration.120ms
        :id="$id('select-listbox')"
        role="listbox"
        class="absolute z-50 mt-1 w-full overflow-hidden rounded-lg border border-slate-200 bg-white shadow-lg"
    >
        {{-- Search box --}}
        <div class="border-b border-slate-100 p-2">
            <input
                type="text"
                x-ref="searchInput"
                x-model="search"
                @input="onSearchInput()"
                @keydown.stop="onKeydown($event)"
                :placeholder="searchPlaceholder"
                class="w-full rounded-md border border-slate-200 px-2.5 py-1.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500/40"
                autocomplete="off"
            >
        </div>

        {{-- Loading --}}
        <div x-show="loading" class="flex items-center gap-2 px-3 py-4 text-sm text-slate-500">
            <svg class="h-4 w-4 animate-spin text-indigo-500" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z"/>
            </svg>
            <span x-text="loadingText"></span>
        </div>

        {{-- Options --}}
        <ul
            x-ref="optionsList"
            x-show="!loading"
            class="max-h-64 overflow-y-auto py-1"
        >
            <template x-for="(opt, index) in visibleOptions" :key="opt.value">
                <li
                    :data-index="index"
                    role="option"
                    :aria-selected="isSelected(opt.value)"
                    @click="toggle(opt)"
                    @mouseenter="highlighted = index"
                    :class="{
                        'bg-indigo-50': highlighted === index && !opt.disabled,
                        'cursor-not-allowed opacity-40': opt.disabled,
                        'cursor-pointer': !opt.disabled,
                    }"
                    class="flex items-center gap-2.5 px-3 py-2 text-sm"
                >
                    {{-- Avatar --}}
                    <template x-if="opt.avatar">
                        <img :src="opt.avatar" :alt="opt.label" class="h-7 w-7 shrink-0 rounded-full object-cover">
                    </template>

                    {{-- Label + subtitle --}}
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-slate-800" x-text="opt.label"></span>
                        <template x-if="opt.subtitle">
                            <span class="block truncate text-xs text-slate-400" x-text="opt.subtitle"></span>
                        </template>
                    </span>

                    {{-- Check --}}
                    <svg
                        x-show="isSelected(opt.value)"
                        class="h-4 w-4 shrink-0 text-indigo-600"
                        viewBox="0 0 20 20" fill="currentColor"
                    >
                        <path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-7.5 7.5a1 1 0 0 1-1.4 0l-3.5-3.5a1 1 0 1 1 1.4-1.4l2.8 2.79 6.8-6.79a1 1 0 0 1 1.4 0Z" clip-rule="evenodd"/>
                    </svg>
                </li>
            </template>

            {{-- Empty state --}}
            <li x-show="showEmptyState" class="px-3 py-4 text-center text-sm text-slate-400">
                <span x-text="noResultsText"></span>
            </li>
        </ul>
    </div>

    {{-- Validation message --}}
    @if($hasError)
        <p class="mt-1 text-xs text-red-600">{{ $errorBag->first($errorKey) }}</p>
    @endif
</div>
