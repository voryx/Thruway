<?php
require "../bootstrap.php";
require __DIR__ . '/FullBufferClient.php';
require __DIR__ . '/BufferFillerClient.php';

//$loop = \React\EventLoop\Factory::create();

$client = new FullBufferClient('myrealm');

$client->addTransportProvider(new \Thruway\Transport\PawlTransportProvider('ws://127.0.0.1:9090/'));

$client->start(false);

$loop = $client->getLoop();

$looping = true;
$loop->addTimer(
    5,
    function () use (&$looping) {
        $looping = false;
    }
);

while ($looping) {
    usleep(1000);
    $loop->tick();
}

echo "Looping no more...\n";

// run forever
while (1) {
    usleep(1000);
}

