<?php

namespace App\Models;

use App\Enums\Inventory\StockConsumptionStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockConsumption extends Model
{
    use HasFactory;

    protected $fillable = [
        'consumption_no',
        'consumption_date',
        'store_id',
        'project_id',
        'remarks',
        'status',
        'created_by',
        'posted_by',
        'posted_at',
    ];

    protected $casts = [
        'consumption_date' => 'date',
        'posted_at' => 'datetime',
        'status' => StockConsumptionStatus::class,
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
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
        return $this->hasMany(StockConsumptionItem::class);
    }

    public function scopePosted(Builder $query): Builder
    {
        return $query->where('status', StockConsumptionStatus::POSTED->value);
    }
}
