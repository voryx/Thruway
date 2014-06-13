<?php

require '../vendor/autoload.php';

$loop = \React\EventLoop\Factory::create();

$transport = new \AutobahnPHP\Transport\WebsocketClient('wss://some.example.com:8080', $loop);

$transport->startTransport();

$loop->run();