<?php

declare(strict_types=1);

namespace Lvt;

final class Auth
{
    private const DUMMY_HASH = '$2y$10$DPyLRZH2kdjptnLw4YZiruA4/c9Y1EtIAMARfm0jOS4F0bqg28EJG';

    private static bool $resolved = false;
    private static ?array $user = null;

    public static function resetCache(): void
    {
        self::$resolved = false;
        self::$user = null;
    }

    public static function attempt(string $email, string $password, string $ip): string
    {
        $email = strtolower(trim($email));
        if (RateLimiter::tooMany($email, $ip)) {
            Audit::write(null, 'login_locked', 'user', null, 'Too many sign-in attempts');
            return 'locked';
        }
        $user = Database::one('SELECT * FROM users WHERE email = ?', [$email]);
        $hash = $user['password_hash'] ?? self::DUMMY_HASH;
        $ok = is_string($hash) && password_verify($password, $hash);
        if (!$user || !$ok) {
            RateLimiter::hit($email, $ip, false);
            Audit::write(null, 'login_failed', 'user', isset($user['id']) ? (int) $user['id'] : null, 'Failed sign-in');
            return 'invalid';
        }
        if ((int) $user['active'] !== 1) {
            RateLimiter::hit($email, $ip, false);
            return 'inactive';
        }
        RateLimiter::hit($email, $ip, true);
        Session::regenerate();
        if ((int) $user['totp_enabled'] === 1) {
            Session::set('pending_uid', (int) $user['id']);
            Session::set('pending_until', time() + 300);
            return '2fa';
        }
        Session::set('enroll_uid', (int) $user['id']);
        return 'enroll';
    }

    public static function confirmTotp(string $code, string $ip): string
    {
        $uid = (int) Session::get('pending_uid', 0);
        $until = (int) Session::get('pending_until', 0);
        if ($uid <= 0 || time() > $until) {
            return 'expired';
        }
        $user = Database::one('SELECT * FROM users WHERE id = ?', [$uid]);
        if (!$user || (int) $user['active'] !== 1) {
            return 'invalid';
        }
        if (RateLimiter::tooMany((string) $user['email'], $ip)) {
            return 'locked';
        }
        try {
            $secret = Crypto::decrypt((string) $user['totp_secret']);
        } catch (\Throwable) {
            return 'invalid';
        }
        if (Totp::verify($secret, $code)) {
            self::completeLogin($user);
            return 'ok';
        }
        if (self::consumeRecovery((int) $user['id'], $code)) {
            self::completeLogin($user);
            Audit::write((int) $user['id'], 'login_recovery_code', 'user', (int) $user['id'], 'Recovery code used');
            return 'ok';
        }
        RateLimiter::hit((string) $user['email'], $ip, false);
        return 'invalid';
    }

    public static function user(): ?array
    {
        if (self::$resolved) {
            return self::$user;
        }
        self::$resolved = true;
        $uid = (int) Session::get('uid', 0);
        $sid = (string) Session::get('sid', '');
        if ($uid <= 0 || $sid === '') {
            return self::$user = null;
        }
        $row = Database::one(
            'SELECT * FROM user_sessions WHERE user_id = ? AND token_hash = ?',
            [$uid, hash('sha256', $sid)]
        );
        if (!$row) {
            self::clearAuthKeys();
            return self::$user = null;
        }
        $timeout = max(5, (int) Settings::get()['session_timeout_minutes']) * 60;
        $last = strtotime((string) $row['last_activity']);
        $exp = strtotime((string) $row['expires_at']);
        if ($last === false || $exp === false || (time() - $last) > $timeout || $exp < time()) {
            Database::exec('DELETE FROM user_sessions WHERE id = ?', [$row['id']]);
            self::clearAuthKeys();
            Session::flash('error', 'Your session timed out. Sign in again.');
            return self::$user = null;
        }
        Database::updateRow('user_sessions', [
            'last_activity' => now_dt(),
            'expires_at' => date('Y-m-d H:i:s', time() + $timeout),
        ], 'id = ?', [$row['id']]);
        $user = Database::one('SELECT * FROM users WHERE id = ? AND active = 1', [$uid]);
        if (!$user) {
            self::clearAuthKeys();
            return self::$user = null;
        }
        unset($user['password_hash'], $user['totp_secret']);
        return self::$user = $user;
    }

    public static function enrolling(): ?array
    {
        $uid = (int) Session::get('enroll_uid', 0);
        if ($uid <= 0) {
            return null;
        }
        return Database::one(
            'SELECT id, name, email, role, totp_enabled, active FROM users WHERE id = ? AND active = 1',
            [$uid]
        );
    }

    public static function beginEnrollment(int $userId): string
    {
        $user = Database::one('SELECT totp_secret, totp_enabled FROM users WHERE id = ?', [$userId]);
        if ($user && (int) $user['totp_enabled'] !== 1 && filled($user['totp_secret'] ?? null)) {
            return Crypto::decrypt((string) $user['totp_secret']);
        }
        $secret = Totp::generateSecret();
        Database::updateRow('users', [
            'totp_secret' => Crypto::encrypt($secret),
            'totp_enabled' => 0,
            'updated_at' => now_dt(),
        ], 'id = ?', [$userId]);
        return $secret;
    }

