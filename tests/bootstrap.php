<?php

declare(strict_types=1);

ob_start();

require dirname(__DIR__) . '/bootstrap/start.php';

$migrate = dirname(__DIR__) . '/bin/migrate.php';
$output = [];
exec('php ' . escapeshellarg($migrate) . ' 2>&1', $output, $code);
if ($code !== 0) {
    fwrite(STDERR, implode("\n", $output) . "\nTest database migration failed.\n");
    exit(1);
}
