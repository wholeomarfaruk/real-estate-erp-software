<?php

namespace App\Jobs;

use App\Models\Message;
use App\Services\Sms\Providers\AlphaSmsProvider;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CheckAlphaSmsDeliveryStatusJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(public readonly ?int $messageId = null) {}

    public function handle(): void
    {
        if ($this->messageId) {
            $this->checkSingleMessage($this->messageId);
        } else {
            $this->checkPendingMessages();
        }
    }

    private function checkSingleMessage(int $messageId): void
    {
        $message = Message::find($messageId);

        if (!$message || $message->type !== 'sms' || !$message->alpha_request_id) {
            return;
        }

        $this->updateMessageDeliveryStatus($message);
    }

    private function checkPendingMessages(): void
    {
        $messages = Message::where('type', 'sms')
            ->where('status', 'sent')
            ->whereNotNull('alpha_request_id')
            ->where(function ($query) {
                $query->whereNull('last_status_check')
                    ->orWhere('last_status_check', '<', now()->subMinutes(5));
            })
            ->limit(50)
            ->get();

        foreach ($messages as $message) {
            $this->updateMessageDeliveryStatus($message);
        }
    }

    private function updateMessageDeliveryStatus(Message $message): void
    {
        try {
            $gateway = $message->gateway ?? null;
            if (!$gateway || $gateway->provider !== 'alpha_sms') {
                $gateway = \App\Models\SmsGateway::where('provider', 'alpha_sms')->first();
                if (!$gateway) {
                    return;
                }
            }

            $provider = new AlphaSmsProvider($gateway->credentials);
            $result = $provider->checkDeliveryStatus($message->alpha_request_id);

            if (!$result['success']) {
                $message->update(['last_status_check' => now()]);
                return;
            }

            $status = $result['status'] ?? 'sent';

            if ($status === 'delivered') {
                $message->update([
                    'status' => 'delivered',
                    'delivered_at' => now(),
                    'last_status_check' => now(),
                ]);
                $message->addTimelineEvent('delivered', [
                    'via' => 'sms',
                    'provider' => 'alpha_sms',
                    'request_id' => $message->alpha_request_id,
                ]);
            } elseif ($status === 'failed') {
                $message->update([
                    'status' => 'failed',
                    'last_status_check' => now(),
                ]);
                $message->addTimelineEvent('failed', [
                    'via' => 'sms',
                    'provider' => 'alpha_sms',
                    'reason' => 'Delivery failed per Alpha SMS report',
                ]);
            } else {
                $message->update(['last_status_check' => now()]);
            }
        } catch (\Throwable $e) {
            \Log::error('CheckAlphaSmsDeliveryStatusJob error', [
                'message_id' => $message->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function failed(\Throwable $e): void
    {
        \Log::error('CheckAlphaSmsDeliveryStatusJob failed', [
            'message_id' => $this->messageId,
            'error' => $e->getMessage(),
        ]);
    }
}