    /** @return list<string> */
    public static function enableTotp(int $userId, string $code): array
    {
        $user = Database::one('SELECT * FROM users WHERE id = ?', [$userId]);
        if (!$user || !filled($user['totp_secret'] ?? null)) {
            throw new HttpException(422, 'Set up an authenticator before confirming.');
        }
        $secret = Crypto::decrypt((string) $user['totp_secret']);
        if (!Totp::verify($secret, $code)) {
            throw new HttpException(422, 'That authenticator code does not match. Check the clock on your phone and try again.');
        }
        $plain = [];
        Database::transaction(function () use ($userId, &$plain): void {
            Database::exec('DELETE FROM recovery_codes WHERE user_id = ?', [$userId]);
            for ($i = 0; $i < 8; $i++) {
                $code = self::makeRecoveryCode();
                $plain[] = $code;
                Database::insertRow('recovery_codes', [
                    'user_id' => $userId,
                    'code_hash' => hash('sha256', self::normalizeRecovery($code)),
                    'used_at' => null,
                ]);
            }
            Database::updateRow('users', [
                'totp_enabled' => 1,
                'updated_at' => now_dt(),
            ], 'id = ?', [$userId]);
        });
        $fresh = Database::one('SELECT * FROM users WHERE id = ?', [$userId]);
        self::completeLogin($fresh);
        Audit::write($userId, 'totp_enabled', 'user', $userId, 'Authenticator enabled');
        return $plain;
    }

    public static function logout(): void
    {
        $user = self::user();
        $sid = (string) Session::get('sid', '');
        if ($sid !== '') {
            Database::exec('DELETE FROM user_sessions WHERE token_hash = ?', [hash('sha256', $sid)]);
        }
        if ($user) {
            Audit::write((int) $user['id'], 'logout', 'user', (int) $user['id'], 'Signed out');
        }
        self::clearAuthKeys();
        Session::forget('enroll_uid');
        Session::forget('pending_uid');
        Session::forget('pending_until');
        self::resetCache();
        Session::regenerate();
    }

    public static function canSeeGateNotes(?array $user, int $propertyId): bool
    {
        if (!$user) {
            return false;
        }
        if (in_array($user['role'], ['owner', 'manager'], true)) {
            return true;
        }
        if ($user['role'] !== 'porter') {
            return false;
        }
        $assigned = (int) Database::value(
            'SELECT COUNT(*) FROM route_runs r
             INNER JOIN route_stops s ON s.route_run_id = r.id
             WHERE r.porter_id = ? AND s.property_id = ?',
            [(int) $user['id'], $propertyId]
        );
        if ($assigned > 0) {
            return true;
        }
        $template = (int) Database::value(
            'SELECT COUNT(*) FROM route_templates t
             INNER JOIN route_template_stops s ON s.template_id = t.id
             WHERE t.porter_id = ? AND s.property_id = ? AND t.active = 1',
            [(int) $user['id'], $propertyId]
        );
        return $template > 0;
    }

    private static function completeLogin(array $user): void
    {
        Session::forget('pending_uid');
        Session::forget('pending_until');
        Session::forget('enroll_uid');
        Session::regenerate();
        $token = bin2hex(random_bytes(32));
        $timeout = max(5, (int) Settings::get()['session_timeout_minutes']) * 60;
        $ip = '0.0.0.0';
        $ua = '';
        try {
            $ip = Request::current()->ip();
            $ua = Request::current()->userAgent();
        } catch (\Throwable) {
            $ip = '0.0.0.0';
        }
        Database::insertRow('user_sessions', [
            'user_id' => (int) $user['id'],
            'token_hash' => hash('sha256', $token),
            'ip' => $ip,
            'user_agent' => $ua,
            'last_activity' => now_dt(),
            'created_at' => now_dt(),
            'expires_at' => date('Y-m-d H:i:s', time() + $timeout),
        ]);
        Session::set('uid', (int) $user['id']);
        Session::set('sid', $token);
        Database::updateRow('users', [
            'last_login_at' => now_dt(),
            'updated_at' => now_dt(),
        ], 'id = ?', [(int) $user['id']]);
        self::resetCache();
        Audit::write((int) $user['id'], 'login', 'user', (int) $user['id'], 'Signed in');
    }

    private static function consumeRecovery(int $userId, string $code): bool
    {
        $normalized = self::normalizeRecovery($code);
        if (strlen($normalized) < 8) {
            return false;
        }
        $rows = Database::all('SELECT id, code_hash FROM recovery_codes WHERE user_id = ? AND used_at IS NULL', [$userId]);
        $hash = hash('sha256', $normalized);
        foreach ($rows as $row) {
            if (hash_equals((string) $row['code_hash'], $hash)) {
                Database::updateRow('recovery_codes', ['used_at' => now_dt()], 'id = ?', [(int) $row['id']]);
                return true;
            }
        }
        return false;
    }

    private static function makeRecoveryCode(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $raw = '';
        for ($i = 0; $i < 8; $i++) {
            $raw .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        return substr($raw, 0, 4) . '-' . substr($raw, 4, 4);
    }

    private static function normalizeRecovery(string $code): string
    {
        return strtoupper((string) preg_replace('/[^A-Z0-9]/i', '', $code));
    }

    private static function clearAuthKeys(): void
    {
        Session::forget('uid');
        Session::forget('sid');
        self::resetCache();
    }
}
