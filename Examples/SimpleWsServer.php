<?php

require __DIR__.'/../../../autoload.php';

use AutobahnPHP\Transport\RatchetTransport;
use AutobahnPHP\Peer\Router;

$router = new Router();

$transport = new RatchetTransport($router, "127.0.0.1", 9000);

$transport->startTransport();