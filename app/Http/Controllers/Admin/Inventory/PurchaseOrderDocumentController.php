<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Livewire\Admin\Inventory\Concerns\InteractsWithInventoryAccess;
use App\Models\PurchaseOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpFoundation\Response;

class PurchaseOrderDocumentController extends Controller
{
    use InteractsWithInventoryAccess;

    public function print(PurchaseOrder $purchaseOrder): View
    {
        $this->authorizePermission('inventory.purchase_order.view');

        $purchaseOrder->load($this->relations());
        $this->ensurePurchaseOrderAccessible($purchaseOrder);

        return view('admin.inventory.purchase-orders.document', [
            'purchaseOrder' => $purchaseOrder,
            'isPdf' => false,
            'companyName' => config('app.name'),
            'companyAddress' => auth()->user()?->address,
            'companyPhone' => auth()->user()?->phone,
            'companyEmail' => auth()->user()?->email,
        ]);
    }

    public function pdf(PurchaseOrder $purchaseOrder): Response
    {
        $this->authorizePermission('inventory.purchase_order.view');

        $purchaseOrder->load($this->relations());
        $this->ensurePurchaseOrderAccessible($purchaseOrder);

        $pdf = Pdf::loadView('admin.inventory.purchase-orders.document', [
            'purchaseOrder' => $purchaseOrder,
            'isPdf' => true,
            'companyName' => config('app.name'),
            'companyAddress' => auth()->user()?->address,
            'companyPhone' => auth()->user()?->phone,
            'companyEmail' => auth()->user()?->email,
        ])->setPaper('a4');

        return $pdf->download('po-'.($purchaseOrder->po_no ?: $purchaseOrder->id).'.pdf');
    }

    public function download(PurchaseOrder $purchaseOrder): Response
    {
        $this->authorizePermission('inventory.purchase_order.view');

        $purchaseOrder->load([
            'supplier:id,name,phone,email,address',
            'store:id,name,code,project_id',
            'store.project:id,name',
            'items.product:id,name,product_unit_id',
            'items.product.unit:id,name,code',
        ]);
        $this->ensurePurchaseOrderAccessible($purchaseOrder);

        $subtotal = (float) $purchaseOrder->items->sum(fn ($item): float => (float) $item->estimated_total_price);
        $discount = 0.0;
        $tax = 0.0;
        $total = round($subtotal - $discount + $tax, 2);

        $pdf = Pdf::loadView('pdf.inventory.purchase-order', [
            'companyName' => config('app.name'),
            'purchaseOrder' => $purchaseOrder,
            'subtotal' => round($subtotal, 2),
            'discount' => $discount,
            'tax' => $tax,
            'total' => $total,
            'generatedAt' => now(),
        ])->setPaper('a4');

        return $pdf->download('po-'.($purchaseOrder->po_no ?: $purchaseOrder->id).'.pdf');
    }

    protected function authorizePermission(string $permission): void
    {
        abort_unless(auth()->user()?->can($permission), 403, 'Unauthorized action.');
    }

    /**
     * @return array<int, string>
     */
    protected function relations(): array
    {
        return [
            'requester:id,name',
            'store:id,name,code,address',
            'supplier:id,code,name,company_name,phone,email,address',
            'engineerApprover:id,name',
            'chairmanApprover:id,name',
            'accountsApprover:id,name',
            'items.product:id,name,sku',
            'items.supplier:id,name,code',
        ];
    }

    protected function ensurePurchaseOrderAccessible(PurchaseOrder $purchaseOrder): void
    {
        $user = auth()->user();
        if (! $user) {
            abort(403, 'Unauthorized action.');
        }

        if ($this->hasInventoryWideAccess($this->purchaseOrderGlobalAccessPermissions())) {
            return;
        }

        $storeIds = \App\Models\Store::query()->managedBy($user->id)->pluck('id')->all();

        abort_unless(
            in_array((int) $purchaseOrder->store_id, $storeIds, true),
            403,
            'You are not allowed to access this purchase order.'
        );
    }
}
