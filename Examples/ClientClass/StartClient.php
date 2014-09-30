<?php
/**
 * This example is from the blog post:
 * http://voryx.net/creating-a-custom-php-wamp-client-for-thruway/
 *
 * NOTICE that the server is not the local server.
 * This is because the example uses the demo server.
 */
if (file_exists(__DIR__ . '/../../../../autoload.php')) {
    require __DIR__ . '/../../../../autoload.php';
} else {
    require __DIR__ . '/../../vendor/autoload.php';
}
require __DIR__ . '/FreeSpaceClient.php'; // so PHP can use our class

$client = new FreeSpaceClient('myrealm');

$client->addTransportProvider(new \Thruway\Transport\PawlTransportProvider('ws://demo.thruway.ws:9090/'));

$client->start();
