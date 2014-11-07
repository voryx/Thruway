<?php

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/Clients/InternalClient.php';
require_once __DIR__ . '/Clients/SimpleAuthProviderClient.php';
require_once __DIR__ . '/Clients/AbortAfterHelloAuthProviderClient.php';
require_once __DIR__ . '/Clients/DisclosePublisherClient.php';

use Thruway\Logging\Logger;
use Thruway\Peer\Router;
use Thruway\Transport\RatchetTransportProvider;

//Logger::set(new \Psr\Log\NullLogger());


$timeout = isset($argv[1]) ? $argv[1] : 0;

$router = new Router();

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
$authorizingRealm     = new \Thruway\Realm('authorizing_realm');
$authorizingRealm->setAuthorizationManager($authorizationManager);
$router->getRealmManager()->addRealm($authorizingRealm);
$router->addTransportProvider(new \Thruway\Transport\InternalClientTransportProvider($authorizationManager));
// Create a realm with Authentication also
// to test some stuff
$authAndAuthAuthorizer = new \Thruway\Authentication\AuthorizationManager("authful_realm");
$authAndAuthRealm      = new \Thruway\Realm("authful_realm");
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

//Client for Disclose Publisher Test
$dpClient                  = new DisclosePublisherClient('testSimpleAuthRealm', $loop);
$internalTransportProvider = new Thruway\Transport\InternalClientTransportProvider($dpClient);
$router->addTransportProvider($internalTransportProvider);

if ($timeout) {
    $loop->addTimer($timeout, function () use ($loop) {
            $loop->stop();
        }
    );
}

$router->start();



