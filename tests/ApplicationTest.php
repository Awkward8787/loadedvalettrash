<?php

declare(strict_types=1);

namespace Lvt\Tests;

use Lvt\Database;
use Lvt\Settings;
use PHPUnit\Framework\Attributes\DataProvider;

final class ApplicationTest extends TestCase
{
    public function testValidApplicationIsStoredWithoutEmail(): void
    {
        $client = $this->client();
        $client->ip = '10.40.1.1';
        $client->get('/apply');
        $page = $client->get('/apply');
        $this->assertSame(200, $page->status);
        $this->assertStringContainsString('Send application', $page->body);
        $this->assertStringContainsString('hiring porters for evening pickups in Valdosta, Lowndes County, Hahira, and Remerton, Georgia.', $page->body);
        $this->assertStringContainsString('6:00 to 8:00 PM', $page->body);
        $this->assertStringContainsString('8:00 to 10:00 PM', $page->body);
        $this->assertStringNotContainsString('<select', $page->body);
        $this->assertStringNotContainsString('Job details', $page->body);
        $this->assertStringNotContainsString('background check', strtolower($page->body));
        $this->assertDoesNotMatchRegularExpression('/\$\d/', $page->body);

        $done = $client->follow($client->post('/apply', $this->payload($client->csrf(), [
            'nights' => ['tue', 'fri'],
            'has_transportation' => 'no',
            'is_adult' => 'yes',
            'experience' => 'Warehouse nights.',
            'notes' => 'I can start this month.',
        ])));
        $this->assertSame(200, $done->status);
        $this->assertStringContainsString('We have your application.', $done->body);
        $this->assertStringNotContainsString('email sent', strtolower($done->body));
        $row = Database::one('SELECT * FROM applications WHERE email = ?', ['taylor@example.test']);
        $this->assertNotNull($row);
        $this->assertSame('Taylor Applicant', $row['full_name']);
        $this->assertSame('229-555-0100', $row['phone']);
        $this->assertSame('tue,fri', $row['nights']);
        $this->assertSame(0, (int) $row['has_transportation']);
        $this->assertSame(1, (int) $row['is_adult']);
        $this->assertSame('Warehouse nights.', $row['experience']);
        $this->assertSame('I can start this month.', $row['notes']);
        $this->assertSame('new', $row['status']);
    }

    public function testScheduleTimesAndJobDetailsComeFromSettings(): void
    {
        Database::exec(
            'UPDATE settings SET setout_start = ?, setout_end = ?, pickup_start = ?, pickup_end = ?, porter_job_details = ? WHERE id = 1',
            ['17:00:00', '19:00:00', '19:00:00', '21:00:00', 'Bring a flashlight.']
        );
        Settings::flush();
        try {
            $page = $this->client()->get('/apply');
            $this->assertStringContainsString('5:00 to 7:00 PM', $page->body);
            $this->assertStringContainsString('7:00 to 9:00 PM', $page->body);
            $this->assertStringNotContainsString('6:00 to 8:00 PM', $page->body);
            $this->assertStringContainsString('Job details', $page->body);
            $this->assertStringContainsString('Bring a flashlight.', $page->body);
        } finally {
            Database::exec(
                'UPDATE settings SET setout_start = ?, setout_end = ?, pickup_start = ?, pickup_end = ?, porter_job_details = NULL WHERE id = 1',
                ['18:00:00', '20:00:00', '20:00:00', '22:00:00']
            );
            Settings::flush();
        }
    }

    public function testHoneypotDoesNotStoreAnApplication(): void
    {
        $client = $this->client();
        $client->ip = '10.40.1.2';
        $client->get('/apply');
        $done = $client->follow($client->post('/apply', $this->payload($client->csrf(), [
            'company_website' => 'https://spam.example',
        ])));
        $this->assertSame(200, $done->status);
        $this->assertStringContainsString('Thanks. We have it.', $done->body);
        $this->assertSame(0, (int) Database::value('SELECT COUNT(*) FROM applications'));
    }

    public function testMissingCsrfIsRejected(): void
    {
        $client = $this->client();
        $client->get('/apply');
        $response = $client->post('/apply', $this->payload('not-the-token'));
        $this->assertSame(419, $response->status);
        $this->assertStringContainsString('Refresh the page and submit it again.', $response->body);
        $this->assertSame(0, (int) Database::value('SELECT COUNT(*) FROM applications'));
    }

