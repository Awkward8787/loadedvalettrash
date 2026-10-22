<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap/start.php';

use Lvt\Database;

$email = strtolower(trim((string) env('ADMIN_EMAIL', '')));
$password = (string) env('ADMIN_PASSWORD', '');
$name = trim((string) env('ADMIN_NAME', 'Anthony Jaamal Jones'));
if ($name === '') {
    $name = 'Anthony Jaamal Jones';
}

if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
    fwrite(STDERR, "Set ADMIN_EMAIL in the environment before seeding.\n");
    exit(1);
}
$problem = password_problem($password);
if ($problem !== null) {
    fwrite(STDERR, "ADMIN_PASSWORD: {$problem}\n");
    exit(1);
}

$ready = Database::value('SELECT COUNT(*) FROM settings WHERE id = 1');
if ((int) $ready !== 1) {
    fwrite(STDERR, "Run php bin/migrate.php before seeding.\n");
    exit(1);
}

$existing = Database::one('SELECT id, email FROM users WHERE role = "owner" ORDER BY id LIMIT 1');
if ($existing) {
    echo "An owner account already exists ({$existing['email']}). Nothing was changed.\n";
    exit(0);
}

$now = now_dt();
Database::insertRow('users', [
    'name' => $name,
    'email' => $email,
    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    'role' => 'owner',
    'phone' => null,
    'pay_rate_cents' => 0,
    'availability' => null,
    'totp_secret' => null,
    'totp_enabled' => 0,
    'active' => 1,
    'last_login_at' => null,
    'created_at' => $now,
    'updated_at' => $now,
]);

echo "Created the owner account for {$email}.\n";
echo "Sign in once and turn on the authenticator. No other records were created.\n";
