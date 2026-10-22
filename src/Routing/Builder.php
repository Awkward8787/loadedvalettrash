<?php

declare(strict_types=1);

namespace Lvt\Routing;

use Lvt\Database;

final class Builder
{
    /** @param list<int> $propertyIds */
    public static function createRun(string $date, ?int $porterId, string $startTime, array $propertyIds, ?int $templateId = null): int
    {
        $runId = Database::insertRow('route_runs', [
            'template_id' => $templateId,
            'service_date' => $date,
            'porter_id' => $porterId,
            'start_time' => strlen($startTime) === 5 ? $startTime . ':00' : $startTime,
            'status' => 'scheduled',
            'notes' => null,
            'created_at' => now_dt(),
        ]);
        $order = 0;
        foreach ($propertyIds as $propertyId) {
            $propertyId = (int) $propertyId;
            if ($propertyId <= 0) {
                continue;
            }
            $buildings = Database::all(
                'SELECT * FROM buildings WHERE property_id = ? ORDER BY sort_order, id',
                [$propertyId]
            );
            if ($buildings === []) {
                $stopId = Database::insertRow('route_stops', [
                    'route_run_id' => $runId,
                    'property_id' => $propertyId,
                    'building_id' => null,
                    'sort_order' => $order++,
                    'status' => 'pending',
                    'completed_at' => null,
                    'notes' => null,
                ]);
                self::doorsForStop($stopId, $propertyId, null, $date);
                continue;
            }
            $first = true;
            foreach ($buildings as $building) {
                $stopId = Database::insertRow('route_stops', [
                    'route_run_id' => $runId,
                    'property_id' => $propertyId,
                    'building_id' => (int) $building['id'],
                    'sort_order' => $order++,
                    'status' => 'pending',
                    'completed_at' => null,
                    'notes' => null,
                ]);
                self::doorsForStop($stopId, $propertyId, (int) $building['id'], $date, $first);
                $first = false;
            }
        }
        return $runId;
    }

    private static function doorsForStop(int $stopId, int $propertyId, ?int $buildingId, string $date, bool $includeUnassigned = true): void
    {
        $params = [$propertyId, $date, $date];
        $sql = 'SELECT * FROM residents
                WHERE property_id = ?
                  AND status = "active"
                  AND (start_date IS NULL OR start_date <= ?)
                  AND (cancel_date IS NULL OR cancel_date >= ?)';
        if ($buildingId === null) {
            // whole-property stop
        } elseif ($includeUnassigned) {
            $sql .= ' AND (building_id = ? OR building_id IS NULL)';
            $params[] = $buildingId;
        } else {
            $sql .= ' AND building_id = ?';
            $params[] = $buildingId;
        }
        $sql .= ' ORDER BY unit_number';
        $residents = Database::all($sql, $params);
        foreach ($residents as $resident) {
            Database::insertRow('door_checks', [
                'route_stop_id' => $stopId,
                'resident_id' => (int) $resident['id'],
                'unit_label' => (string) $resident['unit_number'],
                'status' => 'pending',
                'notes' => null,
                'updated_at' => now_dt(),
            ]);
        }
    }

    public static function generateFromTemplates(int $weeks): int
    {
        $weeks = max(1, min(12, $weeks));
        $templates = Database::all(
            'SELECT * FROM route_templates WHERE active = 1 AND day_of_week BETWEEN 1 AND 5'
        );
        $created = 0;
        $start = strtotime(today_date() . ' 12:00:00');
        $end = strtotime('+' . ($weeks * 7) . ' days', $start === false ? time() : $start);
        for ($ts = $start; $ts <= $end; $ts += 86400) {
            $date = date('Y-m-d', $ts);
            if (!Calendar::isServiceDay($date)) {
                continue;
            }
            $dow = (int) date('N', $ts);
            foreach ($templates as $template) {
                if ((int) $template['day_of_week'] !== $dow) {
                    continue;
                }
                $exists = (int) Database::value(
                    'SELECT COUNT(*) FROM route_runs WHERE template_id = ? AND service_date = ?',
                    [(int) $template['id'], $date]
                );
                if ($exists > 0) {
                    continue;
                }
                $stops = Database::all(
                    'SELECT property_id FROM route_template_stops WHERE template_id = ? ORDER BY sort_order, id',
                    [(int) $template['id']]
                );
                $ids = array_map(static fn (array $row): int => (int) $row['property_id'], $stops);
                if ($ids === []) {
                    continue;
                }
                self::createRun(
                    $date,
                    $template['porter_id'] !== null ? (int) $template['porter_id'] : null,
                    (string) $template['start_time'],
                    $ids,
                    (int) $template['id']
                );
                $created++;
            }
        }
        return $created;
    }
}
