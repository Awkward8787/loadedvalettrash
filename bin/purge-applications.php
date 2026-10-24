<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    if (function_exists('http_response_code')) {
        http_response_code(403);
    }
    $message = "This script can only run from the command line.\n";
    if (defined('STDERR')) {
        fwrite(STDERR, $message);
    } else {
        echo $message;
    }
    exit(1);
}

require dirname(__DIR__) . '/bootstrap/start.php';

use Lvt\Database;

try {
    $cutoff = (new DateTimeImmutable('now'))->modify('-1 year')->format('Y-m-d H:i:s');
    $statement = Database::pdo()->prepare('DELETE FROM job_applications WHERE created_at < ?');
    $statement->execute([$cutoff]);
    $deleted = $statement->rowCount();
    if ($deleted < 0) {
        fwrite(STDERR, "Could not delete old applications.\n");
        exit(1);
    }
    echo 'Deleted ' . $deleted . " application(s).\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Could not delete old applications: ' . $e->getMessage() . "\n");
    exit(1);
}

exit(0);
