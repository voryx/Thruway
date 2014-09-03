<?php

require 'bootstrap.php';
require 'Clients/InternalClient.php';
require 'Clients/SimpleAuthProviderClient.php';

use Thruway\Peer\Router;
use Thruway\Transport\RatchetTransportProvider;

$mgr = new \Thruway\Manager\ManagerDummy();
$mgr->setLogger(new \Thruway\ConsoleLogger());

$router = new Router(null, $mgr);



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



