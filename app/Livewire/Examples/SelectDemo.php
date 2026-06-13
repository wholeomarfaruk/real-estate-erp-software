<?php

namespace App\Livewire\Examples;

use App\Models\Customer;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * EXAMPLE — demonstrates every mode of <x-forms.select>.
 *
 * This is reference documentation for wiring the reusable select into a real
 * Livewire screen. Copy the relevant pieces; it is not routed by default.
 *
 * @see resources/views/components/forms/select.blade.php
 */
class SelectDemo extends Component
{
    // 1) Single select (local options)
    public ?int $customer_id = null;

    // 2) Multi select (local options) — array property
    public array $project_ids = [];

    // 3) Remote / live-search single select
    public ?int $remote_customer_id = null;

    /**
     * Preloaded options for the local single/multi selects.
     * Small datasets (≤ ~1,000 rows) are fine to ship to the browser.
     */
    public function getCustomerOptionsProperty(): array
    {
        return Customer::query()
            ->orderBy('name')
            ->limit(1000)
            ->get(['id', 'name', 'customer_id', 'profile_image_id'])
            ->map(fn (Customer $c): array => [
                'value' => $c->id,
                'label' => $c->name,
                'subtitle' => $c->customer_id, // e.g. "CUS-001"
            ])
            ->all();
    }

    public function getProjectOptionsProperty(): array
    {
        // Replace with Project::query()... in a real screen.
        return [
            ['value' => 1, 'label' => 'Riverside Tower', 'subtitle' => 'PRJ-001'],
            ['value' => 2, 'label' => 'Green Valley', 'subtitle' => 'PRJ-002'],
            ['value' => 3, 'label' => 'Skyline Residency', 'subtitle' => 'PRJ-003', 'disabled' => true],
        ];
    }

    /**
     * Remote search method called by the select's debounced live-search.
     *
     * MUST return a plain array of option arrays. Keep the result set small
     * (server-side LIMIT) — this is what makes 10,000+ row datasets viable.
     */
    public function searchCustomers(string $term): array
    {
        $term = trim($term);

        return Customer::query()
            ->when($term !== '', function ($query) use ($term) {
                $query->where(function ($q) use ($term) {
                    $q->where('name', 'like', "%{$term}%")
                        ->orWhere('customer_id', 'like', "%{$term}%")
                        ->orWhere('phone', 'like', "%{$term}%");
                });
            })
            ->orderBy('name')
            ->limit(25) // cap payload — never stream 10k rows to the client
            ->get(['id', 'name', 'customer_id'])
            ->map(fn (Customer $c): array => [
                'value' => $c->id,
                'label' => $c->name,
                'subtitle' => $c->customer_id,
            ])
            ->all();
    }

    public function save(): void
    {
        $this->validate([
            'customer_id' => ['required', 'integer'],
            'project_ids' => ['array', 'min:1'],
            'remote_customer_id' => ['nullable', 'integer'],
        ], [
            'customer_id.required' => 'Please choose a customer.',
            'project_ids.min' => 'Select at least one project.',
        ]);

        // ... persist ...
        session()->flash('saved', true);
    }

    public function render(): View
    {
        return view('livewire.examples.select-demo');
    }
}
