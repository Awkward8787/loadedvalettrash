<?php

declare(strict_types=1);

namespace Lvt;

final class Request
{
    private static ?self $current = null;

    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query,
        public readonly array $post,
        public readonly array $files,
        public readonly array $cookies,
        public readonly array $server,
    ) {
    }

    public static function setCurrent(self $request): void
    {
        self::$current = $request;
    }

    public static function current(): self
    {
        if (!self::$current) {
            throw new \RuntimeException('No current request.');
        }
        return self::$current;
    }

    public static function fromGlobals(): self
    {
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $path = parse_url($uri, PHP_URL_PATH);
        $path = is_string($path) ? $path : '/';
        $cookies = [];
        $raw = (string) ($_SERVER['HTTP_COOKIE'] ?? '');
        if ($raw !== '') {
            foreach (explode(';', $raw) as $part) {
                $bits = explode('=', trim($part), 2);
                if (count($bits) === 2) {
                    $cookies[urldecode($bits[0])] = urldecode($bits[1]);
                }
            }
        }
        return new self(
            strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')),
            $path === '' ? '/' : $path,
            $_GET,
            $_POST,
            $_FILES,
            $cookies,
            $_SERVER,
        );
    }

    public function string(string $key, string $default = ''): string
    {
        $value = $this->post[$key] ?? $this->query[$key] ?? $default;
        if (is_array($value)) {
            return $default;
        }
        return trim((string) $value);
    }

    public function int(string $key, int $default = 0): int
    {
        $value = $this->string($key, '');
        if ($value === '' || preg_match('/^-?\d+$/', $value) !== 1) {
            return $default;
        }
        return (int) $value;
    }

    public function ip(): string
    {
        $ip = (string) ($this->server['REMOTE_ADDR'] ?? '0.0.0.0');
        return substr($ip, 0, 64);
    }

    public function userAgent(): string
    {
        return substr((string) ($this->server['HTTP_USER_AGENT'] ?? ''), 0, 255);
    }

    public function file(string $key): ?array
    {
        $file = $this->files[$key] ?? null;
        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        return $file;
    }
}
