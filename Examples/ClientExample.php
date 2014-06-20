<?php
use Thruway\ClientSession;

if (file_exists(__DIR__ . '/../../../autoload.php')) {
    require __DIR__ . '/../../../autoload.php';
} else {
    require __DIR__ . '/../vendor/autoload.php';
}

$client = new \Thruway\Peer\Client("realm1");

$client->on(
    'open',
    function (ClientSession $session) {
        $session->subscribe(
            "com.myapp.hello",
            function ($msg) {
                $msg[0];
            }
        );
    }
);


$client->addTransportProvider(new \Thruway\Transport\PawlTransportProvider());

$client->start();
