<?php

namespace App\Services\Sms\Providers;

use App\Services\Sms\SmsProviderInterface;
use Illuminate\Support\Facades\Http;

class SslWirelessProvider implements SmsProviderInterface
{
    public function __construct(private array $credentials) {}

    public function send(string $to, string $message): array
    {
        try {
            $response = Http::timeout(10)->post($this->credentials['api_url'], [
                'user'   => $this->credentials['api_user'],
                'pass'   => $this->credentials['api_password'],
                'sms'    => $message,
                'sid'    => $this->credentials['sid'] ?? '',
                'msisdn' => $to,
            ]);

            if ($response->successful()) {
                return ['success' => true, 'response' => $response->json()];
            }

            return ['success' => false, 'error' => 'SSL Wireless API error: ' . $response->status()];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
