<?php
if (file_exists(__DIR__ . '/../../../autoload.php')) {
    require __DIR__ . '/../../../autoload.php';
} else {
    require __DIR__ . '/../vendor/autoload.php';
}

$loop = \React\EventLoop\Factory::create();


$client = new \AutobahnPHP\Peer\Client("realm1", $loop);

$loop->addTimer(1, function () use ($client) {
        $client->getSubscriber()->subscribe("com.myapp.hello", function ($msg) {
                var_dump($msg);
            });
    });


$client->addTransportProvider(new \AutobahnPHP\Transport\PawlTransportProvider());

$client->start();
