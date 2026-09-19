<?php

namespace App\Services\Sms;

use App\Models\SmsGateway;
use App\Services\Sms\Providers\AlphaSmsProvider;
use App\Services\Sms\Providers\BulkSmsDhakaProvider;
use App\Services\Sms\Providers\ReveSmsProvider;

class SmsService
{
    /**
     * @param  SmsGateway|null  $gateway  Explicit gateway to send through (e.g. when
     *         resending via a different provider). Falls back to the active gateway.
     */
    public function send(string $to, string $body, ?SmsGateway $gateway = null): array
    {
        $gateway ??= SmsGateway::where('is_active', true)->first();
        \Log::info("Attempting to send SMS to {$to} via gateway: " . ($gateway?->provider ?? 'none'));

        if (!$gateway) {
            return ['success' => false, 'error' => 'No active SMS gateway configured'];
        }

        $driver = $this->resolveDriver($gateway->provider, $gateway->credentials);
        \Log::info("Initialized SMS driver for provider: {$gateway->provider}", ['driver_class' => $driver ? get_class($driver) : null]);

        if (!$driver) {
            return ['success' => false, 'error' => "Unknown SMS provider: {$gateway->provider}"];
        }

        \Log::info("Sending SMS to {$to} via driver: " . get_class($driver));

        $result = $driver->send($to, $body);
        $result['gateway_id'] = $gateway->id;
        $result['provider'] = $gateway->provider;

        return $result;
    }

    public function resolveDriver(string $providerName, array $credentials): ?SmsProviderInterface
    {
        return match($providerName) {
            'bulk_sms_dhaka' => new BulkSmsDhakaProvider($credentials),
            'alpha_sms'      => new AlphaSmsProvider($credentials),
            'reve_sms'       => new ReveSmsProvider($credentials),
            default          => null,
        };
    }
}
