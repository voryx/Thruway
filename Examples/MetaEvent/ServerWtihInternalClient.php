<?php
/**
 * This is an example of how to use the InternalClientTransportProvider
 *
 * For more information go to:
 * http://voryx.net/creating-internal-client-thruway/
 */
require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/InternalClient.php';

use \Thruway\Peer\Router;
use \Thruway\Transport\RatchetTransportProvider;
use \Thruway\Transport\InternalClientTransportProvider;

$timeout = isset($argv[1]) ? $argv[1] : 0;

$router = new Router();

$transportProvider = new RatchetTransportProvider('127.0.0.1', 9090);
$router->addTransportProvider($transportProvider);

$internalClient = new \InternalClient();
$internalClient->setRouter($router);
$internalTransportProvider = new InternalClientTransportProvider($internalClient);
$router->addTransportProvider($internalTransportProvider);

if ($timeout) {
    $loop = $router->getLoop();
    $loop->addTimer($timeout, function() use ($loop) {
        $loop->stop();
    });
}
$router->getLoop()->addPeriodicTimer(30, [$internalClient, 'checkKeepAlive']);
$router->start();
