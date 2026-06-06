<?php

namespace App\Models;

use App\Enums\Projects\EstimateStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectEstimate extends Model
{
    protected $fillable = [
        'project_id',
        'estimate_no',
        'title',
        'version',
        'estimate_date',
        'status',
        'total_estimated_amount',
        'notes',
        'attachments',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'estimate_date'          => 'date',
        'approved_at'            => 'datetime',
        'attachments'            => 'array',
        'total_estimated_amount' => 'decimal:2',
        'status'                 => EstimateStatus::class,
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(EstimateItem::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function getTotalCostAttribute(): float
    {
        return (float) $this->items->sum('estimated_amount');
    }

    public function isLocked(): bool
    {
        return $this->status === EstimateStatus::APPROVED;
    }

    public function totalByType(string $costType): float
    {
        return (float) $this->items->where('cost_type', $costType)->sum('estimated_amount');
    }
}
