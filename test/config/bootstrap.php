<?php

declare(strict_types=1);

$autoloadFile = __DIR__ . '/../../vendor/autoload.php';
if (! file_exists($autoloadFile)) {
    throw new RuntimeException('Install dependencies to run test suite.');
}
require_once $autoloadFile;
