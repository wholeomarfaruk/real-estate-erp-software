<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Project extends Model
{
    protected $fillable = [
        'name',
        'code',
        'project_type',
        'location',
        'start_date',
        'end_date',
        'budget',
        'status',
        'description',
        'documents',
        'image',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'budget' => 'decimal:2',
        'documents' => 'array',
        'status' => \App\Enums\Project\Status::class,
        'project_type'=> \App\Enums\Project\Type::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
}
