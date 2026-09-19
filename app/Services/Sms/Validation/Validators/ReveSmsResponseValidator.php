<?php

namespace App\Services\Sms\Validation\Validators;

use App\Enums\Sms\SmsErrorCode;
use App\Services\Sms\Validation\SmsValidationResult;

class ReveSmsResponseValidator
{
    public function validate(array $response): SmsValidationResult
    {
        if (!array_key_exists('Status', $response)) {
            return SmsValidationResult::failure(
                SmsErrorCode::VALIDATION_MISSING_SUCCESS_FIELD,
                'Status field missing from response'
            );
        }

        $status = (string) $response['Status'];

        if ($status !== '0') {
            $text = $response['Text'] ?? 'Unknown error';
            return SmsValidationResult::failure(
                SmsErrorCode::VALIDATION_ERROR_CODE_PRESENT,
                "REVE SMS status {$status}: {$text}"
            );
        }

        if (!isset($response['Message_ID'])) {
            return SmsValidationResult::failure(
                SmsErrorCode::VALIDATION_MISSING_MESSAGE_ID,
                'Message_ID not found in response'
            );
        }

        return SmsValidationResult::success((string) $response['Message_ID']);
    }
}
