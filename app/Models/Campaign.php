<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Campaign extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'description', 'type', 'audience_id', 'template_id',
        'schedule_type', 'scheduled_at', 'status', 'stats',
        'created_by', 'updated_by', 'started_at', 'completed_at',
    ];

    protected $casts = [
        'stats'        => 'array',
        'scheduled_at' => 'datetime',
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function audience(): BelongsTo
    {
        return $this->belongsTo(MarketingAudience::class, 'audience_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(CommunicationTemplate::class, 'template_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'campaign_id');
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft'     => '#6B7280',
            'queued'    => '#3B82F6',
            'running'   => '#F59E0B',
            'completed' => '#10B981',
            'paused'    => '#8B5CF6',
            'failed'    => '#EF4444',
            default     => '#6B7280',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft'     => 'Draft',
            'queued'    => 'Queued',
            'running'   => 'Running',
            'completed' => 'Completed',
            'paused'    => 'Paused',
            'failed'    => 'Failed',
            default     => ucfirst($this->status),
        };
    }

    public function getSentCountAttribute(): int
    {
        return (int) ($this->stats['sent'] ?? $this->messages()->where('status', 'sent')->count());
    }

    public function getFailedCountAttribute(): int
    {
        return (int) ($this->stats['failed'] ?? $this->messages()->where('status', 'failed')->count());
    }
}
