<?php

namespace App\Models;

use App\Enums\Inventory\StockAdjustmentStatus;
use App\Enums\Inventory\StockAdjustmentType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockAdjustment extends Model
{
    use HasFactory;

    protected $fillable = [
        'adjustment_no',
        'adjustment_date',
        'store_id',
        'adjustment_type',
        'reason',
        'remarks',
        'status',
        'created_by',
        'posted_by',
        'posted_at',
    ];

    protected $casts = [
        'adjustment_date' => 'date',
        'adjustment_type' => StockAdjustmentType::class,
        'status' => StockAdjustmentStatus::class,
        'posted_at' => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockAdjustmentItem::class);
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', StockAdjustmentStatus::DRAFT->value);
    }

    public function scopePosted(Builder $query): Builder
    {
        return $query->where('status', StockAdjustmentStatus::POSTED->value);
    }

    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', StockAdjustmentStatus::CANCELLED->value);
    }
}
