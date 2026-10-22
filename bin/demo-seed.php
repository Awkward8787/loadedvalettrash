<?php

declare(strict_types=1);

/**
 * Local development data only. Refuses to run unless APP_ENV=local.
 * Every record is labeled DEMO. No reviews are created.
 * Passwords and authenticator secrets are written to storage/demo-credentials.txt,
 * which is gitignored. Do not run this on Hostinger.
 */

require dirname(__DIR__) . '/bootstrap/start.php';

use Lvt\Crypto;
use Lvt\Database;
use Lvt\Routing\Builder;
use Lvt\Totp;

if (env('APP_ENV') !== 'local') {
    fwrite(STDERR, "Refusing to load demo data because APP_ENV is not local.\n");
    exit(1);
}

if ((int) Database::value('SELECT COUNT(*) FROM settings WHERE id = 1') !== 1) {
    fwrite(STDERR, "Run php bin/migrate.php first.\n");
    exit(1);
}

if (Database::one('SELECT id FROM properties WHERE name = ?', ['DEMO Oak Court'])) {
    echo "DEMO Oak Court already exists. Nothing was changed.\n";
    exit(0);
}

$now = now_dt();
$lines = [
    'Loaded Valet Trash — LOCAL DEMO CREDENTIALS',
    'These accounts exist only because bin/demo-seed.php was run with APP_ENV=local.',
    'Do not use them in production. This file is gitignored.',
    'Generated ' . $now,
    '',
];

$porterSecret = Totp::generateSecret();
$managerSecret = Totp::generateSecret();
$porterPassword = 'DemoPorter' . random_int(1000, 9999) . 'a';
$managerPassword = 'DemoManager' . random_int(1000, 9999) . 'a';

$managerId = Database::insertRow('users', [
    'name' => 'DEMO Manager',
    'email' => 'demo-manager@example.test',
    'password_hash' => password_hash($managerPassword, PASSWORD_DEFAULT),
    'role' => 'manager',
    'phone' => '229-555-0191',
    'pay_rate_cents' => 0,
    'availability' => 'Office hours',
    'totp_secret' => Crypto::encrypt($managerSecret),
    'totp_enabled' => 1,
    'active' => 1,
    'last_login_at' => null,
    'created_at' => $now,
    'updated_at' => $now,
]);

$porterId = Database::insertRow('users', [
    'name' => 'DEMO Porter',
    'email' => 'demo-porter@example.test',
    'password_hash' => password_hash($porterPassword, PASSWORD_DEFAULT),
    'role' => 'porter',
    'phone' => '229-555-0192',
    'pay_rate_cents' => 1500,
    'availability' => 'Mon–Fri evenings',
    'totp_secret' => Crypto::encrypt($porterSecret),
    'totp_enabled' => 1,
    'active' => 1,
    'last_login_at' => null,
    'created_at' => $now,
    'updated_at' => $now,
]);

$lines[] = 'Manager  demo-manager@example.test';
$lines[] = 'Password ' . $managerPassword;
$lines[] = 'TOTP     ' . $managerSecret;
$lines[] = '';
$lines[] = 'Porter   demo-porter@example.test';
$lines[] = 'Password ' . $porterPassword;
$lines[] = 'TOTP     ' . $porterSecret;
$lines[] = '';
$lines[] = 'The owner account is created only by bin/seed.php from ADMIN_EMAIL and ADMIN_PASSWORD.';
$lines[] = 'The owner still enrolls an authenticator on first sign-in. That secret is not written here.';

$companyId = Database::insertRow('management_companies', [
    'name' => 'DEMO Management Co',
    'notes' => 'Local demo company. Not a real client.',
    'created_at' => $now,
    'updated_at' => $now,
]);

$propertyId = Database::insertRow('properties', [
    'management_company_id' => $companyId,
    'name' => 'DEMO Oak Court',
    'address' => '100 Demo Lane',
    'city' => 'Valdosta',
    'state' => 'GA',
    'postal_code' => '31601',
    'units_count' => 48,
    'planned_doors' => 12,
    'website' => null,
    'has_valet_trash' => 'no',
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
    'compactor_location' => 'DEMO compactor at the north end of the lot',
    'gate_access_notes' => 'DEMO access note for local screenshots only. Not a real code.',
    'contract_start' => today_date(),
    'contract_end' => null,
    'trial_start' => null,
    'trial_end' => null,
    'follow_up_on' => null,
    'supplies_cents' => null,
    'gas_cents' => null,
    'created_at' => $now,
    'updated_at' => $now,
]);

$buildingId = Database::insertRow('buildings', [
    'property_id' => $propertyId,
    'name' => 'DEMO Building A',
    'floors' => 2,
    'unit_count' => 12,
    'sort_order' => 1,
    'hazards_notes' => null,
    'compactor_trip_after' => 1,
]);

foreach (['A1', 'A2', 'A3'] as $unit) {
    Database::insertRow('residents', [
        'property_id' => $propertyId,
        'building_id' => $buildingId,
        'unit_number' => $unit,
        'name' => 'DEMO Resident ' . $unit,
        'start_date' => today_date(),
        'cancel_date' => null,
        'status' => 'active',
        'notes' => 'Local demo roster row',
        'created_at' => $now,
        'updated_at' => $now,
    ]);
}

Database::insertRow('property_contacts', [
    'property_id' => $propertyId,
    'name' => 'DEMO Leasing Contact',
    'role_title' => 'Community manager',
    'phone' => '229-555-0193',
    'email' => 'demo-office@example.test',
    'is_primary' => 1,
    'created_at' => $now,
]);

$leadId = Database::insertRow('properties', [
    'management_company_id' => $companyId,
    'name' => 'DEMO Pine Court',
    'address' => '200 Demo Lane',
    'city' => 'Hahira',
    'state' => 'GA',
    'postal_code' => '31632',
    'units_count' => 80,
    'planned_doors' => null,
    'website' => null,
    'has_valet_trash' => 'unknown',
    'stage' => 'walkthrough',
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
    'compactor_location' => null,
    'gate_access_notes' => null,
    'contract_start' => null,
    'contract_end' => null,
    'trial_start' => null,
    'trial_end' => null,
    'follow_up_on' => today_date(),
    'supplies_cents' => null,
    'gas_cents' => null,
    'created_at' => $now,
    'updated_at' => $now,
]);

$dow = (int) date('N');
if ($dow >= 1 && $dow <= 5) {
    Builder::createRun(today_date(), $porterId, '20:00:00', [$propertyId]);
}

$path = dirname(__DIR__) . '/storage/demo-credentials.txt';
file_put_contents($path, implode(PHP_EOL, $lines) . PHP_EOL);
chmod($path, 0600);

echo "Demo data loaded. Credentials written to storage/demo-credentials.txt\n";
echo "Manager user id {$managerId}, porter user id {$porterId}, live property id {$propertyId}, lead id {$leadId}.\n";
echo "No reviews were created.\n";
