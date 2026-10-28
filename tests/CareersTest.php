<?php

declare(strict_types=1);

namespace Lvt\Tests;

use Lvt\Database;

final class CareersTest extends TestCase
{
    public function testCareersPageListsTheRolesPayAndPhoneOnlyThere(): void
    {
        $client = $this->client();
        $page = $client->get('/careers');
        $this->assertSame(200, $page->status);
        $this->assertStringContainsString('<h3>Laborer</h3>', $page->body);
        $this->assertStringContainsString('id="role-laborer"', $page->body);
        $this->assertStringContainsString('type="radio"', $page->body);
        $this->assertStringContainsString('name="role"', $page->body);
        $this->assertStringContainsString('value="laborer"', $page->body);
        $this->assertStringContainsString('value="supervisor"', $page->body);
        $this->assertStringContainsString('>Laborer</span>', $page->body);
        $this->assertStringContainsString('required', $page->body);
        $this->assertStringNotContainsString('<select', $page->body);
        $this->assertStringNotContainsString('<option', $page->body);
        $applyAt = strpos($page->body, 'href="#apply"');
        $videoAt = strpos($page->body, 'lvt-hiring-720p.mp4');
        $this->assertNotFalse($applyAt);
        $this->assertNotFalse($videoAt);
        $this->assertLessThan($videoAt, $applyAt);
        $this->assertMatchesRegularExpression(
            '/<video\b(?![^>]*\b(?:autoplay|muted|loop)\b)[^>]*\bcontrols\b[^>]*\bplaysinline\b[^>]*\bpreload="metadata"[^>]*\bposter="\/assets\/media\/lvt-hiring-poster\.jpg\?v=\d+"[^>]*\bwidth="1280"[^>]*\bheight="720"/',
            $page->body
        );
        $this->assertMatchesRegularExpression('#<source src="/assets/media/lvt-hiring-720p\.mp4\?v=\d+" type="video/mp4">#', $page->body);
        $this->assertMatchesRegularExpression('#<track kind="captions" src="/assets/media/lvt-hiring\.vtt\?v=\d+" srclang="en" label="English" default>#', $page->body);
        $this->assertDoesNotMatchRegularExpression('/<video\b[^>]*\b(?:autoplay|muted|loop)\b/', $page->body);
        $this->assertStringNotContainsString('Laborer / Porter', $page->body);
        $this->assertStringContainsString('Starting at $18 an hour.', $page->body);
        $this->assertStringContainsString('Supervisor', $page->body);
        $this->assertStringContainsString('Starting at $20 an hour.', $page->body);
        $this->assertStringContainsString('Monday through Friday', $page->body);
        $this->assertStringContainsString('6:00 to 8:00 PM', $page->body);
        $this->assertStringContainsString('8:00 to 10:00 PM', $page->body);
        $this->assertStringContainsString('compactor or dumpster', $page->body);
        $this->assertStringContainsString('Loaded Valet Trash is hiring in Valdosta / Lowndes County.', $page->body);
        $this->assertStringContainsString('evening routes in Valdosta / Lowndes County.', $page->body);
        $this->assertStringContainsString('href="tel:+12295958852"', $page->body);
        $this->assertStringContainsString('(229) 595-8852', $page->body);
        $this->assertStringContainsString('href="/careers"', $page->body);
        $this->assertStringContainsString('>Careers<', $page->body);
        $this->assertDoesNotMatchRegularExpression('/mailto:/', $page->body);
        $this->assertStringNotContainsString('1099', $page->body);
        $this->assertStringNotContainsString('W-2', $page->body);
        $this->assertStringNotContainsString('benefit', strtolower($page->body));
        $this->assertStringNotContainsString('–', $page->body);
        $this->assertStringNotContainsString('—', $page->body);
        $this->assertStringNotContainsString('type="file"', $page->body);

        $home = $client->get('/');
        $this->assertStringContainsString('href="/careers"', $home->body);
        $this->assertStringNotContainsString('(229) 595-8852', $home->body);
        $this->assertStringNotContainsString('229-595-8852', $home->body);

        $map = $client->get('/sitemap.xml');
        $this->assertStringContainsString('/careers', $map->body);
    }

