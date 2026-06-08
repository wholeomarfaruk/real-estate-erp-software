<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $fillable = [
        'type', 'campaign_id', 'automation_id', 'member_type', 'member_id',
        'recipient', 'subject', 'body', 'status', 'provider_response', 'sent_at', 'sent_by',
    ];

    protected $casts = [
        'provider_response' => 'array',
        'sent_at'           => 'datetime',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class, 'campaign_id');
    }

    public function sentByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function getMemberAttribute(): Lead|Customer|null
    {
        return match ($this->member_type) {
            'lead'     => Lead::find($this->member_id),
            'customer' => Customer::find($this->member_id),
            default    => null,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'sent'      => '#10B981',
            'delivered' => '#3B82F6',
            'queued'    => '#F59E0B',
            'failed'    => '#EF4444',
            'opened'    => '#8B5CF6',
            default     => '#6B7280',
        };
    }
}
