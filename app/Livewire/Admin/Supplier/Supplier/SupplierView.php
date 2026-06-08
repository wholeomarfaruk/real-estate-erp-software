<?php

namespace App\Livewire\Admin\Supplier\Supplier;

use App\Livewire\Admin\Supplier\Concerns\InteractsWithSupplierAccess;
use App\Models\Supplier;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class SupplierView extends Component
{
    use InteractsWithSupplierAccess;

    public Supplier $supplier;

    public function mount(Supplier $supplier): void
    {
        $this->authorizePermission('supplier.view');
        $this->supplier = $supplier;
    }

    public function render(): View
    {
        $this->authorizePermission('supplier.view');

        $supplier = Supplier::query()
            ->withCount([
                'purchaseOrders',
                'stockReceives',
                'purchaseReturns',
                'purchaseInvoices',
            ])
            ->findOrFail($this->supplier->id);

        $latestPurchases = $supplier->purchaseOrders()
            ->latest('order_date')
            ->latest('id')
            ->limit(5)
            ->get(['id', 'po_no', 'order_date', 'status', 'actual_purchase_amount', 'due_amount']);

        $pendingBills = $supplier->purchaseInvoices()
            ->where('status', '!=', 'paid')
            ->latest('due_date')
            ->latest('id')
            ->limit(5)
            ->get(['id', 'invoice_no', 'invoice_date', 'due_amount']);

        return view('livewire.admin.supplier.supplier.supplier-view', [
            'supplier' => $supplier,
            'latestPurchases' => $latestPurchases,
            'pendingBills' => $pendingBills,
        ])->layout('layouts.admin.admin');
    }
}
