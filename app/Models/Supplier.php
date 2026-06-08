<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
    public function purchaseFunds()
    {
        return $this->morphMany(PurchaseFund::class, 'receiver');
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
