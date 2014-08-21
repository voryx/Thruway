<?php

require "../bootstrap.php";
require 'SimpleClientAuth.php';

$client = new \Thruway\Peer\Client('somerealm');

$client->addClientAuthenticator(new SimpleClientAuth());

$client->addTransportProvider(new \Thruway\Transport\PawlTransportProvider());

$client->start();