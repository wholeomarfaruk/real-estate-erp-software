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

                // BulkSMS Dhaka returns success with message_id in response
                return [
                    'success' => true,
                    'response' => array_merge($data, ['provider' => 'bulk_sms_dhaka']),
                    'id' => $data['message_id'] ?? $data['msg_id'] ?? $data['id'] ?? null,
                ];
            }

            // Handle error responses
            $error = $response->json()['message'] ?? $response->json()['error'] ?? 'BulkSMS Dhaka API error';
            return ['success' => false, 'error' => 'BulkSMS Dhaka API error: ' . $error];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
