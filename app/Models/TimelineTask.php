<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimelineTask extends Model
{
    protected $fillable = [
        'timeline_phase_id',
        'name',
        'start_date',
        'end_date',
        'status',
        'progress_percentage',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'progress_percentage' => 'decimal:2',
    ];

    public function phase(): BelongsTo
    {
        return $this->belongsTo(TimelinePhase::class, 'timeline_phase_id');
    }
}
