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
                $data = $response->json();
                return [
                    'success' => true,
                    'response' => array_merge($data, ['provider' => 'ssl_wireless']),
                    'id' => $data['MessageID'] ?? $data['msg_id'] ?? null,
                ];
            }

            return ['success' => false, 'error' => 'SSL Wireless API error: ' . $response->status()];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function checkDeliveryStatus(string $messageId): array
    {
        try {
            $response = Http::timeout(10)->post(
                $this->credentials['api_url'] . '/status',
                [
                    'user'       => $this->credentials['api_user'],
                    'pass'       => $this->credentials['api_password'],
                    'MessageID'  => $messageId,
                ]
            );

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['Status'])) {
                    $status = strtolower($data['Status']);

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

            return ['success' => false, 'error' => 'SSL Wireless status check failed: ' . $response->status()];
        } catch (\Throwable $e) {
            \Log::error('SSL Wireless delivery status check exception', [
                'message_id' => $messageId,
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
