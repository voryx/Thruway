<?php

require '../vendor/autoload.php';

$router = new \AutobahnPHP\Router();
//
//$router->addRole(new Broker());

try {
    $transport = new \AutobahnPHP\Transport\RatchetTransport();

    $transport->startTransport();
} catch (Exception $e) {
    echo $e->getMessage() . "\n";
}


//$router->run();