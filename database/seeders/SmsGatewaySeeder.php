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
                    'website'      => 'https://www.twilio.com',
                    'dashboard'    => 'https://console.twilio.com',
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
                    'website'      => 'https://www.sslwireless.com',
                    'dashboard'    => 'https://sms.sslwireless.com/account/dashboard',
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
                    'website'    => 'https://www.vonage.com',
                    'dashboard'  => 'https://dashboard.nexmo.com',
                ],
                'is_active'   => false,
                'created_by'  => 1,
                'updated_by'  => 1,
            ]
        );

        // Test BulkSMS Dhaka Gateway
        SmsGateway::firstOrCreate(
            ['name' => 'Test BulkSMS Dhaka'],
            [
                'provider'    => 'bulk_sms_dhaka',
                'credentials' => [
                    'api_token' => env('BULKSMS_DHAKA_API_TOKEN', 'demo_api_token'),
                    'sender_id' => env('BULKSMS_DHAKA_SENDER_ID', 'DemoSender'),
                    'website'   => 'https://bulksmsdhaka.com',
                    'dashboard' => 'https://bulksmsdhaka.com/account/dashboard',
                ],
                'is_active'   => false,
                'created_by'  => 1,
                'updated_by'  => 1,
            ]
        );

        // Test Alpha SMS Gateway
        SmsGateway::firstOrCreate(
            ['name' => 'Test Alpha SMS'],
            [
                'provider'    => 'alpha_sms',
                'credentials' => [
                    'api_key'        => env('ALPHA_SMS_API_KEY', 'demo_api_key'),
                    'type'           => env('ALPHA_SMS_TYPE', 'text'),
                    'api_url_send'   => env('ALPHA_SMS_API_URL_SEND', 'https://api.sms.net.bd/sendsms'),
                    'api_url_balance' => env('ALPHA_SMS_API_URL_BALANCE', 'https://api.sms.net.bd/user/balance/'),
                    'website'        => 'https://sms.bd',
                    'dashboard'      => 'https://portal.sms.net.bd/login/',
                ],
                'is_active'   => false,
                'created_by'  => 1,
                'updated_by'  => 1,
            ]
        );
    }
}
