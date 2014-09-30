<?php

/**
 * Class RouterTest
 */
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


    /**
     * @var \Thruway\Transport\TransportInterface
     */
    private $msg;


    public function setup()
    {
        $this->router = new \Thruway\Peer\Router();


        // Create a stub for the Transport Provider class.
        $this->transportProviderMock = $this->getMock('\Thruway\Transport\TransportProviderInterface');

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
            ->will($this->returnValue(["type" => "ratchet", "transportAddress" => "127.0.0.1"]));

        $router->onOpen($transport);

        $this->assertGreaterThan(0, count($router->managerGetSessionCount()));

        return ['router' => $router, 'transport' => $transport];
    }

    /**
     * @depends testOnOpen
     * @param $rt array
     * @return array
     *
     * https://github.com/tavendo/WAMP/blob/master/spec/basic.md#hello-1
     */
    public function testHelloMessage($rt)
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
     * @depends testHelloMessage
     * @param $rt array
     * @return array
     *
     * https://github.com/tavendo/WAMP/blob/master/spec/basic.md#subscribe-1
     */
    public function testSubscribeMessage($rt)
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
     * @depends testHelloMessage
     * @param $rt array
     * @return array
     *
     * https://github.com/tavendo/WAMP/blob/master/spec/basic.md#subscription-error
     */
    public function testSubscribeEmptyTopicMessage($rt)
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
     * @depends testHelloMessage
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
                            "Should not return an error when trying to subscribe to topic more than once"
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
     * @depends testHelloMessage
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


    /**
     * Publish from within the same session as the subscribes
     * @depends testHelloMessage
     * @param $rt array
     * @return array
     *
     * https://github.com/tavendo/WAMP/blob/master/spec/basic.md#publish-1
     */
    public function testPublishMessageSameSession($rt)
    {
        $rt['transport']->expects($this->at(1))
            ->method('sendMessage')
            ->with(
                $this->callback(
                    function ($msg) {
                        $this->assertTrue(
                            false,
                            ' The Publisher of an event should never receive the published event even if the Publisher is also a Subscriber of the topic published to.'
                        );

                        return $msg instanceof Thruway\Message\EventMessage;
                    }
                )
            )->will($this->returnValue(null));


        $msg = new \Thruway\Message\PublishMessage('654321', new stdClass(), 'test.topic', ["hello world"]);
        $rt['router']->onMessage($rt['transport'], $msg);

        return $rt;
    }

    /**
     * @depends testHelloMessage
     * @param $rt array
     * @return array
     *
     * https://github.com/tavendo/WAMP/blob/master/spec/basic.md#published-1
     */
    public function testPublishAcknowledgeMessage($rt)
    {
        $rt['transport']->expects($this->at(1))
            ->method('sendMessage')
            ->with(
                $this->callback(
                    function ($msg) {
                        $this->assertInstanceOf('\Thruway\Message\PublishedMessage', $msg);
                        $this->assertEquals('78654321', $msg->getRequestId());

                        return $msg instanceof Thruway\Message\PublishedMessage;
                    }
                )
            )->will($this->returnValue(null));


        $msg = new \Thruway\Message\PublishMessage('78654321', ['acknowledge' => true], 'test.topic', ["hello world"]);
        $rt['router']->onMessage($rt['transport'], $msg);

        return $rt;
    }


    /**
     * @depends testHelloMessage
     * @param $rt array
     * @return array
     *
     * https://github.com/tavendo/WAMP/blob/master/spec/basic.md#event-1
     */
    public function testEventMessages($rt)
    {
        $rt['transport']->expects($this->atMost(2))
            ->method('sendMessage')
            ->with(
                $this->callback(
                    function (\Thruway\Message\EventMessage $msg) {
                        $this->assertInstanceOf('\Thruway\Message\EventMessage', $msg);
                        $this->assertEquals('999654321', $msg->getPublicationId());
                        $this->assertEquals('test.topic', $msg->getSubscriptionId());
                        $this->assertCount(1, $msg->getArguments());
                        $this->assertEquals('hello world', $msg->getArguments()[0]);

                        //@todo add argskw check

                        return $msg instanceof Thruway\Message\EventMessage;
                    }
                )
            )->will($this->returnValue(null));
    }


    /**
     * Publish a message from a different session
     *
     * @depends testStart
     * @param \Thruway\Peer\Router $router
     * @return array
     *
     * https://github.com/tavendo/WAMP/blob/master/spec/basic.md#publish-1
     */
    public function testPublishMessage(\Thruway\Peer\Router $router)
    {

        $transport = $this->getMock('Thruway\Transport\TransportInterface');

        // Configure the stub.
        $transport->expects($this->any())
            ->method('getTransportDetails')
            ->will($this->returnValue(["type" => "ratchet", "transportAddress" => "127.0.0.1"]));

        //Simulate onOpen
        $router->onOpen($transport);

        //Simulate a HelloMessage
        $helloMessage = new \Thruway\Message\HelloMessage("test.realm", []);
        $router->onMessage($transport, $helloMessage);

        //Publish Message
        $msg = new \Thruway\Message\PublishMessage('999654321', new stdClass(), 'test.topic', ["hello world"]);
        $router->onMessage($transport, $msg);

    }
}