<?php

namespace Database\Seeders;

use App\Models\SmsGateway;
use Illuminate\Database\Seeder;

class SmsGatewaySeeder extends Seeder
{
    public function run(): void
    {
        // Test BulkSMS Dhaka Gateway (Active by default)
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
                'is_active'   => true,
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
                    'api_key'         => env('ALPHA_SMS_API_KEY', 'demo_api_key'),
                    'type'            => env('ALPHA_SMS_TYPE', 'text'),
                    'api_url_send'    => env('ALPHA_SMS_API_URL_SEND', 'https://api.sms.net.bd/sendsms'),
                    'api_url_balance' => env('ALPHA_SMS_API_URL_BALANCE', 'https://api.sms.net.bd/user/balance/'),
                    'website'         => 'https://sms.bd',
                    'dashboard'       => 'https://portal.sms.net.bd/login/',
                ],
                'is_active'   => false,
                'created_by'  => 1,
                'updated_by'  => 1,
            ]
        );
    }
}
