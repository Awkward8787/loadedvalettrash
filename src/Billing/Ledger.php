<?php

declare(strict_types=1);

namespace Lvt\Billing;

use Lvt\Database;
use Lvt\Settings;

final class Ledger
{
    public static function billableCount(int $propertyId, int $year, int $month): int
    {
        $start = sprintf('%04d-%02d-01', $year, $month);
        $end = date('Y-m-t', strtotime($start . ' 12:00:00'));
        return (int) Database::value(
            'SELECT COUNT(*) FROM residents
             WHERE property_id = ?
               AND status IN ("active","cancelled")
               AND (start_date IS NULL OR start_date <= ?)
               AND (
                    (status = "active" AND (cancel_date IS NULL OR cancel_date >= ?))
                    OR (status = "cancelled" AND cancel_date IS NOT NULL AND cancel_date >= ?)
               )',
            [$propertyId, $end, $start, $start]
        );
    }

    public static function activeDoorCount(?int $propertyId = null): int
    {
        $today = today_date();
        $sql = 'SELECT COUNT(*) FROM residents
                WHERE status = "active"
                  AND (start_date IS NULL OR start_date <= ?)
                  AND (cancel_date IS NULL OR cancel_date >= ?)';
        $params = [$today, $today];
        if ($propertyId !== null) {
            $sql .= ' AND property_id = ?';
            $params[] = $propertyId;
        }
        return (int) Database::value($sql, $params);
    }

    public static function estimateDoors(array $property): int
    {
        $active = self::activeDoorCount((int) $property['id']);
        if ($active > 0) {
            return $active;
        }
        if ($property['planned_doors'] !== null && (int) $property['planned_doors'] > 0) {
            return (int) $property['planned_doors'];
        }
        if (in_array($property['pricing_model'], ['per_door', 'flat'], true)) {
            return (int) $property['units_count'];
        }
        return 0;
    }

    /** @return array{gross_cents:int,share_cents:int,due_cents:int,door_count:int,rate_cents:int,share_percent:float} */
    public static function quote(array $property, int $year, int $month): array
    {
        $settings = Settings::get();
        $model = (string) $property['pricing_model'];
        if ($model === 'flat') {
            $flat = (int) ($property['flat_rate_cents'] ?? 0);
            $math = Calculator::flat($flat);
            return $math + [
                'door_count' => (int) $property['units_count'],
                'rate_cents' => $flat,
                'share_percent' => 0.0,
            ];
        }
        if ($model === 'per_door') {
            $doors = (int) $property['units_count'];
            $rate = (int) ($property['per_door_rate_cents'] ?? 0);
            $math = Calculator::perDoor($doors, $rate);
            return $math + [
                'door_count' => $doors,
                'rate_cents' => $rate,
                'share_percent' => 0.0,
            ];
        }
        $doors = self::billableCount((int) $property['id'], $year, $month);
        $rate = (int) ($property['opt_in_price_cents'] ?? $settings['opt_in_price_cents']);
        $share = $property['property_share_percent'] !== null
            ? (float) $property['property_share_percent']
            : (float) $settings['default_property_share_percent'];
        $math = Calculator::optIn($doors, $rate, $share);
        return $math + [
            'door_count' => $doors,
            'rate_cents' => $rate,
            'share_percent' => $share,
        ];
    }

    public static function dueDate(int $year, int $month): string
    {
        $day = min(28, max(1, (int) Settings::get()['remittance_due_day']));
        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }

    public static function isLate(array $invoice): bool
    {
        if (!in_array($invoice['status'], ['draft', 'sent'], true)) {
            return false;
        }
        return (string) $invoice['due_on'] < today_date();
    }

