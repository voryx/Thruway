<?php

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/RawSocketClient.php';

$client = new RawSocketClient('realm1');

$client->addTransportProvider(new \Thruway\Transport\RawSocketClientTransportProvider('127.0.0.1', 8181));

$client->start();