    public function testValidApplicationIsStored(): void
    {
        $client = $this->client();
        $client->ip = '10.9.1.1';
        $client->get('/careers');
        $done = $client->follow($client->post('/careers', $this->payload($client, [
            'is_18' => 'no',
            'has_transportation' => 'no',
            'days' => ['fri', 'mon', 'mon', 'nope'],
            'email' => '',
        ])));
        $this->assertStringContainsString('we have your application', strtolower($done->body));
        $this->assertStringNotContainsString('soon', strtolower($done->body));
        $row = Database::one('SELECT * FROM job_applications WHERE full_name = ?', ['Riley Porter']);
        $this->assertNotNull($row);
        $this->assertSame('laborer', $row['role']);
        $this->assertSame('new', $row['status']);
        $this->assertSame('(229) 555-0142', $row['phone']);
        $this->assertNull($row['email']);
        $this->assertSame('Valdosta', $row['city']);
        $this->assertSame(0, (int) $row['is_adult']);
        $this->assertSame(0, (int) $row['has_transportation']);
        $this->assertSame('mon,fri', $row['days_available']);
        $this->assertSame(1, (int) $row['can_lift']);
        $this->assertSame('2026-11-02', $row['start_date']);
        $this->assertSame(1, (int) $row['consent']);
        $this->assertSame('10.9.1.1', $row['ip']);
        $this->assertNull($row['notes']);
        $audit = Database::one('SELECT action FROM audit_log WHERE entity_type = ? AND entity_id = ?', ['job_application', (int) $row['id']]);
        $this->assertSame('application.create', $audit['action']);
    }

    public function testInvalidApplicationIsRejected(): void
    {
        $client = $this->client();
        $client->ip = '10.9.1.2';
        $client->get('/careers');
        $empty = $client->post('/careers', [
            '_csrf' => $client->csrf(),
            'role' => '',
            'full_name' => '',
            'phone' => '',
            'email' => '',
            'city' => '',
            'experience' => '',
            'start_date' => '',
            'heard_about' => '',
            'consent' => '',
            'company_website' => '',
        ]);
        $this->assertSame(422, $empty->status);
        $this->assertStringContainsString('Enter your full name.', $empty->body);
        $this->assertStringContainsString('Choose at least one evening', $empty->body);
        $this->assertSame(0, (int) Database::value('SELECT COUNT(*) FROM job_applications'));

        $bad = $client->post('/careers', $this->payload($client, [
            'role' => 'owner',
            'email' => 'not-an-email',
            'phone' => 'abc',
            'start_date' => '2026-13-40',
            'consent' => '',
        ]));
        $this->assertSame(422, $bad->status);
        $this->assertStringContainsString('Choose a role.', $bad->body);
        $this->assertStringContainsString('aria-invalid="true" aria-describedby="err-role"', $bad->body);
        $this->assertStringContainsString('Enter a valid email', $bad->body);
        $this->assertStringContainsString('Confirm we can contact you', $bad->body);
        $this->assertSame(0, (int) Database::value('SELECT COUNT(*) FROM job_applications'));
    }

    public function testHoneypotDoesNotStoreAnApplication(): void
    {
        $client = $this->client();
        $client->ip = '10.9.1.3';
        $client->get('/careers');
        $done = $client->follow($client->post('/careers', $this->payload($client, [
            'company_website' => 'https://spam.example',
        ])));
        $this->assertLessThan(400, $done->status);
        $this->assertSame(0, (int) Database::value('SELECT COUNT(*) FROM job_applications'));
    }

