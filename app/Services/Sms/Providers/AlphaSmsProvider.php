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
            $apiUrl = $this->credentials['api_url_send'] ?? 'https://api.sms.net.bd/sendsms';

            $response = Http::timeout(10)->post($apiUrl, [
                'api_key'    => $this->credentials['api_key'],
                'type'       => $this->credentials['type'] ?? 'text',
                'message'    => $message,
                'number'     => $to,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['status']) && $data['status'] === 'success') {
                    return [
                        'success' => true,
                        'response' => array_merge($data, ['provider' => 'alpha_sms']),
                        'id' => $data['message_id'] ?? $data['msg_id'] ?? $data['id'] ?? null,
                    ];
                }

                if ($response->status() === 200 && isset($data['message_id'])) {
                    return [
                        'success' => true,
                        'response' => array_merge($data, ['provider' => 'alpha_sms']),
                        'id' => $data['message_id'],
                    ];
                }

                $error = $data['message'] ?? $data['error'] ?? 'Alpha SMS API error';
                return ['success' => false, 'error' => $error];
            }

            $error = $response->json()['message'] ?? $response->json()['error'] ?? 'Alpha SMS API error: ' . $response->status();
            return ['success' => false, 'error' => $error];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function checkBalance(): array
    {
        try {
            $apiUrl = $this->credentials['api_url_balance'] ?? 'https://api.sms.net.bd/user/balance/';

            $response = Http::timeout(10)->get($apiUrl, [
                'api_key' => $this->credentials['api_key'],
            ]);

            if ($response->successful()) {
                $data = $response->json();

                \Log::info('Alpha SMS balance API response', [
                    'status_code' => $response->status(),
                    'response' => $data,
                ]);

                if (isset($data['balance'])) {
                    return [
                        'success' => true,
                        'balance' => $data['balance'],
                        'currency' => $data['currency'] ?? 'TK',
                        'response' => $data,
                    ];
                }

                if (isset($data['data']['balance'])) {
                    return [
                        'success' => true,
                        'balance' => $data['data']['balance'],
                        'currency' => $data['currency'] ?? $data['data']['currency'] ?? 'TK',
                        'response' => $data,
                    ];
                }

                if (isset($data['amount'])) {
                    return [
                        'success' => true,
                        'balance' => $data['amount'],
                        'currency' => $data['currency'] ?? 'TK',
                        'response' => $data,
                    ];
                }

                if (isset($data['user']['balance'])) {
                    return [
                        'success' => true,
                        'balance' => $data['user']['balance'],
                        'currency' => $data['currency'] ?? 'TK',
                        'response' => $data,
                    ];
                }

                return ['success' => false, 'error' => 'API returned unexpected response. Check logs.'];
            }

            $error = $response->json()['message'] ?? $response->json()['error'] ?? 'Balance check failed';
            return ['success' => false, 'error' => $error];
        } catch (\Throwable $e) {
            \Log::error('Alpha SMS balance check exception', [
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'error' => 'Connection error. Please try again.'];
        }
    }
}
