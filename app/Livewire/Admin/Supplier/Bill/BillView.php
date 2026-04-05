<?php

namespace App\Livewire\Admin\Supplier\Bill;

use App\Livewire\Admin\Supplier\Concerns\InteractsWithSupplierAccess;
use App\Models\SupplierBill;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class BillView extends Component
{
    use InteractsWithSupplierAccess;

    public SupplierBill $bill;

    public function mount(SupplierBill $bill): void
    {
        $this->authorizePermission('supplier.bill.view');
        $this->bill = $bill;
    }

    public function render(): View
    {
        $this->authorizePermission('supplier.bill.view');

        SupplierBill::syncOverdueStatuses();

        $bill = SupplierBill::query()
            ->with([
                'supplier:id,name,code,contact_person,phone,email,address',
                'creator:id,name',
                'updater:id,name',
                'purchaseOrder:id,po_no,order_date,status',
                'stockReceive:id,receive_no,receive_date,status',
                'items.product:id,name,sku',
                'items.unit:id,name',
            ])
            ->findOrFail($this->bill->id);

        return view('livewire.admin.supplier.bill.bill-view', [
            'bill' => $bill,
        ])->layout('layouts.admin.admin');
    }
}
