<?php

declare(strict_types=1);

namespace Lvt;

final class Totp
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public static function generateSecret(int $bytes = 20): string
    {
        return self::base32Encode(random_bytes($bytes));
    }

    public static function normalize(string $secret): string
    {
        return strtoupper((string) preg_replace('/\s+/', '', $secret));
    }

    public static function code(string $secret, ?int $time = null): string
    {
        $time = $time ?? time();
        $counter = intdiv($time, 30);
        $key = self::base32Decode(self::normalize($secret));
        $binary = pack('N*', 0) . pack('N*', $counter);
        $hash = hash_hmac('sha1', $binary, $key, true);
        $offset = ord($hash[19]) & 0x0f;
        $value = (
            ((ord($hash[$offset]) & 0x7f) << 24)
            | ((ord($hash[$offset + 1]) & 0xff) << 16)
            | ((ord($hash[$offset + 2]) & 0xff) << 8)
            | (ord($hash[$offset + 3]) & 0xff)
        ) % 1000000;
        return str_pad((string) $value, 6, '0', STR_PAD_LEFT);
    }

    public static function verify(string $secret, string $code, int $window = 1): bool
    {
        $code = (string) preg_replace('/\s+/', '', $code);
        if (preg_match('/^\d{6}$/', $code) !== 1) {
            return false;
        }
        $now = time();
        for ($i = -$window; $i <= $window; $i++) {
            if (hash_equals(self::code($secret, $now + ($i * 30)), $code)) {
                return true;
            }
        }
        return false;
    }

    public static function provisioningUri(string $secret, string $account, string $issuer = 'Loaded Valet Trash'): string
    {
        $label = rawurlencode($issuer . ':' . $account);
        return 'otpauth://totp/' . $label
            . '?secret=' . rawurlencode(self::normalize($secret))
            . '&issuer=' . rawurlencode($issuer)
            . '&period=30&digits=6';
    }

    public static function base32Encode(string $bytes): string
    {
        $alphabet = self::ALPHABET;
        $bits = '';
        $length = strlen($bytes);
        for ($i = 0; $i < $length; $i++) {
            $bits .= str_pad(decbin(ord($bytes[$i])), 8, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach (str_split($bits, 5) as $chunk) {
            if (strlen($chunk) < 5) {
                $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
            }
            $out .= $alphabet[bindec($chunk)];
        }
        return $out;
    }

    public static function base32Decode(string $secret): string
    {
        $secret = self::normalize($secret);
        $secret = rtrim($secret, '=');
        $bits = '';
        $length = strlen($secret);
        for ($i = 0; $i < $length; $i++) {
            $pos = strpos(self::ALPHABET, $secret[$i]);
            if ($pos === false) {
                throw new \InvalidArgumentException('Invalid authenticator secret.');
            }
            $bits .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
        }
        $bytes = '';
        foreach (str_split($bits, 8) as $chunk) {
            if (strlen($chunk) === 8) {
                $bytes .= chr(bindec($chunk));
            }
        }
        return $bytes;
    }
}
