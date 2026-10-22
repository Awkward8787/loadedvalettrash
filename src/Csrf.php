<?php

declare(strict_types=1);

namespace Lvt;

final class Csrf
{
    public static function token(): string
    {
        $token = Session::get('_csrf');
        if (!is_string($token) || strlen($token) < 20) {
            $token = bin2hex(random_bytes(32));
            Session::set('_csrf', $token);
        }
        return $token;
    }

    public static function verify(?string $token): bool
    {
        $known = Session::get('_csrf');
        return is_string($known) && is_string($token) && $token !== '' && hash_equals($known, $token);
    }
}
