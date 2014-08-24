<?php

require 'bootstrap.php';
require 'Clients/InternalClient.php';
require 'Clients/SimpleAuthProviderClient.php';

use Thruway\Peer\Router;
use Thruway\Transport\RatchetTransportProvider;

$router = new Router();

$authMgr = new \Thruway\Authentication\AuthenticationManager();

$router->setAuthenticationManager($authMgr);
$router->addTransportProvider(new \Thruway\Transport\InternalClientTransportProvider($authMgr));

//Provide authentication for the realm: 'testSimpleAuthRealm'
$authProvClient = new SimpleAuthProviderClient(["testSimpleAuthRealm"]);
$router->addTransportProvider(new \Thruway\Transport\InternalClientTransportProvider($authProvClient));


$transportProvider = new RatchetTransportProvider("127.0.0.1", 8080);

$router->addTransportProvider($transportProvider);

$theInternalClient = new InternalClient('testRealm');
$theInternalClient->setRouter($router);

$internalTransportProvider = new Thruway\Transport\InternalClientTransportProvider($theInternalClient);
$router->addTransportProvider($internalTransportProvider);

$router->start();



