<?php

namespace App\Services\Sms\Providers;

use App\Services\Sms\SmsProviderInterface;
use Illuminate\Support\Facades\Http;

class VonageProvider implements SmsProviderInterface
{
    public function __construct(private array $credentials) {}

    public function send(string $to, string $message): array
    {
        try {
            $response = Http::timeout(10)->post('https://rest.nexmo.com/sms/json', [
                'api_key'    => $this->credentials['api_key'],
                'api_secret' => $this->credentials['api_secret'],
                'to'         => $to,
                'from'       => $this->credentials['from'],
                'text'       => $message,
            ]);

            if ($response->successful()) {
                return ['success' => true, 'response' => $response->json()];
            }

            return ['success' => false, 'error' => 'Vonage API error: ' . $response->status()];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
