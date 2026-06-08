<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmTask extends Model
{
    protected $fillable = [
        'title', 'description', 'type', 'priority', 'status',
        'due_at', 'completed_at', 'related_type', 'related_id',
        'assigned_to', 'created_by',
    ];

    protected $casts = [
        'due_at'       => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isOverdue(): bool
    {
        return $this->due_at && $this->due_at->isPast() && $this->status !== 'done';
    }

    public function getPriorityColorAttribute(): string
    {
        return match ($this->priority) {
            'urgent' => '#DC2626',
            'high'   => '#F59E0B',
            'medium' => '#3B82F6',
            'low'    => '#6B7280',
            default  => '#6B7280',
        };
    }
}
