<?php

require '../vendor/autoload.php';

use AutobahnPHP\Transport\RatchetTransport;

$router = new \AutobahnPHP\Router();

$transport = new RatchetTransport($router, "127.0.0.1", 8080);

$transport->startTransport();