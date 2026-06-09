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
                $data = $response->json();
                return [
                    'success' => true,
                    'response' => array_merge($data, ['provider' => 'vonage']),
                    'id' => $data['messages'][0]['message-id'] ?? $data['messageId'] ?? null,
                ];
            }

            return ['success' => false, 'error' => 'Vonage API error: ' . $response->status()];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function checkDeliveryStatus(string $messageId): array
    {
        try {
            $response = Http::timeout(10)->post(
                'https://rest.nexmo.com/sms/search/message',
                [
                    'api_key'    => $this->credentials['api_key'],
                    'api_secret' => $this->credentials['api_secret'],
                    'id'         => $messageId,
                ]
            );

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['items']) && !empty($data['items'][0])) {
                    $item = $data['items'][0];
                    $status = isset($item['final-status']) ? strtolower($item['final-status']) : null;

                    if ($status === 'delivered') {
                        return [
                            'success' => true,
                            'status' => 'delivered',
                            'response' => $data,
                        ];
                    } elseif ($status === 'failed' || $status === 'rejected') {
                        return [
                            'success' => true,
                            'status' => 'failed',
                            'response' => $data,
                        ];
                    }
                }

                return [
                    'success' => true,
                    'status' => 'sent',
                    'response' => $data,
                ];
            }

            return ['success' => false, 'error' => 'Vonage status check failed: ' . $response->status()];
        } catch (\Throwable $e) {
            \Log::error('Vonage delivery status check exception', [
                'message_id' => $messageId,
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
