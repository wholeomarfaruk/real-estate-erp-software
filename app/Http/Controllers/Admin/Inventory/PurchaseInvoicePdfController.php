<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Models\PurchaseInvoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class PurchaseInvoicePdfController extends Controller
{
    public function download(PurchaseInvoice $purchaseInvoice): Response
    {
        $purchaseInvoice->load([
            'supplier',
            'purchaseOrder:id,po_no',
            'items.product:id,name',
            'creator:id,name',
            'approver:id,name',
            'bankingPaymentRequests' => fn ($q) => $q
                ->with(['bankAccount:id,bank_name', 'requestedBy:id,name'])
                ->where('status', 'completed')
                ->latest(),
        ]);

        $pdf = Pdf::loadView('pdf.inventory.purchase-invoice', [
            'invoice'     => $purchaseInvoice,
            'companyName' => config('app.name'),
        ])->setPaper('a4', 'portrait');

        $filename = ($purchaseInvoice->invoice_no ?: 'PI-' . $purchaseInvoice->id) . '.pdf';

        return $pdf->download($filename);
    }
}
