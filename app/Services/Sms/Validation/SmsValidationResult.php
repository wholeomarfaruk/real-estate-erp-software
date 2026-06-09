<?php

namespace App\Services\Sms\Validation;

use App\Enums\Sms\SmsErrorCode;

class SmsValidationResult
{
    public function __construct(
        public readonly bool $isValid,
        public readonly string $messageId = '',
        public readonly ?SmsErrorCode $errorCode = null,
        public readonly string $errorMessage = '',
    ) {}

    public static function success(string $messageId): self
    {
        return new self(isValid: true, messageId: $messageId);
    }

    public static function failure(SmsErrorCode $errorCode, string $errorMessage): self
    {
        return new self(
            isValid: false,
            errorCode: $errorCode,
            errorMessage: $errorMessage
        );
    }
}
