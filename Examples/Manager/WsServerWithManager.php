<?php

if (file_exists(__DIR__.'/../../../../autoload.php')) {
    require __DIR__.'/../../../../autoload.php';
} else {
    require __DIR__ . '/../../vendor/autoload.php';
}

use Thruway\Peer\Router;
use Thruway\Transport\RatchetTransportProvider;

$manager = new \Thruway\Manager\ManagerClient();

$loop = \React\EventLoop\Factory::create();

//$loop->addTimer(2, array($manager, "testSubscribe"));

$router = new Router($loop, $manager);

$transportProvider = new RatchetTransportProvider("127.0.0.1", 9090);

$internalClientTransportProvider = new \Thruway\Transport\InternalClientTransportProvider($manager);

$router->addTransportProvider($transportProvider);
$router->addTransportProvider($internalClientTransportProvider);

$router->start();