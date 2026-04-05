<?php

namespace App\Livewire\Admin\Supplier\Payment;

use App\Livewire\Admin\Supplier\Concerns\InteractsWithSupplierAccess;
use App\Models\SupplierBill;
use App\Models\SupplierPayment;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class PaymentView extends Component
{
    use InteractsWithSupplierAccess;

    public SupplierPayment $payment;

    public function mount(SupplierPayment $payment): void
    {
        $this->authorizePermission('supplier.payment.view');
        $this->payment = $payment;
    }

    public function render(): View
    {
        $this->authorizePermission('supplier.payment.view');

        SupplierBill::syncOverdueStatuses();

        $payment = SupplierPayment::query()
            ->with([
                'supplier:id,name,code,contact_person,phone,email,address',
                'creator:id,name',
                'updater:id,name',
                'allocations.bill:id,bill_no,bill_date,due_date,total_amount,paid_amount,due_amount,status',
            ])
            ->findOrFail($this->payment->id);

        return view('livewire.admin.supplier.payment.payment-view', [
            'payment' => $payment,
        ])->layout('layouts.admin.admin');
    }
}
