<?php

require "../bootstrap.php";
use Thruway\Peer\Router;
use Thruway\Transport\RatchetTransportProvider;

$router = new Router();

$router->registerModule(new RatchetTransportProvider("127.0.0.1", 9090));

$router->start();