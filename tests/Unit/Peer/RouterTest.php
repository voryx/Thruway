<?php

class RouterTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var \Thruway\Peer\Router
     */
    private $router;

    /**
     * @var \Thruway\Transport\RatchetTransportProvider
     */
    private $transportProviderMock;

    /**
     * @var \Thruway\Transport\TransportInterface
     */
//    private $transportMock;


    private $msg;

    /**
     *
     */
    public function setup()
    {
        $this->router = new \Thruway\Peer\Router();


        // Create a stub for the Transport Provider class.
        $this->transportProviderMock = $this->getMock('\Thruway\Transport\RatchetTransportProvider');

    }

    public function testLoopCreated()
    {
        $this->assertInstanceOf('\React\EventLoop\LoopInterface', $this->router->getLoop());
    }

    public function testDummyManager()
    {
        $this->assertInstanceOf('Thruway\Manager\ManagerDummy', $this->router->getManager());
    }

    public function testNullAuthenticationManager()
    {
        $this->assertNull($this->router->getAuthenticationManager());
    }

    /**
     * If you start the router without a transport provider, it should throw an exception
     *
     * @expectedException Exception
     */
    public function testTransportProvidersException()
    {
        $this->router->start();
    }


    /**
     *
     */
    public function testStart()
    {
        $this->router->addTransportProvider($this->transportProviderMock);

        $this->router->start();

        return $this->router;

    }

    /**
     * @depends testStart
     * @param \Thruway\Peer\Router $router
     * @return array
     */
    public function testOnOpen(\Thruway\Peer\Router $router)
    {
        $transport = $this->getMock('Thruway\Transport\TransportInterface');

        // Configure the stub.
        $transport->expects($this->any())
            ->method('getTransportDetails')
            ->will(
                $this->returnValue(
                    [
                        "type" => "ratchet",
                        "transportAddress" => "127.0.0.1"
                    ]
                )
            );


        $router->onOpen($transport);

        $this->assertGreaterThan(0, count($router->managerGetSessionCount()));

        return ['router' => $router, 'transport' => $transport];
    }

    /**
     * @depends testOnOpen
     * @param $rt array
     * @return array
     */
    public function testHelloWelcomeMessages($rt)
    {


        $rt['transport']->expects($this->at(0))
            ->method('sendMessage')
            ->with(
                $this->callback(
                    function ($msg) {
                        $this->assertInstanceOf('\Thruway\Message\WelcomeMessage', $msg);

                        return $msg instanceof Thruway\Message\WelcomeMessage;
                    }
                )
            )->will($this->returnValue(null));

        $helloMessage = new \Thruway\Message\HelloMessage("test.realm", []);
        $rt['router']->onMessage($rt['transport'], $helloMessage);

        return $rt;
    }

    /**
     * @depends testHelloWelcomeMessages
     * @param $rt array
     * @return array
     */
    public function testSubscribeMessages($rt)
    {
        $rt['transport']->expects($this->at(1))
            ->method('sendMessage')
            ->with(
                $this->callback(
                    function ($msg) {
                        $this->assertInstanceOf('\Thruway\Message\SubscribedMessage', $msg);
//                        $this->assertTrue(is_numeric($msg->getSubscriptionId()), "Subscription ID should be a number");
                        $this->assertEquals('1111111', $msg->getRequestId());

                        return $msg instanceof Thruway\Message\SubscribedMessage;

                    }
                )
            )->will($this->returnValue(null));


        $msg = new \Thruway\Message\SubscribeMessage('1111111', [], 'test.topic');
        $rt['router']->onMessage($rt['transport'], $msg);

        return $rt;
    }

    /**
     * @depends testHelloWelcomeMessages
     * @param $rt array
     * @return array
     */
    public function testSubscribeEmptyTopicMessages($rt)
    {
        $rt['transport']->expects($this->at(1))
            ->method('sendMessage')
            ->with(
                $this->callback(
                    function ($msg) {
                        $this->assertInstanceOf(
                            '\Thruway\Message\ErrorMessage',
                            $msg,
                            'Should return an error when topic is empty'
                        );
                        $this->assertEquals("wamp.error.invalid_uri", $msg->getErrorURI());
                        $this->assertEquals('222222', $msg->getRequestId());

                        return $msg instanceof Thruway\Message\ErrorMessage;
                    }
                )
            )->will($this->returnValue(null));


        $msg = new \Thruway\Message\SubscribeMessage('222222', [], '');
        $rt['router']->onMessage($rt['transport'], $msg);

        return $rt;
    }

    /**
     * @depends testHelloWelcomeMessages
     * @param $rt array
     * @return array
     */
    public function testSubscribeDuplicateTopic($rt)
    {
        $rt['transport']->expects($this->at(1))
            ->method('sendMessage')
            ->with(
                $this->callback(
                    function ($msg) {

                        $this->assertInstanceOf(
                            '\Thruway\Message\SubscribedMessage',
                            $msg,
                            "Should return an error when trying to subscribe to topic more than once"
                        );

                        $this->assertEquals('333333', $msg->getRequestId());

                        return $msg instanceof Thruway\Message\SubscribedMessage;
                    }
                )
            )->will($this->returnValue(null));

        $msg = new \Thruway\Message\SubscribeMessage('333333', [], 'test.topic');
        $rt['router']->onMessage($rt['transport'], $msg);

        /* @var $router \Thruway\Peer\Router */
        $router = $rt['router'];
        $subscriptions = $router->getRealmManager()->getRealm('test.realm')->getBroker()->managerGetSubscriptions()[0];

        $this->assertEquals(2, count($subscriptions));

        return $rt;
    }


    /**
     * @depends testHelloWelcomeMessages
     * @param $rt array
     * @return array
     */
    public function testSubscribeInvalidURI($rt)
    {
        $rt['transport']->expects($this->at(1))
            ->method('sendMessage')
            ->with(
                $this->callback(
                    function ($msg) {

                        $this->assertInstanceOf(
                            '\Thruway\Message\ErrorMessage',
                            $msg,
                            "Should return an error when trying to subscribe to topic more than once"
                        );

                        $this->assertEquals('55555', $msg->getRequestId());
                        $this->assertEquals('wamp.error.invalid_uri', $msg->getErrorURI());

                        return $msg instanceof Thruway\Message\ErrorMessage;
                    }
                )
            )->will($this->returnValue(null));


        $msg = new \Thruway\Message\SubscribeMessage('55555', [], 'test.topic1$$$');
        $rt['router']->onMessage($rt['transport'], $msg);


        return $rt;
    }
}