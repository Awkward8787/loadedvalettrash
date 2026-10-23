<?php

declare(strict_types=1);

if (!defined('LVT_ROOT')) {
    define('LVT_ROOT', dirname(__DIR__));
}

function env(string $key, ?string $default = null): ?string
{
    $value = $_ENV[$key] ?? getenv($key);
    if ($value === false || $value === null || $value === '') {
        return $default;
    }
    return (string) $value;
}

function load_env_file(string $path): void
{
    if (!is_file($path)) {
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        return;
    }
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        $eq = strpos($line, '=');
        if ($eq === false) {
            continue;
        }
        $key = trim(substr($line, 0, $eq));
        $value = trim(substr($line, $eq + 1));
        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"'))
            || (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            $value = substr($value, 1, -1);
        }
        if (getenv($key) === false) {
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
        }
    }
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function now_dt(): string
{
    return date('Y-m-d H:i:s');
}

function today_date(): string
{
    return date('Y-m-d');
}

function filled(mixed $value): bool
{
    return trim((string) $value) !== '';
}

function blank_to_null(?string $value): ?string
{
    $value = trim((string) $value);
    return $value === '' ? null : $value;
}

function money(int $cents): string
{
    $negative = $cents < 0;
    $cents = abs($cents);
    $formatted = number_format($cents / 100, 2);
    return ($negative ? '-$' : '$') . $formatted;
}

function money_plain(int $cents): string
{
    if ($cents % 100 === 0) {
        $negative = $cents < 0;
        return ($negative ? '-$' : '$') . number_format(abs($cents) / 100, 0);
    }
    return money($cents);
}

function clock_label(string $time): string
{
    $dt = DateTimeImmutable::createFromFormat('H:i:s', strlen($time) === 5 ? $time . ':00' : $time);
    if ($dt === false) {
        $dt = DateTimeImmutable::createFromFormat('H:i', substr($time, 0, 5));
    }
    return $dt ? $dt->format('g:i A') : $time;
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(\Lvt\Csrf::token()) . '">';
}

function old(string $key, string $default = ''): string
{
    $bag = $GLOBALS['_old'] ?? [];
    $value = $bag[$key] ?? $default;
    return e(is_scalar($value) ? (string) $value : $default);
}

function field_error(string $key): string
{
    $msg = $GLOBALS['_errors'][$key] ?? '';
    if ($msg === '') {
        return '';
    }
    return '<p class="field-error" id="err-' . e($key) . '" role="alert">' . e($msg) . '</p>';
}

function has_error(string $key): bool
{
    return isset($GLOBALS['_errors'][$key]) && $GLOBALS['_errors'][$key] !== '';
}

function flashes(): array
{
    return $GLOBALS['_flashes'] ?? [];
}

function safe_next(?string $next, string $fallback = '/admin'): string
{
    if (!is_string($next) || $next === '') {
        return $fallback;
    }
    if (!str_starts_with($next, '/') || str_starts_with($next, '//') || str_contains($next, '://')) {
        return $fallback;
    }
    return $next;
}

function csv_safe(string $value): string
{
    if (preg_match('/^[=+\-@\t\r]/', $value) === 1) {
        return "'" . $value;
    }
    return $value;
}

function base_url(string $path = ''): string
{
    $base = rtrim((string) env('APP_URL', ''), '/');
    if ($path === '') {
        return $base;
    }
    return $base . '/' . ltrim($path, '/');
}

function selected(string $current, string $value): string
{
    return $current === $value ? ' selected' : '';
}

function checked_in(string $haystack, string $needle): string
{
    $parts = array_map('trim', explode(',', $haystack));
    return in_array($needle, $parts, true) ? ' checked' : '';
}

function nl(string $text): string
{
    return nl2br(e($text), false);
}

function dollars_to_cents(string $value): ?int
{
    $value = str_replace(['$', ',', ' '], '', trim($value));
    if ($value === '') {
        return null;
    }
    if (preg_match('/^\d+(\.\d{1,2})?$/', $value) !== 1) {
        return null;
    }
    return (int) round(((float) $value) * 100);
}

function cents_to_dollars(?int $cents): string
{
    if ($cents === null) {
        return '';
    }
    return number_format($cents / 100, 2, '.', '');
}

function maps_search(string $query): string
{
    return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($query);
}

function maps_directions(array $addresses): string
{
    $addresses = array_values(array_filter(array_map('trim', $addresses), static fn (string $a): bool => $a !== ''));
    if ($addresses === []) {
        return '';
    }
    $origin = array_shift($addresses);
    $destination = $addresses === [] ? $origin : array_pop($addresses);
    $url = 'https://www.google.com/maps/dir/?api=1&origin=' . rawurlencode($origin) . '&destination=' . rawurlencode((string) $destination) . '&travelmode=driving';
    if ($addresses !== []) {
        $url .= '&waypoints=' . rawurlencode(implode('|', $addresses));
    }
    return $url;
}

function password_problem(string $password): ?string
{
    if (strlen($password) < 12) {
        return 'Use at least 12 characters.';
    }
    if (preg_match('/[A-Za-z]/', $password) !== 1 || preg_match('/\d/', $password) !== 1) {
        return 'Use both letters and a number.';
    }
    return null;
}

function valid_date(string $value): bool
{
    $dt = DateTimeImmutable::createFromFormat('Y-m-d', $value);
    return $dt !== false && $dt->format('Y-m-d') === $value;
}

function property_address(array $property): string
{
    $stateZip = trim(((string) ($property['state'] ?? '')) . ' ' . ((string) ($property['postal_code'] ?? '')));
    $parts = [];
    foreach ([(string) ($property['address'] ?? ''), (string) ($property['city'] ?? ''), $stateZip] as $part) {
        if (trim($part) !== '') {
            $parts[] = trim($part);
        }
    }
    return implode(', ', $parts);
}
