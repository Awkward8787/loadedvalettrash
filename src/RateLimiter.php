<?php

declare(strict_types=1);

namespace Lvt;

final class RateLimiter
{
    public const EMAIL_LIMIT = 5;
    public const IP_LIMIT = 8;
    public const WINDOW_SECONDS = 900;
    public const APPLICATION_LIMIT = 5;
    public const CONTACT_LIMIT = 5;
    public const JUNK_LIMIT = 5;

    public static function tooMany(string $email, string $ip): bool
    {
        $cutoff = date('Y-m-d H:i:s', time() - self::WINDOW_SECONDS);
        $email = strtolower(trim($email));
        $byEmail = (int) Database::value(
            'SELECT COUNT(*) FROM login_attempts WHERE success = 0 AND email = ? AND attempted_at >= ?',
            [$email, $cutoff]
        );
        $byIp = (int) Database::value(
            'SELECT COUNT(*) FROM login_attempts WHERE success = 0 AND ip = ? AND attempted_at >= ?',
            [$ip, $cutoff]
        );
        return $byEmail >= self::EMAIL_LIMIT || $byIp >= self::IP_LIMIT;
    }

    public static function tooManyApplications(string $ip): bool
    {
        $cutoff = date('Y-m-d H:i:s', time() - self::WINDOW_SECONDS);
        $count = (int) Database::value(
            'SELECT COUNT(*) FROM job_applications WHERE ip = ? AND created_at >= ?',
            [$ip, $cutoff]
        );
        return $count >= self::APPLICATION_LIMIT;
    }

    public static function tooManyContactInquiries(string $ip): bool
    {
        return self::tooManyFromTable('contact_inquiries', $ip, self::CONTACT_LIMIT);
    }

    public static function tooManyJunkEstimates(string $ip): bool
    {
        return self::tooManyFromTable('junk_estimates', $ip, self::JUNK_LIMIT);
    }

    private static function tooManyFromTable(string $table, string $ip, int $limit): bool
    {
        Database::guardIdent($table);
        $cutoff = date('Y-m-d H:i:s', time() - self::WINDOW_SECONDS);
        $count = (int) Database::value(
            'SELECT COUNT(*) FROM ' . $table . ' WHERE ip = ? AND created_at >= ?',
            [$ip, $cutoff]
        );
        return $count >= $limit;
    }

    public static function hit(string $email, string $ip, bool $success): void
    {
        Database::insertRow('login_attempts', [
            'email' => strtolower(trim($email)),
            'ip' => $ip,
            'success' => $success ? 1 : 0,
            'attempted_at' => now_dt(),
        ]);
    }
}
