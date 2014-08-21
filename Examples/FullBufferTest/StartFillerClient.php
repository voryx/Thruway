<?php
require "../bootstrap.php";
require __DIR__ . '/BufferFillerClient.php';

$client = new BufferFillerClient('myrealm');

$client->addTransportProvider(new \Thruway\Transport\PawlTransportProvider('ws://127.0.0.1:9090/'));

$client->start();