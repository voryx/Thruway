<?php

if (file_exists(__DIR__.'/../../../autoload.php')) {
    require __DIR__.'/../../../autoload.php';
} else {
    require __DIR__.'/../vendor/autoload.php';
}

$client = new \AutobahnPHP\Peer\Client();

$internalTransport = null;

$client->addRole(new \AutobahnPHP\Role\Caller())
    ->addRole(new \AutobahnPHP\Role\Callee())

