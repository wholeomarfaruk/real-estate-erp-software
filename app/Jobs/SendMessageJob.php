<?php

namespace App\Jobs;

use App\Models\Message;
use App\Services\Sms\SmsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

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
                Mail::html($message->body, function ($mail) use ($message) {
                    $mail->to($message->recipient)
                         ->subject($message->subject ?: config('app.name') . ' Message')
                         ->from(config('mail.from.address'), config('mail.from.name'));
                });
            } else {
                $result = app(SmsService::class)->send($message->recipient, $message->body);
                if (!$result['success']) {
                    throw new \RuntimeException($result['error'] ?? 'SMS sending failed');
                }
            }

            $message->update([
                'status'  => 'sent',
                'sent_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $message->update([
                'status'            => 'failed',
                'provider_response' => ['error' => $e->getMessage()],
            ]);
        }
    }

    public function failed(\Throwable $e): void
    {
        Message::where('id', $this->messageId)->update([
            'status'            => 'failed',
            'provider_response' => ['error' => $e->getMessage()],
        ]);
    }
}
