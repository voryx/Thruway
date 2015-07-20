<?php
/**
 * This client can connect to this server:
 * @see https://github.com/tavendo/AutobahnPython/tree/master/examples/twisted/wamp/authentication/wampcra
 */

require_once __DIR__ . "/../bootstrap.php";
require_once __DIR__ . '/MyClient.php';

$client = new MyClient('realm1');

$client->setAttemptRetry(false);

//$user   = "peter";
//$secret = "secret1";

$user     = "joe";
$password = "secret2";

$client->setAuthId($user);

$client->addClientAuthenticator(new \Thruway\Authentication\ClientWampCraAuthenticator($user, $password));

$client->addTransportProvider(new \Thruway\Transport\PawlTransportProvider("ws://127.0.0.1:9090/ws"));

$client->start();