<?php

namespace App\Livewire\Admin\Inventory\PurchaseInvoice;

use App\Enums\Accounts\AccountType;
use App\Enums\Accounts\EntryMethod;
use App\Livewire\Admin\Inventory\Concerns\InteractsWithInventoryAccess;
use App\Models\Account;
use App\Models\PurchaseInvoice;
use App\Services\Inventory\PurchaseInvoiceService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class PurchaseInvoiceApprovalForm extends Component
{
    use InteractsWithInventoryAccess;

    public PurchaseInvoice $invoice;

    // -------------------------------------------------------------------------
    // Editable header fields (accounts manager only, while PENDING)
    // -------------------------------------------------------------------------
    public float  $discount_amount               = 0;
    public float  $shipping_amount               = 0;
    public float  $paid_amount                   = 0;
    public ?int   $inventory_account_id          = null;
    public ?int   $accounts_payable_account_id   = null;
    public ?int   $payment_account_id            = null;
    public string $payment_method                = '';
    public float  $advance_adjusted_amount       = 0;
    public ?int   $advance_account_id            = null;
    public string $due_date                      = '';
    public string $supplier_invoice_no           = '';
    public string $remarks                       = '';

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

    public function updatedDiscountAmount(): void              { $this->recalculate(); }
    public function updatedShippingAmount(): void              { $this->recalculate(); }
    public function updatedPaidAmount(): void                  { $this->recalculate(); }
    public function updatedAdvanceAdjustedAmount(): void       { $this->recalculate(); }
    public function updatedItems(): void                       { $this->recalculate(); }

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
                (int) auth()->id()
            );

            $this->invoice = $updated->loadMissing([
                'items.product:id,name,unit',
                'supplier:id,name',
                'stockReceive:id,receive_no,receive_date',
                'inventoryAccount:id,name,code',
                'payableAccount:id,name,code',
                'paymentAccount:id,name,code',
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
                (int) auth()->id()
            );

            $this->invoice = $approved->loadMissing([
                'items.product:id,name,unit',
                'supplier:id,name',
                'stockReceive:id,receive_no,receive_date',
                'inventoryAccount:id,name,code',
                'payableAccount:id,name,code',
                'paymentAccount:id,name,code',
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
        $accounts = Account::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'type']);

        $cashBankAccounts  = $accounts->filter(fn ($a) => in_array($a->type?->value, [AccountType::CASH->value, AccountType::BANK->value]));

        $inventoryAccounts = $accounts->sortBy('name');
        $payableAccounts   = $accounts->sortBy('name');
        $paymentAccounts   = $cashBankAccounts->sortBy('name');
        $advanceAccounts   = $accounts
            ->filter(fn ($a) => stripos($a->name, 'advance') !== false)
            ->sortBy('name');

        // Total unreconciled advance for this PO
        $totalAdvance = 0.0;
        if ($this->invoice->purchase_order_id) {
            $released  = (float) \App\Models\PurchaseFund::query()
                ->where('purchase_order_id', $this->invoice->purchase_order_id)
                ->whereNotNull('transaction_id')
                ->sum('amount');
            $adjusted  = (float) \App\Models\PurchaseInvoice::query()
                ->where('purchase_order_id', $this->invoice->purchase_order_id)
                ->where('id', '!=', $this->invoice->id)
                ->whereNotNull('transaction_id')
                ->sum('advance_adjusted_amount');
            $totalAdvance = round(max(0, $released - $adjusted), 2);
        }

        return view('livewire.admin.inventory.purchase-invoice.purchase-invoice-approval-form', [
            'inventoryAccounts' => $inventoryAccounts,
            'payableAccounts'   => $payableAccounts,
            'paymentAccounts'   => $paymentAccounts,
            'advanceAccounts'   => $advanceAccounts,
            'paymentMethods'    => EntryMethod::cases(),
            'isEditable'        => $this->invoice->status->isEditable(),
            'isPosted'          => $this->invoice->status->isPosted(),
            'totalAvailableAdvance' => $totalAdvance,
        ])->layout('layouts.admin.admin');
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function populateFromInvoice(): void
    {
        $this->discount_amount             = (float) $this->invoice->discount_amount;
        $this->shipping_amount             = (float) $this->invoice->shipping_amount;
        $this->paid_amount                 = (float) $this->invoice->paid_amount;
        $this->inventory_account_id        = $this->invoice->inventory_account_id;
        $this->accounts_payable_account_id = $this->invoice->accounts_payable_account_id;
        $this->payment_account_id          = $this->invoice->payment_account_id;
        $this->payment_method              = (string) ($this->invoice->payment_method ?? '');
        $this->advance_adjusted_amount     = (float) ($this->invoice->advance_adjusted_amount ?? 0);
        $this->advance_account_id          = $this->invoice->advance_account_id;
        $this->due_date                    = $this->invoice->due_date?->format('Y-m-d') ?? '';
        $this->supplier_invoice_no         = (string) ($this->invoice->supplier_invoice_no ?? '');
        $this->remarks                     = (string) ($this->invoice->remarks ?? '');

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
        $total              = round(max(0, $subtotal - $this->discount_amount + $this->shipping_amount), 3);
        $this->total_amount = $total;
        $paid               = round(min(max(0, $this->paid_amount), $total), 3);
        $advance            = round(min(max(0, $this->advance_adjusted_amount), $total - $paid), 3);
        $this->due_amount   = round(max(0, $total - $paid - $advance), 3);
    }

    /** @return array<string, mixed> */
    private function buildPayload(): array
    {
        return [
            'discount_amount'              => $this->discount_amount,
            'shipping_amount'              => $this->shipping_amount,
            'paid_amount'                  => $this->paid_amount,
            'inventory_account_id'         => $this->inventory_account_id,
            'accounts_payable_account_id'  => $this->accounts_payable_account_id,
            'payment_account_id'           => $this->payment_account_id,
            'payment_method'               => $this->payment_method ?: null,
            'advance_adjusted_amount'      => $this->advance_adjusted_amount,
            'advance_account_id'           => $this->advance_account_id,
            'due_date'                     => $this->due_date ?: null,
            'supplier_invoice_no'          => $this->supplier_invoice_no ?: null,
            'remarks'                      => $this->remarks ?: null,
            'items'                        => array_map(fn ($i) => [
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
            'discount_amount'  => ['numeric', 'min:0'],
            'shipping_amount'  => ['numeric', 'min:0'],
            'paid_amount'      => ['numeric', 'min:0'],
            'items.*.unit_price'      => ['numeric', 'min:0'],
            'items.*.discount_amount' => ['numeric', 'min:0'],
        ];
    }

    /** @return array<string, mixed> */
    private function approvalRules(): array
    {
        $needsPayable = $this->due_amount > 0;
        $needsPayment = $this->paid_amount > 0;
        $hasAdvance   = $this->advance_adjusted_amount > 0;

        return [
            'discount_amount'             => ['numeric', 'min:0'],
            'shipping_amount'             => ['numeric', 'min:0'],
            'paid_amount'                 => ['numeric', 'min:0'],
            'advance_adjusted_amount'     => ['numeric', 'min:0'],
            'inventory_account_id'        => ['required', 'integer', 'exists:accounts,id'],
            'accounts_payable_account_id' => $needsPayable ? ['required', 'integer', 'exists:accounts,id'] : ['nullable'],
            'advance_account_id'          => $hasAdvance   ? ['required', 'integer', 'exists:accounts,id'] : ['nullable'],
            'payment_account_id'          => $needsPayment ? ['required', 'integer', 'exists:accounts,id'] : ['nullable'],
            'payment_method'              => $needsPayment ? ['required', 'string'] : ['nullable'],
            'items.*.unit_price'          => ['numeric', 'min:0'],
            'items.*.discount_amount'     => ['numeric', 'min:0'],
        ];
    }
}