    public function testApplicationsAreRateLimitedByIp(): void
    {
        $client = $this->client();
        $client->ip = '10.9.1.4';
        $client->get('/careers');
        for ($i = 0; $i < 5; $i++) {
            $done = $client->follow($client->post('/careers', $this->payload($client, [
                'full_name' => 'Applicant ' . $i,
            ])));
            $this->assertStringContainsString('we have your application', strtolower($done->body));
        }
        $blocked = $client->post('/careers', $this->payload($client, [
            'full_name' => 'Applicant blocked',
        ]));
        $this->assertSame(422, $blocked->status);
        $this->assertStringContainsString('Too many applications', $blocked->body);
        $this->assertSame(5, (int) Database::value('SELECT COUNT(*) FROM job_applications'));
        $this->assertSame(0, (int) Database::value('SELECT COUNT(*) FROM job_applications WHERE full_name = ?', ['Applicant blocked']));
    }

    public function testPorterCannotSeeApplicationsAndOfficeCanUpdateThem(): void
    {
        $public = $this->client();
        $public->ip = '10.9.1.5';
        $public->get('/careers');
        $public->follow($public->post('/careers', $this->payload($public)));
        $row = Database::one('SELECT id FROM job_applications WHERE full_name = ?', ['Riley Porter']);
        $this->assertNotNull($row);
        $id = (int) $row['id'];

        $porter = $this->makeStaff('porter', 'porter-jobs@example.test');
        $porterClient = $this->client();
        $porterClient->ip = '10.9.1.6';
        $this->signIn($porterClient, $porter);
        $tonight = $porterClient->get('/admin/tonight');
        $this->assertStringNotContainsString('/admin/applications', $tonight->body);
        $csrf = $porterClient->csrf();
        $this->assertSame(403, $porterClient->get('/admin/applications')->status);
        $this->assertSame(403, $porterClient->get('/admin/applications/' . $id)->status);
        $denied = $porterClient->post('/admin/applications/' . $id, [
            '_csrf' => $csrf,
            'status' => 'hired',
            'notes' => 'Should not save',
        ]);
        $this->assertSame(403, $denied->status);
        $this->assertSame('new', Database::one('SELECT status FROM job_applications WHERE id = ?', [$id])['status']);

        $manager = $this->makeStaff('manager', 'manager-jobs@example.test');
        $managerClient = $this->client();
        $managerClient->ip = '10.9.1.7';
        $this->signIn($managerClient, $manager);
        $this->assertSame(200, $managerClient->get('/admin/applications')->status);

        $owner = $this->makeStaff('owner', 'owner-jobs@example.test');
        $ownerClient = $this->client();
        $ownerClient->ip = '10.9.1.8';
        $this->signIn($ownerClient, $owner);
        $list = $ownerClient->get('/admin/applications');
        $this->assertSame(200, $list->status);
        $this->assertStringContainsString('Riley Porter', $list->body);
        $this->assertStringContainsString('id="filter-role-laborer"', $list->body);
        $this->assertStringContainsString('value="laborer"', $list->body);
        $this->assertStringContainsString('>Laborer</span>', $list->body);
        $this->assertStringContainsString('id="filter-role-all"', $list->body);
        $this->assertStringContainsString('>All</span>', $list->body);
        $this->assertStringContainsString('value="all" checked', $list->body);
        $this->assertStringNotContainsString('<select', $list->body);
        $this->assertStringNotContainsString('<option', $list->body);
        $this->assertStringNotContainsString('Laborer / Porter', $list->body);
        $this->assertStringContainsString('href="/admin/applications"', $list->body);

        $byRole = $ownerClient->get('/admin/applications?role=supervisor&status=all');
        $this->assertSame(200, $byRole->status);
        $this->assertStringNotContainsString('Riley Porter', $byRole->body);
        $this->assertStringContainsString('value="supervisor" checked', $byRole->body);
        $this->assertStringContainsString('name="status" value="all" checked', $byRole->body);
        $byStatus = $ownerClient->get('/admin/applications?role=laborer&status=new');
        $this->assertStringContainsString('Riley Porter', $byStatus->body);
        $this->assertStringContainsString('value="laborer" checked', $byStatus->body);
        $this->assertStringContainsString('value="new" checked', $byStatus->body);

        $detail = $ownerClient->get('/admin/applications/' . $id);
        $this->assertSame(200, $detail->status);
        $this->assertStringContainsString('18 or older: No', $detail->body);
        $this->assertStringContainsString('id="status-new"', $detail->body);
        $this->assertStringContainsString('value="new" checked', $detail->body);
        $this->assertStringNotContainsString('<select', $detail->body);
        $saved = $ownerClient->follow($ownerClient->post('/admin/applications/' . $id, [
            '_csrf' => $ownerClient->csrf(),
            'status' => 'interview',
            'notes' => 'Call after 5.',
        ]));
        $this->assertStringContainsString('Application updated.', $saved->body);
        $this->assertStringContainsString('value="interview" checked', $saved->body);
        $this->assertStringNotContainsString('<select', $saved->body);
        $this->assertStringContainsString('Call after 5.', $saved->body);
        $updated = Database::one('SELECT status, notes FROM job_applications WHERE id = ?', [$id]);
        $this->assertSame('interview', $updated['status']);
        $this->assertSame('Call after 5.', $updated['notes']);
        $audit = Database::one(
            'SELECT action FROM audit_log WHERE entity_type = ? AND entity_id = ? AND action = ?',
            ['job_application', $id, 'application.update']
        );
        $this->assertNotNull($audit);

        $invalid = $ownerClient->post('/admin/applications/' . $id, [
            '_csrf' => $ownerClient->csrf(),
            'status' => 'fired',
            'notes' => 'Nope',
        ]);
        $this->assertSame(422, $invalid->status);
        $this->assertSame('interview', Database::one('SELECT status FROM job_applications WHERE id = ?', [$id])['status']);
    }

