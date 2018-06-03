<?php

require __DIR__ . "/../bootstrap.php";
require 'SimpleClientAuth.php';

$url = "ws://127.0.0.1:9090/";

$client = new \Thruway\Peer\Client('somerealm');

$client->addClientAuthenticator(new SimpleClientAuth());

$client->addTransportProvider(new \Thruway\Transport\PawlTransportProvider($url));

$client->start();
