<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\Message;
use App\Services\Mail\MailService;
use App\Services\Sms\SmsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendMessageJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public readonly int $messageId) {}

    public function handle(): void
    {
        $message = Message::find($this->messageId);

        if (! $message) return;

        try {
            if ($message->type === 'email') {
                $subject = $message->subject ?: config('app.name') . ' Message';

                app(MailService::class)->send(
                    $message->recipient,
                    $subject,
                    $message->body,
                );

                $message->update([
                    'status'  => 'sent',
                    'sent_at' => now(),
                ]);
                $message->addTimelineEvent('sent', ['via' => 'email']);
            } else {
                $result = app(SmsService::class)->send($message->recipient, $message->body);
                if (!$result['success']) {
                    throw new \RuntimeException($result['error'] ?? 'SMS sending failed');
                }

                $gateway = \App\Models\SmsGateway::where('is_active', true)->first();
                $provider = $gateway?->provider ?? 'unknown';

                $messageId = $result['response']['id']
                    ?? $result['response']['message_id']
                    ?? $result['response']['MessageID']
                    ?? $result['response']['sid']
                    ?? $result['id']
                    ?? null;

                $updateData = [
                    'status'              => 'sent',
                    'sent_at'             => now(),
                    'external_id'         => $messageId,
                    'provider_response'   => $result['response'],
                    'sms_provider'        => $provider,
                    'provider_message_id' => $messageId,
                ];

                if ($provider === 'alpha_sms') {
                    $updateData['alpha_request_id'] = $result['response']['data']['request_id'] ?? $result['id'] ?? null;
                    $updateData['alpha_payload'] = [
                        'recipient' => $message->recipient,
                        'body'      => $message->body,
                        'timestamp' => now()->toIso8601String(),
                    ];
                }

                $message->update($updateData);
                $message->addTimelineEvent('sent', [
                    'via'      => 'sms',
                    'provider' => $provider,
                ]);
            }
        } catch (\Throwable $e) {
            $message->update([
                'status'            => 'failed',
                'provider_response' => ['error' => $e->getMessage()],
            ]);
            $message->addTimelineEvent('failed', ['error' => $e->getMessage()]);
        } finally {
            $this->maybeCompleteCampaign($message);
        }
    }

    public function failed(\Throwable $e): void
    {
        $message = Message::find($this->messageId);
        if ($message) {
            $message->update([
                'status'            => 'failed',
                'provider_response' => ['error' => $e->getMessage()],
            ]);
            $this->maybeCompleteCampaign($message);
        }
    }

    private function maybeCompleteCampaign(Message $message): void
    {
        if (! $message->campaign_id) return;

        $campaign = Campaign::find($message->campaign_id);
        if (! $campaign || $campaign->status !== 'running') return;

        $pending = Message::where('campaign_id', $campaign->id)
            ->where('status', 'queued')
            ->exists();

        if ($pending) return;

        $sent   = Message::where('campaign_id', $campaign->id)->where('status', 'sent')->count();
        $failed = Message::where('campaign_id', $campaign->id)->where('status', 'failed')->count();
        $total  = Message::where('campaign_id', $campaign->id)->count();

        $campaign->update([
            'status'       => 'completed',
            'completed_at' => now(),
            'stats'        => ['sent' => $sent, 'failed' => $failed, 'total' => $total],
        ]);
    }
}
