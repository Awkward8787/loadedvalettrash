<?php

declare(strict_types=1);

namespace Lvt\Tests;

use Lvt\Database;
use Lvt\Routing\Builder;

final class RoleTest extends TestCase
{
    public function testPorterCannotOpenBillingAndOnlySeesTheirRoute(): void
    {
        $porter = $this->makeStaff('porter', 'porter-a@example.test');
        $other = $this->makeStaff('porter', 'porter-b@example.test');
        $mine = $this->insertProperty('Porter Own Court');
        $theirs = $this->insertProperty('Porter Other Court');
        Builder::createRun(today_date(), $porter['id'], '20:00:00', [$mine]);
        Builder::createRun(today_date(), $other['id'], '20:00:00', [$theirs]);

        $client = $this->client();
        $client->ip = '10.8.1.1';
        $this->signIn($client, $porter);

        $billing = $client->get('/admin/billing');
        $this->assertSame(403, $billing->status);

        $settings = $client->get('/admin/settings');
        $this->assertSame(403, $settings->status);

        $tonight = $client->get('/admin/tonight');
        $this->assertSame(200, $tonight->status);
        $this->assertStringContainsString('Porter Own Court', $tonight->body);
        $this->assertStringNotContainsString('Porter Other Court', $tonight->body);
    }

    public function testManagerCannotEditSettingsAndOwnerCan(): void
    {
        $manager = $this->makeStaff('manager', 'manager@example.test');
        $client = $this->client();
        $client->ip = '10.8.2.2';
        $this->signIn($client, $manager);
        $this->assertSame(403, $client->get('/admin/settings')->status);
        $this->assertSame(200, $client->get('/admin/billing')->status);

        $owner = $this->makeStaff('owner', 'owner-role@example.test');
        $ownerClient = $this->client();
        $ownerClient->ip = '10.8.2.3';
        $this->signIn($ownerClient, $owner);
        $page = $ownerClient->get('/admin/settings');
        $this->assertSame(200, $page->status);
        $this->assertStringContainsString('Insurance', $page->status === 200 ? $page->body : '');
    }

    public function testGateNotesStayOffTheRosterExport(): void
    {
        $owner = $this->makeStaff('owner', 'export@example.test');
        $propertyId = $this->insertProperty('Export Court', [
            'gate_access_notes' => 'DO-NOT-EXPORT-GATE',
        ]);
        Database::insertRow('residents', [
            'property_id' => $propertyId,
            'building_id' => null,
            'unit_number' => '12',
            'name' => '=cmd',
            'start_date' => '2026-10-01',
            'cancel_date' => null,
            'status' => 'active',
            'notes' => null,
            'created_at' => now_dt(),
            'updated_at' => now_dt(),
        ]);
        $client = $this->client();
        $client->ip = '10.8.3.3';
        $this->signIn($client, $owner);
        $csv = $client->get('/admin/properties/' . $propertyId . '/residents.csv?year=2026&month=10');
        $this->assertSame(200, $csv->status);
        $this->assertStringContainsString("'=cmd", $csv->body);
        $this->assertStringNotContainsString('DO-NOT-EXPORT-GATE', $csv->body);
        $this->assertStringNotContainsString('gate', strtolower($csv->body));
    }
}
