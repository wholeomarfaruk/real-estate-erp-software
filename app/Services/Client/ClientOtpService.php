<?php

namespace App\Services\Client;

use App\Models\ClientOtpVerification;
use App\Models\User;
use App\Services\Mail\MailService;
use App\Services\Sms\SmsService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ClientOtpService
{
    /** OTP lifetime in minutes. */
    public const EXPIRY_MINUTES = 5;

    /** Max verify attempts before a code is burned. */
    public const MAX_ATTEMPTS = 5;

    /** Minimum seconds between (re)sends for a single verification. */
    public const RESEND_COOLDOWN_SECONDS = 60;

    public function __construct(
        private readonly SmsService $sms,
        private readonly MailService $mail,
    ) {
    }

    /**
     * Create a fresh OTP for the user, deliver it over the given channel,
     * and return the verification record.
     *
     * @param  'sms'|'email'  $channel
     */
    public function start(User $user, string $channel): ClientOtpVerification
    {
        $destination = $channel === 'email'
            ? $user->email
            : trim(($user->country_code ?? '') . $user->phone);

        if (blank($destination)) {
            throw new RuntimeException("The account has no {$channel} destination on file.");
        }

        $code = $this->generateCode();

        $verification = ClientOtpVerification::create([
            'user_id'      => $user->id,
            'channel'      => $channel,
            'destination'  => $destination,
            'otp_hash'     => Hash::make($code),
            'attempts'     => 0,
            'expires_at'   => now()->addMinutes(self::EXPIRY_MINUTES),
            'last_sent_at' => now(),
        ]);

        $this->deliver($verification, $code);

        return $verification;
    }

    /**
     * Re-issue a new code for an existing, still-pending verification.
     */
    public function resend(ClientOtpVerification $verification): ClientOtpVerification
    {
        if ($verification->isVerified()) {
            throw new RuntimeException('This verification has already been completed.');
        }

        if ($verification->last_sent_at
            && $verification->last_sent_at->diffInSeconds(now()) < self::RESEND_COOLDOWN_SECONDS) {
            $wait = self::RESEND_COOLDOWN_SECONDS - $verification->last_sent_at->diffInSeconds(now());
            throw new RuntimeException("Please wait {$wait} seconds before requesting a new code.");
        }

        $code = $this->generateCode();

        $verification->update([
            'otp_hash'     => Hash::make($code),
            'attempts'     => 0,
            'expires_at'   => now()->addMinutes(self::EXPIRY_MINUTES),
            'last_sent_at' => now(),
        ]);

        $this->deliver($verification, $code);

        return $verification;
    }

    /**
     * Validate a submitted code against the verification.
     * Returns true on success (and stamps verified_at); false otherwise.
     */
    public function verify(ClientOtpVerification $verification, string $code): bool
    {
        if ($verification->isVerified()) {
            throw new RuntimeException('This code has already been used.');
        }

        if ($verification->isExpired()) {
            throw new RuntimeException('This code has expired. Please request a new one.');
        }

        if ($verification->attempts >= self::MAX_ATTEMPTS) {
            throw new RuntimeException('Too many incorrect attempts. Please request a new code.');
        }

        if (! Hash::check($code, $verification->otp_hash)) {
            $verification->increment('attempts');

            return false;
        }

        $verification->update(['verified_at' => now()]);

        return true;
    }

    private function generateCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    private function deliver(ClientOtpVerification $verification, string $code): void
    {
        if ($verification->channel === 'email') {
            $this->mail->send(
                $verification->destination,
                'Your login verification code',
                $this->emailBody($code),
            );

            return;
        }

        $result = $this->sms->send(
            $verification->destination,
            "Your login verification code is {$code}. It expires in " . self::EXPIRY_MINUTES . ' minutes.',
        );

        if (! ($result['success'] ?? false)) {
            Log::error('Client OTP SMS failed', ['to' => $verification->destination, 'result' => $result]);
            throw new RuntimeException($result['error'] ?? 'Failed to send the verification SMS.');
        }
    }

    private function emailBody(string $code): string
    {
        return <<<HTML
            <div style="font-family:Arial,sans-serif;max-width:480px;margin:auto">
                <h2 style="color:#111">Login verification</h2>
                <p>Use the following one-time code to complete your login:</p>
                <p style="font-size:32px;font-weight:bold;letter-spacing:6px;color:#111">{$code}</p>
                <p style="color:#666">This code expires in {$this->expiryMinutes()} minutes. If you did not try to log in, you can ignore this email.</p>
            </div>
            HTML;
    }

    private function expiryMinutes(): int
    {
        return self::EXPIRY_MINUTES;
    }
}
