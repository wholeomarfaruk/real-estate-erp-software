<?php

namespace App\Models;

use App\Enums\Inventory\PurchaseInvoiceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseInvoice extends Model
{
    protected $fillable = [
        'supplier_id',
        'purchase_order_id',
        'stock_receive_id',
        'invoice_no',
        'invoice_date',
        'due_date',
        'supplier_invoice_no',
        'subtotal',
        'discount_amount',
        'shipping_amount',
        'total_amount',
        'paid_amount',
        'due_amount',
        'status',
        'inventory_account_id',
        'accounts_payable_account_id',
        'payment_account_id',
        'advance_account_id',
        'advance_adjusted_amount',
        'payment_method',
        'transaction_id',
        'purchase_payable_id',
        'payment_id',
        'attachments',
        'remarks',
        'created_by',
        'confirmed_by',   // accounts manager who approved
        'confirmed_at',   // approval timestamp
    ];

    protected $casts = [
        'invoice_date'    => 'date',
        'due_date'        => 'date',
        'confirmed_at'    => 'datetime',
        'subtotal'        => 'decimal:3',
        'discount_amount' => 'decimal:3',
        'shipping_amount' => 'decimal:3',
        'total_amount'    => 'decimal:3',
        'paid_amount'     => 'decimal:3',
        'due_amount'      => 'decimal:3',
        'attachments'     => 'array',
        'status'          => PurchaseInvoiceStatus::class,
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function stockReceive(): BelongsTo
    {
        return $this->belongsTo(StockReceive::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseInvoiceItem::class)->orderBy('id');
    }

    public function payable(): BelongsTo
    {
        return $this->belongsTo(PurchasePayable::class, 'purchase_payable_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function initialPayment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }

    public function inventoryAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'inventory_account_id');
    }

    public function payableAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'accounts_payable_account_id');
    }

    public function paymentAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'payment_account_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    // -------------------------------------------------------------------------
    // Computed helpers
    // -------------------------------------------------------------------------

    /**
     * Re-compute subtotal and total from line items + header discount/shipping.
     * Does NOT save — caller must call save() or update().
     */
    public function syncTotals(): void
    {
        $subtotal = round((float) $this->items()->sum('total_amount'), 3);
        $discount = round(max(0, (float) $this->discount_amount), 3);
        $shipping = round(max(0, (float) $this->shipping_amount), 3);

        $this->subtotal     = $subtotal;
        $this->total_amount = round(max(0, $subtotal - $discount + $shipping), 3);
    }

    /**
     * Derive due_amount and status from paid_amount vs total_amount.
     * Requires the invoice to already be posted (isPosted() = true).
     * Does NOT save — caller must call save().
     */
    public function recalculatePaymentStatus(): void
    {
        $total = round(max(0, (float) $this->total_amount), 3);
        $paid  = round(max(0, (float) $this->paid_amount), 3);
        $due   = round(max(0, $total - $paid), 3);

        $this->due_amount = $due;

        if (! $this->status->isPosted()) {
            return;
        }

        if ($due <= 0) {
            $this->status = PurchaseInvoiceStatus::PAID;
        } elseif ($paid > 0) {
            $this->status = PurchaseInvoiceStatus::PARTIALLY_PAID;
        } else {
            $this->status = PurchaseInvoiceStatus::APPROVED;
        }
    }
}