    /** @return array{hours:float,labor_cents:int,supplies_cents:int,gas_cents:int,reserve_cents:int,fixed_share_cents:int,profit_cents:int,sites:int} */
    public static function profitForInvoice(array $invoice): array
    {
        $settings = Settings::get();
        $property = Database::one('SELECT * FROM properties WHERE id = ?', [(int) $invoice['property_id']]);
        $supplies = (int) ($property['supplies_cents'] ?? $settings['per_site_supplies_cents']);
        $gas = (int) ($property['gas_cents'] ?? $settings['per_site_gas_cents']);
        $reserve = (int) $settings['per_site_vehicle_reserve_cents'];
        $sites = max(1, (int) Database::value(
            'SELECT COUNT(*) FROM invoices WHERE period_year = ? AND period_month = ? AND status <> "void"',
            [(int) $invoice['period_year'], (int) $invoice['period_month']]
        ));
        $fixedShare = (int) round(((int) $settings['monthly_fixed_costs_cents']) / $sites);
        $labor = self::laborForPropertyMonth(
            (int) $invoice['property_id'],
            (int) $invoice['period_year'],
            (int) $invoice['period_month']
        );
        $profit = Calculator::profitCents(
            (int) $invoice['due_cents'],
            $labor['labor_cents'],
            $supplies,
            $gas,
            $reserve,
            $fixedShare
        );
        return [
            'hours' => $labor['hours'],
            'labor_cents' => $labor['labor_cents'],
            'supplies_cents' => $supplies,
            'gas_cents' => $gas,
            'reserve_cents' => $reserve,
            'fixed_share_cents' => $fixedShare,
            'profit_cents' => $profit,
            'sites' => $sites,
        ];
    }

    /** @return array{hours:float,labor_cents:int} */
    public static function laborForPropertyMonth(int $propertyId, int $year, int $month): array
    {
        $start = sprintf('%04d-%02d-01 00:00:00', $year, $month);
        $endTs = strtotime($start . ' +1 month');
        $end = date('Y-m-d H:i:s', $endTs === false ? time() : $endTs);
        $entries = Database::all(
            'SELECT t.id, t.user_id, t.route_run_id, t.clock_in, t.clock_out, u.pay_rate_cents
             FROM time_entries t
             INNER JOIN users u ON u.id = t.user_id
             WHERE t.clock_out IS NOT NULL AND t.clock_in >= ? AND t.clock_in < ?',
            [$start, $end]
        );
        $multiplier = (float) Settings::get()['payroll_multiplier'];
        $hours = 0.0;
        $labor = 0;
        foreach ($entries as $entry) {
            $in = strtotime((string) $entry['clock_in']);
            $out = strtotime((string) $entry['clock_out']);
            if ($in === false || $out === false || $out <= $in) {
                continue;
            }
            $entryHours = ($out - $in) / 3600;
            $share = self::propertyShareOfEntry((int) ($entry['route_run_id'] ?? 0), $propertyId);
            if ($share <= 0) {
                continue;
            }
            $attributed = $entryHours * $share;
            $hours += $attributed;
            $labor += Calculator::laborCents($attributed, (int) $entry['pay_rate_cents'], $multiplier);
        }
        return ['hours' => round($hours, 4), 'labor_cents' => $labor];
    }

    private static function propertyShareOfEntry(int $routeRunId, int $propertyId): float
    {
        if ($routeRunId <= 0) {
            return 0.0;
        }
        $stops = Database::all(
            'SELECT DISTINCT property_id FROM route_stops WHERE route_run_id = ?',
            [$routeRunId]
        );
        if ($stops === []) {
            return 0.0;
        }
        $weights = [];
        foreach ($stops as $stop) {
            $property = Database::one('SELECT * FROM properties WHERE id = ?', [(int) $stop['property_id']]);
            if (!$property) {
                continue;
            }
            $weights[(int) $property['id']] = self::propertyMinutes($property);
        }
        if (!array_key_exists($propertyId, $weights)) {
            return 0.0;
        }
        $split = Calculator::splitHours(1.0, $weights);
        return (float) ($split[$propertyId] ?? 0.0);
    }

    public static function propertyMinutes(array $property): int
    {
        $settings = Settings::get();
        $perDoor = $property['minutes_per_door'] !== null
            ? (float) $property['minutes_per_door']
            : (float) $settings['minutes_per_door'];
        $fixed = $property['minutes_fixed'] !== null
            ? (int) $property['minutes_fixed']
            : (int) $settings['minutes_per_site'];
        return \Lvt\Routing\Estimator::minutes(self::estimateDoors($property), 1, $perDoor, $fixed);
    }
}
