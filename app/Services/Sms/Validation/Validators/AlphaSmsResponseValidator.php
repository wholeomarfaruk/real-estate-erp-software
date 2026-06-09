<?php

namespace App\Services\Sms\Validation\Validators;

use App\Enums\Sms\SmsErrorCode;
use App\Services\Sms\Validation\SmsValidationResult;

class AlphaSmsResponseValidator
{
    public function validate(array $response): SmsValidationResult
    {
        if (!isset($response['error'])) {
            return SmsValidationResult::failure(
                SmsErrorCode::VALIDATION_MISSING_SUCCESS_FIELD,
                'Error field missing from response'
            );
        }

        $errorCode = $response['error'];

        if ($errorCode != 0) {
            return SmsValidationResult::failure(
                SmsErrorCode::VALIDATION_ERROR_CODE_PRESENT,
                "API error code {$errorCode}: " . ($response['msg'] ?? 'Unknown error')
            );
        }

        if (!isset($response['data']['request_id'])) {
            return SmsValidationResult::failure(
                SmsErrorCode::VALIDATION_MISSING_MESSAGE_ID,
                'request_id not found in data'
            );
        }

        return SmsValidationResult::success($response['data']['request_id']);
    }
}
