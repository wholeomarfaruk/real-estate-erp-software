<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $fillable = [
        'type', 'campaign_id', 'automation_id', 'member_type', 'member_id',
        'recipient', 'subject', 'body', 'status', 'provider_response', 'sent_at', 'delivered_at',
        'sent_by', 'webhook_data', 'timeline', 'external_id', 'alpha_request_id', 'alpha_payload', 'last_status_check',
        'sms_provider', 'provider_message_id',
    ];

    protected $casts = [
        'provider_response' => 'array',
        'webhook_data'      => 'array',
        'timeline'          => 'array',
        'alpha_payload'     => 'array',
        'sent_at'           => 'datetime',
        'delivered_at'      => 'datetime',
        'last_status_check' => 'datetime',
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

    public function addTimelineEvent(string $event, array $data = []): void
    {
        $timeline = $this->timeline ?? [];
        $timeline[] = [
            'event'      => $event,
            'timestamp'  => now()->toIso8601String(),
            'data'       => $data,
        ];
        $this->update(['timeline' => $timeline]);
    }
}
