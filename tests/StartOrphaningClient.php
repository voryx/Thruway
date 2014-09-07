<?php
require 'bootstrap.php';
require 'Clients/OrphaningClient.php';

$client = new OrphaningClient('testRealm');

$client->addTransportProvider(new \Thruway\Transport\PawlTransportProvider('ws://127.0.0.1:8080/'));

$client->start();