<?php

require __DIR__ . '/../../vendor/autoload.php';

use React\EventLoop\Timer\Timer;
use Thruway\ClientSession;
use Thruway\Peer\Client;
use Thruway\Transport\PawlTransportProvider;

$client = new Client("realm1");
$client->addTransportProvider(new PawlTransportProvider("ws://127.0.0.1:8080/ws"));

$client->on('open', function (ClientSession $session) {

    $add2 = function ($args) {
        return $args[0] + $args[1];
    };
    $session->register('com.example.add2', $add2);

    $session->getLoop()->addPeriodicTimer(1, function (Timer $timer) use ($session) {
        static $i = 1;
        $session->publish('com.example.oncounter', [$i++]);
        $session->call('com.example.mul2', [2, 2]);
    });
});

$client->start();
