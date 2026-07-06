<?php

namespace App\Services\Client;

use App\Models\ClientPasswordReset;
use App\Models\User;
use App\Services\Mail\MailService;
use App\Services\Sms\SmsService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ClientPasswordResetService
{
    /** Code lifetime in minutes. */
    public const EXPIRY_MINUTES = 15;

    /** Max verify attempts before a code is burned. */
    public const MAX_ATTEMPTS = 5;

    /** Minimum seconds between issuing new codes for a single user. */
    public const RESEND_COOLDOWN_SECONDS = 60;

    public function __construct(
        private readonly SmsService $sms,
        private readonly MailService $mail,
    ) {
    }

    /**
     * Issue a fresh reset code for the user and deliver it over the given channel.
     * The client app collects this code (plus the user's login and new password)
     * and submits them together to complete the reset — see reset().
     *
     * @param  'sms'|'email'  $channel
     */
    public function send(User $user, string $channel): ClientPasswordReset
    {
        $destination = $channel === 'email' ? $user->email : $user->phone;

        if (blank($destination)) {
            throw new RuntimeException("This account has no {$channel} on file.");
        }

        $recent = ClientPasswordReset::where('user_id', $user->id)->latest('id')->first();

        if ($recent && $recent->created_at->diffInSeconds(now()) < self::RESEND_COOLDOWN_SECONDS) {
            $wait = self::RESEND_COOLDOWN_SECONDS - $recent->created_at->diffInSeconds(now());
            throw new RuntimeException("Please wait {$wait} seconds before requesting another code.");
        }

        // Invalidate any previously issued, still-usable codes for this user.
        ClientPasswordReset::where('user_id', $user->id)
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $reset = ClientPasswordReset::create([
            'user_id'    => $user->id,
            'code_hash'  => Hash::make($code),
            'channel'    => $channel,
            'attempts'   => 0,
            'expires_at' => now()->addMinutes(self::EXPIRY_MINUTES),
        ]);

        $this->deliver($user, $destination, $channel, $code);

        return $reset;
    }

    /**
     * Verify the code and set the new password in one step.
     *
     * @throws RuntimeException if there is no pending reset, the code is
     *                          wrong, expired, or attempts are exhausted
     */
    public function reset(User $user, string $code, string $newPassword): void
    {
        $reset = ClientPasswordReset::where('user_id', $user->id)
            ->whereNull('used_at')
            ->latest('id')
            ->first();

        if (! $reset) {
            throw new RuntimeException('No password reset code was requested for this account.');
        }

        if ($reset->isExpired()) {
            throw new RuntimeException('This code has expired. Please request a new one.');
        }

        if ($reset->attempts >= self::MAX_ATTEMPTS) {
            throw new RuntimeException('Too many incorrect attempts. Please request a new code.');
        }

        if (! Hash::check($code, $reset->code_hash)) {
            $reset->increment('attempts');

            throw new RuntimeException('The code you entered is incorrect.');
        }

        $user->forceFill(['password' => Hash::make($newPassword)])->save();
        $reset->update(['used_at' => now()]);

        // Force re-login on every device once the password changes.
        $user->tokens()->delete();
    }

    /**
     * Admin-triggered — generate a brand-new password, set it on the account
     * immediately, and deliver the plaintext credentials + login link over
     * the given channel. Unlike send()/reset(), no code entry is required.
     *
     * @param  'sms'|'email'  $channel
     */
    public function generateAndSend(User $user, string $channel): void
    {
        $destination = $channel === 'email' ? $user->email : $user->phone;

        if (blank($destination)) {
            throw new RuntimeException("This account has no {$channel} on file.");
        }

        $password = $this->generatePassword();

        $user->forceFill(['password' => Hash::make($password)])->save();

        // Force re-login on every device once the password changes.
        $user->tokens()->delete();

        $this->deliverCredentials($user, $destination, $channel, $password);
    }

    private function generatePassword(): string
    {
        // Unambiguous character set (no 0/O/1/I/l) since this is read off an
        // SMS/email and typed back in by hand.
        $alphabet = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
        $password = '';

        for ($i = 0; $i < 10; $i++) {
            $password .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return $password;
    }

    private function deliverCredentials(User $user, string $destination, string $channel, string $password): void
    {
        $loginUrl = config('services.client_app.login_url');
        $username = $user->email && ! str_ends_with($user->email, '@clients.local') ? $user->email : $user->phone;

        if ($channel === 'email') {
            $this->mail->send(
                $destination,
                'Your account password',
                $this->credentialsEmailBody($user, $username, $password, $loginUrl),
            );

            return;
        }

        $result = $this->sms->send(
            $destination,
            "Hi {$user->name}, your login: {$username} / Password: {$password}. Log in: {$loginUrl}",
        );

        if (! ($result['success'] ?? false)) {
            Log::error('Client credentials SMS failed', ['to' => $destination, 'result' => $result]);
            throw new RuntimeException($result['error'] ?? 'Failed to send the credentials SMS.');
        }
    }

    private function credentialsEmailBody(User $user, string $username, string $password, string $loginUrl): string
    {
        return <<<HTML
            <div style="font-family:Arial,sans-serif;max-width:480px;margin:auto">
                <h2 style="color:#111">Your account is ready</h2>
                <p>Hi {$user->name},</p>
                <p>Here are your login details:</p>
                <p style="margin:4px 0;">Username: <strong>{$username}</strong></p>
                <p style="margin:4px 0;">Password: <strong style="font-family:monospace; letter-spacing:2px;">{$password}</strong></p>
                <p><a href="{$loginUrl}" style="display:inline-block;padding:10px 20px;background:#111;color:#fff;text-decoration:none;border-radius:6px;">Log in</a></p>
                <p style="color:#666">For your security, please change this password after logging in.</p>
            </div>
            HTML;
    }

    private function deliver(User $user, string $destination, string $channel, string $code): void
    {
        if ($channel === 'email') {
            $this->mail->send(
                $destination,
                'Your password reset code',
                $this->emailBody($user, $code),
            );

            return;
        }

        $result = $this->sms->send(
            $destination,
            "Your password reset code is {$code}. It expires in " . self::EXPIRY_MINUTES . ' minutes.',
        );

        if (! ($result['success'] ?? false)) {
            Log::error('Client password reset SMS failed', ['to' => $destination, 'result' => $result]);
            throw new RuntimeException($result['error'] ?? 'Failed to send the reset SMS.');
        }
    }

    private function emailBody(User $user, string $code): string
    {
        return <<<HTML
            <div style="font-family:Arial,sans-serif;max-width:480px;margin:auto">
                <h2 style="color:#111">Password reset code</h2>
                <p>Hi {$user->name},</p>
                <p>Use the following code in the app to set a new password:</p>
                <p style="font-size:32px;font-weight:bold;letter-spacing:6px;color:#111">{$code}</p>
                <p style="color:#666">This code expires in {$this->expiryMinutes()} minutes. If you did not request this, you can ignore this email.</p>
            </div>
            HTML;
    }

    private function expiryMinutes(): int
    {
        return self::EXPIRY_MINUTES;
    }
}
