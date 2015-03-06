<?php

/**
 * This requires "facebook/php-sdk-v4" : "4.0.*"
 */

require '../../bootstrap.php';
require 'FacebookAuthProvider.php';

use Thruway\Peer\Router;
use Thruway\Transport\RatchetTransportProvider;

$router = new Router();

$authMgr = new \Thruway\Authentication\AuthenticationManager();

$router->setAuthenticationManager($authMgr);
$router->addTransportProvider(new \Thruway\Transport\InternalClientTransportProvider($authMgr));
$router->addInternalClient($authMgr);

$authProvClient = new FacebookAuthProvider(['*'], 'YOUR_APP_ID', 'YOUR_APP_SECRET');
$router->addTransportProvider(new \Thruway\Transport\InternalClientTransportProvider($authProvClient));
$router->addInternalClient($authProvClient);

$router->registerModule(new RatchetTransportProvider("127.0.0.1", 9090));

$router->start();