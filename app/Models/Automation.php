<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Automation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'description', 'trigger_event', 'action_type',
        'template_id', 'delay_minutes', 'conditions', 'status',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'conditions'    => 'array',
        'delay_minutes' => 'integer',
    ];

    public const TRIGGER_EVENTS = [
        'lead.created'         => 'Lead Created',
        'lead.status_changed'  => 'Lead Status Changed',
        'lead.converted'       => 'Lead Converted to Customer',
        'followup.scheduled'   => 'Follow-up Scheduled',
        'followup.completed'   => 'Follow-up Completed',
        'booking.created'      => 'Booking Created',
        'payment.due'          => 'Payment Due',
    ];

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
        return $this->hasMany(Message::class, 'automation_id');
    }

    public function getTriggerLabelAttribute(): string
    {
        return static::TRIGGER_EVENTS[$this->trigger_event] ?? $this->trigger_event;
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
    }
}
