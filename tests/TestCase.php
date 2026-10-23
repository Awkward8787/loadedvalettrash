<?php

declare(strict_types=1);

namespace Lvt\Tests;

use Lvt\Auth;
use Lvt\Crypto;
use Lvt\Database;
use Lvt\Settings;
use Lvt\Totp;
use PHPUnit\Framework\TestCase as PhpUnitTestCase;

abstract class TestCase extends PhpUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Auth::resetCache();
        Settings::flush();
        $pdo = Database::pdo();
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        foreach ([
            'audit_log',
            'invoices',
            'inquiries',
            'job_applications',
            'reviews',
            'issues',
            'time_entries',
            'door_checks',
            'route_stops',
            'route_runs',
            'route_template_stops',
            'route_templates',
            'residents',
            'buildings',
            'property_notes',
            'property_contacts',
            'properties',
            'management_companies',
            'user_sessions',
            'login_attempts',
            'recovery_codes',
            'users',
        ] as $table) {
            $pdo->exec('TRUNCATE TABLE ' . $table);
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
        Database::exec(
            'UPDATE settings SET phone = NULL, email = NULL, address = NULL, insurance_text = NULL,
             opt_in_price_cents = 3500, default_property_share_percent = 10.00,
             minutes_per_door = 1.00, minutes_per_site = 45, pickup_window_minutes = 120,
             default_wage_cents = 1500, payroll_multiplier = 1.10,
             per_site_supplies_cents = 4000, per_site_gas_cents = 9000,
             per_site_vehicle_reserve_cents = 2500, monthly_fixed_costs_cents = 34900,
             remittance_due_day = 10, session_timeout_minutes = 30
             WHERE id = 1'
        );
        Settings::flush();
    }

    protected function client(): HttpClient
    {
        return new HttpClient();
    }

    /** @return array{id:int,email:string,password:string,secret:string,role:string} */
    protected function makeStaff(string $role, string $email, bool $totp = true): array
    {
        $password = 'CorrectHorse1';
        $secret = Totp::generateSecret();
        $now = now_dt();
        $id = Database::insertRow('users', [
            'name' => ucfirst($role) . ' Test',
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => $role,
            'phone' => null,
            'pay_rate_cents' => $role === 'porter' ? 1500 : 0,
            'availability' => null,
            'totp_secret' => $totp ? Crypto::encrypt($secret) : null,
            'totp_enabled' => $totp ? 1 : 0,
            'active' => 1,
            'last_login_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        return [
            'id' => $id,
            'email' => $email,
            'password' => $password,
            'secret' => $secret,
            'role' => $role,
        ];
    }

    protected function signIn(HttpClient $client, array $staff): void
    {
        $client->get('/login');
        $response = $client->post('/login', [
            '_csrf' => $client->csrf(),
            'email' => $staff['email'],
            'password' => $staff['password'],
            'next' => '/admin',
        ]);
        $response = $client->follow($response);
        if (str_contains($response->body, 'id="totp-secret"')) {
            if (preg_match('/id="totp-secret">([^<]+)</', $response->body, $match) !== 1) {
                $this->fail('Enrollment secret was not on the page.');
            }
            $secret = (string) preg_replace('/\s+/', '', $match[1]);
            $posted = $client->post('/account/security', [
                '_csrf' => $client->csrf(),
                'code' => Totp::code($secret),
            ]);
            $client->follow($posted);
            return;
        }
        $this->assertStringContainsString('Authenticator code', $response->body);
        $posted = $client->post('/login/2fa', [
            '_csrf' => $client->csrf(),
            'code' => Totp::code($staff['secret']),
        ]);
        $landed = $client->follow($posted);
        $this->assertLessThan(400, $landed->status);
    }

    protected function insertProperty(string $name, array $extra = []): int
    {
        $now = now_dt();
        return Database::insertRow('properties', array_merge([
            'management_company_id' => null,
            'name' => $name,
            'address' => '1 Test Street',
            'city' => 'Valdosta',
            'state' => 'GA',
            'postal_code' => '31601',
            'units_count' => 60,
            'planned_doors' => null,
            'website' => null,
            'has_valet_trash' => 'unknown',
            'stage' => 'live',
            'lost_reason' => null,
            'pricing_model' => 'opt_in',
            'opt_in_price_cents' => null,
            'property_share_percent' => null,
            'per_door_rate_cents' => null,
            'flat_rate_cents' => null,
            'service_days' => 'mon,tue,wed,thu,fri',
            'setout_start' => null,
            'setout_end' => null,
            'pickup_start' => null,
            'pickup_end' => null,
            'minutes_per_door' => null,
            'minutes_fixed' => null,
            'compactor_location' => 'North lot',
            'gate_access_notes' => null,
            'contract_start' => null,
            'contract_end' => null,
            'trial_start' => null,
            'trial_end' => null,
            'follow_up_on' => null,
            'supplies_cents' => null,
            'gas_cents' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ], $extra));
    }
}
