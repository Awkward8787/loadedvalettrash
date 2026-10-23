<?php

declare(strict_types=1);

namespace Lvt;

/** Same window and caps as the login limiter, stored apart from login_attempts. */
final class FormRateLimiter
{
    public static function tooMany(string $form, string $email, string $ip): bool
    {
        $cutoff = date('Y-m-d H:i:s', time() - RateLimiter::WINDOW_SECONDS);
        $email = strtolower(trim($email));
        $ip = substr($ip, 0, 64);
        $byIp = (int) Database::value(
            'SELECT COUNT(*) FROM form_attempts WHERE form_key = ? AND ip = ? AND attempted_at >= ?',
            [$form, $ip, $cutoff]
        );
        if ($byIp >= RateLimiter::IP_LIMIT) {
            return true;
        }
        if ($email === '') {
            return false;
        }
        $byEmail = (int) Database::value(
            'SELECT COUNT(*) FROM form_attempts WHERE form_key = ? AND email = ? AND attempted_at >= ?',
            [$form, $email, $cutoff]
        );
        return $byEmail >= RateLimiter::EMAIL_LIMIT;
    }

    public static function hit(string $form, string $email, string $ip): void
    {
        Database::insertRow('form_attempts', [
            'form_key' => substr($form, 0, 40),
            'email' => substr(strtolower(trim($email)), 0, 190),
            'ip' => substr($ip, 0, 64),
            'attempted_at' => now_dt(),
        ]);
    }
}
