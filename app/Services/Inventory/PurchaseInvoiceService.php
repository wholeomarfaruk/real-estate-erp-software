<?php

namespace App\Services\Inventory;

use App\Enums\Accounts\TransactionType;
use App\Enums\Inventory\PurchaseInvoiceStatus;
use App\Models\Account;
use App\Models\AdvanceAdjustment;
use App\Models\PurchaseFund;
use App\Models\PurchaseInvoice;
use App\Models\StockReceive;
use App\Services\Accounts\LedgerService;
use App\Services\NumberSequenceService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseInvoiceService
{
    public function __construct(
        private readonly NumberSequenceService $sequences,
        private readonly LedgerService $ledger,
    ) {}

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function generateInvoiceNo(): string
    {
        return $this->sequences->next('PI');
    }

    public function generatePaymentNo(): string
    {
        return $this->sequences->next('PI-PMT');
    }

    protected function resolveActorId(?int $userId): int
    {
        $actorId = $userId ?? (int) Auth::id();

        if ($actorId <= 0) {
            throw new \DomainException('A valid authenticated user is required for this action.');
        }

        return $actorId;
    }

    // -------------------------------------------------------------------------
    // Step 1 — Auto-create invoice from stock receive (PENDING)
    // -------------------------------------------------------------------------

    /**
     * Idempotent — skips silently if an invoice already exists for this receive.
     * Called automatically inside StockReceiveService::postReceive().
     */
    public function createFromStockReceive(StockReceive $stockReceive, int $userId): PurchaseInvoice
    {
        $existing = PurchaseInvoice::query()
            ->where('stock_receive_id', $stockReceive->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        $stockReceive->loadMissing('items');

        $subtotal = round((float) $stockReceive->items->sum('total_price'), 3);

        $invoice = PurchaseInvoice::query()->create([
            'invoice_no'       => $this->generateInvoiceNo(),
            'invoice_date'     => $stockReceive->receive_date,
            'supplier_id'      => $stockReceive->supplier_id,
            'purchase_order_id'=> $stockReceive->purchase_order_id,
            'stock_receive_id' => $stockReceive->id,
            'subtotal'         => $subtotal,
            'discount_amount'  => 0,
            'shipping_amount'  => 0,
            'total_amount'     => $subtotal,
            'paid_amount'      => 0,
            'due_amount'       => $subtotal,
            'status'           => PurchaseInvoiceStatus::PENDING->value,
            'created_by'       => $userId,
        ]);

        foreach ($stockReceive->items as $item) {
            $invoice->items()->create([
                'product_id'            => $item->product_id,
                'stock_receive_item_id' => $item->id,
                'purchase_order_item_id'=> $item->purchase_order_item_id ?? null,
                'qty'                   => $item->quantity,
                'unit_price'            => $item->unit_price,
                'discount_amount'       => 0,
                'total_amount'          => $item->total_price,
            ]);
        }

        return $invoice;
    }

    // -------------------------------------------------------------------------
    // Step 2 — Accounts manager saves edits before approving (still PENDING)
    // -------------------------------------------------------------------------

    /**
     * Update amounts and account selections on a PENDING invoice without posting.
     * Safe to call multiple times (idempotent edit).
     *
     * @param  array{
     *   discount_amount?:float|null,
     *   shipping_amount?:float|null,
     *   paid_amount?:float|null,
     *   inventory_account_id?:int|null,
     *   accounts_payable_account_id?:int|null,
     *   payment_account_id?:int|null,
     *   payment_method?:string|null,
     *   due_date?:string|null,
     *   supplier_invoice_no?:string|null,
     *   remarks?:string|null,
     *   items?:array<int, array{id:int, unit_price:float, discount_amount?:float, remarks?:string|null}>
     * }  $payload
     */
    public function updatePending(PurchaseInvoice $invoice, array $payload, ?int $userId = null): PurchaseInvoice
    {
        $this->resolveActorId($userId);

        return DB::transaction(function () use ($invoice, $payload): PurchaseInvoice {
            $locked = PurchaseInvoice::query()
                ->with('items')
                ->lockForUpdate()
                ->findOrFail($invoice->id);

            if (! $locked->status->isEditable()) {
                throw new \DomainException('Only pending invoices can be edited.');
            }

            // Update line items if provided
            foreach ($payload['items'] ?? [] as $row) {
                $item = $locked->items->firstWhere('id', (int) ($row['id'] ?? 0));
                if (! $item) {
                    continue;
                }

                $unitPrice = round(max(0, (float) ($row['unit_price'] ?? $item->unit_price)), 3);
                $discount  = round(max(0, (float) ($row['discount_amount'] ?? 0)), 3);
                $total     = round(max(0, ($item->qty * $unitPrice) - $discount), 3);

                $item->update([
                    'unit_price'      => $unitPrice,
                    'discount_amount' => $discount,
                    'total_amount'    => $total,
                    'remarks'         => $row['remarks'] ?? $item->remarks,
                ]);
            }

            $locked->refresh()->loadMissing('items');

            // Shipping + payment-at-approval were removed: no freight, no cash leg here.
            $subtotal  = round((float) $locked->items->sum('total_amount'), 3);
            $discount  = round(max(0, (float) ($payload['discount_amount'] ?? $locked->discount_amount)), 3);
            $total     = round(max(0, $subtotal - $discount), 3);
            $due       = $total;

            $locked->update([
                'subtotal'            => $subtotal,
                'discount_amount'     => $discount,
                'shipping_amount'     => 0,
                'total_amount'        => $total,
                'paid_amount'         => 0,
                'due_amount'          => $due,
                'due_date'            => $payload['due_date'] ?? $locked->due_date,
                'supplier_invoice_no' => $payload['supplier_invoice_no'] ?? $locked->supplier_invoice_no,
                'remarks'             => $payload['remarks'] ?? $locked->remarks,
            ]);

            return $locked->refresh();
        });
    }

    // -------------------------------------------------------------------------
    // Step 3 — Accounts manager approves: posts accounting entries
    // -------------------------------------------------------------------------

    /**
     * Approve a PENDING invoice.  Creates the journal entry, payable (if any due),
     * and initial payment record (if paid_amount > 0), then flips the status.
     *
     * Accounting rules:
     *   Case 1 — no payment:
     *       DR inventory_account_id     [total]
     *       CR accounts_payable_account [total]
     *
     *   Case 2 — partial payment:
     *       DR inventory_account_id     [total]
     *       CR accounts_payable_account [due]
     *       CR payment_account_id       [paid]
     *
     *   Case 3 — full payment:
     *       DR inventory_account_id     [total]
     *       CR payment_account_id       [total]
     *
     * @param  array{
     *   inventory_account_id:int,
     *   accounts_payable_account_id?:int|null,
     *   payment_account_id?:int|null,
     *   payment_method?:string|null,
     *   discount_amount?:float|null,
     *   shipping_amount?:float|null,
     *   paid_amount?:float|null,
     *   due_date?:string|null,
     *   supplier_invoice_no?:string|null,
     *   remarks?:string|null,
     *   items?:array<int, array{id:int, unit_price:float, discount_amount?:float, remarks?:string|null}>
     * }  $payload
     */
    public function approve(PurchaseInvoice $invoice, array $payload, ?int $userId = null): PurchaseInvoice
    {
        $actorId = $this->resolveActorId($userId);

        return DB::transaction(function () use ($invoice, $payload, $actorId): PurchaseInvoice {
            $locked = PurchaseInvoice::query()
                ->with('items')
                ->lockForUpdate()
                ->findOrFail($invoice->id);

            if ($locked->status !== PurchaseInvoiceStatus::PENDING) {
                throw new \DomainException('Only pending invoices can be approved.');
            }

            // ------------------------------------------------------------------
            // 1. Update line items
            // ------------------------------------------------------------------
            foreach ($payload['items'] ?? [] as $row) {
                $item = $locked->items->firstWhere('id', (int) ($row['id'] ?? 0));
                if (! $item) {
                    continue;
                }
                $unitPrice = round(max(0, (float) ($row['unit_price'] ?? $item->unit_price)), 3);
                $discount  = round(max(0, (float) ($row['discount_amount'] ?? 0)), 3);
                $total     = round(max(0, ($item->qty * $unitPrice) - $discount), 3);
                $item->update([
                    'unit_price'      => $unitPrice,
                    'discount_amount' => $discount,
                    'total_amount'    => $total,
                    'remarks'         => $row['remarks'] ?? $item->remarks,
                ]);
            }

            $locked->refresh()->loadMissing('items');

            // ------------------------------------------------------------------
            // 2. Compute final totals — operational page books the payable only;
            //    no shipping, no advance application, no payment at approval.
            // ------------------------------------------------------------------
            $subtotal = round((float) $locked->items->sum('total_amount'), 3);
            $discount = round(max(0, (float) ($payload['discount_amount'] ?? $locked->discount_amount)), 3);
            $total    = round(max(0, $subtotal - $discount), 3);
            $due      = $total;

            if ($total <= 0) {
                throw new \DomainException('Invoice total must be greater than zero before approval.');
            }

            $notes = 'Purchase Invoice #' . $locked->invoice_no;

            // ------------------------------------------------------------------
            // 3. Auto-post the payable via the configured `purchase.invoice` event
            //    (Dr Inventory / Cr Accounts Payable for the full total). Accounts
            //    are admin-configured — no per-invoice picking on the page.
            // ------------------------------------------------------------------
            $txnPurchase = app(\App\Services\Accounts\PostingEngine::class)->record(
                'purchase.invoice',
                new \App\Accounting\PostingContext(
                    amount: $total,
                    datetime: $locked->invoice_date->format('Y-m-d') . ' 00:00:00',
                    referenceType: 'purchase_invoice',
                    referenceId: (int) $locked->id,
                    notes: $notes,
                    actorId: $actorId,
                ),
            );

            // Resolve the event's posted legs so the invoice records which COA
            // accounts were used (the A/P account is needed by the payment flow).
            $txnPurchase->loadMissing('lines.account');
            $inventoryAccountId = (int) ($txnPurchase->lines->firstWhere(fn ($l) => (float) $l->debit > 0)?->account_id ?? 0);
            $payableAccountId   = (int) ($txnPurchase->lines->firstWhere(fn ($l) => (float) $l->credit > 0)?->account_id ?? 0);

            // ------------------------------------------------------------------
            // 8. Determine final invoice status
            // ------------------------------------------------------------------
            // Approval just books the payable; payment happens via the Payment Module.
            $newStatus = PurchaseInvoiceStatus::APPROVED;

            // ------------------------------------------------------------------
            // 9. Persist invoice
            // ------------------------------------------------------------------
            $locked->update([
                'subtotal'                    => $subtotal,
                'discount_amount'             => $discount,
                'shipping_amount'             => 0,
                'total_amount'                => $total,
                'paid_amount'                 => 0,
                'due_amount'                  => $due,
                'status'                      => $newStatus->value,
                'inventory_account_id'        => $inventoryAccountId,
                'accounts_payable_account_id' => $payableAccountId ?: null,
                'payment_account_id'          => null,
                'payment_method'              => null,
                'transaction_id'              => (int) $txnPurchase->id,
                'due_date'                    => $payload['due_date'] ?? $locked->due_date,
                'supplier_invoice_no'         => $payload['supplier_invoice_no'] ?? $locked->supplier_invoice_no,
                'remarks'                     => $payload['remarks'] ?? $locked->remarks,
                'confirmed_by'                => $actorId,
                'confirmed_at'                => now(),
            ]);

            return $locked->refresh();
        });
    }

    // -------------------------------------------------------------------------
    // Cancel
    // -------------------------------------------------------------------------

    /**
     * Cancel a PENDING invoice.  No accounting entries exist yet, so cancellation
     * is purely a status change.
     */
    public function cancelInvoice(PurchaseInvoice $invoice, ?int $userId = null, ?string $remarks = null): PurchaseInvoice
    {
        $this->resolveActorId($userId);

        return DB::transaction(function () use ($invoice, $remarks): PurchaseInvoice {
            $locked = PurchaseInvoice::query()->lockForUpdate()->findOrFail($invoice->id);

            if (! $locked->status->canBeCancelled()) {
                throw new \DomainException('Only pending invoices can be cancelled.');
            }

            $locked->update([
                'status'  => PurchaseInvoiceStatus::CANCELLED->value,
                'remarks' => $remarks ?? $locked->remarks,
            ]);

            return $locked->refresh();
        });
    }

    // -------------------------------------------------------------------------
    // Payment sync (called by payment module after additional payments)
    // -------------------------------------------------------------------------

    /**
     * Re-sync invoice paid_amount and status after additional payments made
     * through the payment module against the linked PurchasePayable.
     *
     * Totals:
     *   invoice.paid_amount = initial_payment_at_approval + sum(payable.paid_amount)
     */
    public function syncPaymentStatus(PurchaseInvoice $invoice): void
    {
        $locked = PurchaseInvoice::query()->lockForUpdate()->findOrFail($invoice->id);

        if (! $locked->status->isPosted()) {
            return;
        }

        // Sum only payment CR ledger lines — exclude the payable/AP credit posted at
        // invoice approval. Approval credits accounts_payable_account_id; actual
        // payments credit a bank/cash account. Summing from lines (not the header
        // summary) keeps this correct under double-entry.
        $paid = (float) \App\Models\TransactionLine::query()
            ->whereHas('transaction', function ($q) use ($locked): void {
                $q->where('reference_type', 'purchase_invoice')
                    ->where('reference_id', $locked->id);
            })
            ->where('credit', '>', 0)
            ->where('account_id', '!=', $locked->accounts_payable_account_id)
            ->sum('credit');

        $locked->paid_amount = round($paid, 3);
        $locked->recalculatePaymentStatus();
        $locked->save();
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /** @param array<int, array{fund_id:int, amount:float}> $lines */
    private function resolveAdvanceAdjustments(array $lines): array
    {
        return array_values(array_filter(
            array_map(fn ($line) => [
                'fund_id' => (int) ($line['fund_id'] ?? 0),
                'amount'  => round(max(0, (float) ($line['amount'] ?? 0)), 3),
            ], $lines),
            fn ($line) => $line['fund_id'] > 0 && $line['amount'] > 0
        ));
    }

    private function resolveAdvanceAccountId(): int
    {
        // Same Supplier Advance asset account the fund release debited
        // (purchase.supplier_advance event), so clearing offsets it cleanly.
        $account = Account::query()->where('code', 'ASSET-SUP-ADV')->first();

        if (! $account) {
            throw new \DomainException('Supplier Advance account (ASSET-SUP-ADV) not found. Please run the chart-of-accounts seeder.');
        }

        return (int) $account->id;
    }
}
