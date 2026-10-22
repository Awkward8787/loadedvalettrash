<?php

declare(strict_types=1);

namespace Lvt;

final class Session
{
    private static bool $started = false;

    public static function start(Request $request): void
    {
        if (self::$started && session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
            self::$started = false;
        }
        $path = LVT_ROOT . '/storage/sessions';
        if (!is_dir($path)) {
            mkdir($path, 0770, true);
        }
        session_save_path($path);
        session_name((string) env('SESSION_NAME', 'lvt_session'));
        ini_set('session.use_cookies', '0');
        ini_set('session.use_only_cookies', '0');
        $incoming = (string) ($request->cookies[session_name()] ?? '');
        if (preg_match('/^[a-zA-Z0-9,-]{16,128}$/', $incoming) === 1) {
            session_id($incoming);
        } else {
            // A long-lived process (the test runner) keeps the last id. A request
            // with no valid cookie must not resume that session.
            session_id(bin2hex(random_bytes(16)));
        }
        session_start();
        self::$started = true;
        if (!is_string($_SESSION['_csrf'] ?? null) || strlen((string) $_SESSION['_csrf']) < 20) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
    }

    public static function id(): string
    {
        return session_id();
    }

    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function flash(string $type, string $message): void
    {
        $_SESSION['_flash'][$type] = $message;
    }

    public static function pullFlashes(): array
    {
        $flashes = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return is_array($flashes) ? $flashes : [];
    }

    public static function flashOld(array $input): void
    {
        unset($input['_csrf'], $input['password'], $input['password_confirm'], $input['totp']);
        $_SESSION['_old'] = $input;
    }

    public static function pullOld(): array
    {
        $old = $_SESSION['_old'] ?? [];
        unset($_SESSION['_old']);
        return is_array($old) ? $old : [];
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        self::$started = false;
    }

    public static function cookieHeader(): string
    {
        $secure = str_starts_with((string) env('APP_URL', ''), 'https://');
        $parts = [
            session_name() . '=' . session_id(),
            'Path=/',
            'HttpOnly',
            'SameSite=Lax',
        ];
        if ($secure) {
            $parts[] = 'Secure';
        }
        return implode('; ', $parts);
    }
}
