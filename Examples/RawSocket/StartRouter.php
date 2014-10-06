<?php

require '../bootstrap.php';

$router = new \Thruway\Peer\Router();

$router->addTransportProvider(new \Thruway\Transport\RawSocketTransportProvider("127.0.0.1", 8181));

$router->start();