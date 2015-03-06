<?php
require __DIR__ . '/../../bootstrap.php';

require 'GithubCallbackAuthProvider.php';

use Thruway\Peer\Router;
use Thruway\Transport\RatchetTransportProvider;

$loop = React\EventLoop\Factory::create();
$socket = new React\Socket\Server($loop);

$http = new React\Http\Server($socket);

//Callback Port
$socket->listen(1337);

$router = new Router($loop);

$authMgr = new \Thruway\Authentication\AuthenticationManager();

$router->setAuthenticationManager($authMgr);
$router->addInternalClient($authMgr);

$authProvClient = new GithubCallbackAuthProvider(["*"], $http, "[YOUR-CLIENT_ID]", "[YOUR-CLIENT-SECRET]");
$router->addInternalClient($authProvClient);

//WAMP Server
$router->registerModule(new RatchetTransportProvider("127.0.0.1", 9090));

$router->start();