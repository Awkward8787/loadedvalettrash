<?php

declare(strict_types=1);

namespace Lvt\Tests;

use Lvt\Database;
use Lvt\Totp;

final class AuthTest extends TestCase
{
    public function testPasswordIsHashedAndAWrongPasswordIsRejected(): void
    {
        $staff = $this->makeStaff('owner', 'owner@example.test');
        $row = Database::one('SELECT password_hash FROM users WHERE id = ?', [$staff['id']]);
        $this->assertNotSame($staff['password'], $row['password_hash']);
        $this->assertTrue(password_verify($staff['password'], (string) $row['password_hash']));

        $client = $this->client();
        $client->ip = '10.9.1.1';
        $client->get('/login');
        $response = $client->post('/login', [
            '_csrf' => $client->csrf(),
            'email' => $staff['email'],
            'password' => 'not-the-password',
        ]);
        $this->assertSame(422, $response->status);
        $this->assertStringContainsString('do not match', $response->body);
    }

    public function testMissingCsrfIsRejected(): void
    {
        $this->makeStaff('owner', 'csrf@example.test');
        $client = $this->client();
        $client->get('/login');
        $response = $client->post('/login', [
            'email' => 'csrf@example.test',
            'password' => 'CorrectHorse1',
        ]);
        $this->assertSame(419, $response->status);
    }

    public function testRateLimitBlocksEvenACorrectPassword(): void
    {
        $staff = $this->makeStaff('owner', 'locked@example.test');
        $client = $this->client();
        $client->ip = '10.9.2.2';
        for ($i = 0; $i < 5; $i++) {
            $client->get('/login');
            $response = $client->post('/login', [
                '_csrf' => $client->csrf(),
                'email' => $staff['email'],
                'password' => 'wrong-password-' . $i,
            ]);
            $this->assertSame(422, $response->status);
        }
        $client->get('/login');
        $blocked = $client->post('/login', [
            '_csrf' => $client->csrf(),
            'email' => $staff['email'],
            'password' => $staff['password'],
        ]);
        $this->assertSame(429, $blocked->status);
        $this->assertStringContainsString('Too many attempts', $blocked->body);
    }

    public function testEnrollmentThenRecoveryCodeWorksOnce(): void
    {
        $staff = $this->makeStaff('owner', 'enroll@example.test', false);
        $client = $this->client();
        $client->ip = '10.9.3.3';
        $client->get('/login');
        $client->follow($client->post('/login', [
            '_csrf' => $client->csrf(),
            'email' => $staff['email'],
            'password' => $staff['password'],
        ]));
        $this->assertMatchesRegularExpression('/id="totp-secret">([^<]+)</', $client->lastBody);
        preg_match('/id="totp-secret">([^<]+)</', $client->lastBody, $secretMatch);
        $secret = (string) preg_replace('/\s+/', '', $secretMatch[1]);
        $enrolled = $client->follow($client->post('/account/security', [
            '_csrf' => $client->csrf(),
            'code' => Totp::code($secret),
        ]));
        preg_match_all('/<code>([A-Z0-9]{4}-[A-Z0-9]{4})<\/code>/', $enrolled->body, $codes);
        $this->assertCount(8, $codes[1]);
        $recovery = $codes[1][0];

        $client->post('/logout', ['_csrf' => $client->csrf()]);
        $fresh = $this->client();
        $fresh->ip = '10.9.3.4';
        $fresh->get('/login');
        $fresh->follow($fresh->post('/login', [
            '_csrf' => $fresh->csrf(),
            'email' => $staff['email'],
            'password' => $staff['password'],
        ]));
        $ok = $fresh->follow($fresh->post('/login/2fa', [
            '_csrf' => $fresh->csrf(),
            'code' => $recovery,
        ]));
        $this->assertStringContainsString('Dashboard', $ok->body);

        $fresh->post('/logout', ['_csrf' => $fresh->csrf()]);
        $again = $this->client();
        $again->ip = '10.9.3.5';
        $again->get('/login');
        $again->follow($again->post('/login', [
            '_csrf' => $again->csrf(),
            'email' => $staff['email'],
            'password' => $staff['password'],
        ]));
        $reused = $again->post('/login/2fa', [
            '_csrf' => $again->csrf(),
            'code' => $recovery,
        ]);
        $this->assertSame(422, $reused->status);
    }

    public function testSessionTimeoutRequiresANewSignIn(): void
    {
        $staff = $this->makeStaff('owner', 'timeout@example.test');
        $client = $this->client();
        $client->ip = '10.9.4.4';
        $this->signIn($client, $staff);
        Database::exec(
            'UPDATE user_sessions SET last_activity = ?, expires_at = ?',
            ['2026-10-22 00:00:00', '2026-10-22 00:30:00']
        );
        $response = $client->get('/admin');
        $this->assertSame(302, $response->status);
        $this->assertStringContainsString('/login', (string) $response->headers['Location']);
    }
}
