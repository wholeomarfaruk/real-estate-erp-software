<?php

namespace App\Enums\Sms;

enum SmsErrorCode: string
{
    case VALIDATION_MISSING_SUCCESS_FIELD = '1001';
    case VALIDATION_MISSING_MESSAGE_ID = '1002';
    case VALIDATION_FAILED_STATUS = '1003';
    case VALIDATION_INVALID_RESPONSE_STRUCTURE = '1004';
    case VALIDATION_ERROR_CODE_PRESENT = '1005';
    case VALIDATION_VONAGE_INVALID_STATUS = '1006';
    case VALIDATION_RESPONSE_NOT_JSON = '1007';

    public function description(): string
    {
        return match($this) {
            self::VALIDATION_MISSING_SUCCESS_FIELD => 'Required success indicator missing',
            self::VALIDATION_MISSING_MESSAGE_ID => 'Message ID not found in response',
            self::VALIDATION_FAILED_STATUS => 'Provider indicates delivery failed',
            self::VALIDATION_INVALID_RESPONSE_STRUCTURE => 'Response structure does not match expected format',
            self::VALIDATION_ERROR_CODE_PRESENT => 'Provider error code indicates failure',
            self::VALIDATION_VONAGE_INVALID_STATUS => 'Vonage status code is not 0 (success)',
            self::VALIDATION_RESPONSE_NOT_JSON => 'Cannot parse response as JSON',
        };
    }
}
