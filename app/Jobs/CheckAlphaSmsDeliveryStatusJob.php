<?php

namespace App\Jobs;

use App\Models\Message;
use App\Services\Sms\Providers\AlphaSmsProvider;
use App\Services\Sms\Providers\BulkSmsDhakaProvider;
use App\Services\Sms\Providers\SslWirelessProvider;
use App\Services\Sms\Providers\TwilioProvider;
use App\Services\Sms\Providers\VonageProvider;
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

        if (!$message || $message->type !== 'sms' || !$message->provider_message_id) {
            return;
        }

        $this->updateMessageDeliveryStatus($message);
    }

    private function checkPendingMessages(): void
    {
        $messages = Message::where('type', 'sms')
            ->where('status', 'sent')
            ->whereNotNull('provider_message_id')
            ->whereNotNull('sms_provider')
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
            if (!$message->sms_provider || !$message->provider_message_id) {
                return;
            }

            $gateway = \App\Models\SmsGateway::where('provider', $message->sms_provider)->first();
            if (!$gateway) {
                return;
            }

            $provider = $this->getProvider($message->sms_provider, $gateway->credentials);
            if (!$provider) {
                return;
            }

            $result = $provider->checkDeliveryStatus($message->provider_message_id);

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
                    'provider' => $message->sms_provider,
                    'message_id' => $message->provider_message_id,
                ]);
            } elseif ($status === 'failed') {
                $message->update([
                    'status' => 'failed',
                    'last_status_check' => now(),
                ]);
                $message->addTimelineEvent('failed', [
                    'via' => 'sms',
                    'provider' => $message->sms_provider,
                    'reason' => 'Delivery failed per provider report',
                ]);
            } else {
                $message->update(['last_status_check' => now()]);
            }
        } catch (\Throwable $e) {
            \Log::error('CheckSmsDeliveryStatusJob error', [
                'message_id' => $message->id,
                'provider' => $message->sms_provider ?? 'unknown',
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function getProvider(string $providerName, array $credentials)
    {
        return match($providerName) {
            'ssl_wireless'     => new SslWirelessProvider($credentials),
            'twilio'           => new TwilioProvider($credentials),
            'vonage'           => new VonageProvider($credentials),
            'bulk_sms_dhaka'   => new BulkSmsDhakaProvider($credentials),
            'alpha_sms'        => new AlphaSmsProvider($credentials),
            default            => null,
        };
    }

    public function failed(\Throwable $e): void
    {
        \Log::error('CheckSmsDeliveryStatusJob failed', [
            'message_id' => $this->messageId,
            'error' => $e->getMessage(),
        ]);
    }
}
