<?php

namespace App\Services\Sms;

use App\Models\SmsGateway;
use App\Services\Sms\Providers\SslWirelessProvider;
use App\Services\Sms\Providers\TwilioProvider;
use App\Services\Sms\Providers\VonageProvider;

class SmsService
{
    public function send(string $to, string $body): array
    {
        $gateway = SmsGateway::where('is_active', true)->first();

        if (!$gateway) {
            return ['success' => false, 'error' => 'No active SMS gateway configured'];
        }

        $driver = match($gateway->provider) {
            'ssl_wireless' => new SslWirelessProvider($gateway->credentials),
            'twilio'       => new TwilioProvider($gateway->credentials),
            'vonage'       => new VonageProvider($gateway->credentials),
        };

        return $driver->send($to, $body);
    }
}
