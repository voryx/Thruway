<?php
require '../bootstrap.php';
require __DIR__ . '/LastClient.php';
require_once 'RelayClient.php';
require __DIR__ . '/CallingClient.php';

$loop = \React\EventLoop\Factory::create();

$theClients = [];

$promises = [];

// Using demo.thruway.ws server if your test is without server
//$url = "ws://demo.thruway.ws:9090/";
$url = "ws://127.0.0.1:9090/";

for ($i = 0; $i < 100; $i++) {

    $client = new RelayClient('theRealm', $loop, $i);

    $client->addTransportProvider(new \Thruway\Transport\PawlTransportProvider($url));

    $client->start(false);

    array_push($promises, $client->getRegisteredPromise());

    array_push($theClients, $client);
}

$lastClient = new LastClient('theRealm', $loop, $i);

$lastClient->addTransportProvider(new \Thruway\Transport\PawlTransportProvider($url));

$lastClient->start(false);

array_push($promises, $lastClient->getRegisteredPromise());

$allDeferred = new \React\Promise\Deferred();

$thePromise = \React\Promise\all($promises);

$callingClient = new CallingClient('theRealm', $loop, $thePromise);

$callingClient->addTransportProvider(new \Thruway\Transport\PawlTransportProvider($url));

$callingClient->start();

