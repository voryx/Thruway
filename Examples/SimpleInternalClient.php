<?php

if (file_exists(__DIR__.'/../../../autoload.php')) {
    require __DIR__.'/../../../autoload.php';
} else {
    require __DIR__.'/../vendor/autoload.php';
}

use AutobahnPHP\Peer\Router;
use AutobahnPHP\Transport\RatchetTransportProvider;

$manager = new \AutobahnPHP\ManagerClient();

$loop = \React\EventLoop\Factory::create();

$loop->addTimer(2, array($manager, "testSubscribe"));

$router = new Router($loop);

$transportProvider = new RatchetTransportProvider("127.0.0.1", 9090);

$internalClientTransportProvider = new \AutobahnPHP\Transport\InternalClientTransportProvider($manager);

$router->addTransportProvider($transportProvider);
$router->addTransportProvider($internalClientTransportProvider);

$router->start();