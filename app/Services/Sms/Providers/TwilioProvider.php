<?php

namespace App\Services\Sms\Providers;

use App\Services\Sms\SmsProviderInterface;
use Illuminate\Support\Facades\Http;

class TwilioProvider implements SmsProviderInterface
{
    public function __construct(private array $credentials) {}

    public function send(string $to, string $message): array
    {
        try {
            $auth = base64_encode($this->credentials['account_sid'] . ':' . $this->credentials['auth_token']);

            $response = Http::timeout(10)
                ->withHeaders(['Authorization' => 'Basic ' . $auth])
                ->asForm()
                ->post('https://api.twilio.com/2010-04-01/Accounts/' . $this->credentials['account_sid'] . '/Messages.json', [
                    'Body' => $message,
                    'From' => $this->credentials['from_number'],
                    'To'   => $to,
                ]);

            if ($response->successful()) {
                return ['success' => true, 'response' => $response->json()];
            }

            return ['success' => false, 'error' => 'Twilio API error: ' . $response->status()];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
