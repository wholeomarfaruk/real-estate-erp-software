{{--
    Party picker — searchable select bound to $master_profile_id.
    Requires the WithProfilePicker trait on the component.
    @include('livewire.admin.profiles.partials.profile-picker', ['label' => 'Paid to (profile)'])
    Optional: 'required' => true (shows * instead of "(optional)").
--}}
@php $required = $required ?? false; @endphp
<div>
    <label class="mb-1 block text-sm font-medium text-gray-700">
        {{ $label ?? 'Profile' }}
        @if ($required)
            <span class="text-red-500">*</span>
        @else
            <span class="font-normal text-gray-400">(optional)</span>
        @endif
    </label>

    <x-searchable-select field="master_profile_id" :value="$master_profile_id"
        :options="$this->profilePickerOptions()"
        placeholder="— Select profile —"
        search-placeholder="Search by name, code, phone or role…" />

    @error('master_profile_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
</div>
