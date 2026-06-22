<?php

namespace App\Services;

use App\Models\User;
use App\Models\VerifyCode;

/**
 * Self-contained two-factor authentication helpers for the client login flow
 * (docs/modernization/02-client-api-contract.md §4).
 *
 *  - TOTP: RFC 6238, HMAC-SHA1, 6 digits, 30s step. No external composer dependency.
 *  - Email: short numeric codes persisted in the VerifyCode model and mailed to the user.
 */
class TwoFactorService
{
    /** TOTP code length. */
    private const DIGITS = 6;

    /** TOTP time step in seconds. */
    private const PERIOD = 30;

    /** How many steps before/after "now" are still accepted (clock skew tolerance). */
    private const WINDOW = 1;

    /** Email verification code lifetime in minutes. */
    private const EMAIL_TTL_MINUTES = 5;

    /**
     * Verify a 6-digit TOTP code against the user's stored Base32 secret.
     */
    public function verifyTotp(User $user, string $code): bool
    {
        $secret = (string) $user->two_factor_secret;
        $code = preg_replace('/\D/', '', $code) ?? '';

        if ($secret === '' || strlen($code) !== self::DIGITS) {
            return false;
        }

        $key = $this->base32Decode($secret);
        if ($key === '') {
            return false;
        }

        $counter = intdiv(time(), self::PERIOD);

        for ($offset = -self::WINDOW; $offset <= self::WINDOW; $offset++) {
            if (hash_equals($this->hotp($key, $counter + $offset), $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Compute the HOTP value (the TOTP building block) for a binary key + counter.
     */
    private function hotp(string $key, int $counter): string
    {
        // 8-byte big-endian counter.
        $binCounter = pack('N*', 0, $counter);

        $hash = hash_hmac('sha1', $binCounter, $key, true);

        // Dynamic truncation (RFC 4226 §5.3).
        $bytes = unpack('C*', $hash);
        $offset = $bytes[20] & 0x0F; // last nibble; unpack is 1-indexed.

        $binary = (($bytes[$offset + 1] & 0x7F) << 24)
            | (($bytes[$offset + 2] & 0xFF) << 16)
            | (($bytes[$offset + 3] & 0xFF) << 8)
            | ($bytes[$offset + 4] & 0xFF);

        $otp = $binary % (10 ** self::DIGITS);

        return str_pad((string) $otp, self::DIGITS, '0', STR_PAD_LEFT);
    }

    /**
     * Decode an RFC 4648 Base32 string into raw bytes. Returns '' on invalid input.
     */
    private function base32Decode(string $secret): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = strtoupper(rtrim($secret, '='));
        $secret = preg_replace('/\s+/', '', $secret) ?? '';

        if ($secret === '') {
            return '';
        }

        $bits = '';
        foreach (str_split($secret) as $char) {
            $index = strpos($alphabet, $char);
            if ($index === false) {
                return '';
            }
            $bits .= str_pad(decbin($index), 5, '0', STR_PAD_LEFT);
        }

        $bytes = '';
        foreach (str_split($bits, 8) as $chunk) {
            if (strlen($chunk) === 8) {
                $bytes .= chr((int) bindec($chunk));
            }
        }

        return $bytes;
    }

    /**
     * Generate, persist (VerifyCode), and return a numeric email verification code.
     * The caller is responsible for mailing it; we only store the record.
     */
    public function issueEmailCode(User $user, string $uuid, ?string $rustdeskId = null): string
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Invalidate any previous outstanding email codes for this user + device.
        VerifyCode::where('user_id', $user->id)
            ->where('type', VerifyCode::TYPE_EMAIL)
            ->where('uuid', $uuid)
            ->update(['status' => 0]);

        VerifyCode::create([
            'user_id' => $user->id,
            'type' => VerifyCode::TYPE_EMAIL,
            'uuid' => $uuid !== '' ? $uuid : (string) $user->id,
            'code' => $code,
            'rustdesk_id' => $rustdeskId,
            'status' => 1,
            'expires_at' => now()->addMinutes(self::EMAIL_TTL_MINUTES),
        ]);

        return $code;
    }

    /**
     * Verify a previously issued email code, consuming it on success.
     */
    public function verifyEmailCode(User $user, string $uuid, string $code): bool
    {
        $code = trim($code);

        if ($code === '') {
            return false;
        }

        $record = VerifyCode::where('user_id', $user->id)
            ->where('type', VerifyCode::TYPE_EMAIL)
            ->where('uuid', $uuid !== '' ? $uuid : (string) $user->id)
            ->where('status', 1)
            ->orderByDesc('id')
            ->first();

        if (! $record) {
            return false;
        }

        if ($record->expires_at !== null && $record->expires_at->isPast()) {
            $record->forceFill(['status' => 0])->save();

            return false;
        }

        if (! hash_equals((string) $record->code, $code)) {
            return false;
        }

        // Single-use: consume the code.
        $record->forceFill(['status' => 0])->save();

        return true;
    }
}
