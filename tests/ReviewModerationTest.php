<?php

declare(strict_types=1);

namespace Lvt\Tests;

use Lvt\Database;

final class ReviewModerationTest extends TestCase
{
    public function testPendingReviewsStayHiddenUntilApproved(): void
    {
        $client = $this->client();
        $client->ip = '10.7.1.1';
        $client->get('/reviews/new');
        $body = 'The breezeways were clear every night we walked them this month.';
        $submitted = $client->follow($client->post('/reviews', [
            '_csrf' => $client->csrf(),
            'reviewer_name' => 'Casey Manager',
            'property_name' => 'Quiet Court',
            'role_title' => 'Community manager',
            'rating' => '5',
            'body' => $body,
            'company_website' => '',
        ]));
        $this->assertStringContainsString('approval queue', $submitted->body);
        $row = Database::one('SELECT status FROM reviews WHERE reviewer_name = ?', ['Casey Manager']);
        $this->assertSame('pending', $row['status']);

        $public = $client->get('/reviews');
        $this->assertStringNotContainsString($body, $public->body);
        $this->assertStringContainsString('No published reviews yet', $public->body);
        $home = $client->get('/');
        $this->assertStringNotContainsString($body, $home->body);

        $owner = $this->makeStaff('owner', 'moderator@example.test');
        $admin = $this->client();
        $admin->ip = '10.7.1.2';
        $this->signIn($admin, $owner);
        $id = (int) Database::value('SELECT id FROM reviews WHERE reviewer_name = ?', ['Casey Manager']);
        $admin->follow($admin->post('/admin/reviews/' . $id, [
            '_csrf' => $admin->csrf(),
            'decision' => 'approved',
        ]));
        $shown = $client->get('/');
        $this->assertStringContainsString($body, $shown->body);

        $admin->follow($admin->post('/admin/reviews/' . $id, [
            '_csrf' => $admin->csrf(),
            'decision' => 'rejected',
        ]));
        $hidden = $client->get('/reviews');
        $this->assertStringNotContainsString($body, $hidden->body);
        $this->assertSame('rejected', Database::value('SELECT status FROM reviews WHERE id = ?', [$id]));
    }
}
