<?php

require __DIR__ . "/../bootstrap.php";

use Thruway\Peer\Router;
use Thruway\Transport\RatchetTransportProvider;

$manager = new \Thruway\Manager\ManagerClient();

$loop = \React\EventLoop\Factory::create();

//$loop->addTimer(2, array($manager, "testSubscribe"));

$router = new Router($loop, $manager);

$transportProvider = new RatchetTransportProvider("127.0.0.1", 9090);

$router->addTransportProvider($transportProvider);
$router->addInternalClient($manager);

$router->start();