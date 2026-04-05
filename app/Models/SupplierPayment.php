<?php

namespace App\Models;

use App\Enums\Supplier\SupplierPaymentMethod;
use App\Enums\Supplier\SupplierPaymentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupplierPayment extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'supplier_id',
        'payment_no',
        'payment_date',
        'payment_method',
        'account_name',
        'account_reference',
        'reference_no',
        'transaction_no',
        'cheque_no',
        'remarks',
        'total_amount',
        'allocated_amount',
        'unallocated_amount',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'payment_method' => SupplierPaymentMethod::class,
        'total_amount' => 'decimal:2',
        'allocated_amount' => 'decimal:2',
        'unallocated_amount' => 'decimal:2',
        'status' => SupplierPaymentStatus::class,
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(SupplierPaymentAllocation::class)->orderBy('id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNotIn('status', [
            SupplierPaymentStatus::DRAFT->value,
            SupplierPaymentStatus::CANCELLED->value,
        ]);
    }

    public function canEdit(): bool
    {
        return $this->status === SupplierPaymentStatus::DRAFT;
    }

    public function canCancel(): bool
    {
        return $this->status !== SupplierPaymentStatus::CANCELLED;
    }

    public function resolveStatus(bool $preserveDraft = false): SupplierPaymentStatus
    {
        if ($this->status === SupplierPaymentStatus::CANCELLED) {
            return SupplierPaymentStatus::CANCELLED;
        }

        if ($preserveDraft) {
            return SupplierPaymentStatus::DRAFT;
        }

        $allocated = round(max(0, (float) $this->allocated_amount), 2);
        $unallocated = round(max(0, (float) $this->unallocated_amount), 2);

        if ($allocated <= 0) {
            return SupplierPaymentStatus::POSTED;
        }

        if ($unallocated <= 0) {
            return SupplierPaymentStatus::FULLY_ALLOCATED;
        }

        return SupplierPaymentStatus::PARTIAL_ALLOCATED;
    }

    public function syncAmountsAndStatus(bool $preserveDraft = false): void
    {
        $totalAmount = round(max(0, (float) $this->total_amount), 2);
        $allocatedAmount = round(max(0, (float) $this->allocated_amount), 2);
        $allocatedAmount = min($allocatedAmount, $totalAmount);
        $unallocatedAmount = round(max(0, $totalAmount - $allocatedAmount), 2);

        $this->total_amount = $totalAmount;
        $this->allocated_amount = $allocatedAmount;
        $this->unallocated_amount = $unallocatedAmount;
        $this->status = $this->resolveStatus($preserveDraft)->value;
    }
}
