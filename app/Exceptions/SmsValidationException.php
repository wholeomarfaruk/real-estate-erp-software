<?php

namespace App\Exceptions;

use App\Enums\Sms\SmsErrorCode;
use Exception;

class SmsValidationException extends Exception
{
    public function __construct(
        public readonly SmsErrorCode $errorCode,
        public readonly string $provider,
        string $message = '',
        public readonly ?array $responseSnapshot = null,
    ) {
        if (!$message) {
            $message = $errorCode->description();
        }

        parent::__construct($message);
    }

    public function getFullError(): string
    {
        return "[{$this->errorCode->value}] {$this->provider}: {$this->message}";
    }
}
