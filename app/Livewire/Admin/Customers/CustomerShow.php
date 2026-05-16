<?php

namespace App\Livewire\Admin\Customers;

use App\Models\Customer;
use App\Models\File;
use Livewire\Component;

class CustomerShow extends Component
{
    public Customer $customer;

    public function mount(Customer $customer): void
    {
        abort_unless(auth()->user()?->can('customer.view'), 403);

        $this->customer = $customer->load(['createdByUser', 'updatedByUser']);
    }

    public function render()
    {
        $docFile      = $this->customer->doc_file_id
            ? File::find($this->customer->doc_file_id)
            : null;

        $profileImage = $this->customer->profile_image_id
            ? File::find($this->customer->profile_image_id)
            : null;

        $canEdit = auth()->user()?->can('customer.edit');

        return view('livewire.admin.customers.customer-show', compact('docFile', 'profileImage', 'canEdit'))
            ->layout('layouts.admin.admin');
    }
}
