<?php


class WampErrorExceptionTest extends PHPUnit_Framework_TestCase {
    function testWampErrorException() {
        $loop = \React\EventLoop\Factory::create();

        $router = new \Thruway\Peer\Router($loop);

        //$router->addTransportProvider(new \Thruway\Transport\RawSocketTransportProvider());

        $client = new \Thruway\Peer\Client("realm1", $loop);
        $client->setAttemptRetry(false);
        $client->on('open', function (\Thruway\ClientSession $session) use ($router) {
            $session->register('procedure_with_exception', function ($args) {
                throw new \Thruway\WampErrorException("error.from.exception", $args, (object)[
                    "theKw" => "great"
                ], (object)[ "more_details" => "some_more_details" ]);
            })->then(function () use ($session, $router) {
                $session->call('procedure_with_exception', ['one', 'two'])->then(function ($args) use ($router) {
                    $this->fail('Call with wamp exception should not have succeeded.');
                    $router->stop();
                }, function ($err) use ($router) {
                    /** @var \Thruway\Message\ErrorMessage $err */
                    $this->assertInstanceOf('Thruway\Message\ErrorMessage', $err);
                    $this->assertTrue(is_array($err->getArguments()));
                    $this->assertEquals(2, count($err->getArguments()));
                    $this->assertEquals("one", $err->getArguments()[0]);
                    $this->assertEquals("two", $err->getArguments()[1]);
                    $this->assertInstanceOf('stdClass', $err->getArgumentsKw());
                    $this->assertObjectHasAttribute('theKw', $err->getArgumentsKw());
                    $this->assertEquals('great', $err->getArgumentsKw()->theKw);
                    $this->assertObjectHasAttribute('more_details', $err->getDetails());
                    $this->assertEquals('some_more_details', $err->getDetails()->more_details);
                    $this->assertEquals('error.from.exception', $err->getErrorURI());

                    $router->stop();
                });
            });
        });

        //$client->addTransportProvider(new \Thruway\Transport\RawSocketClientTransportProvider());

        $router->addInternalClient($client);
        $router->start();

    }
} 