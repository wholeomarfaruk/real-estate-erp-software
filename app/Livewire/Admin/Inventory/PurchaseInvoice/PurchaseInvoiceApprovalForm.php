<?php

namespace App\Livewire\Admin\Inventory\PurchaseInvoice;

use App\Livewire\Admin\Inventory\Concerns\InteractsWithInventoryAccess;
use App\Models\PurchaseInvoice;
use App\Services\Inventory\PurchaseInvoiceService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class PurchaseInvoiceApprovalForm extends Component
{
    use InteractsWithInventoryAccess;

    public PurchaseInvoice $invoice;

    // -------------------------------------------------------------------------
    // Editable operational fields (while PENDING). No accounting on this page —
    // approval auto-posts the payable via the purchase.invoice accounting event.
    // -------------------------------------------------------------------------
    public float  $discount_amount     = 0;
    public string $due_date            = '';
    public string $supplier_invoice_no = '';
    public string $remarks             = '';

    /** Historical payment from an old approval — read-only display only. */
    public float  $paid_amount         = 0;

    // -------------------------------------------------------------------------
    // Line items  (editable: unit_price + item discount while PENDING)
    // -------------------------------------------------------------------------
    public array $items = [];

    // -------------------------------------------------------------------------
    // Computed display (reactive)
    // -------------------------------------------------------------------------
    public float $subtotal     = 0;
    public float $total_amount = 0;
    public float $due_amount   = 0;

    public function mount(PurchaseInvoice $purchaseInvoice): void
    {
        $this->authorizePermission('inventory.purchase_invoice.view');

        $this->invoice = $purchaseInvoice->loadMissing([
            'items.product:id,name,unit',
            'supplier:id,name',
            'stockReceive:id,receive_no,receive_date',
            'purchaseOrder:id,po_no',
            'inventoryAccount:id,name,code',
            'payableAccount:id,name,code',
            'paymentAccount:id,name,code',
            'approver:id,name',
        ]);

        $this->populateFromInvoice();
    }

    // -------------------------------------------------------------------------
    // Reactive recalculation
    // -------------------------------------------------------------------------

    public function updatedDiscountAmount(): void        { $this->recalculate(); }
    public function updatedItems(): void                 { $this->recalculate(); }

    // -------------------------------------------------------------------------
    // Actions
    // -------------------------------------------------------------------------

    /** Save edits without posting — invoice stays PENDING. */
    public function saveDraft(): void
    {
        $this->authorizePermission('inventory.purchase_invoice.approve');

        if (! $this->invoice->status->isEditable()) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Invoice can no longer be edited.']);
            return;
        }

        $this->validate($this->draftRules());

        try {
            $updated = app(PurchaseInvoiceService::class)->updatePending(
                $this->invoice,
                $this->buildPayload(),
                (int) Auth::id()
            );

            $this->invoice = $updated->loadMissing([
                'items.product:id,name,unit',
                'supplier:id,name',
                'stockReceive:id,receive_no,receive_date',
            ]);

            $this->populateFromInvoice();
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Invoice draft saved.']);
        } catch (\Throwable $e) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /** Post accounting entries and approve the invoice. */
    public function approve(): void
    {
        $this->authorizePermission('inventory.purchase_invoice.approve');

        if (! $this->invoice->status->isEditable()) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Invoice has already been approved or cancelled.']);
            return;
        }

        $this->validate($this->approvalRules());

        try {
            $approved = app(PurchaseInvoiceService::class)->approve(
                $this->invoice,
                $this->buildPayload(),
                (int) Auth::id()
            );

            $this->invoice = $approved->loadMissing([
                'items.product:id,name,unit',
                'supplier:id,name',
                'stockReceive:id,receive_no,receive_date',
                'approver:id,name',
            ]);

            $this->populateFromInvoice();
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Invoice approved and accounting entries posted.']);
        } catch (\Throwable $e) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    // -------------------------------------------------------------------------
    // Render
    // -------------------------------------------------------------------------

    public function render(): View
    {
        return view('livewire.admin.inventory.purchase-invoice.purchase-invoice-approval-form', [
            'isEditable' => $this->invoice->status->isEditable(),
            'isPosted'   => $this->invoice->status->isPosted(),
        ])->layout('layouts.admin.admin');
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function populateFromInvoice(): void
    {
        $this->discount_amount     = (float) $this->invoice->discount_amount;
        $this->paid_amount         = (float) $this->invoice->paid_amount; // historical, read-only
        $this->due_date            = $this->invoice->due_date?->format('Y-m-d') ?? '';
        $this->supplier_invoice_no = (string) ($this->invoice->supplier_invoice_no ?? '');
        $this->remarks             = (string) ($this->invoice->remarks ?? '');

        $this->items = $this->invoice->items->map(fn ($item) => [
            'id'              => $item->id,
            'product_name'    => $item->product?->name ?? '—',
            'product_unit'    => $item->product?->unit ?? '',
            'qty'             => (float) $item->qty,
            'unit_price'      => (float) $item->unit_price,
            'discount_amount' => (float) $item->discount_amount,
            'total_amount'    => (float) $item->total_amount,
            'remarks'         => (string) ($item->remarks ?? ''),
        ])->toArray();

        $this->recalculate();
    }

    private function recalculate(): void
    {
        $subtotal = 0.0;

        foreach ($this->items as &$item) {
            $lineTotal = round(
                max(0, ((float) $item['qty'] * (float) $item['unit_price']) - (float) $item['discount_amount']),
                3
            );
            $item['total_amount'] = $lineTotal;
            $subtotal += $lineTotal;
        }
        unset($item);

        $this->subtotal     = round($subtotal, 3);
        $total              = round(max(0, $subtotal - $this->discount_amount), 3);
        $this->total_amount = $total;
        // No payment/advance at approval — the due is the full payable.
        $this->due_amount   = $total;
    }

    /** @return array<string, mixed> */
    private function buildPayload(): array
    {
        return [
            'discount_amount'     => $this->discount_amount,
            'due_date'            => $this->due_date ?: null,
            'supplier_invoice_no' => $this->supplier_invoice_no ?: null,
            'remarks'             => $this->remarks ?: null,
            'items' => array_map(fn ($i) => [
                'id'              => $i['id'],
                'unit_price'      => $i['unit_price'],
                'discount_amount' => $i['discount_amount'],
                'remarks'         => $i['remarks'] ?? null,
            ], $this->items),
        ];
    }

    /** @return array<string, mixed> */
    private function draftRules(): array
    {
        return [
            'discount_amount'         => ['numeric', 'min:0'],
            'items.*.unit_price'      => ['numeric', 'min:0'],
            'items.*.discount_amount' => ['numeric', 'min:0'],
        ];
    }

    /** @return array<string, mixed> */
    private function approvalRules(): array
    {
        // No accounting inputs on this page — only operational fields are validated.
        return [
            'discount_amount'         => ['numeric', 'min:0'],
            'items.*.unit_price'      => ['numeric', 'min:0'],
            'items.*.discount_amount' => ['numeric', 'min:0'],
        ];
    }
}
