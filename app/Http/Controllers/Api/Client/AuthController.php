<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Client\ForgotPasswordRequest;
use App\Http\Requests\Api\Client\LoginRequest;
use App\Http\Requests\Api\Client\ResendOtpRequest;
use App\Http\Requests\Api\Client\ResetPasswordRequest;
use App\Http\Requests\Api\Client\VerifyOtpRequest;
use App\Models\ClientOtpVerification;
use App\Models\User;
use App\Services\Client\ClientOtpService;
use App\Services\Client\ClientPasswordResetService;
use App\Services\Client\ClientPayloadBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class AuthController extends Controller
{
    public function __construct(
        private readonly ClientOtpService $otp,
        private readonly ClientPasswordResetService $passwordResets,
    ) {
    }

    /**
     * Step 1 — validate credentials and dispatch an OTP.
     * No access token is issued here.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $login   = $request->string('login')->trim()->value();
        $channel = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'sms';

        $user = $this->resolveClient($login);

        // Uniform failure for unknown account or bad password (no user enumeration).
        if (! $user || ! Hash::check($request->string('password')->value(), (string) $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['The provided credentials are incorrect.'],
            ]);
        }

        try {
            $verification = $this->otp->start($user, $channel);
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }

        return response()->json([
            'success'         => true,
            'otp_required'    => true,
            'verification_id' => $verification->verification_id,
            'channel'         => $verification->channel,
            'destination'     => $this->maskDestination($verification),
            'expires_in'      => ClientOtpService::EXPIRY_MINUTES * 60,
            'message'         => 'A verification code has been sent.',
        ]);
    }

    /**
     * Self-service — send a password reset code to the account matching the
     * given email/phone. Always returns a generic success response so the
     * endpoint can't be used to enumerate which accounts exist.
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $login   = $request->string('login')->trim()->value();
        $channel = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'sms';

        $user = $this->resolveClient($login);

        if ($user) {
            try {
                $this->passwordResets->send($user, $channel);
            } catch (RuntimeException $e) {
                Log::error('Client forgot-password send failed', ['login' => $login, 'error' => $e->getMessage()]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'If an account matches, a password reset code has been sent.',
        ]);
    }

    /**
     * Complete a password reset using the code delivered by forgotPassword().
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $login = $request->string('login')->trim()->value();
        $user  = $this->resolveClient($login);

        // Same generic failure whether the account doesn't exist or the code is wrong.
        if (! $user) {
            return $this->error('The code you entered is incorrect.', 422);
        }

        try {
            $this->passwordResets->reset($user, $request->string('code')->value(), $request->string('password')->value());
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Your password has been reset. Please log in again.',
        ]);
    }

    /**
     * Step 2 — verify the OTP and issue an access token.
     */
    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $verification = $this->pendingVerification($request->string('verification_id')->value());

        try {
            $ok = $this->otp->verify($verification, $request->string('otp')->value());
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }

        if (! $ok) {
            return $this->error('The verification code is incorrect.', 422);
        }

        $user = $verification->user;

        // Mark the phone/email as verified now that the client proved ownership.
        if ($verification->channel === 'sms' && $user->phone_verified_at === null) {
            $user->forceFill(['phone_verified_at' => now()])->save();
        } elseif ($verification->channel === 'email' && $user->email_verified_at === null) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        $token = $user->createToken('client-app')->plainTextToken;

        return response()->json([
            'success'      => true,
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'user'         => ClientPayloadBuilder::user($user),
            'customer'     => ClientPayloadBuilder::customer($user->customer),
        ]);
    }

    /**
     * Re-send a new code for a still-pending verification.
     */
    public function resendOtp(ResendOtpRequest $request): JsonResponse
    {
        $verification = $this->pendingVerification($request->string('verification_id')->value());

        try {
            $this->otp->resend($verification);
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }

        return response()->json([
            'success'     => true,
            'destination' => $this->maskDestination($verification),
            'expires_in'  => ClientOtpService::EXPIRY_MINUTES * 60,
            'message'     => 'A new verification code has been sent.',
        ]);
    }

    /**
     * Revoke the token used for the current request.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
    }

    private function resolveClient(string $login): ?User
    {
        return User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'client'))
            ->where(fn ($q) => $q->where('email', $login)->orWhere('phone', $login))
            ->first();
    }

    private function pendingVerification(string $verificationId): ClientOtpVerification
    {
        $verification = ClientOtpVerification::where('verification_id', $verificationId)
            ->whereNull('verified_at')
            ->first();

        if (! $verification) {
            throw ValidationException::withMessages([
                'verification_id' => ['This verification session is invalid or already completed.'],
            ]);
        }

        return $verification;
    }

    private function maskDestination(ClientOtpVerification $verification): string
    {
        $value = $verification->destination;

        if ($verification->channel === 'email' && str_contains($value, '@')) {
            [$name, $domain] = explode('@', $value, 2);
            $visible = mb_substr($name, 0, 2);

            return $visible . str_repeat('*', max(mb_strlen($name) - 2, 1)) . '@' . $domain;
        }

        // Phone: keep last 3 digits visible.
        $len = mb_strlen($value);

        return $len <= 3 ? $value : str_repeat('*', $len - 3) . mb_substr($value, -3);
    }

    private function error(string $message, int $status): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], $status);
    }
}
