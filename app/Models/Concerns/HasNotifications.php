<?php

namespace App\Models\Concerns;

use App\Models\NotificationRecipient;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasNotifications
{
    public function notificationRecipients(): MorphMany
    {
        return $this->morphMany(NotificationRecipient::class, 'recipient');
    }

    public function unreadNotifications(): MorphMany
    {
        return $this->notificationRecipients()->where('is_read', false);
    }

    public function notifyingUser(): ?User
    {
        return $this instanceof User ? $this : $this->user;
    }
}
