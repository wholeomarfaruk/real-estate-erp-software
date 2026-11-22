@php
    $in  = 'h-10 w-full rounded-lg border border-gray-300 bg-white px-3! text-sm text-gray-800 transition placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/15';
    $lbl = 'mb-1 block text-xs font-medium text-gray-700';
    $err = fn ($k) => $errors->first($k);
@endphp

{{-- Kind toggle --}}
<div class="inline-flex rounded-lg bg-gray-100 p-1">
    @foreach (['individual' => 'Individual', 'company' => 'Company / Organization'] as $value => $label)
        <button type="button" wire:click="$set('form.kind', '{{ $value }}')"
            class="rounded-md px-3 py-1.5 text-xs font-semibold transition {{ ($form['kind'] ?? 'individual') === $value ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">
            {{ $label }}
        </button>
    @endforeach
</div>

<fieldset class="space-y-3">
    <legend class="mb-2 text-[11px] font-semibold uppercase tracking-wider text-gray-400">Identity</legend>
    <div class="grid gap-3 sm:grid-cols-2">
        <div class="sm:col-span-2">
            <label class="{{ $lbl }}">{{ ($form['kind'] ?? '') === 'company' ? 'Company name' : 'Full name' }} <span class="text-red-500">*</span></label>
            <input type="text" wire:model="form.name" class="{{ $in }}" placeholder="{{ ($form['kind'] ?? '') === 'company' ? 'e.g. Rahman Steel Ltd.' : 'e.g. Abdur Rahman' }}">
            @if ($err('form.name')) <p class="mt-1 text-xs text-red-600">{{ $err('form.name') }}</p> @endif
        </div>
        <div>
            <label class="{{ $lbl }}">{{ ($form['kind'] ?? '') === 'company' ? 'Contact person' : 'Organization' }}</label>
            <input type="text" wire:model="form.organization" class="{{ $in }}">
        </div>
        <div>
            <label class="{{ $lbl }}">Designation</label>
            <input type="text" wire:model="form.designation" class="{{ $in }}" placeholder="e.g. Managing Director">
        </div>
        @if (($form['kind'] ?? '') !== 'company')
            <div>
                <label class="{{ $lbl }}">Gender</label>
                <select wire:model="form.gender" class="{{ $in }}">
                    <option value="">—</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                    <option value="other">Other</option>
                    <option value="prefer_not_to_say">Prefer not to say</option>
                </select>
            </div>
            <div>
                <label class="{{ $lbl }}">Date of birth</label>
                <input type="date" wire:model="form.date_of_birth" class="{{ $in }}">
                @if ($err('form.date_of_birth')) <p class="mt-1 text-xs text-red-600">{{ $err('form.date_of_birth') }}</p> @endif
            </div>
        @endif
    </div>
</fieldset>

<fieldset class="space-y-3">
    <legend class="mb-2 text-[11px] font-semibold uppercase tracking-wider text-gray-400">Contact</legend>
    <div class="grid gap-3 sm:grid-cols-2">
        <div>
            <label class="{{ $lbl }}">Phone <span class="text-red-500">*</span> <span class="font-normal text-gray-400">(or email)</span></label>
            <input type="tel" wire:model="form.phone" class="{{ $in }}" placeholder="01XXXXXXXXX">
            @if ($err('form.phone')) <p class="mt-1 text-xs text-red-600">{{ $err('form.phone') }}</p> @endif
        </div>
        <div>
            <label class="{{ $lbl }}">Alternate phone</label>
            <input type="tel" wire:model="form.phone_alt" class="{{ $in }}">
        </div>
        <div class="sm:col-span-2">
            <label class="{{ $lbl }}">Email</label>
            <input type="email" wire:model="form.email" class="{{ $in }}" placeholder="name@example.com">
            @if ($err('form.email')) <p class="mt-1 text-xs text-red-600">{{ $err('form.email') }}</p> @endif
        </div>
        <div class="sm:col-span-2">
            <label class="{{ $lbl }}">Address</label>
            <input type="text" wire:model="form.address" class="{{ $in }}" placeholder="House, road, area">
        </div>
        <div>
            <label class="{{ $lbl }}">District</label>
            <input type="text" wire:model="form.district" class="{{ $in }}">
        </div>
    </div>
</fieldset>

<fieldset class="space-y-3">
    <legend class="mb-2 text-[11px] font-semibold uppercase tracking-wider text-gray-400">Documents</legend>
    <div class="grid gap-3 sm:grid-cols-3">
        <div>
            <label class="{{ $lbl }}">Document type</label>
            <select wire:model="form.doc_type" class="{{ $in }}">
                <option value="">—</option>
                @foreach ($docTypes as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="{{ $lbl }}">Document no</label>
            <input type="text" wire:model="form.doc_no" class="{{ $in }}">
        </div>
        <div>
            <label class="{{ $lbl }}">TIN</label>
            <input type="text" wire:model="form.tin_no" class="{{ $in }}">
        </div>
    </div>
</fieldset>

<fieldset class="space-y-3">
    <legend class="mb-2 text-[11px] font-semibold uppercase tracking-wider text-gray-400">Other</legend>
    <div class="grid gap-3 sm:grid-cols-3">
        <div>
            <label class="{{ $lbl }}">Status</label>
            <select wire:model="form.status" class="{{ $in }}">
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>
        <div class="sm:col-span-2">
            <label class="{{ $lbl }}">Notes</label>
            <input type="text" wire:model="form.notes" class="{{ $in }}">
        </div>
    </div>
</fieldset>
