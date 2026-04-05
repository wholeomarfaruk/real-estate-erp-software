<?php

namespace App\Livewire\Admin\Supplier\Return;

use App\Livewire\Admin\Supplier\Concerns\InteractsWithSupplierAccess;
use App\Models\SupplierReturn;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class SupplierReturnView extends Component
{
    use InteractsWithSupplierAccess;

    public SupplierReturn $return;

    public function mount(SupplierReturn $return): void
    {
        $this->authorizePermission('supplier.return.view');
        $this->return = $return;
    }

    public function render(): View
    {
        $this->authorizePermission('supplier.return.view');

        $supplierReturn = SupplierReturn::query()
            ->with([
                'supplier:id,name,code,contact_person,phone,email,address',
                'supplierBill:id,bill_no,bill_date,due_date,status,total_amount,due_amount',
                'stockReceive:id,receive_no,receive_date,status',
                'purchaseOrder:id,po_no,order_date,status',
                'items.product:id,name,sku',
                'items.unit:id,name',
                'creator:id,name',
                'updater:id,name',
                'approver:id,name',
            ])
            ->findOrFail($this->return->id);

        return view('livewire.admin.supplier.return.supplier-return-view', [
            'supplierReturn' => $supplierReturn,
        ])->layout('layouts.admin.admin');
    }
}
