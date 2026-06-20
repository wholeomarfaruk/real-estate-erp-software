<?php

namespace App\Livewire\Admin\Inventory\PurchaseInvoice;

use App\Enums\Accounts\AccountType;
use App\Enums\Accounts\EntryMethod;
use App\Livewire\Admin\Inventory\Concerns\InteractsWithInventoryAccess;
use App\Models\Account;
use App\Models\PurchaseFund;
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
    // Editable header fields (accounts manager only, while PENDING)
    // -------------------------------------------------------------------------
    public float  $discount_amount               = 0;
    public float  $shipping_amount               = 0;
    public float  $paid_amount                   = 0;
    public ?int   $inventory_account_id          = null;
    public ?int   $accounts_payable_account_id   = null;
    public ?int   $payment_account_id            = null;
    public string $payment_method                = '';
    public string $due_date                      = '';
    public string $supplier_invoice_no           = '';
    public string $remarks                       = '';

    /**
     * Per-fund advance adjustment lines.
     * Each element: {fund_id, category, total_amount, remaining, adjust_amount}
     * The user edits adjust_amount per row.
     */
    public array $advanceFundLines = [];

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
    public function updatedShippingAmount(): void        { $this->recalculate(); }
    public function updatedPaidAmount(): void            { $this->recalculate(); }
    public function updatedAdvanceFundLines(): void      { $this->recalculate(); }
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
                (int) Auth::id()
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
        $ledgerAccounts = Account::query()
            ->where('is_active', true)
            ->where('type', AccountType::LEDGER->value)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'type']);

        $cashBankAccounts = Account::query()
            ->where('is_active', true)
            ->whereNot('type', AccountType::LEDGER->value)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'type']);

        // Refresh advance fund lines every render so remaining balances are live
        if ($this->invoice->purchase_order_id && $this->invoice->status->isEditable()) {
            $this->syncAdvanceFundLines();
        }

        return view('livewire.admin.inventory.purchase-invoice.purchase-invoice-approval-form', [
            'inventoryAccounts' => $ledgerAccounts,
            'payableAccounts'   => $ledgerAccounts,
            'paymentAccounts'   => $cashBankAccounts,
            'paymentMethods'    => EntryMethod::cases(),
            'isEditable'        => $this->invoice->status->isEditable(),
            'isPosted'          => $this->invoice->status->isPosted(),
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

        $this->syncAdvanceFundLines();
        $this->recalculate();
    }

    /**
     * Load (or refresh) advanceFundLines from the PO's completed fund releases.
     * Preserves any adjust_amount the user already typed.
     */
    private function syncAdvanceFundLines(): void
    {
        if (! $this->invoice->purchase_order_id) {
            $this->advanceFundLines = [];
            return;
        }

        $existing = collect($this->advanceFundLines)->keyBy('fund_id');

        $funds = PurchaseFund::query()
            ->where('purchase_order_id', $this->invoice->purchase_order_id)
            ->where('status', 'completed')
            ->whereNotNull('transaction_id')
            ->with(['transactionCategory:id,name', 'transaction:id,type', 'transaction.lines:id,transaction_id,debit,credit'])
            ->get();

        $this->advanceFundLines = $funds->map(function ($fund) use ($existing) {
            $remaining = $fund->transaction ? $fund->transaction->remainingAdvance() : 0.0;

            // Preserve user-typed amount if already set; default to 0
            $adjustAmount = (float) ($existing->get($fund->id)['adjust_amount'] ?? 0);
            $adjustAmount = round(min($adjustAmount, $remaining), 3);

            return [
                'fund_id'       => $fund->id,
                'category'      => $fund->transactionCategory?->name ?? '—',
                'total_amount'  => (float) $fund->amount,
                'remaining'     => round($remaining, 2),
                'adjust_amount' => $adjustAmount,
            ];
        })->toArray();
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
        $totalAdvance       = round(array_sum(array_column($this->advanceFundLines, 'adjust_amount')), 3);
        $this->due_amount   = round(max(0, $total - $paid - $totalAdvance), 3);
    }

    /** @return array<string, mixed> */
    private function buildPayload(): array
    {
        return [
            'discount_amount'             => $this->discount_amount,
            'shipping_amount'             => $this->shipping_amount,
            'paid_amount'                 => $this->paid_amount,
            'inventory_account_id'        => $this->inventory_account_id,
            'accounts_payable_account_id' => $this->accounts_payable_account_id,
            'payment_account_id'          => $this->payment_account_id,
            'payment_method'              => $this->payment_method ?: null,
            'due_date'                    => $this->due_date ?: null,
            'supplier_invoice_no'         => $this->supplier_invoice_no ?: null,
            'remarks'                     => $this->remarks ?: null,
            'advance_adjustments'         => array_values(array_filter(
                array_map(fn ($line) => [
                    'fund_id' => $line['fund_id'],
                    'amount'  => (float) $line['adjust_amount'],
                ], $this->advanceFundLines),
                fn ($line) => $line['amount'] > 0
            )),
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
            'discount_amount'             => ['numeric', 'min:0'],
            'shipping_amount'             => ['numeric', 'min:0'],
            'paid_amount'                 => ['numeric', 'min:0'],
            'items.*.unit_price'          => ['numeric', 'min:0'],
            'items.*.discount_amount'     => ['numeric', 'min:0'],
            'advanceFundLines.*.adjust_amount' => ['numeric', 'min:0'],
        ];
    }

    /** @return array<string, mixed> */
    private function approvalRules(): array
    {
        $needsPayable = $this->due_amount > 0;
        $needsPayment = $this->paid_amount > 0;

        return [
            'discount_amount'                  => ['numeric', 'min:0'],
            'shipping_amount'                  => ['numeric', 'min:0'],
            'paid_amount'                      => ['numeric', 'min:0'],
            'inventory_account_id'             => ['required', 'integer', 'exists:accounts,id'],
            'accounts_payable_account_id'      => $needsPayable ? ['required', 'integer', 'exists:accounts,id'] : ['nullable'],
            'payment_account_id'               => $needsPayment ? ['required', 'integer', 'exists:accounts,id'] : ['nullable'],
            'payment_method'                   => $needsPayment ? ['required', 'string'] : ['nullable'],
            'advanceFundLines.*.adjust_amount' => ['numeric', 'min:0'],
            'items.*.unit_price'               => ['numeric', 'min:0'],
            'items.*.discount_amount'          => ['numeric', 'min:0'],
        ];
    }
}
