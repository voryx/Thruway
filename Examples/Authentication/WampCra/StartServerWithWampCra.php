<?php
require '../../bootstrap.php';

require 'UserDb.php';

use Thruway\Authentication\WampCraAuthProvider;
use Thruway\Peer\Router;
use Thruway\Transport\RatchetTransportProvider;

$router = new Router();

// setup some users to auth against
$userDb = new UserDb();

$userDb->add('peter', 'secret1', 'salt123');
$userDb->add('joe', 'secret2', "mmm...salt");

/**
 * The AuthenticationManager is both an internal client and also responsible
 * for handling all authentication for the router.
 *
 * When authentication is needed, the router consults the AuthenticationManager
 * which will consult registered Authentication Providers to do the actual authentication.
 *
 * The Authentication providers and the AuthenticationManager communicate through WAMP
 * in the thruway.auth realm.
 */
$authMgr = new \Thruway\Authentication\AuthenticationManager();

$router->setAuthenticationManager($authMgr);
$router->addTransportProvider(new \Thruway\Transport\InternalClientTransportProvider($authMgr));


$authProvClient = new WampCraAuthProvider(array("realm1"));
$authProvClient->setUserDb($userDb);
$router->addTransportProvider(new \Thruway\Transport\InternalClientTransportProvider($authProvClient));


$transportProvider = new RatchetTransportProvider("127.0.0.1", 9090);

$router->addTransportProvider($transportProvider);


$router->start();