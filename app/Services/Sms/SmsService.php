<?php

namespace App\Services\Sms;

use App\Models\SmsGateway;
use App\Services\Sms\Providers\AlphaSmsProvider;
use App\Services\Sms\Providers\BulkSmsDhakaProvider;

class SmsService
{
    public function send(string $to, string $body): array
    {
        $gateway = SmsGateway::where('is_active', true)->first();
        \Log::info("Attempting to send SMS to {$to} via gateway: " . ($gateway?->provider ?? 'none'));

        if (!$gateway) {
            return ['success' => false, 'error' => 'No active SMS gateway configured'];
        }

        $driver = match($gateway->provider) {
            'bulk_sms_dhaka' => new BulkSmsDhakaProvider($gateway->credentials),
            'alpha_sms'      => new AlphaSmsProvider($gateway->credentials),
            default          => null,
        };
        \Log::info("Initialized SMS driver for provider: {$gateway->provider}", ['driver_class' => get_class($driver)]);

        if (!$driver) {
            return ['success' => false, 'error' => "Unknown SMS provider: {$gateway->provider}"];
        }

        \Log::info("Sending SMS to {$to} via driver: " . get_class($driver));
        return $driver->send($to, $body);
    }
}
