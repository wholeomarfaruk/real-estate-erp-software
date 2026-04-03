<?php

namespace App\Models;

use App\Enums\Inventory\TransferStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransferTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transfer_no',
        'sender_store_id',
        'receiver_store_id',
        'transfer_date',
        'status',
        'requested_by',
        'approved_by',
        'received_by',
        'requested_at',
        'approved_at',
        'received_at',
        'remarks',
    ];

    protected $casts = [
        'transfer_date' => 'date',
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'received_at' => 'datetime',
        'status' => TransferStatus::class,
    ];

    public function senderStore(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'sender_store_id');
    }

    public function receiverStore(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'receiver_store_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransferItem::class);
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', TransferStatus::DRAFT->value);
    }

    public function scopeRequested(Builder $query): Builder
    {
        return $query->where('status', TransferStatus::REQUESTED->value);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', TransferStatus::APPROVED->value);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', TransferStatus::COMPLETED->value);
    }

    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', TransferStatus::CANCELLED->value);
    }
}
