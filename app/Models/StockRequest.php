<?php

namespace App\Models;

use App\Enums\Inventory\StockRequestPriority;
use App\Enums\Inventory\StockRequestStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_no',
        'request_date',
        'requester_store_id',
        'source_store_id',
        'project_id',
        'requested_by',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'fulfilled_by',
        'fulfilled_at',
        'status',
        'priority',
        'remarks',
    ];

    protected $casts = [
        'request_date' => 'date',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'fulfilled_at' => 'datetime',
        'status' => StockRequestStatus::class,
        'priority' => StockRequestPriority::class,
    ];

    public function requesterStore(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'requester_store_id');
    }

    public function sourceStore(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'source_store_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejecter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function fulfiller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'fulfilled_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockRequestItem::class);
    }

    public function transferLinks(): HasMany
    {
        return $this->hasMany(StockRequestTransferLink::class);
    }

    public function transfers(): BelongsToMany
    {
        return $this->belongsToMany(TransferTransaction::class, 'stock_request_transfer_links')
            ->withTimestamps();
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', StockRequestStatus::DRAFT->value);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', StockRequestStatus::PENDING->value);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->whereIn('status', [
            StockRequestStatus::APPROVED->value,
            StockRequestStatus::PARTIALLY_FULFILLED->value,
            StockRequestStatus::FULFILLED->value,
        ]);
    }
}