    public function testPrivacyExplainsHowJobApplicationsAreKept(): void
    {
        $page = $this->client()->get('/privacy');
        $this->assertSame(200, $page->status);
        $this->assertStringContainsString('Job applications', $page->body);
        $this->assertStringContainsString('Email is optional.', $page->body);
        $this->assertStringContainsString('whether you are 18 or older', $page->body);
        $this->assertStringContainsString('your availability', $page->body);
        $this->assertStringContainsString('the date you can start', $page->body);
        $this->assertStringContainsString('IP address of the submission for spam protection', $page->body);
        $this->assertStringContainsString('review applications and to contact applicants about jobs', $page->body);
        $this->assertStringContainsString('Only the owner and managers of Loaded Valet Trash can see applications.', $page->body);
        $this->assertStringContainsString('We do not sell them and we do not share them.', $page->body);
        $this->assertStringContainsString('We keep applications for 1 year and then delete them.', $page->body);
        $this->assertStringContainsString('Updated October 23, 2026.', $page->body);
        $this->assertStringContainsString('href="tel:+12295958852"', $page->body);
        $this->assertStringContainsString('(229) 595-8852', $page->body);
        $this->assertStringNotContainsString('–', $page->body);
        $this->assertStringNotContainsString('—', $page->body);
    }

    /** @param array<string,mixed> $override */
    private function payload(HttpClient $client, array $override = []): array
    {
        return array_merge([
            '_csrf' => $client->csrf(),
            'role' => 'laborer',
            'full_name' => 'Riley Porter',
            'phone' => '(229) 555-0142',
            'email' => 'riley@example.test',
            'city' => 'Valdosta',
            'is_18' => 'no',
            'has_transportation' => 'no',
            'days' => ['mon', 'fri'],
            'can_lift' => 'yes',
            'experience' => 'Carried trash on a campus route.',
            'start_date' => '2026-11-02',
            'heard_about' => 'A friend on a route',
            'consent' => '1',
            'company_website' => '',
        ], $override);
    }
}
