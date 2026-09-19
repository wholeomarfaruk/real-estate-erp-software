<?php

namespace App\Services\Sms\Providers;

use App\Exceptions\SmsValidationException;
use App\Services\Sms\SmsProviderInterface;
use App\Services\Sms\Validation\Validators\ReveSmsResponseValidator;
use Illuminate\Support\Facades\Http;

/**
 * REVE SMS (smpp.revesms.com) HTTP API driver.
 *
 * Endpoint/parameter names come from REVE's published HTTP API (apikey,
 * secretkey, callerID, toUser, messageContent) — verify against the account's
 * own "HTTP Configuration" panel before relying on this in production, since
 * REVE is white-labelled per reseller and the submit URL can be account-specific.
 */
class ReveSmsProvider implements SmsProviderInterface
{
    public function __construct(private array $credentials) {}

    public function send(string $to, string $message): array
    {
        try {
            $apiUrl = $this->credentials['submit_url'] ?? 'https://smpp.revesms.com/smsapi/sendtext';

            $phone = ltrim($to, '+');

            $payload = [
                'apikey'         => $this->credentials['api_key'],
                'secretkey'      => $this->credentials['secret_key'],
                'callerID'       => $this->credentials['sender_id'] ?? '',
                'toUser'         => $phone,
                'messageContent' => $message,
            ];

            \Log::info('REVE SMS send request', [
                'url' => $apiUrl,
                'payload' => array_merge($payload, ['apikey' => '***', 'secretkey' => '***']),
            ]);

            $response = Http::timeout(10)->withoutVerifying()->get($apiUrl, $payload);

            \Log::info('REVE SMS send API response', [
                'status_code' => $response->status(),
                'response' => $response->json() ?? $response->body(),
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if (!is_array($data)) {
                    return ['success' => false, 'error' => 'REVE SMS: Non-JSON response received'];
                }

                try {
                    $validator = new ReveSmsResponseValidator();
                    $result = $validator->validate($data);

                    if ($result->isValid) {
                        return [
                            'success' => true,
                            'response' => array_merge($data, ['provider' => 'reve_sms']),
                            'id' => $result->messageId,
                        ];
                    }

                    throw new SmsValidationException(
                        $result->errorCode,
                        'reve_sms',
                        $result->errorMessage,
                        $data
                    );
                } catch (SmsValidationException $e) {
                    \Log::warning('REVE SMS validation failed: ' . $e->getFullError());
                    return ['success' => false, 'error' => $e->getFullError()];
                }
            }

            $error = $response->json()['Text'] ?? $response->json()['Message'] ?? 'API request failed';
            return ['success' => false, 'error' => "REVE SMS HTTP Error {$response->status()}: {$error}"];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function checkDeliveryStatus(string $messageId): array
    {
        try {
            $apiUrl = $this->credentials['status_url'] ?? 'https://smpp.revesms.com/smsapi/status';

            $response = Http::timeout(10)->withoutVerifying()->get($apiUrl, [
                'apikey'    => $this->credentials['api_key'],
                'secretkey' => $this->credentials['secret_key'],
                'messageID' => $messageId,
            ]);

            \Log::info('REVE SMS delivery status check', [
                'message_id' => $messageId,
                'status_code' => $response->status(),
                'response' => $response->json() ?? $response->body(),
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if (!is_array($data)) {
                    return ['success' => false, 'error' => 'REVE SMS: Non-JSON status response received'];
                }

                $text = strtoupper((string) ($data['Text'] ?? ''));

                if ($text === 'DELIVRD') {
                    return ['success' => true, 'status' => 'delivered', 'response' => $data];
                }

                if (in_array($text, ['REJECTD', 'UNDELIV', 'EXPIRED'], true)) {
                    return ['success' => true, 'status' => 'failed', 'response' => $data];
                }

                if ($text === 'PENDING') {
                    return ['success' => true, 'status' => 'pending', 'response' => $data];
                }

                return ['success' => true, 'status' => 'sent', 'response' => $data];
            }

            return ['success' => false, 'error' => 'REVE SMS status check failed: ' . $response->status()];
        } catch (\Throwable $e) {
            \Log::error('REVE SMS delivery status check exception', [
                'message_id' => $messageId,
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
