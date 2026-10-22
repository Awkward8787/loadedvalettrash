<?php

declare(strict_types=1);

namespace Lvt\Routing;

final class Estimator
{
    public static function minutes(int $doors, int $sites, float $minutesPerDoor = 1.0, int $minutesPerSite = 45): int
    {
        if ($doors < 0 || $sites < 0 || $minutesPerDoor < 0 || $minutesPerSite < 0) {
            throw new \InvalidArgumentException('Route inputs cannot be negative.');
        }
        return (int) round(($doors * $minutesPerDoor) + ($sites * $minutesPerSite));
    }

    public static function portersNeeded(int $minutes, int $windowMinutes = 120): int
    {
        if ($windowMinutes <= 0) {
            throw new \InvalidArgumentException('Pickup window must be positive.');
        }
        if ($minutes <= 0) {
            return 1;
        }
        return (int) max(1, (int) ceil($minutes / $windowMinutes));
    }

    public static function finish(string $start, int $minutes): string
    {
        $start = substr($start, 0, 5);
        $dt = \DateTimeImmutable::createFromFormat('H:i', $start);
        if ($dt === false) {
            throw new \InvalidArgumentException('Bad start time.');
        }
        return $dt->modify('+' . $minutes . ' minutes')->format('g:i A');
    }

    public static function overWindow(int $minutes, int $windowMinutes = 120): bool
    {
        return $minutes > $windowMinutes;
    }
}
