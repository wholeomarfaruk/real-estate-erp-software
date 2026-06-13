{{--
    EXAMPLE usage of <x-forms.select>. Reference only.

    Pairs with App\Livewire\Examples\SelectDemo.
--}}
<div class="mx-auto max-w-2xl space-y-8 p-6">
    <h1 class="text-2xl font-bold text-slate-800">Reusable Select — Demo</h1>

    @if (session('saved'))
        <div class="rounded bg-green-50 px-4 py-2 text-sm text-green-700">Saved.</div>
    @endif

    {{-- 1) Single select, local options, validation --}}
    <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Customer (single, local)</label>
        <x-forms.select
            wire:model="customer_id"
            :options="$this->customerOptions"
            placeholder="Select a customer..."
            search-placeholder="Search customers..."
        />
    </div>

    {{-- 2) Multi select with removable badges --}}
    <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Projects (multiple)</label>
        <x-forms.select
            wire:model="project_ids"
            :options="$this->projectOptions"
            multiple
            placeholder="Select projects..."
        />
        <p class="mt-1 text-xs text-slate-500">Selected: {{ implode(', ', $project_ids) ?: '—' }}</p>
    </div>

    {{-- 3) Remote / live search for huge datasets (10,000+ rows) --}}
    <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Customer (remote search)</label>
        <x-forms.select
            wire:model="remote_customer_id"
            live-search
            search-method="searchCustomers"
            :min-search-chars="2"
            placeholder="Type to search customers..."
            search-placeholder="Type at least 2 characters..."
        />
    </div>

    {{-- 4) Disabled --}}
    <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Disabled example</label>
        <x-forms.select
            :options="[['value' => 1, 'label' => 'Locked option']]"
            placeholder="Disabled"
            disabled
        />
    </div>

    <button wire:click="save" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
        Save
    </button>
</div>
