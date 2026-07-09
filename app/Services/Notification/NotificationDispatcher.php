<?php

namespace App\Services\Notification;

use App\Enums\Notification\NotificationType;
use App\Models\Notification;
use App\Models\NotificationRecipient;
use App\Models\NotificationSetting;
use App\Notifications\PushNotification;
use Illuminate\Database\Eloquent\Model;

class NotificationDispatcher
{
    public function sendToOne(
        Model $recipient,
        NotificationType $type,
        string $title,
        string $body,
        ?string $actionUrl = null,
        ?string $badge = null,
    ): ?Notification {
        $settings = NotificationSetting::current();

        if (! $settings->isTypeEnabled($type)) {
            return null;
        }

        $notification = Notification::create([
            'type'       => $type,
            'title'      => $title,
            'body'       => $body,
            'badge'      => $badge,
            'action_url' => $actionUrl,
            'status'     => 'sent',
            'sent_at'    => now(),
        ]);

        $this->deliverTo($notification, [$recipient]);

        return $notification;
    }

    public function sendToMany(
        iterable $recipients,
        NotificationType $type,
        string $title,
        string $body,
        ?string $actionUrl = null,
        ?string $badge = null,
        int $chunkSize = 500,
    ): ?Notification {
        $settings = NotificationSetting::current();

        if (! $settings->isTypeEnabled($type)) {
            return null;
        }

        $notification = Notification::create([
            'type'       => $type,
            'title'      => $title,
            'body'       => $body,
            'badge'      => $badge,
            'action_url' => $actionUrl,
            'status'     => 'sent',
            'sent_at'    => now(),
        ]);

        $this->deliverTo($notification, $recipients, $chunkSize);

        return $notification;
    }

    /**
     * Create recipient rows for an already-persisted notification and deliver
     * push, then mark it sent. Used for drafts/scheduled sends, where the
     * Notification row exists before its audience is resolved.
     *
     * $queue is false (synchronous) for manual "Send Now" so it doesn't
     * depend on a queue worker being alive; the scheduled cron command
     * passes true since it already runs in the background.
     */
    public function dispatchExisting(Notification $notification, iterable $recipients, bool $queue = false): void
    {
        $this->deliverTo($notification, $recipients, queue: $queue);

        $notification->update(['status' => 'sent', 'sent_at' => now()]);
    }

    private function deliverTo(Notification $notification, iterable $recipients, int $chunkSize = 500, bool $queue = false): void
    {
        $settings = NotificationSetting::current();

        $now = now();
        $rows = [];
        $users = [];

        foreach ($recipients as $recipient) {
            $rows[] = [
                'notification_id' => $notification->id,
                'recipient_type'  => $recipient::class,
                'recipient_id'    => $recipient->getKey(),
                'is_read'         => false,
                'read_at'         => null,
                'created_at'      => $now,
                'updated_at'      => $now,
            ];

            if ($settings->web_push_enabled && $user = $recipient->notifyingUser()) {
                $users[$user->getKey()] = $user;
            }

            if (count($rows) >= $chunkSize) {
                NotificationRecipient::insert($rows);
                $rows = [];
            }
        }

        if ($rows) {
            NotificationRecipient::insert($rows);
        }

        foreach (array_chunk($users, $chunkSize, true) as $chunk) {
            foreach ($chunk as $user) {
                if ($queue) {
                    $user->notify(new PushNotification($notification));
                } else {
                    $user->notifyNow(new PushNotification($notification));
                }
            }
        }
    }
}
