@props([
    /** Array/Collection of options. Each item: ['value' => ..., 'label' => ...] OR an associative [value => label] map. */
    'options' => [],
    /** Currently selected value (used to mark <option selected> on first render). */
    'selected' => null,
    /** Placeholder text shown in the search box. */
    'placeholder' => 'Search and select...',
    /** Include a blank option at the top so the field can be cleared. */
    'allowEmpty' => true,
    /** Label for the blank option. */
    'emptyLabel' => 'Select...',
])

{{--
    Reusable searchable select powered by Tom Select.

    Tom Select rewrites the DOM around the native <select>, so the field is wrapped
    in wire:ignore and the chosen value is pushed to Livewire from the JS init.
    Pass the Livewire property through wire:model (e.g. wire:model.live="customerId").

    Usage:
        <x-tom-select
            wire:model.live="customerId"
            :options="$customers"   (each item ['value' => 1, 'label' => 'Acme'])
            :selected="$customerId"
            placeholder="Search customer..." />
--}}

@php
    // Resolve the Livewire property name from whichever wire:model variant was passed.
    $wireModel = collect($attributes->getAttributes())
        ->keys()
        ->first(fn ($key) => str_starts_with($key, 'wire:model'));
    $wireProperty = $wireModel ? $attributes->get($wireModel) : null;

    // Normalise options into [['value' => ..., 'label' => ...], ...].
    $normalized = collect($options)->map(function ($item, $key) {
        if (is_array($item) && array_key_exists('value', $item)) {
            return ['value' => $item['value'], 'label' => $item['label'] ?? $item['value']];
        }

        // Associative [value => label] map.
        return ['value' => $key, 'label' => $item];
    })->values();
@endphp

<div wire:ignore x-data x-init="$nextTick(() => window.initTomSelect && window.initTomSelect())">
    <select {{ $attributes->whereDoesntStartWith('wire:model')->merge(['class' => 'tom-select w-full']) }}
            data-placeholder="{{ $placeholder }}"
            @if($wireProperty) data-wire-model="{{ $wireProperty }}" @endif>
        @if($allowEmpty)
            <option value="">{{ $emptyLabel }}</option>
        @endif
        @foreach($normalized as $option)
            <option value="{{ $option['value'] }}" @selected((string) $selected === (string) $option['value'])>
                {{ $option['label'] }}
            </option>
        @endforeach
    </select>
</div>
