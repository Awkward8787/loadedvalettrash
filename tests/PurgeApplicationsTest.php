<?php

declare(strict_types=1);

namespace Lvt\Tests;

use Lvt\Database;

final class PurgeApplicationsTest extends TestCase
{
    public function testPurgeDeletesApplicationsOlderThanOneYearAndKeepsNewerRows(): void
    {
        $now = new \DateTimeImmutable('now');
        $this->insertApplication('Older Than A Year', $now->modify('-1 year')->modify('-1 day')->format('Y-m-d H:i:s'));
        $this->insertApplication('Inside The Year', $now->modify('-1 year')->modify('+1 day')->format('Y-m-d H:i:s'));
        $this->insertApplication('Submitted Yesterday', $now->modify('-1 day')->format('Y-m-d H:i:s'));

        $first = $this->runPurge();
        $this->assertSame(0, $first['code'], $first['output']);
        $this->assertStringContainsString('Deleted 1 application(s).', $first['output']);
        $this->assertNull(Database::one('SELECT id FROM job_applications WHERE full_name = ?', ['Older Than A Year']));
        $this->assertNotNull(Database::one('SELECT id FROM job_applications WHERE full_name = ?', ['Inside The Year']));
        $this->assertNotNull(Database::one('SELECT id FROM job_applications WHERE full_name = ?', ['Submitted Yesterday']));
        $this->assertSame(2, (int) Database::value('SELECT COUNT(*) FROM job_applications'));

        $second = $this->runPurge();
        $this->assertSame(0, $second['code'], $second['output']);
        $this->assertStringContainsString('Deleted 0 application(s).', $second['output']);
        $this->assertSame(2, (int) Database::value('SELECT COUNT(*) FROM job_applications'));
    }

    /** @return array{code:int,output:string} */
    private function runPurge(): array
    {
        $script = dirname(__DIR__) . '/bin/purge-applications.php';
        $output = [];
        $code = 0;
        exec('php ' . escapeshellarg($script) . ' 2>&1', $output, $code);
        return ['code' => $code, 'output' => implode("\n", $output)];
    }

    private function insertApplication(string $name, string $createdAt): void
    {
        Database::insertRow('job_applications', [
            'role' => 'laborer',
            'full_name' => $name,
            'phone' => '(229) 555-0100',
            'email' => null,
            'city' => 'Valdosta',
            'is_adult' => 1,
            'has_transportation' => 1,
            'days_available' => 'mon',
            'can_lift' => 1,
            'experience' => null,
            'start_date' => '2026-11-02',
            'heard_about' => 'Test',
            'consent' => 1,
            'status' => 'new',
            'notes' => null,
            'ip' => '127.0.0.1',
            'created_at' => $createdAt,
        ]);
    }
}
