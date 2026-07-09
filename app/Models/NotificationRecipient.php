<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class NotificationRecipient extends Model
{
    protected $fillable = ['notification_id', 'recipient_type', 'recipient_id', 'is_read', 'read_at'];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function notification(): BelongsTo
    {
        return $this->belongsTo(Notification::class);
    }

    public function recipient(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('is_read', false);
    }

    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $query) use ($user) {
            $query->where(function (Builder $query) use ($user) {
                $query->where('recipient_type', User::class)
                    ->where('recipient_id', $user->id);
            });

            if ($customerId = $user->customer?->id) {
                $query->orWhere(function (Builder $query) use ($customerId) {
                    $query->where('recipient_type', Customer::class)
                        ->where('recipient_id', $customerId);
                });
            }

            if ($employeeId = $user->employee?->id) {
                $query->orWhere(function (Builder $query) use ($employeeId) {
                    $query->where('recipient_type', Employee::class)
                        ->where('recipient_id', $employeeId);
                });
            }
        });
    }
}
