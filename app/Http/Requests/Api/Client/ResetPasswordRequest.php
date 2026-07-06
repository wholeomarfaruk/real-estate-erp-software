<?php

namespace App\Http\Requests\Api\Client;

use App\Actions\Fortify\PasswordValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    use PasswordValidationRules;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'login'    => ['required', 'string'],   // email or phone number
            'code'     => ['required', 'string', 'digits:6'],
            'password' => $this->passwordRules(),
        ];
    }
}
