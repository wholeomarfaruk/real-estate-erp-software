<?php

namespace App\Services\Client;

class ClientNotificationCounts
{
    /**
     * Placeholder until the Notice/News/Notification systems exist —
     * static values so the client app can wire up these fields now.
     */
    public static function unread(): array
    {
        return [
            'unread_notifications' => 3,
            'unread_notices'       => 2,
        ];
    }
}
