<?php

declare(strict_types=1);

namespace Lvt\Tests;

use Lvt\Database;
use Lvt\Routing\Builder;
use Lvt\Routing\Calendar;
use Lvt\Routing\Estimator;

final class RouteEstimateTest extends TestCase
{
    public function testDoorAndSiteMinutesMatchTheStaffingExamples(): void
    {
        $this->assertSame(105, Estimator::minutes(60, 1));
        $this->assertSame(1, Estimator::portersNeeded(105, 120));
        $this->assertSame(145, Estimator::minutes(100, 1));
        $this->assertSame(2, Estimator::portersNeeded(145, 120));
        $this->assertSame(245, Estimator::minutes(200, 1));
        $this->assertSame(3, Estimator::portersNeeded(245, 120));
        $this->assertSame('9:45 PM', Estimator::finish('20:00', 105));
        $this->assertFalse(Estimator::overWindow(105, 120));
        $this->assertTrue(Estimator::overWindow(145, 120));
    }

    public function testHolidaysAndWeekendsAreNotServiceNights(): void
    {
        $this->assertFalse(Calendar::isServiceDay('2026-11-26'));
        $this->assertTrue(Calendar::isServiceDay('2027-01-18'));
        $this->assertFalse(Calendar::isServiceDay('2026-10-24'));
        $this->assertTrue(Calendar::isServiceDay('2026-10-22'));
    }

    public function testWeeklyGenerationSkipsThanksgiving(): void
    {
        $porter = $this->makeStaff('porter', 'route-porter@example.test');
        $propertyId = $this->insertProperty('Route Court');
        $templateId = Database::insertRow('route_templates', [
            'name' => 'Thursday route',
            'porter_id' => $porter['id'],
            'day_of_week' => 4,
            'start_time' => '20:00:00',
            'active' => 1,
            'created_at' => now_dt(),
        ]);
        Database::insertRow('route_template_stops', [
            'template_id' => $templateId,
            'property_id' => $propertyId,
            'sort_order' => 1,
        ]);
        $created = Builder::generateFromTemplates(8);
        $this->assertGreaterThan(0, $created);
        $thanksgiving = (int) Database::value(
            'SELECT COUNT(*) FROM route_runs WHERE service_date = ?',
            ['2026-11-26']
        );
        $this->assertSame(0, $thanksgiving);
        $tonight = (int) Database::value(
            'SELECT COUNT(*) FROM route_runs WHERE service_date = ? AND template_id = ?',
            ['2026-10-22', $templateId]
        );
        $this->assertSame(1, $tonight);
    }
}
