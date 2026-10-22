<?php

declare(strict_types=1);

namespace Lvt;

final class Crypto
{
    public static function key(): string
    {
        $raw = (string) env('APP_KEY', '');
        if (str_starts_with($raw, 'base64:')) {
            $decoded = base64_decode(substr($raw, 7), true);
            $raw = $decoded === false ? '' : $decoded;
        }
        if (strlen($raw) !== 32) {
            throw new \RuntimeException('APP_KEY must decode to exactly 32 bytes. Generate one with: php -r "echo \'base64:\'.base64_encode(random_bytes(32)), PHP_EOL;"');
        }
        return $raw;
    }

    public static function encrypt(string $plain): string
    {
        $iv = random_bytes(12);
        $tag = '';
        $cipher = openssl_encrypt($plain, 'aes-256-gcm', self::key(), OPENSSL_RAW_DATA, $iv, $tag);
        if ($cipher === false || strlen($tag) !== 16) {
            throw new \RuntimeException('Encryption failed.');
        }
        return base64_encode($iv . $tag . $cipher);
    }

    public static function decrypt(string $encoded): string
    {
        $bin = base64_decode($encoded, true);
        if ($bin === false || strlen($bin) < 12 + 16 + 1) {
            throw new \RuntimeException('Could not read an encrypted value.');
        }
        $iv = substr($bin, 0, 12);
        $tag = substr($bin, 12, 16);
        $cipher = substr($bin, 28);
        $plain = openssl_decrypt($cipher, 'aes-256-gcm', self::key(), OPENSSL_RAW_DATA, $iv, $tag);
        if ($plain === false) {
            throw new \RuntimeException('Could not decrypt a stored secret. Check APP_KEY.');
        }
        return $plain;
    }
}
