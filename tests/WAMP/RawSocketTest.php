<?php

/**
 * Class RawSocketTest
 *
 *
 */
class RawSocketTest extends PHPUnit_Framework_TestCase {
    private $_result;

    public function test() {
        $loop = \React\EventLoop\Factory::create();
        $client = new \Thruway\Peer\Client('raw_realm', $loop);
        $client->addTransportProvider(new \Thruway\Transport\RawSocketClientTransportProvider('127.0.0.1', 28181));

        $client->setAttemptRetry(false);

        $this->_result = null;

        $client->on('open', function (\Thruway\ClientSession $session) {
            $session->register('some_rpc', function () use ($session) {
                $this->_result = "success";
                $session->close();
            })->then(function () use ($session) {
                $session->call("some_rpc");
            });
        });

        $client->start();

        $this->assertNotNull($this->_result);
    }
}
