<?php
require "../bootstrap.php";
require 'SimpleAuthProviderClient.php';

use Thruway\Peer\Router;
use Thruway\Transport\RatchetTransportProvider;

$router = new Router();

$router->registerModule(new \Thruway\Authentication\AuthenticationManager());

//Provide authentication for the realm: 'somerealm'
$authProvClient = new SimpleAuthProviderClient(["somerealm"]);
$router->addInternalClient($authProvClient);

$router->registerModule(new RatchetTransportProvider("127.0.0.1", 9090));

$router->start();