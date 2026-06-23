<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'contact_person',
        'phone',
        'alternate_phone',
        'secondary_phone',
        'email',
        'address',
        'trade_license_no',
        'tin_no',
        'bin_no',
        'is_blocked',
        'notes',
        'image_id',
        'cover_image_id',
        'documents',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'status' => 'boolean',
        'is_blocked' => 'boolean',
        'documents' => 'json',
    ];

    public function stockReceives(): HasMany
    {
        return $this->hasMany(StockReceive::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function purchaseReturns(): HasMany
    {
        return $this->hasMany(PurchaseReturn::class);
    }

    public function purchasePayables(): HasMany
    {
        return $this->hasMany(PurchasePayable::class);
    }

    public function purchaseInvoices(): HasMany
    {
        return $this->hasMany(PurchaseInvoice::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('status', true)
            ->where('is_blocked', false);
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query
            ->where('status', false)
            ->where('is_blocked', false);
    }

    public function scopeBlocked(Builder $query): Builder
    {
        return $query->where('is_blocked', true);
    }

    public function scopeStatusKey(Builder $query, string $key): Builder
    {
        return match ($key) {
            'active'   => $query->where('status', true)->where('is_blocked', false),
            'inactive' => $query->where('status', false)->where('is_blocked', false),
            'blocked'  => $query->where('is_blocked', true),
            default    => $query,
        };
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term): void {
            foreach (['code', 'name', 'phone', 'alternate_phone', 'email', 'address', 'contact_person'] as $col) {
                $q->orWhere($col, 'like', "%{$term}%");
            }
        });
    }

    public function getStatusLabelAttribute(): string
    {
        if ((bool) $this->is_blocked) {
            return 'Blocked';
        }

        return (bool) $this->status ? 'Active' : 'Inactive';
    }

    public function getStatusKeyAttribute(): string
    {
        return strtolower($this->status_label);
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status_label) {
            'Active' => 'bg-green-100 text-green-700',
            'Blocked' => 'bg-rose-100 text-rose-700',
            default => 'bg-amber-100 text-amber-700',
        };
    }

    protected function alternatePhone(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value, array $attributes): ?string => $value ?: ($attributes['secondary_phone'] ?? null),
            set: function (?string $value): array {
                return [
                    'alternate_phone' => $value,
                    'secondary_phone' => $value,
                ];
            },
        );
    }

    protected function secondaryPhone(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value, array $attributes): ?string => $value ?: ($attributes['alternate_phone'] ?? null),
            set: function (?string $value): array {
                return [
                    'secondary_phone' => $value,
                    'alternate_phone' => $value,
                ];
            },
        );
    }
    public function purchaseFunds()
    {
        return $this->morphMany(PurchaseFund::class, 'receiver');
    }

    /**
     * All advance funds belonging to this supplier — i.e. every fund against one
     * of the supplier's purchase orders. Unlike purchaseFunds() (receiver morph),
     * this also includes advances routed through an employee (via_employee), since
     * the advance still belongs to the PO's supplier.
     */
    public function advanceFunds(): HasManyThrough
    {
        return $this->hasManyThrough(
            PurchaseFund::class,
            PurchaseOrder::class,
            'supplier_id',        // FK on purchase_orders → suppliers
            'purchase_order_id',  // FK on purchase_funds → purchase_orders
            'id',                 // local key on suppliers
            'id'                  // local key on purchase_orders
        );
    }

    /** Banking payment requests sourced to this supplier (e.g. advances). */
    public function bankingRequests()
    {
        return $this->morphMany(BankingPaymentRequest::class, 'sourceable');
    }

    /** Advance payment requests sourced to this supplier. */
    public function advanceRequests()
    {
        return $this->morphMany(BankingPaymentRequest::class, 'sourceable')
            ->where('source_type', \App\Enums\Accounts\TransactionType::ADVANCE->value);
    }

    /**
     * Posted advance transactions for this supplier (the ledger source of truth).
     * The BankingPaymentRequest is only the request handler; the actual advance
     * lives in `transactions` keyed by reference_type/reference_id.
     */
    public function advanceTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'reference_id')
            ->where('transactions.reference_type', static::class)
            ->where('transactions.type', \App\Enums\Accounts\TransactionType::ADVANCE->value);
    }

    /**
     * Remaining (unadjusted) supplier advance still available to apply to invoices.
     * = total advance debit movement − everything already adjusted against it.
     */
    public function advanceRemaining(): float
    {
        return (float) $this->advanceTransactions()
            ->with(['lines:id,transaction_id,debit', 'advanceAdjustmentsGiven:id,advance_transaction_id,amount'])
            ->get()
            ->sum(fn (Transaction $t) => $t->remainingAdvance());
    }

    /** Total outstanding payable across this supplier's invoices. */
    public function totalDue(): float
    {
        return (float) $this->purchaseInvoices()->sum('due_amount');
    }
public function getAccountsAttribute()
{
    return Account::query()
        ->whereHas('referenceLinks', function ($query) {
            $query->where('reference_key', 'supplier');
        })
        ->get();
}
}
