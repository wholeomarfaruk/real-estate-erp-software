<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const OPENING_BALANCE_TYPE_PAYABLE = 'payable';

    public const OPENING_BALANCE_TYPE_ADVANCE = 'advance';

    protected $fillable = [
        'name',
        'code',
        'company_name',
        'contact_person',
        'phone',
        'alternate_phone',
        'secondary_phone',
        'email',
        'address',
        'trade_license_no',
        'tin_no',
        'bin_no',
        'opening_balance',
        'opening_balance_type',
        'payment_terms_days',
        'credit_limit',
        'is_blocked',
        'notes',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'status' => 'boolean',
        'is_blocked' => 'boolean',
        'opening_balance' => 'decimal:2',
        'payment_terms_days' => 'integer',
        'credit_limit' => 'decimal:2',
    ];

    public function stockReceives(): HasMany
    {
        return $this->hasMany(StockReceive::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function purchaseOrders(): BelongsToMany
    {
        return $this->belongsToMany(
            PurchaseOrder::class,
            'purchase_order_items',
            'supplier_id',
            'purchase_order_id'
        );
    }

    public function purchaseReturns(): HasMany
    {
        return $this->hasMany(PurchaseReturn::class);
    }

    public function supplierBills(): HasMany
    {
        return $this->hasMany(SupplierBill::class);
    }

    public function supplierPayments(): HasMany
    {
        return $this->hasMany(SupplierPayment::class);
    }

    public function supplierReturns(): HasMany
    {
        return $this->hasMany(SupplierReturn::class);
    }

    public function purchasePayables(): HasMany
    {
        return $this->hasMany(PurchasePayable::class);
    }

    public function supplierLedgers(): HasMany
    {
        return $this->hasMany(SupplierLedger::class);
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

    public function scopeWithCurrentDue(Builder $query): Builder
    {
        return $query
            ->select('suppliers.*')
            ->selectRaw(self::currentDueExpression().' as current_due');
    }

    public function scopeHasDue(Builder $query): Builder
    {
        return $query->whereRaw(self::currentDueExpression().' > 0');
    }

    public function scopeWithoutDue(Builder $query): Builder
    {
        return $query->whereRaw(self::currentDueExpression().' <= 0');
    }

    public static function currentDueExpression(): string
    {
        return "CASE WHEN opening_balance_type = '".self::OPENING_BALANCE_TYPE_PAYABLE."' THEN COALESCE(opening_balance, 0) ELSE 0 END";
    }

    public function getCurrentDueAttribute(): float
    {
        if (array_key_exists('current_due', $this->attributes)) {
            return round((float) $this->attributes['current_due'], 2);
        }

        if ($this->opening_balance_type !== self::OPENING_BALANCE_TYPE_PAYABLE) {
            return 0;
        }

        return round(max(0, (float) $this->opening_balance), 2);
    }

    public function getStatusLabelAttribute(): string
    {
        if ((bool) $this->is_blocked) {
            return 'Blocked';
        }

        return (bool) $this->status ? 'Active' : 'Inactive';
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
}
