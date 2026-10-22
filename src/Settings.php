<?php

declare(strict_types=1);

namespace Lvt;

final class Settings
{
    private static ?array $cache = null;

    public static function get(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }
        $row = Database::one('SELECT * FROM settings WHERE id = 1');
        if (!$row) {
            throw new \RuntimeException('Settings row is missing. Run php bin/migrate.php.');
        }
        self::$cache = $row;
        return $row;
    }

    public static function flush(): void
    {
        self::$cache = null;
    }
}
