<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

if (getenv('APP_ENV') === 'testing') {
    load_env_file(dirname(__DIR__) . '/.env.testing');
} else {
    load_env_file(dirname(__DIR__) . '/.env');
}

date_default_timezone_set((string) env('APP_TIMEZONE', 'America/New_York'));
