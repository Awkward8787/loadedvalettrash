<?php

declare(strict_types=1);

namespace Lvt\Routing;

use Lvt\Database;

final class Calendar
{
    public static function isServiceDay(string $date): bool
    {
        $ts = strtotime($date . ' 12:00:00');
        if ($ts === false) {
            return false;
        }
        $n = (int) date('N', $ts);
        if ($n >= 6) {
            return false;
        }
        $holiday = Database::one('SELECT service_off FROM holidays WHERE observed_on = ?', [$date]);
        if ($holiday && (int) $holiday['service_off'] === 1) {
            return false;
        }
        return true;
    }
}
