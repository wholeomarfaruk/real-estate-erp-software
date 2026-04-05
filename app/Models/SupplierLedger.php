<?php

namespace App\Models;

use App\Enums\Supplier\SupplierLedgerTransactionType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierLedger extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'transaction_date',
        'transaction_type',
        'reference_type',
        'reference_id',
        'reference_no',
        'description',
        'debit',
        'credit',
        'balance',
        'status',
        'created_by',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'transaction_type' => SupplierLedgerTransactionType::class,
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
        'balance' => 'decimal:2',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeForSupplier(Builder $query, ?int $supplierId): Builder
    {
        return $query->when($supplierId, fn (Builder $builder): Builder => $builder->where('supplier_id', $supplierId));
    }

    public function getTransactionTypeLabelAttribute(): string
    {
        return $this->transaction_type?->label() ?? str((string) $this->transaction_type)->replace('_', ' ')->title();
    }
}
