<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap/start.php';

use Lvt\Request;

$app = require dirname(__DIR__) . '/bootstrap/app.php';
$app->handle(Request::fromGlobals())->send();
