<?php

if (file_exists(__DIR__.'/../../../autoload.php')) {
    require __DIR__.'/../../../autoload.php';
} else {
    require __DIR__.'/../vendor/autoload.php';
}

use AutobahnPHP\Transport\RatchetTransport;
use AutobahnPHP\Peer\Router;

$router = new Router();

$transport = new RatchetTransport($router, "127.0.0.1", 9090);

$transport->startTransport();