<?php

require_once __DIR__ . '/bootstrap.php';

use Thruway\Peer\Router;
use Thruway\Transport\RatchetTransportProvider;

//Logger::set(new \Psr\Log\NullLogger());

$timeout = isset($argv[1]) ? $argv[1] : 0;
$router  = new Router();
$loop    = $router->getLoop();

//Create a WebSocket connection that listens on localhost port 8090
//$router->addTransportProvider(new RatchetTransportProvider("127.0.0.1", 8090));

$router->registerModules([

    // Create Authentication Manager
    new \Thruway\Authentication\AuthenticationManager(),
    // Test stuff for Authorization
    new \Thruway\Authentication\AuthorizationManager('authorizing_realm'),
    // Create a realm with Authentication also to test some stuff
    new \Thruway\Authentication\AuthorizationManager("authful_realm"),
    // Client for End-to-End testing
    new \Thruway\Tests\Clients\InternalClient('testRealm'),
    // Client for Disclose Publisher Test
    new \Thruway\Tests\Clients\DisclosePublisherClient('testSimpleAuthRealm'),
    // State Handler Testing
    new \Thruway\Subscription\StateHandlerRegistry('state.test.realm'),

    // Websocket listener
    new RatchetTransportProvider("127.0.0.1", 8090),
    // Rawsocket listener
    new \Thruway\Transport\RawSocketTransportProvider('127.0.0.1', 28181)

]);

//Provide authentication for the realm: 'testSimpleAuthRealm'
$router->addInternalClient(new \Thruway\Tests\Clients\SimpleAuthProviderClient(["testSimpleAuthRealm", "authful_realm"]));


// provide aborting auth provider
$router->addInternalClient(new \Thruway\Tests\Clients\AbortAfterHelloAuthProviderClient(["abortafterhello"]));
$router->addInternalClient(new \Thruway\Tests\Clients\AbortAfterHelloWithDetailsAuthProviderClient(["abortafterhellowithdetails"]));
$router->addInternalClient(new \Thruway\Tests\Clients\AbortAfterAuthenticateWithDetailsAuthProviderClient(["aaawd"]));

$router->addInternalClient(new \Thruway\Tests\Clients\QueryParamAuthProviderClient(["query_param_auth_realm"]));

////////////////////////
//WAMP-CRA Authentication
// setup some users to auth against
$userDb = new \Thruway\Tests\UserDb();
$userDb->add('peter', 'secret1', 'salt123');
$userDb->add('joe', 'secret2', "mmm...salt");

//Add the WAMP-CRA Auth Provider
$authProvClient = new \Thruway\Authentication\WampCraAuthProvider(["test.wampcra.auth"], $loop);
$authProvClient->setUserDb($userDb);
$router->addInternalClient($authProvClient);
///////////////////////

if ($timeout) {
    $loop->addTimer($timeout, function () use ($loop) {
        $loop->stop();
    });
}

$router->start();
