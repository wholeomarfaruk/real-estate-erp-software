<?php

namespace App\Services\Sms;

interface SmsProviderInterface
{
    public function send(string $to, string $message): array;
    public function checkDeliveryStatus(string $messageId): array;
}
