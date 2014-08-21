<?php
/**
 * This client can connect to this server:
 * https://github.com/tavendo/AutobahnPython/tree/master/examples/twisted/wamp/authentication/wampcra
 */


require "../bootstrap.php";
require 'MyClient.php';

$client = new MyClient('realm1');

$client->setAttemptRetry(false);

$user = "peter";
$secret = "secret1";

$user = "joe";
$password = "secret2";

$client->setAuthId($user);

$client->addClientAuthenticator(new \Thruway\ClientWampCraAuthenticator($user, $password));

$client->addTransportProvider(new \Thruway\Transport\PawlTransportProvider("ws://127.0.0.1:8080/ws"));

$client->start();