<?php

namespace App\View\Components\Forms;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Reusable custom select for Laravel 12 + Livewire 3 + Alpine.
 *
 * Single / multi select, local filtering or debounced remote (Livewire) search,
 * keyboard navigation, removable badges, loading/empty/disabled states, and
 * `@error` validation styling.
 *
 * @see resources/views/components/forms/select.blade.php
 * @see resources/js/select-component.js
 */
class Select extends Component
{
    /**
     * Normalised options: list of
     * ['value' => mixed, 'label' => string, 'subtitle' => ?string,
     *  'avatar' => ?string, 'disabled' => bool].
     */
    public array $normalizedOptions;

    /**
     * The Livewire property bound via wire:model, resolved from attributes
     * (e.g. "customer_id"). Used to entangle Alpine state ↔ Livewire.
     */
    public ?string $wireModel = null;

    public function __construct(
        /** Preloaded options (array|Collection). Ignored for remote search beyond seeding. */
        public array|Collection $options = [],
        /** Allow selecting multiple values; renders removable badges. */
        public bool $multiple = false,
        /** Enable debounced remote search via a Livewire method instead of local filtering. */
        public bool $liveSearch = false,
        /** Livewire method name to call for remote search, e.g. "searchCustomers". */
        public ?string $searchMethod = null,
        /** Field name (used for the error bag + a hidden input fallback). */
        public ?string $name = null,
        /** Placeholder shown when nothing is selected. */
        public string $placeholder = 'Select...',
        /** Placeholder inside the search box. */
        public string $searchPlaceholder = 'Search...',
        public string $noResultsText = 'No results found',
        public string $loadingText = 'Loading...',
        /** Fully disable the control. */
        public bool $disabled = false,
        /** Show a clear-all (×) affordance. */
        public bool $clearable = true,
        /** Minimum characters before a remote search fires. */
        public int $minSearchChars = 0,
        /** Debounce window for remote search, in milliseconds. */
        public int $debounceMs = 300,
        /** Optional error key override; defaults to the resolved wire:model name. */
        public ?string $error = null,
    ) {
        $this->normalizedOptions = $this->normalizeOptions($options);
    }

    /**
     * Coerce mixed option shapes into a consistent structure the Alpine
     * component understands. Accepts:
     *   - ['value' => 1, 'label' => 'A', 'subtitle' => '...', 'avatar' => '...', 'disabled' => false]
     *   - [1 => 'A'] associative value => label maps
     */
    protected function normalizeOptions(array|Collection $options): array
    {
        return collect($options)
            ->map(function ($item, $key) {
                if ($item instanceof Arrayable) {
                    $item = $item->toArray();
                }

                if (is_array($item) && array_key_exists('value', $item)) {
                    return [
                        'value' => $item['value'],
                        'label' => (string) ($item['label'] ?? $item['value']),
                        'subtitle' => isset($item['subtitle']) ? (string) $item['subtitle'] : null,
                        'avatar' => $item['avatar'] ?? null,
                        'disabled' => (bool) ($item['disabled'] ?? false),
                    ];
                }

                // Associative [value => label] map.
                return [
                    'value' => $key,
                    'label' => (string) $item,
                    'subtitle' => null,
                    'avatar' => null,
                    'disabled' => false,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * The error bag key to inspect for invalid styling.
     */
    public function errorKey(): ?string
    {
        return $this->error ?? $this->wireModel ?? $this->name;
    }

    public function render(): View
    {
        return view('components.forms.select');
    }
}
