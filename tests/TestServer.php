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
$authProvClient = new SimpleAuthProviderClient(["testSimpleAuthRealm", "authful_realm"]);
$router->addTransportProvider(new \Thruway\Transport\InternalClientTransportProvider($authProvClient));

// provide aborting auth provider
$authAbortAfterHello = new AbortAfterHelloAuthProviderClient(["abortafterhello"]);
$router->addTransportProvider(new \Thruway\Transport\InternalClientTransportProvider($authAbortAfterHello));

////////////////////
// Test stuff for Authorization
$authorizationManager = new \Thruway\Authentication\AuthorizationManager('authorizing_realm');
$authorizingRealm = new \Thruway\Realm('authorizing_realm');
$authorizingRealm->setAuthorizationManager($authorizationManager);
$router->getRealmManager()->addRealm($authorizingRealm);
$router->addTransportProvider(new \Thruway\Transport\InternalClientTransportProvider($authorizationManager));
// Create a realm with Authentication also
// to test some stuff
$authAndAuthAuthorizer = new \Thruway\Authentication\AuthorizationManager("authful_realm");
$authAndAuthRealm = new \Thruway\Realm("authful_realm");
$authAndAuthRealm->setAuthorizationManager($authAndAuthAuthorizer);
$authAndAuthRealm->setAuthenticationManager($authMgr);
$router->getRealmManager()->addRealm($authAndAuthRealm);
$router->addTransportProvider(new \Thruway\Transport\InternalClientTransportProvider($authAndAuthAuthorizer));
////////////////////

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



