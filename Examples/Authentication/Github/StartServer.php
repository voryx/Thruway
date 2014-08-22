<?php
require '../../bootstrap.php';
require 'GithubAuthProvider.php';

use Thruway\Peer\Router;
use Thruway\Transport\RatchetTransportProvider;

$router = new Router();

$authMgr = new \Thruway\Authentication\AuthenticationManager();

$router->setAuthenticationManager($authMgr);
$router->addTransportProvider(new \Thruway\Transport\InternalClientTransportProvider($authMgr));

$authProvClient = new GithubAuthProvider(["*"],"[YOUR-CLIENT_ID]", "[YOUR-CLIENT-SECRET]");
$router->addTransportProvider(new \Thruway\Transport\InternalClientTransportProvider($authProvClient));


$transportProvider = new RatchetTransportProvider("127.0.0.1", 9090);

$router->addTransportProvider($transportProvider);


$router->start();