<?php

namespace App\Livewire\Admin\Profiles\Concerns;

use App\Models\MasterProfile;
use Illuminate\Validation\Rule;

/** Shared master-profile form state, validation and fill helpers. */
trait HasProfileForm
{
    public array $form = [];

    protected function blankProfileForm(): array
    {
        return [
            'kind' => 'individual', 'name' => '', 'organization' => '', 'designation' => '', 'phone' => '', 'phone_alt' => '',
            'email' => '', 'gender' => '', 'date_of_birth' => '', 'doc_type' => 'nid', 'doc_no' => '', 'tin_no' => '',
            'address' => '', 'district' => '', 'status' => 'active', 'notes' => '',
        ];
    }

    protected function fillProfileForm(MasterProfile $profile): void
    {
        $this->form = array_merge($this->blankProfileForm(), array_map(
            fn ($v) => $v ?? '',
            $profile->only(array_keys($this->blankProfileForm()))
        ));
        $this->form['date_of_birth'] = $profile->date_of_birth?->toDateString() ?? '';
    }

    protected function profileRules(?MasterProfile $editing = null): array
    {
        // Duplicates can come from imports. Only enforce uniqueness when the value is new
        // or changed, so an imported duplicate never blocks editing other fields.
        $phone = $this->form['phone'] ? preg_replace('/[^\d+]/', '', (string) $this->form['phone']) : '';
        $email = mb_strtolower(trim((string) $this->form['email']));
        $phoneUnique = ! $editing || $phone !== (string) $editing->phone
            ? [Rule::unique('master_profiles', 'phone')->whereNull('deleted_at')] : [];
        $emailUnique = ! $editing || $email !== (string) $editing->email
            ? [Rule::unique('master_profiles', 'email')->whereNull('deleted_at')] : [];

        return [
            'form.kind'          => ['required', Rule::in(['individual', 'company'])],
            'form.name'          => ['required', 'string', 'max:150'],
            'form.organization'  => ['nullable', 'string', 'max:150'],
            'form.designation'   => ['nullable', 'string', 'max:100'],
            'form.phone'         => ['required_without:form.email', 'nullable', 'string', 'max:30', ...$phoneUnique],
            'form.phone_alt'     => ['nullable', 'string', 'max:30'],
            'form.email'         => ['nullable', 'email', 'max:150', ...$emailUnique],
            'form.gender'        => ['nullable', Rule::in(['male', 'female', 'other', 'prefer_not_to_say'])],
            'form.date_of_birth' => ['nullable', 'date', 'before:today'],
            'form.doc_type'      => ['nullable', Rule::in(array_keys(self::docTypes()))],
            'form.doc_no'        => ['nullable', 'string', 'max:60'],
            'form.tin_no'        => ['nullable', 'string', 'max:60'],
            'form.address'       => ['nullable', 'string', 'max:500'],
            'form.district'      => ['nullable', 'string', 'max:80'],
            'form.status'        => ['required', Rule::in(['active', 'inactive'])],
            'form.notes'         => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function profileAttributes(): array
    {
        return [
            'form.name' => 'name', 'form.phone' => 'phone', 'form.email' => 'email', 'form.date_of_birth' => 'date of birth',
            'form.doc_no' => 'document no', 'form.organization' => 'organization', 'form.gender' => 'gender',
        ];
    }

    /** Validated form data ready for MasterProfile::create/update. */
    protected function profilePayload(): array
    {
        $data = collect($this->form)->map(fn ($v) => is_string($v) ? trim($v) : $v)->map(fn ($v) => $v === '' ? null : $v)->all();
        $data['email'] = $data['email'] ? mb_strtolower($data['email']) : null;
        $data['phone'] = $data['phone'] ? preg_replace('/[^\d+]/', '', $data['phone']) : null;

        // Personal fields don't apply to companies (they stay hidden in the form).
        if ($data['kind'] === 'company') {
            $data['gender'] = null;
            $data['date_of_birth'] = null;
        }

        return $data;
    }

    public static function docTypes(): array
    {
        return ['nid' => 'National ID', 'passport' => 'Passport', 'birth_certificate' => 'Birth Certificate', 'trade_licence' => 'Trade Licence', 'driving_licence' => 'Driving Licence'];
    }
}
