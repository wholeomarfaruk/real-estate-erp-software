<?php

namespace App\Services\Sms\Providers;

use App\Services\Sms\SmsProviderInterface;
use Illuminate\Support\Facades\Http;

class AlphaSmsProvider implements SmsProviderInterface
{
    public function __construct(private array $credentials) {}

    public function send(string $to, string $message): array
    {
        try {
            // Alpha SMS supports both masking and non-masking API
            // Using non-masking API endpoint
            $response = Http::timeout(10)->post('https://api.sms.bd/sendsms', [
                'api_key'    => $this->credentials['api_key'],
                'type'       => $this->credentials['type'] ?? 'text', // text or unicode
                'message'    => $message,
                'number'     => $to,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                // Alpha SMS returns success with various response formats
                // Check for common response indicators
                if (isset($data['status']) && $data['status'] === 'success') {
                    return [
                        'success' => true,
                        'response' => array_merge($data, ['provider' => 'alpha_sms']),
                        'id' => $data['message_id'] ?? $data['msg_id'] ?? $data['id'] ?? null,
                    ];
                }

                // Also handle if response code is 200 without explicit status field
                if ($response->status() === 200 && isset($data['message_id'])) {
                    return [
                        'success' => true,
                        'response' => array_merge($data, ['provider' => 'alpha_sms']),
                        'id' => $data['message_id'],
                    ];
                }

                // Error in response
                $error = $data['message'] ?? $data['error'] ?? 'Alpha SMS API error';
                return ['success' => false, 'error' => $error];
            }

            // HTTP error
            $error = $response->json()['message'] ?? $response->json()['error'] ?? 'Alpha SMS API error: ' . $response->status();
            return ['success' => false, 'error' => $error];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
