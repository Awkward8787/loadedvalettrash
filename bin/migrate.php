<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap/start.php';

use Lvt\Database;

$dir = dirname(__DIR__) . '/migrations';
$files = glob($dir . '/*.sql');
if ($files === false || $files === []) {
    fwrite(STDERR, "No migration files in migrations/.\n");
    exit(1);
}
sort($files, SORT_STRING);

$pdo = Database::pdo();
$pdo->exec(
    'CREATE TABLE IF NOT EXISTS schema_migrations (
        version VARCHAR(190) NOT NULL PRIMARY KEY,
        applied_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
);

$applied = 0;
foreach ($files as $file) {
    $version = basename($file);
    $exists = Database::value('SELECT version FROM schema_migrations WHERE version = ?', [$version]);
    if ($exists) {
        echo "skip {$version}\n";
        continue;
    }
    $sql = (string) file_get_contents($file);
    $sql = (string) preg_replace('/^--.*$/m', '', $sql);
    $statements = preg_split('/;\s*(?:\r?\n|$)/', $sql) ?: [];
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if ($statement === '') {
            continue;
        }
        $pdo->exec($statement);
    }
    Database::insertRow('schema_migrations', [
        'version' => $version,
        'applied_at' => now_dt(),
    ]);
    echo "applied {$version}\n";
    $applied++;
}

echo $applied === 0 ? "Database is up to date.\n" : "Applied {$applied} migration(s).\n";
