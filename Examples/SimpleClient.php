<?php

require '../vendor/autoload.php';

$transport = new \AutobahnPHP\Transport\WebsocketClient('wss://some.example.com:8080');

$client = new \AutobahnPHP\Peer\Client($transport);

$publisher = new \AutobahnPHP\Role\Publisher();

$client->addRole($publisher);

$msg = new \AutobahnPHP\Message\PublishMessage(12345, new \stdClass(), 'bla.bla.bla');

$publisher->publish($msg);

$client->run();