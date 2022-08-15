<?php

declare(strict_types=1);

use GSteel\GoogleTimezone\Exception\InvalidArgument;
use GSteel\GoogleTimezone\Test\Integration\Framework\MockServer;

require __DIR__ . '/../../../vendor/autoload.php';

$port = $argv[1] ?? 8085;
if (! is_numeric($port)) {
    throw new InvalidArgument('Port argument must be a number');
}

$server = new MockServer((int) $port);
$server->start();
