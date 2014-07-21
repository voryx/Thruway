<?php

if (file_exists(__DIR__.'/../../../../autoload.php')) {
    require __DIR__.'/../../../../autoload.php';
} else {
    require __DIR__ . '/../../vendor/autoload.php';
}

require 'SimpleClientAuth.php';

$client = new \Thruway\Peer\Client('somerealm');

$client->addClientAuthenticator(new SimpleClientAuth());

$client->addTransportProvider(new \Thruway\Transport\PawlTransportProvider());

$client->start();