<?php
require 'bootstrap.php';

$client = new \Thruway\Tests\Clients\OrphaningClient('testRealm');

$client->addTransportProvider(new \Thruway\Transport\PawlTransportProvider('ws://127.0.0.1:8090/'));

$client->start();