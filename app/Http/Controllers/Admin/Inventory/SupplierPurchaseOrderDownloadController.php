<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class SupplierPurchaseOrderDownloadController extends Controller
{
    public function download(Supplier $supplier): Response
    {
        $this->authorizePermission('supplier.view');

        $supplier->load(['creator:id,name', 'updater:id,name']);

        $purchaseOrders = PurchaseOrder::query()
            ->whereHas('items', function ($query) use ($supplier): void {
                $query->where('supplier_id', $supplier->id);
            })
            ->with([
                'store:id,name,code,project_id',
                'store.project:id,name',
                'items' => function ($query) use ($supplier): void {
                    $query->where('supplier_id', $supplier->id)
                        ->with([
                            'product:id,name,product_unit_id',
                            'product.unit:id,name,code',
                        ]);
                },
            ])
            ->latest('order_date')
            ->latest('id')
            ->get(['id', 'po_no', 'order_date', 'store_id', 'purchase_mode', 'status', 'remarks']);

        $pdf = Pdf::loadView('pdf.supplier.purchase-orders', [
            'companyName' => config('app.name'),
            'supplier' => $supplier,
            'purchaseOrders' => $purchaseOrders,
            'generatedAt' => now(),
        ])->setPaper('a4');

        $fileName = 'supplier-po-'.($supplier->code ?: $supplier->id).'.pdf';

        return $pdf->download($fileName);
    }

    protected function authorizePermission(string $permission): void
    {
        abort_unless(auth()->user()?->can($permission), 403, 'Unauthorized action.');
    }
}
