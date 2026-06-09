<?php

namespace Database\Seeders;

use App\Models\SmsGateway;
use Illuminate\Database\Seeder;

class SmsGatewaySeeder extends Seeder
{
    public function run(): void
    {
        // Test Twilio Gateway (Active by default)
        SmsGateway::firstOrCreate(
            ['name' => 'Test Twilio'],
            [
                'provider'    => 'twilio',
                'credentials' => [
                    'account_sid'  => env('TWILIO_ACCOUNT_SID', 'AC123456789abcdef'),
                    'auth_token'   => env('TWILIO_AUTH_TOKEN', 'your_auth_token_here'),
                    'from_number'  => env('TWILIO_FROM_NUMBER', '+1234567890'),
                ],
                'is_active'   => true,
                'created_by'  => 1,
                'updated_by'  => 1,
            ]
        );

        // Test SSL Wireless Gateway
        SmsGateway::firstOrCreate(
            ['name' => 'Test SSL Wireless'],
            [
                'provider'    => 'ssl_wireless',
                'credentials' => [
                    'api_url'      => env('SSL_WIRELESS_API_URL', 'https://api.ssl.com.bd/send-sms'),
                    'api_user'     => env('SSL_WIRELESS_USER', 'demo_user'),
                    'api_password' => env('SSL_WIRELESS_PASSWORD', 'demo_password'),
                    'sid'          => env('SSL_WIRELESS_SID', 'DemoSID'),
                ],
                'is_active'   => false,
                'created_by'  => 1,
                'updated_by'  => 1,
            ]
        );

        // Test Vonage Gateway
        SmsGateway::firstOrCreate(
            ['name' => 'Test Vonage'],
            [
                'provider'    => 'vonage',
                'credentials' => [
                    'api_key'    => env('VONAGE_API_KEY', 'demo_api_key'),
                    'api_secret' => env('VONAGE_API_SECRET', 'demo_api_secret'),
                    'from'       => env('VONAGE_FROM', 'Demo'),
                ],
                'is_active'   => false,
                'created_by'  => 1,
                'updated_by'  => 1,
            ]
        );
    }
}
