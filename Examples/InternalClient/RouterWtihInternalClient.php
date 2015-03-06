<?php
/**
 * This is an example of how to use the InternalClientTransportProvider
 *
 * For more information go to:
 * http://voryx.net/creating-internal-client-thruway/
 */

require "../bootstrap.php";
require 'InternalClient.php';

use Thruway\Peer\Router;
use Thruway\Transport\RatchetTransportProvider;

$router = new Router();

$router->registerModule(new RatchetTransportProvider("127.0.0.1", 9090));

$router->addInternalClient(new \InternalClient());

$router->start();