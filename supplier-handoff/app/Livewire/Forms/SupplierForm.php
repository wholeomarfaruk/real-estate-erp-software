<?php

namespace App\Livewire\Forms;

use Livewire\Attributes\Validate;
use Livewire\Form;

class SupplierForm extends Form
{
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|string|max:255')]
    public string $contact_person = '';

    #[Validate('nullable|email|max:255')]
    public string $email = '';

    #[Validate('required|string|max:50')]
    public string $phone = '';

    #[Validate('nullable|string|max:50')]
    public string $alternate_phone = '';

    #[Validate('nullable|string')]
    public string $address = '';

    #[Validate('nullable|string|max:100')]
    public string $trade_license_no = '';

    #[Validate('nullable|string|max:100')]
    public string $tin_no = '';

    #[Validate('nullable|string|max:100')]
    public string $bin_no = '';

    // 'active' | 'inactive' | 'blocked' — mapped to status + is_blocked on save
    #[Validate('required|in:active,inactive,blocked')]
    public string $status = 'active';

    #[Validate('nullable|string')]
    public string $notes = '';

    // attachment IDs
    public ?int $image_id = null;
    public ?int $cover_image_id = null;

    // documents: array of file IDs ONLY (no metadata)
    #[Validate('array')]
    public array $documents = [];

    /** Translate the 3-way status segment into the two DB columns. */
    public function toAttributes(): array
    {
        return [
            'name'             => $this->name,
            'contact_person'   => $this->contact_person ?: null,
            'email'            => $this->email ?: null,
            'phone'            => $this->phone ?: null,
            'alternate_phone'  => $this->alternate_phone ?: null,
            'address'          => $this->address ?: null,
            'trade_license_no' => $this->trade_license_no ?: null,
            'tin_no'           => $this->tin_no ?: null,
            'bin_no'           => $this->bin_no ?: null,
            'status'           => $this->status === 'active',
            'is_blocked'       => $this->status === 'blocked',
            'notes'            => $this->notes ?: null,
            'image_id'         => $this->image_id,
            'cover_image_id'   => $this->cover_image_id,
            'documents'        => array_values(array_filter($this->documents)),
        ];
    }
}
