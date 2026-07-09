<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Services\Notification\NotificationAudienceResolver;
use App\Services\Notification\NotificationDispatcher;
use Illuminate\Console\Command;

class ProcessScheduledNotifications extends Command
{
    protected $signature   = 'notifications:process-scheduled';
    protected $description = 'Send notifications whose scheduled_at time has arrived';

    public function handle(NotificationAudienceResolver $resolver, NotificationDispatcher $dispatcher): int
    {
        $due = Notification::where('status', 'scheduled')
            ->where('scheduled_at', '<=', now())
            ->get();

        foreach ($due as $notification) {
            $recipients = $resolver->resolve($notification->audience_type ?? '', $notification->audience_value ?? []);
            $dispatcher->dispatchExisting($notification, $recipients, queue: true);
        }

        $this->info("Sent {$due->count()} scheduled notification(s).");

        return self::SUCCESS;
    }
}
