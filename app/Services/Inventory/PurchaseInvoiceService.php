<?php

namespace App\Services\Inventory;

use App\Enums\Accounts\TransactionRelationType;
use App\Enums\Accounts\TransactionType;
use App\Enums\Inventory\PurchaseInvoiceStatus;
use App\Models\Account;
use App\Models\AdvanceAdjustment;
use App\Models\PurchaseFund;
use App\Models\PurchaseInvoice;
use App\Models\StockReceive;
use App\Models\Transaction;
use App\Services\NumberSequenceService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseInvoiceService
{
    public function __construct(private readonly NumberSequenceService $sequences) {}

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

            $subtotal  = round((float) $locked->items->sum('total_amount'), 3);
            $discount  = round(max(0, (float) ($payload['discount_amount'] ?? $locked->discount_amount)), 3);
            $shipping  = round(max(0, (float) ($payload['shipping_amount'] ?? $locked->shipping_amount)), 3);
            $total     = round(max(0, $subtotal - $discount + $shipping), 3);
            $paid      = round(max(0, min((float) ($payload['paid_amount'] ?? $locked->paid_amount), $total)), 3);
            $due       = round(max(0, $total - $paid), 3);

            $locked->update([
                'subtotal'                    => $subtotal,
                'discount_amount'             => $discount,
                'shipping_amount'             => $shipping,
                'total_amount'                => $total,
                'paid_amount'                 => $paid,
                'due_amount'                  => $due,
                'inventory_account_id'        => $payload['inventory_account_id'] ?? $locked->inventory_account_id,
                'accounts_payable_account_id' => $payload['accounts_payable_account_id'] ?? $locked->accounts_payable_account_id,
                'payment_account_id'          => $payload['payment_account_id'] ?? $locked->payment_account_id,
                'payment_method'              => $payload['payment_method'] ?? $locked->payment_method,
                'due_date'                    => $payload['due_date'] ?? $locked->due_date,
                'supplier_invoice_no'         => $payload['supplier_invoice_no'] ?? $locked->supplier_invoice_no,
                'remarks'                     => $payload['remarks'] ?? $locked->remarks,
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
            // 2. Compute final totals
            // ------------------------------------------------------------------
            $subtotal = round((float) $locked->items->sum('total_amount'), 3);
            $discount = round(max(0, (float) ($payload['discount_amount'] ?? $locked->discount_amount)), 3);
            $shipping = round(max(0, (float) ($payload['shipping_amount'] ?? $locked->shipping_amount)), 3);
            $total    = round(max(0, $subtotal - $discount + $shipping), 3);

            if ($total <= 0) {
                throw new \DomainException('Invoice total must be greater than zero before approval.');
            }

            $paid = round(max(0, min((float) ($payload['paid_amount'] ?? $locked->paid_amount), $total)), 3);

            // Resolve per-fund advance adjustments
            $advanceLines     = $this->resolveAdvanceAdjustments($payload['advance_adjustments'] ?? []);
            $totalAdvance     = round(array_sum(array_column($advanceLines, 'amount')), 3);
            $totalAdvance     = round(min($totalAdvance, $total - $paid), 3);
            $due              = round(max(0, $total - $paid - $totalAdvance), 3);

            // ------------------------------------------------------------------
            // 3. Validate required accounts
            // ------------------------------------------------------------------
            $inventoryAccountId = (int) ($payload['inventory_account_id'] ?? $locked->inventory_account_id ?? 0);
            $payableAccountId   = (int) ($payload['accounts_payable_account_id'] ?? $locked->accounts_payable_account_id ?? 0);
            $paymentAccountId   = (int) ($payload['payment_account_id'] ?? $locked->payment_account_id ?? 0);
            $paymentMethod      = (string) ($payload['payment_method'] ?? $locked->payment_method ?? '');

            if ($inventoryAccountId <= 0) {
                throw new \DomainException('Inventory / expense account is required.');
            }
            if ($due > 0 && $payableAccountId <= 0) {
                throw new \DomainException('Accounts payable account is required when there is an outstanding due amount.');
            }
            if ($paid > 0 && $paymentAccountId <= 0) {
                throw new \DomainException('Cash / bank account is required when a payment is made.');
            }
            if ($paid > 0 && $paymentMethod === '') {
                throw new \DomainException('Payment method is required when a payment is made.');
            }

            $datetime = $locked->invoice_date->format('Y-m-d') . ' 00:00:00';
            $notes    = 'Purchase Invoice #' . $locked->invoice_no;

            // ------------------------------------------------------------------
            // 4. TXN-PURCHASE: DR inventory/expense account
            // ------------------------------------------------------------------
            $txnPurchase = Transaction::query()->create([
                'account_id'     => $inventoryAccountId,
                'datetime'       => $datetime,
                'type'           => TransactionType::PURCHASE_INVOICE->value,
                'reference_type' => 'purchase_invoice',
                'reference_id'   => (int) $locked->id,
                'debit'          => $total,
                'credit'         => 0,
                'notes'          => $notes,
                'created_by'     => $actorId,
            ]);

            // ------------------------------------------------------------------
            // 5. TXN-ADV-CLEAR per fund: CR advance account + AdvanceAdjustment
            // ------------------------------------------------------------------
            if ($totalAdvance > 0 && ! empty($advanceLines)) {
                $advanceAccountId = $this->resolveAdvanceAccountId();

                foreach ($advanceLines as $line) {
                    if ($line['amount'] <= 0) {
                        continue;
                    }

                    $txnAdvClear = Transaction::query()->create([
                        'account_id'             => $advanceAccountId,
                        'datetime'               => $datetime,
                        'type'                   => TransactionType::PURCHASE_INVOICE->value,
                        'reference_type'         => 'purchase_invoice',
                        'reference_id'           => (int) $locked->id,
                        'debit'                  => 0,
                        'credit'                 => $line['amount'],
                        'notes'                  => $notes . ' – advance clear',
                        'related_transaction_id' => $txnPurchase->id,
                        'relation_type'          => TransactionRelationType::ADVANCE_CLEAR->value,
                        'created_by'             => $actorId,
                    ]);

                    // Link to the original advance transaction via AdvanceAdjustment
                    $fund = PurchaseFund::query()->find($line['fund_id']);
                    if ($fund && $fund->transaction_id) {
                        AdvanceAdjustment::query()->create([
                            'advance_transaction_id' => $fund->transaction_id,
                            'adjust_transaction_id'  => $txnAdvClear->id,
                            'amount'                 => $line['amount'],
                            'notes'                  => $notes,
                            'created_by'             => $actorId,
                        ]);
                    }
                }
            }

            // ------------------------------------------------------------------
            // 6. TXN-PAYMENT: CR cash/bank
            // ------------------------------------------------------------------
            if ($paid > 0) {
                Transaction::query()->create([
                    'account_id'             => $paymentAccountId,
                    'datetime'               => $datetime,
                    'type'                   => TransactionType::PURCHASE_INVOICE->value,
                    'reference_type'         => 'purchase_invoice',
                    'reference_id'           => (int) $locked->id,
                    'debit'                  => 0,
                    'credit'                 => $paid,
                    'method'                 => $paymentMethod,
                    'notes'                  => $notes . ' – payment',
                    'related_transaction_id' => $txnPurchase->id,
                    'relation_type'          => TransactionRelationType::PAIR->value,
                    'created_by'             => $actorId,
                ]);
            }

            // ------------------------------------------------------------------
            // 7. TXN-PAYABLE: CR accounts payable
            // ------------------------------------------------------------------
            if ($due > 0) {
                Transaction::query()->create([
                    'account_id'             => $payableAccountId,
                    'datetime'               => $datetime,
                    'type'                   => TransactionType::PURCHASE_INVOICE->value,
                    'reference_type'         => 'purchase_invoice',
                    'reference_id'           => (int) $locked->id,
                    'debit'                  => 0,
                    'credit'                 => $due,
                    'notes'                  => $notes . ' – payable',
                    'related_transaction_id' => $txnPurchase->id,
                    'relation_type'          => TransactionRelationType::PAIR->value,
                    'created_by'             => $actorId,
                ]);
            }

            // ------------------------------------------------------------------
            // 8. Determine final invoice status
            // ------------------------------------------------------------------
            $newStatus = match (true) {
                $due <= 0 => PurchaseInvoiceStatus::PAID,
                $paid > 0 => PurchaseInvoiceStatus::PARTIALLY_PAID,
                default   => PurchaseInvoiceStatus::APPROVED,
            };

            // ------------------------------------------------------------------
            // 9. Persist invoice
            // ------------------------------------------------------------------
            $locked->update([
                'subtotal'                    => $subtotal,
                'discount_amount'             => $discount,
                'shipping_amount'             => $shipping,
                'total_amount'                => $total,
                'paid_amount'                 => $paid,
                'due_amount'                  => $due,
                'status'                      => $newStatus->value,
                'inventory_account_id'        => $inventoryAccountId,
                'accounts_payable_account_id' => $payableAccountId ?: null,
                'payment_account_id'          => $paymentAccountId ?: null,
                'payment_method'              => $paymentMethod ?: null,
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

        // Sum only payment CRs — exclude the payable/AP pair entry created at invoice approval.
        // The approval pair hits accounts_payable_account_id; actual payments hit a bank account.
        $paid = (float) Transaction::query()
            ->where('reference_type', 'purchase_invoice')
            ->where('reference_id', $locked->id)
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
        $account = Account::query()
            ->whereRaw('LOWER(name) = ?', ['advance'])
            ->first();

        if (! $account) {
            throw new \DomainException('No account named "advance" found. Please create a Ledger account named "Advance".');
        }

        return (int) $account->id;
    }
}
