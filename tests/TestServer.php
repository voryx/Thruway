<?php

require 'bootstrap.php';
require 'Clients/InternalClient.php';

use Thruway\Peer\Router;
use Thruway\Transport\RatchetTransportProvider;

$router = new Router();

$transportProvider = new RatchetTransportProvider("127.0.0.1", 8080);

$router->addTransportProvider($transportProvider);


$internalTransportProvider = new Thruway\Transport\InternalClientTransportProvider(new InternalClient('testRealm'));
$router->addTransportProvider($internalTransportProvider);

$router->start();