<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$dirs = ['src', 'templates', 'bin', 'tests', 'public', 'bootstrap'];
$failed = 0;
$checked = 0;

foreach ($dirs as $dir) {
    $base = $root . '/' . $dir;
    if (!is_dir($base)) {
        continue;
    }
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if (!$file instanceof SplFileInfo || $file->getExtension() !== 'php') {
            continue;
        }
        $path = $file->getPathname();
        $checked++;
        $cmd = 'php -l ' . escapeshellarg($path) . ' 2>&1';
        $output = [];
        $code = 0;
        exec($cmd, $output, $code);
        if ($code !== 0) {
            $failed++;
            fwrite(STDERR, implode("\n", $output) . "\n");
        }
    }
}

if ($failed > 0) {
    fwrite(STDERR, "{$failed} file(s) failed syntax check.\n");
    exit(1);
}

echo "Syntax OK ({$checked} PHP files).\n";
