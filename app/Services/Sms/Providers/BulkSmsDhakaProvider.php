<?php

namespace App\Services\Sms\Providers;

use App\Services\Sms\SmsProviderInterface;
use Illuminate\Support\Facades\Http;

class BulkSmsDhakaProvider implements SmsProviderInterface
{
    public function __construct(private array $credentials) {}

    public function send(string $to, string $message): array
    {
        try {
            $response = Http::timeout(10)->post('https://bulksmsdhaka.com/api/sms/send', [
                'api_token'  => $this->credentials['api_token'],
                'sender_id'  => $this->credentials['sender_id'] ?? 'SenderId',
                'recipients' => $to,
                'message'    => $message,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['success']) && $data['success'] === true && isset($data['message_id'])) {
                    return [
                        'success' => true,
                        'response' => array_merge($data, ['provider' => 'bulk_sms_dhaka']),
                        'id' => $data['message_id'],
                    ];
                }

                if (isset($data['message_id']) && !isset($data['success'])) {
                    return [
                        'success' => true,
                        'response' => array_merge($data, ['provider' => 'bulk_sms_dhaka']),
                        'id' => $data['message_id'],
                    ];
                }

                $error = $data['message'] ?? $data['error'] ?? 'No message_id in response';
                return ['success' => false, 'error' => "BulkSMS Dhaka: {$error}"];
            }

            $error = $response->json()['message'] ?? $response->json()['error'] ?? 'API request failed';
            return ['success' => false, 'error' => "BulkSMS Dhaka HTTP Error {$response->status()}: {$error}"];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function checkDeliveryStatus(string $messageId): array
    {
        try {
            $response = Http::timeout(10)->post(
                'https://bulksmsdhaka.com/api/sms/status',
                [
                    'api_token' => $this->credentials['api_token'],
                    'message_id' => $messageId,
                ]
            );

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['status'])) {
                    $status = strtolower($data['status']);

                    if ($status === 'delivered') {
                        return [
                            'success' => true,
                            'status' => 'delivered',
                            'response' => $data,
                        ];
                    } elseif ($status === 'failed' || $status === 'bounced') {
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

            return ['success' => false, 'error' => 'BulkSMS Dhaka status check failed: ' . $response->status()];
        } catch (\Throwable $e) {
            \Log::error('BulkSMS Dhaka delivery status check exception', [
                'message_id' => $messageId,
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
