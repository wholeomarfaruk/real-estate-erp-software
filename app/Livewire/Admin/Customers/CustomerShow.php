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

        $this->customer = $customer->load([
            'createdByUser',
            'updatedByUser',
            'propertySales.propertyUnit.property',
            'propertySales.propertyUnit.floor',
        ]);
    }

    public function render()
    {
        $sales = $this->customer->propertySales;

        $kpi = [
            'holdings'   => $sales->count(),
            'totalValue' => $sales->sum('net_amount'),
            'paid'       => $sales->where('payment_status', 'paid')->sum('net_amount'),
            'due'        => $sales->whereIn('payment_status', ['pending', 'partial'])->sum('net_amount'),
            'documents'  => $this->customer->doc_no ? 1 : 0,
        ];

        $docFile      = $this->customer->doc_file_id
            ? File::find($this->customer->doc_file_id)
            : null;

        $profileImage = $this->customer->profile_image_id
            ? File::find($this->customer->profile_image_id)
            : null;

        $canEdit = auth()->user()?->can('customer.edit');

        return view('livewire.admin.customers.customer-show', compact('docFile', 'profileImage', 'canEdit', 'sales', 'kpi'))
            ->layout('layouts.admin.admin');
    }
}
