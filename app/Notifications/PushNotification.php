<?php

namespace App\Notifications;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class PushNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly \App\Models\Notification $notification) {}

    public function via($notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush($notifiable, $notification): WebPushMessage
    {
        $message = (new WebPushMessage)
            ->title($this->notification->title)
            ->body($this->notification->body)
            ->icon('/assets/logo/sud-logo.png')
            ->data(['action_url' => $this->notification->action_url]);

        if (in_array($this->notification->badge, ['high', 'urgent'], true)) {
            $message->requireInteraction();
        }

        return $message;
    }
}
