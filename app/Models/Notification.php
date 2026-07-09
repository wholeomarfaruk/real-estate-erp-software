<?php

namespace App\Models;

use App\Enums\Notification\NotificationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Notification extends Model
{
    protected $fillable = [
        'type', 'title', 'body', 'badge', 'action_url',
        'status', 'audience_type', 'audience_value', 'scheduled_at', 'sent_at', 'created_by',
    ];

    protected $casts = [
        'type'           => NotificationType::class,
        'audience_value' => 'array',
        'scheduled_at'   => 'datetime',
        'sent_at'        => 'datetime',
    ];

    public function recipients(): HasMany
    {
        return $this->hasMany(NotificationRecipient::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