    #[DataProvider('invalidApplications')]
    public function testEachValidationError(string $field, array $override, string $message): void
    {
        $client = $this->client();
        $client->ip = '10.40.3.10';
        $client->get('/apply');
        $response = $client->post('/apply', $this->payload($client->csrf(), $override));
        $this->assertSame(422, $response->status, $field);
        $this->assertStringContainsString($message, $response->body, $field);
        $this->assertStringContainsString('id="err-' . $field . '"', $response->body, $field);
        $this->assertSame(0, (int) Database::value('SELECT COUNT(*) FROM applications'), $field);
    }

    public function testApplyFormIsRateLimited(): void
    {
        $client = $this->client();
        $client->ip = '10.40.4.4';
        $client->get('/apply');
        $token = $client->csrf();
        for ($i = 0; $i < 5; $i++) {
            $done = $client->follow($client->post('/apply', $this->payload($token, [
                'email' => 'repeat@example.test',
                'full_name' => 'Repeat Applicant ' . $i,
            ])));
            $this->assertSame(200, $done->status, 'attempt ' . $i);
            $token = $client->csrf();
        }
        $blocked = $client->post('/apply', $this->payload($token, [
            'email' => 'repeat@example.test',
            'full_name' => 'Repeat Applicant blocked',
        ]));
        $this->assertSame(429, $blocked->status);
        $this->assertStringContainsString('Wait 15 minutes, then send the application again.', $blocked->body);
        $this->assertSame(5, (int) Database::value('SELECT COUNT(*) FROM applications'));
    }

    public function testOwnerAndManagerCanReviewAndPorterCannot(): void
    {
        $id = $this->storeApplication('10.40.5.5', [
            'experience' => 'Warehouse nights.',
            'notes' => 'I can start this month.',
        ]);

        $owner = $this->makeStaff('owner', 'apply-owner@example.test');
        $ownerClient = $this->client();
        $ownerClient->ip = '10.40.5.6';
        $this->signIn($ownerClient, $owner);
        $list = $ownerClient->get('/admin/applications');
        $this->assertSame(200, $list->status);
        $this->assertStringContainsString('Taylor Applicant', $list->body);
        $this->assertStringContainsString('>New<', $list->body);
        $detail = $ownerClient->get('/admin/applications/' . $id);
        $this->assertSame(200, $detail->status);
        $this->assertStringContainsString('Warehouse nights.', $detail->body);
        $this->assertStringContainsString('I can start this month.', $detail->body);
        $this->assertStringContainsString('Monday', $detail->body);
        $this->assertStringNotContainsString('<select', $detail->body);
        $saved = $ownerClient->follow($ownerClient->post('/admin/applications/' . $id, [
            '_csrf' => $ownerClient->csrf(),
            'status' => 'hired',
        ]));
        $this->assertStringContainsString('Status saved.', $saved->body);
        $this->assertSame('hired', Database::value('SELECT status FROM applications WHERE id = ?', [$id]));

        $bad = $ownerClient->post('/admin/applications/' . $id, [
            '_csrf' => $ownerClient->csrf(),
            'status' => 'promoted',
        ]);
        $this->assertSame(422, $bad->status);
        $this->assertStringContainsString('Choose a status: New, Contacted, Hired, or Declined.', $bad->body);
        $this->assertSame('hired', Database::value('SELECT status FROM applications WHERE id = ?', [$id]));
        $this->assertSame(404, $ownerClient->get('/admin/applications/999999')->status);

        $manager = $this->makeStaff('manager', 'apply-manager@example.test');
        $managerClient = $this->client();
        $managerClient->ip = '10.40.5.7';
        $this->signIn($managerClient, $manager);
        $this->assertSame(200, $managerClient->get('/admin/applications')->status);
        $this->assertSame(200, $managerClient->get('/admin/applications/' . $id)->status);
        $this->assertSame(['owner', 'manager'], application_review_roles());
    }

    public function testPorterAndOtherRolesGet403(): void
    {
        $pdo = Database::pdo();
        $pdo->exec("ALTER TABLE users MODIFY role ENUM('owner','manager','porter','other') NOT NULL");
        try {
            $id = $this->storeApplication('10.40.6.1');
            $porter = $this->makeStaff('porter', 'apply-porter@example.test');
            $other = $this->makeStaff('other', 'apply-other@example.test');
            foreach ([$porter, $other] as $staff) {
                $client = $this->client();
                $client->ip = '10.40.6.' . $staff['id'];
                $this->signIn($client, $staff);
                $token = $client->csrf();
                $this->assertSame(403, $client->get('/admin/applications')->status, $staff['role']);
                $this->assertSame(403, $client->get('/admin/applications/' . $id)->status, $staff['role']);
                $this->assertSame(403, $client->post('/admin/applications/' . $id, [
                    '_csrf' => $token,
                    'status' => 'declined',
                ])->status, $staff['role']);
            }
            $this->assertSame('new', Database::value('SELECT status FROM applications WHERE id = ?', [$id]));
        } finally {
            Database::exec("DELETE FROM users WHERE role = 'other'");
            $pdo->exec("ALTER TABLE users MODIFY role ENUM('owner','manager','porter') NOT NULL");
        }
    }

