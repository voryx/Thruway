<?php
require 'bootstrap.php';

use Thruway\ClientSession;

$client = new \Thruway\Peer\Client("realm1");

$client->on(
    'open',
    function (ClientSession $session) {
        $session->subscribe(
            "com.myapp.hello",
            function ($msg) {
                echo $msg[0];
            }
        );
    }
);


$client->addTransportProvider(new \Thruway\Transport\PawlTransportProvider());

$client->start();
