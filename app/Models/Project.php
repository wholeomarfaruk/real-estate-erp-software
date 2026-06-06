<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Project extends Model
{
    protected $fillable = [
        'name',
        'code',
        'project_type',
        'location',
        'land_area',
        'building_area',
        'start_date',
        'end_date',
        'handover_date',
        'budget',
        'status',
        'progress_pct',
        'description',
        'documents',
        'image',
        'chief_engineer_id',
        'site_engineer_id',
        'created_by',
    ];

    protected $casts = [
        'start_date'    => 'date',
        'end_date'      => 'date',
        'handover_date' => 'date',
        'budget'        => 'decimal:2',
        'land_area'     => 'decimal:2',
        'building_area' => 'decimal:2',
        'progress_pct'  => 'integer',
        'documents'    => 'array',
        'project_type' => 'array',
        'status'       => \App\Enums\Project\Status::class,
    ];

    /** Returns display labels for all project types */
    public function typeLabels(): array
    {
        if (empty($this->project_type)) {
            return [];
        }
        return array_map(
            fn($v) => \App\Enums\Project\Type::tryFrom($v)?->label() ?? ucfirst($v),
            (array) $this->project_type
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function siteEngineer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'site_engineer_id');
    }

    public function chiefEngineer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'chief_engineer_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function floors(): HasMany
    {
        return $this->hasMany(Floor::class);
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    public function planning(): HasMany
    {
        return $this->hasMany(ProjectPlanning::class);
    }

    public function estimates(): HasMany
    {
        return $this->hasMany(ProjectEstimate::class);
    }

    public function timelinePhases(): HasMany
    {
        return $this->hasMany(TimelinePhase::class);
    }

    public function stores(): HasMany
    {
        return $this->hasMany(Store::class);
    }

    public function stockConsumptions(): HasMany
    {
        return $this->hasMany(StockConsumption::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function engineers()
    {
        return $this->belongsToMany(User::class, 'engineer_projects', 'project_id', 'user_id');
    }

    /** Expense banking requests linked via the sourceable morph (request holders). */
    public function expenseRequests(): MorphMany
    {
        return $this->morphMany(BankingPaymentRequest::class, 'sourceable')
            ->where('source_type', 'expense');
    }

    /**
     * Actual posted project expenses live in the transactions ledger as single entries
     * (type=expense) that reference this project directly with the amount on the credit side
     * (money leaving the account).
     */
    public function expenseTransactions()
    {
        return Transaction::query()
            ->where('type', 'expense')
            ->where('credit', '>', 0)
            ->where('reference_type', self::class)
            ->where('reference_id', $this->id);
    }

    public function daysToHandover(): ?int
    {
        if (!$this->handover_date) {
            return null;
        }
        return max(0, now()->startOfDay()->diffInDays($this->handover_date->startOfDay(), false));
    }

    /** Actual money spent = posted expense transactions (not pending requests). */
    public function totalSpent(): float
    {
        return (float) $this->expenseTransactions()->sum('credit');
    }

    public function approvedEstimate(): ?ProjectEstimate
    {
        return $this->estimates()->where('status', 'approved')->latest('version')->first();
    }
}