    public function testPublicLinksAndPrivacyCopy(): void
    {
        $client = $this->client();
        $home = $client->get('/');
        $this->assertStringContainsString('Now hiring porters.', $home->body);
        $this->assertStringContainsString('href="/apply"', $home->body);
        $heroEnd = strpos($home->body, 'hero-card');
        $hiringAt = strpos($home->body, 'Now hiring porters.');
        $this->assertNotFalse($heroEnd);
        $this->assertNotFalse($hiringAt);
        $this->assertGreaterThan($heroEnd, $hiringAt);
        $this->assertStringContainsString('Doorstep valet trash for your residents, Monday through Friday.', $home->body);

        $privacy = $client->get('/privacy');
        $this->assertStringContainsString('whether you have reliable transportation', $privacy->body);
        $this->assertStringContainsString('whether you are 18 or older', $privacy->body);
        $this->assertStringContainsString('only to consider you for porter work', $privacy->body);
        $this->assertStringContainsString('only for hiring', $privacy->body);
        $this->assertStringContainsString('[retention period: needs Jaamal]', $privacy->body);

        $this->assertStringContainsString('/apply', $client->get('/sitemap.xml')->body);
        $footer = $client->get('/faq');
        $this->assertStringContainsString('Apply now', $footer->body);
    }

    /** @return list<array{0:string,1:array<string,mixed>,2:string}> */
    public static function invalidApplications(): array
    {
        $long = str_repeat('a', 4001);
        return [
            ['full_name', ['full_name' => ''], 'Enter your full name using 2 to 120 characters.'],
            ['full_name', ['full_name' => 'A'], 'Enter your full name using 2 to 120 characters.'],
            ['phone', ['phone' => ''], 'Enter a phone number with 7 to 15 digits.'],
            ['phone', ['phone' => '123'], 'Enter a phone number with 7 to 15 digits.'],
            ['email', ['email' => ''], 'Enter a valid email address, like name@example.com.'],
            ['email', ['email' => 'not-an-email'], 'Enter a valid email address, like name@example.com.'],
            ['nights', ['nights' => []], 'Choose at least one weeknight, Monday through Friday.'],
            ['nights', ['nights' => ['sat']], 'Choose at least one weeknight, Monday through Friday.'],
            ['has_transportation', ['has_transportation' => ''], 'Choose yes or no for reliable transportation.'],
            ['has_transportation', ['has_transportation' => 'maybe'], 'Choose yes or no for reliable transportation.'],
            ['is_adult', ['is_adult' => ''], 'Choose yes or no for whether you are 18 or older.'],
            ['is_adult', ['is_adult' => 'maybe'], 'Choose yes or no for whether you are 18 or older.'],
            ['experience', ['experience' => $long], 'Past work experience is limited to 4,000 characters. Shorten it and send the application again.'],
            ['notes', ['notes' => $long], 'The extra note is limited to 4,000 characters. Shorten it and send the application again.'],
        ];
    }

    /** @param array<string,mixed> $extra */
    private function payload(string $csrf, array $extra = []): array
    {
        return array_merge([
            '_csrf' => $csrf,
            'full_name' => 'Taylor Applicant',
            'phone' => '229-555-0100',
            'email' => 'taylor@example.test',
            'nights' => ['mon', 'wed'],
            'has_transportation' => 'yes',
            'is_adult' => 'yes',
            'experience' => '',
            'notes' => '',
            'company_website' => '',
        ], $extra);
    }

    /** @param array<string,mixed> $extra */
    private function storeApplication(string $ip, array $extra = []): int
    {
        $client = $this->client();
        $client->ip = $ip;
        $client->get('/apply');
        $done = $client->follow($client->post('/apply', $this->payload($client->csrf(), $extra)));
        $this->assertSame(200, $done->status);
        return (int) Database::value('SELECT id FROM applications ORDER BY id DESC LIMIT 1');
    }
}
