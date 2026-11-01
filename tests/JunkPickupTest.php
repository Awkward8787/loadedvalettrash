<?php

declare(strict_types=1);

namespace Lvt\Tests;

use Lvt\Database;
use Lvt\Junk\Quote;

final class JunkPickupTest extends TestCase
{
    public function testCalculatorShowsNoPriceUntilRatesExist(): void
    {
        $client = $this->client();
        $page = $client->get('/junk-pickup');
        $this->assertSame(200, $page->status);
        $this->assertStringContainsString('Junk pickup', $page->body);
        $this->assertStringContainsString(Quote::FOLLOW_UP, $page->body);
        $this->assertStringNotContainsString(Quote::ESTIMATE_LABEL, $page->body);
        $this->assertStringNotContainsString('$', $page->body);
        $this->assertStringContainsString('name="photo"', $page->body);
        $this->assertStringContainsString('Couch', $page->body);
        $this->assertStringContainsString('1/4 truck', $page->body);
        $this->assertStringContainsString('&quot;couch&quot;:null', $page->body);

        $client->ip = '10.7.1.1';
        $client->get('/junk-pickup');
        $done = $client->follow($client->post('/junk-pickup', $this->payload($client)));
        $this->assertStringContainsString('we have your junk pickup request', strtolower($done->body));
        $this->assertStringContainsString(Quote::FOLLOW_UP, $done->body);
        $this->assertStringNotContainsString('$', $done->body);
        $row = Database::one('SELECT * FROM junk_estimates WHERE email = ?', ['jamie@example.test']);
        $this->assertSame('new', $row['status']);
        $this->assertSame('items', $row['mode']);
        $this->assertNull($row['estimate_low_cents']);
        $this->assertNull($row['estimate_high_cents']);
        $this->assertSame('atlanta', $row['service_area']);
        $this->assertStringContainsString('couch', (string) $row['items_json']);

        $owner = $this->makeStaff('owner', 'owner-junk@example.test');
        $admin = $this->client();
        $this->signIn($admin, $owner);
        $list = $admin->get('/admin/outreach');
        $this->assertStringContainsString('Junk pickup estimate', $list->body);
        $this->assertStringContainsString('Couch x 1', $list->body);
        $detail = $admin->get('/admin/junk-estimates/' . (int) $row['id']);
        $this->assertStringContainsString(Quote::FOLLOW_UP, $detail->body);
        $this->assertStringNotContainsString('$', $detail->body);
        $saved = $admin->follow($admin->post('/admin/junk-estimates/' . (int) $row['id'], [
            '_csrf' => $admin->csrf(),
            'status' => 'quoted',
            'notes' => 'Call before noon.',
        ]));
        $this->assertStringContainsString('Estimate updated.', $saved->body);
        $this->assertSame('quoted', Database::one('SELECT status FROM junk_estimates WHERE id = ?', [(int) $row['id']])['status']);
        $this->assertSame('Call before noon.', Database::one('SELECT notes FROM junk_estimates WHERE id = ?', [(int) $row['id']])['notes']);
    }

    public function testRatesProduceARangeAndBlankRatesDoNot(): void
    {
        $owner = $this->makeStaff('owner', 'owner-rates@example.test');
        $admin = $this->client();
        $this->signIn($admin, $owner);
        $form = $admin->get('/admin/junk-rates');
        $this->assertStringContainsString('Leave a row blank', $form->body);
        $this->assertStringNotContainsString('$', $form->body);
        $saved = $admin->follow($admin->post('/admin/junk-rates', [
            '_csrf' => $admin->csrf(),
            'couch_low' => '40.00',
            'couch_high' => '60.00',
        ]));
        $this->assertStringContainsString('Junk pickup rates saved.', $saved->body);
        $this->assertSame(4000, (int) Database::value('SELECT couch_low_cents FROM junk_rates WHERE id = 1'));
        $this->assertSame(6000, (int) Database::value('SELECT couch_high_cents FROM junk_rates WHERE id = 1'));
        $this->assertNull(Database::value('SELECT mattress_low_cents FROM junk_rates WHERE id = 1'));

        $bad = $admin->post('/admin/junk-rates', [
            '_csrf' => $admin->csrf(),
            'couch_low' => '80.00',
            'couch_high' => '10.00',
        ]);
        $this->assertSame(422, $bad->status);
        $this->assertSame(4000, (int) Database::value('SELECT couch_low_cents FROM junk_rates WHERE id = 1'));

        $public = $this->client();
        $public->ip = '10.7.2.2';
        $page = $public->get('/junk-pickup');
        $this->assertStringNotContainsString(Quote::ESTIMATE_LABEL, $page->body);
        $public->get('/junk-pickup');
        $done = $public->follow($public->post('/junk-pickup', $this->payload($public, [
            'qty_couch' => '2',
            'email' => 'range@example.test',
        ])));
        $this->assertStringContainsString(Quote::ESTIMATE_LABEL, $done->body);
        $this->assertStringContainsString('$80.00 to $120.00', $done->body);
        $row = Database::one('SELECT estimate_low_cents, estimate_high_cents FROM junk_estimates WHERE email = ?', ['range@example.test']);
        $this->assertSame(8000, (int) $row['estimate_low_cents']);
        $this->assertSame(12000, (int) $row['estimate_high_cents']);

        $quote = Quote::total('items', ['couch' => 2], null, Quote::book(Database::one('SELECT * FROM junk_rates WHERE id = 1')));
        $this->assertSame(8000, $quote['low']);
        $this->assertSame(12000, $quote['high']);
        $this->assertNull(Quote::total('items', ['mattress' => 1], null, Quote::book(Database::one('SELECT * FROM junk_rates WHERE id = 1'))));
    }

