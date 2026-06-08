<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code', 'name', 'contact_person', 'phone', 'alternate_phone',
        'email', 'address', 'trade_license_no', 'tin_no', 'bin_no',
        'status', 'is_blocked', 'notes', 'image_id', 'cover_image_id',
        'documents', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'status'     => 'boolean',
        'is_blocked' => 'boolean',
        'documents'  => 'array',   // ARRAY OF FILE IDs ONLY — e.g. [40192, 40193]
    ];

    /* ───────────────────────── Code auto-generation ───────────────────────── */
    protected static function booted(): void
    {
        static::creating(function (Supplier $supplier) {
            if (blank($supplier->code)) {
                $last = static::withTrashed()->max('id') ?? 0;
                $supplier->code = 'SUP-' . str_pad($last + 1, 6, '0', STR_PAD_LEFT);
            }
        });
    }

    /* ───────────────────────────── Relationships ──────────────────────────── */
    public function purchaseOrders(): HasMany    { return $this->hasMany(PurchaseOrder::class); }
    public function purchaseInvoices(): HasMany  { return $this->hasMany(PurchaseInvoice::class); }
    public function purchaseReturns(): HasMany   { return $this->hasMany(PurchaseReturn::class); }
    public function stockReceives(): HasMany     { return $this->hasMany(StockReceive::class); }
    public function stockMovements(): HasMany    { return $this->hasMany(StockMovement::class); }
    public function purchasePayables(): HasMany  { return $this->hasMany(PurchasePayable::class); }
    public function purchaseFunds(): MorphMany   { return $this->morphMany(PurchaseFund::class, 'fundable'); }

    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function updater(): BelongsTo { return $this->belongsTo(User::class, 'updated_by'); }

    /* ───────────────────────────── Computed state ─────────────────────────── */

    // Active / Inactive / Blocked
    public function getStatusLabelAttribute(): string
    {
        if ($this->is_blocked) return 'Blocked';
        return $this->status ? 'Active' : 'Inactive';
    }

    // matches the UI pill key (active|inactive|blocked) used in the blade
    public function getStatusKeyAttribute(): string
    {
        return strtolower($this->status_label);
    }

    // Tailwind badge classes (per spec)
    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status_key) {
            'active'   => 'bg-green-100 text-green-700',
            'inactive' => 'bg-amber-100 text-amber-700',
            'blocked'  => 'bg-rose-100 text-rose-700',
        };
    }

    /*
     | Balance = advance − due  (net position with this supplier)
     |   balance < 0  → we owe them (PAYABLE / overdue)
     |   balance > 0  → we paid ahead (ADVANCE held by supplier)
     |   balance == 0 → settled
     |
     | Derive from your ledger. Cheapest path is a single aggregate on
     | purchase_payables (positive = due, negative = advance). Adjust to
     | match your actual schema. Prefer eager-loading withSum() in the
     | Livewire query instead of calling this per-row (N+1).
     */
    public function getBalanceAttribute(): float
    {
        // advance - due. Example using purchasePayables (amount: + = due, - = advance):
        return (float) ($this->purchasePayables()->sum('advance_amount')
                      - $this->purchasePayables()->sum('due_amount'));
    }

    public function getTotalInvoicesAttribute(): int
    {
        // Prefer ->loadCount('purchaseInvoices') / withCount() in the list query.
        return (int) ($this->purchase_invoices_count
            ?? $this->purchaseInvoices()->count());
    }

    public function getUnpaidInvoicesAttribute(): int
    {
        return (int) ($this->unpaid_invoices_count
            ?? $this->purchaseInvoices()->where('status', '!=', 'paid')->count());
    }

    /* ───────────────────────────── Query scopes ───────────────────────────── */

    public function scopeStatusKey($query, string $key)
    {
        return match ($key) {
            'active'   => $query->where('status', true)->where('is_blocked', false),
            'inactive' => $query->where('status', false)->where('is_blocked', false),
            'blocked'  => $query->where('is_blocked', true),
            default    => $query, // 'all'
        };
    }

    public function scopeSearch($query, ?string $term)
    {
        if (blank($term)) return $query;

        return $query->where(function ($q) use ($term) {
            foreach (['code', 'name', 'phone', 'email', 'address', 'contact_person'] as $col) {
                $q->orWhere($col, 'like', "%{$term}%");
            }
        });
    }
}
