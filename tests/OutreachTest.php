<?php

declare(strict_types=1);

namespace Lvt\Tests;

use Lvt\Database;

final class OutreachTest extends TestCase
{
    public function testMessageIsSavedAndListedNewestFirst(): void
    {
        $client = $this->client();
        $client->ip = '10.8.1.1';
        $page = $client->get('/contact');
        $this->assertStringContainsString('id="inq_name"', $page->body);
        $this->assertStringContainsString('Junk pickup estimate', $page->body);
        $this->assertStringContainsString('Valdosta-Lowndes', $page->body);
        $this->assertStringContainsString('name="company_website"', $page->body);
        $done = $client->follow($client->post('/contact/inquiry', $this->payload($client, [
            'inq_name' => 'Ada Manager',
            'inq_need' => 'valet_trash',
        ])));
        $this->assertStringContainsString('we have your message', strtolower($done->body));
        $row = Database::one('SELECT * FROM contact_inquiries WHERE email = ?', ['ada@example.test']);
        $this->assertSame('new', $row['status']);
        $this->assertSame('property_manager', $row['role']);
        $this->assertSame('valdosta_lowndes', $row['service_area']);
        $this->assertSame('valet_trash', $row['need']);
        $this->assertNull($row['phone']);
        $this->assertSame('10.8.1.1', $row['ip']);

        $client->post('/contact/inquiry', $this->payload($client, [
            'inq_name' => 'Second Person',
            'inq_email' => 'second@example.test',
            'inq_need' => 'other',
            'inq_role' => 'resident',
        ]));
        $owner = $this->makeStaff('owner', 'owner-outreach@example.test');
        $admin = $this->client();
        $admin->ip = '10.8.1.9';
        $this->signIn($admin, $owner);
        $list = $admin->get('/admin/outreach');
        $this->assertSame(200, $list->status);
        $this->assertStringContainsString('Inquiries', $list->body);
        $this->assertStringContainsString('2 new', $list->body);
        $this->assertStringContainsString('class="nav-badge">2', $list->body);
        $second = strpos($list->body, 'Second Person');
        $first = strpos($list->body, 'Ada Manager');
        $this->assertNotFalse($second);
        $this->assertNotFalse($first);
        $this->assertLessThan($first, $second);

        $detail = $admin->get('/admin/outreach/' . (int) $row['id']);
        $this->assertStringContainsString('Ada Manager', $detail->body);
        $this->assertStringContainsString('We want valet trash.', $detail->body);
        $this->assertStringContainsString('value="new" checked', $detail->body);
        $saved = $admin->follow($admin->post('/admin/outreach/' . (int) $row['id'], [
            '_csrf' => $admin->csrf(),
            'status' => 'contacted',
            'notes' => 'Called the office.',
        ]));
        $this->assertStringContainsString('Inquiry updated.', $saved->body);
        $this->assertStringContainsString('Called the office.', $saved->body);
        $updated = Database::one('SELECT status, notes FROM contact_inquiries WHERE id = ?', [(int) $row['id']]);
        $this->assertSame('contacted', $updated['status']);
        $this->assertSame('Called the office.', $updated['notes']);
    }

    public function testInquiryValidationHoneypotAndRateLimit(): void
    {
        $client = $this->client();
        $client->ip = '10.8.2.2';
        $client->get('/contact');
        $bad = $client->post('/contact/inquiry', $this->payload($client, [
            'inq_email' => 'not-an-email',
            'inq_name' => '',
        ]));
        $this->assertSame(422, $bad->status);
        $this->assertSame(0, (int) Database::value('SELECT COUNT(*) FROM contact_inquiries'));

        $client->get('/contact');
        $trap = $client->follow($client->post('/contact/inquiry', $this->payload($client, [
            'company_website' => 'https://spam.example',
        ])));
        $this->assertStringContainsString('Thanks. We have it.', $trap->body);
        $this->assertSame(0, (int) Database::value('SELECT COUNT(*) FROM contact_inquiries'));

        $client->get('/contact');
        $jump = $client->follow($client->post('/contact/inquiry', $this->payload($client, [
            'inq_need' => 'junk_estimate',
        ])));
        $this->assertStringContainsString('Request estimate', $jump->body);
        $this->assertSame(0, (int) Database::value('SELECT COUNT(*) FROM contact_inquiries'));

        for ($i = 0; $i < 5; $i++) {
            $client->get('/contact');
            $ok = $client->post('/contact/inquiry', $this->payload($client, [
                'inq_email' => 'person' . $i . '@example.test',
                'inq_name' => 'Person ' . $i,
            ]));
            $this->assertSame(302, $ok->status);
        }
        $client->get('/contact');
        $limited = $client->post('/contact/inquiry', $this->payload($client, [
            'inq_email' => 'blocked@example.test',
        ]));
        $this->assertSame(422, $limited->status);
        $this->assertStringContainsString('Too many messages', $limited->body);
        $this->assertSame(5, (int) Database::value('SELECT COUNT(*) FROM contact_inquiries'));
    }

    public function testInquiryOutputIsEscapedAndPortersCannotOpenIt(): void
    {
        $client = $this->client();
        $client->ip = '10.8.3.3';
        $client->get('/contact');
        $client->follow($client->post('/contact/inquiry', $this->payload($client, [
            'inq_name' => '<script>alert(1)</script>',
            'inq_message' => 'Hello <b>there</b>',
        ])));
        $owner = $this->makeStaff('owner', 'owner-escape@example.test');
        $admin = $this->client();
        $this->signIn($admin, $owner);
        $list = $admin->get('/admin/outreach');
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $list->body);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $list->body);
        $id = (int) Database::value('SELECT id FROM contact_inquiries LIMIT 1');
        $detail = $admin->get('/admin/outreach/' . $id);
        $this->assertStringContainsString('Hello &lt;b&gt;there&lt;/b&gt;', $detail->body);

        $porter = $this->makeStaff('porter', 'porter-outreach@example.test');
        $porterClient = $this->client();
        $this->signIn($porterClient, $porter);
        $this->assertSame(403, $porterClient->get('/admin/outreach')->status);
        $this->assertSame(403, $porterClient->get('/admin/outreach/' . $id)->status);
        $this->assertStringNotContainsString('/admin/outreach', $porterClient->get('/admin/tonight')->body);
    }

    /** @param array<string,mixed> $override */
    private function payload(HttpClient $client, array $override = []): array
    {
        return array_merge([
            '_csrf' => $client->csrf(),
            'inq_name' => 'Ada Manager',
            'inq_email' => 'ada@example.test',
            'inq_phone' => '',
            'inq_role' => 'property_manager',
            'inq_organization' => 'North Court',
            'inq_service_area' => 'valdosta_lowndes',
            'inq_need' => 'junk_pickup',
            'inq_message' => 'We want valet trash.',
            'company_website' => '',
        ], $override);
    }
}
