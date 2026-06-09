<?php

namespace App\Http\Controllers\Api;

use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class WebhookController extends Controller
{
    public function sms(Request $request): Response
    {
        $provider = $request->query('provider', 'unknown');
        $payload = $request->all();

        \Log::info('SMS Webhook received', [
            'provider' => $provider,
            'payload'  => $payload,
        ]);

        try {
            $message = $this->findMessageByProvider($provider, $payload);

            if (!$message) {
                return response('Message not found', 404);
            }

            $this->updateMessageStatus($message, $provider, $payload);

            return response('OK', 200);
        } catch (\Throwable $e) {
            \Log::error('Webhook processing error: ' . $e->getMessage(), [
                'provider' => $provider,
                'payload'  => $payload,
            ]);

            return response('Error processing webhook', 500);
        }
    }

    private function findMessageByProvider(string $provider, array $payload): ?Message
    {
        return match ($provider) {
            'twilio'      => $this->findTwilioMessage($payload),
            'ssl_wireless' => $this->findSslWirelessMessage($payload),
            'vonage'       => $this->findVonageMessage($payload),
            default        => null,
        };
    }

    private function findTwilioMessage(array $payload): ?Message
    {
        $messageId = $payload['MessageSid'] ?? null;
        return $messageId ? Message::where('external_id', $messageId)->first() : null;
    }

    private function findSslWirelessMessage(array $payload): ?Message
    {
        $messageId = $payload['MessageID'] ?? $payload['msg_id'] ?? null;
        return $messageId ? Message::where('external_id', $messageId)->first() : null;
    }

    private function findVonageMessage(array $payload): ?Message
    {
        $messageId = $payload['messageId'] ?? $payload['message_id'] ?? null;
        return $messageId ? Message::where('external_id', $messageId)->first() : null;
    }

    private function updateMessageStatus(Message $message, string $provider, array $payload): void
    {
        $message->webhook_data = $payload;

        $status = match ($provider) {
            'twilio'       => $this->getTwilioStatus($payload),
            'ssl_wireless' => $this->getSslWirelessStatus($payload),
            'vonage'       => $this->getVonageStatus($payload),
            default        => null,
        };

        if ($status) {
            $message->status = $status;

            if ($status === 'delivered') {
                $message->delivered_at = now();
            }

            $message->save();
            $message->addTimelineEvent('webhook_received', [
                'provider' => $provider,
                'new_status' => $status,
            ]);
        }
    }

    private function getTwilioStatus(array $payload): ?string
    {
        $twilioStatus = $payload['MessageStatus'] ?? null;

        return match ($twilioStatus) {
            'queued'     => 'queued',
            'sending'    => 'sent',
            'sent'       => 'sent',
            'delivered'  => 'delivered',
            'failed'     => 'failed',
            'undelivered' => 'failed',
            default      => null,
        };
    }

    private function getSslWirelessStatus(array $payload): ?string
    {
        $status = $payload['Status'] ?? $payload['status'] ?? null;

        return match ($status) {
            'Successful' => 'delivered',
            'Sent'       => 'sent',
            'Failed'     => 'failed',
            'Pending'    => 'queued',
            default      => null,
        };
    }

    private function getVonageStatus(array $payload): ?string
    {
        $status = $payload['status'] ?? $payload['message-status'] ?? null;

        return match ($status) {
            '0' => 'delivered',
            '1' => 'failed',
            '2' => 'failed',
            '8' => 'queued',
            'success' => 'delivered',
            'failed'  => 'failed',
            default   => null,
        };
    }
}
