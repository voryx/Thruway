<?php

use AutobahnPHP\Peer;

require '../vendor/autoload.php';

$server = Server('localhost', '8080');

$server->addPeer(new BasicBroker());

$server->run();