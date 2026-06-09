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

        if (!$gateway) {
            return ['success' => false, 'error' => 'No active SMS gateway configured'];
        }

        $driver = match($gateway->provider) {
            'bulk_sms_dhaka' => new BulkSmsDhakaProvider($gateway->credentials),
            'alpha_sms'      => new AlphaSmsProvider($gateway->credentials),
            default          => null,
        };

        if (!$driver) {
            return ['success' => false, 'error' => "Unknown SMS provider: {$gateway->provider}"];
        }

        return $driver->send($to, $body);
    }
}
