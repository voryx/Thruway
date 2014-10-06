<?php

require '../bootstrap.php';
require 'RawSocketClient.php';

$client = new RawSocketClient('realm1');

$client->addTransportProvider(new \Thruway\Transport\RawSocketClientTransportProvider('127.0.0.1', 8181));

$client->start();