<?php

namespace App\Services\Client;

use App\Models\Customer;
use App\Models\NotificationRecipient;

class ClientNotificationCounts
{
    /**
     * unread_notices stays a placeholder until the Notice system exists.
     */
    public static function unread(?Customer $customer): array
    {
        $user = $customer?->user;

        return [
            'unread_notifications' => $user ? NotificationRecipient::forUser($user)->unread()->count() : 0,
            'unread_notices'       => 2,
        ];
    }
}
