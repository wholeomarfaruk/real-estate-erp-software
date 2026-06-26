<div>
    @if ($entryType?->hasHardcodedForm())
        @livewire($entryType->form_component, ['entryType' => $entryType, 'key' => $entryType->slug])
    @else
        @livewire('admin.accounts.entry.generic-entry-form', ['entryType' => $entryType])
    @endif
</div>
