<?php

require 'bootstrap.php';
require 'Clients/InternalClient.php';
require 'Clients/SimpleAuthProviderClient.php';
require 'Clients/AbortAfterHelloAuthProviderClient.php';


use Thruway\Peer\Router;
use Thruway\Transport\RatchetTransportProvider;

$timeout = isset($argv[1]) ? $argv[1] : 0;

$mgr = new \Thruway\Manager\ManagerDummy();
$mgr->setLogger(new \Thruway\ConsoleLogger());

$router = new Router(null, $mgr);

$loop = $router->getLoop();


$authMgr = new \Thruway\Authentication\AuthenticationManager();

$router->setAuthenticationManager($authMgr);
$router->addTransportProvider(new \Thruway\Transport\InternalClientTransportProvider($authMgr));

//Provide authentication for the realm: 'testSimpleAuthRealm'
$authProvClient = new SimpleAuthProviderClient(["testSimpleAuthRealm"]);
$router->addTransportProvider(new \Thruway\Transport\InternalClientTransportProvider($authProvClient));

// provide aborting auth provider
$authAbortAfterHello = new AbortAfterHelloAuthProviderClient(["abortafterhello"]);
$router->addTransportProvider(new \Thruway\Transport\InternalClientTransportProvider($authAbortAfterHello));

$transportProvider = new RatchetTransportProvider("127.0.0.1", 8090);

$router->addTransportProvider($transportProvider);

$theInternalClient = new InternalClient('testRealm', $loop);
$theInternalClient->setRouter($router);

$internalTransportProvider = new Thruway\Transport\InternalClientTransportProvider($theInternalClient);
$router->addTransportProvider($internalTransportProvider);

if ($timeout) {
    $loop->addTimer($timeout, function () use ($loop) {
            $loop->stop();
        }
    );
}

$router->start();



