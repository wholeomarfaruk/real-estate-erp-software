<div>
    @if ($entryDef && $entryDef->componentClass)
        @livewire($entryDef->componentClass)
    @else
        <div style="padding: 2rem; text-align: center; color: #999;">
            Entry form not found.
        </div>
    @endif
</div>