    public function testMoveOutAndJunkEstimateLinksDoNotCreateProposalLeads(): void
    {
        $client = $this->client();
        $asked = $client->get('/contact?intent=move_out');
        $this->assertSame(302, $asked->status);
        $page = $client->follow($asked);
        $this->assertStringContainsString('Request estimate', $page->body);
        $this->assertStringNotContainsString('$', $page->body);

        $client->get('/contact');
        $sent = $client->follow($client->post('/contact', [
            '_csrf' => $client->csrf(),
            'kind' => 'move_out',
            'property_name' => 'Should Not Become A Lead',
            'units' => '1',
            'contact_name' => 'Riley Office',
            'contact_email' => 'riley@example.test',
            'contact_phone' => '',
            'message' => 'Furniture by the door.',
            'company_website' => '',
        ]));
        $this->assertStringContainsString('Junk pickup', $sent->body);
        $this->assertSame(0, (int) Database::value('SELECT COUNT(*) FROM inquiries'));
        $this->assertSame(0, (int) Database::value('SELECT COUNT(*) FROM properties'));
        $this->assertSame(0, (int) Database::value('SELECT COUNT(*) FROM junk_estimates'));
    }

    public function testJunkFormRejectsEmptyLoadsAndLimitsRepeats(): void
    {
        $client = $this->client();
        $client->ip = '10.7.4.4';
        $client->get('/junk-pickup');
        $empty = $client->post('/junk-pickup', $this->payload($client, ['qty_couch' => '0']));
        $this->assertSame(422, $empty->status);
        $this->assertSame(0, (int) Database::value('SELECT COUNT(*) FROM junk_estimates'));

        $client->get('/junk-pickup');
        $trap = $client->follow($client->post('/junk-pickup', $this->payload($client, [
            'company_website' => 'http://spam.example',
        ])));
        $this->assertStringContainsString('Thanks. We have it.', $trap->body);
        $this->assertSame(0, (int) Database::value('SELECT COUNT(*) FROM junk_estimates'));

        for ($i = 0; $i < 5; $i++) {
            $client->get('/junk-pickup');
            $ok = $client->post('/junk-pickup', $this->payload($client, [
                'email' => 'junk' . $i . '@example.test',
                'name' => 'Junk ' . $i,
            ]));
            $this->assertSame(302, $ok->status);
        }
        $client->get('/junk-pickup');
        $limited = $client->post('/junk-pickup', $this->payload($client, [
            'email' => 'blocked-junk@example.test',
        ]));
        $this->assertSame(422, $limited->status);
        $this->assertStringContainsString('Too many estimates', $limited->body);
    }

    public function testPorterCannotOpenEstimatesOrRates(): void
    {
        $porter = $this->makeStaff('porter', 'porter-junk@example.test');
        $client = $this->client();
        $this->signIn($client, $porter);
        $this->assertSame(403, $client->get('/admin/junk-rates')->status);
        $this->assertSame(403, $client->get('/admin/outreach')->status);
        $tonight = $client->get('/admin/tonight');
        $this->assertStringNotContainsString('/admin/junk-rates', $tonight->body);
        $this->assertStringNotContainsString('/admin/outreach', $tonight->body);
    }

    /** @param array<string,mixed> $override */
    private function payload(HttpClient $client, array $override = []): array
    {
        return array_merge([
            '_csrf' => $client->csrf(),
            'mode' => 'items',
            'qty_couch' => '1',
            'qty_mattress' => '0',
            'qty_appliance' => '0',
            'qty_table' => '0',
            'qty_bags' => '0',
            'address' => '100 Peachtree St, Atlanta',
            'service_area' => 'atlanta',
            'preferred_on' => '2026-12-01',
            'name' => 'Jamie Resident',
            'email' => 'jamie@example.test',
            'phone' => '',
            'note' => 'Couch by the curb.',
            'company_website' => '',
        ], $override);
    }
}
