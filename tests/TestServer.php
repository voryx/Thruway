<?php

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/Clients/InternalClient.php';
require_once __DIR__ . '/Clients/SimpleAuthProviderClient.php';
require_once __DIR__ . '/Clients/AbortAfterAuthenticateWithDetailsAuthProviderClient.php';
require_once __DIR__ . '/Clients/AbortAfterHelloAuthProviderClient.php';
require_once __DIR__ . '/Clients/AbortAfterHelloWithDetailsAuthProviderClient.php';
require_once __DIR__ . '/Clients/DisclosePublisherClient.php';
require_once __DIR__ . '/Clients/QueryParamAuthProviderClient.php';
require_once __DIR__ . '/UserDb.php';

use Thruway\Logging\Logger;
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
    new InternalClient('testRealm'),
    // Client for Disclose Publisher Test
    new DisclosePublisherClient('testSimpleAuthRealm'),
    // State Handler Testing
    new \Thruway\Subscription\StateHandlerRegistry('state.test.realm'),

    // Websocket listener
    new RatchetTransportProvider("127.0.0.1", 8090),
    // Rawsocket listener
    new \Thruway\Transport\RawSocketTransportProvider('127.0.0.1', 28181)

]);

//Provide authentication for the realm: 'testSimpleAuthRealm'
$router->addInternalClient(new SimpleAuthProviderClient(["testSimpleAuthRealm", "authful_realm"]));


// provide aborting auth provider
$router->addInternalClient(new AbortAfterHelloAuthProviderClient(["abortafterhello"]));
$router->addInternalClient(new AbortAfterHelloWithDetailsAuthProviderClient(["abortafterhellowithdetails"]));
$router->addInternalClient(new AbortAfterAuthenticateWithDetailsAuthProviderClient(["aaawd"]));

$router->addInternalClient(new QueryParamAuthProviderClient(["query_param_auth_realm"]));

////////////////////////
//WAMP-CRA Authentication
// setup some users to auth against
$userDb = new UserDb();
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
