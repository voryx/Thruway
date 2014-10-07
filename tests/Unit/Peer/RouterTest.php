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

    /**
     * Test router start
     *
     * @return \Thruway\Peer\Router
     * @throws Exception
     */
    public function testStart()
    {
        $this->router->addTransportProvider($this->transportProviderMock);

        $this->router->start();

        return $this->router;

    }

    /**
     * Test onOpen
     *
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
     * Test Hello message
     *
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
     * Test Subscribe message
     *
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
     * Test Subscribe to an empty topic
     *
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
     * Test Duplicate Subscription from the same session
     *
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
        $router        = $rt['router'];
        $subscriptions = $router->getRealmManager()->getRealm('test.realm')->getBroker()->managerGetSubscriptions()[0];

        $this->assertEquals(2, count($subscriptions));

        return $rt;
    }


    /**
     * Test Subscribe with an invalid URI
     *
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
     *
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
     * Test Publish with Acknowledge flag
     *
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
     * Test Event Message
     *
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
                        $this->assertCount(1, $msg->getArguments());
                        $this->assertEquals('hello world', $msg->getArguments()[0]);

                        //@todo add argskw check

                        return $msg instanceof Thruway\Message\EventMessage;
                    }
                )
            )->will($this->returnValue(null));
    }


    /**
     * Publish a message from a different session to the method above
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


    /**
     * Test UnSubscribe message
     *
     * @see https://github.com/tavendo/WAMP/blob/master/spec/basic.md#unsubscribe-1
     */
    public function testUnSubscribeMessage()
    {
        $router = $this->router;
        $router->addTransportProvider($this->transportProviderMock);
        $router->start();
        $transport = $this->getMock('Thruway\Transport\TransportInterface');

        // Configure the stub.
        $transport->expects($this->any())
            ->method('getTransportDetails')
            ->will($this->returnValue(["type" => "ratchet", "transportAddress" => "127.0.0.1"]));

        //Simulate onOpen
        $router->onOpen($transport);

        //Simulate HelloMessage
        $helloMessage = new \Thruway\Message\HelloMessage("test.realm2", []);
        $router->onMessage($transport, $helloMessage);

        //Subscribe to a topic
        $subscriptionId = null;
        $transport->expects($this->at(1))
            ->method('sendMessage')
            ->with(
                $this->callback(
                    function ($msg) use (&$subscriptionId) {
                        $this->assertInstanceOf('\Thruway\Message\SubscribedMessage', $msg);
                        $this->assertEquals('7777777', $msg->getRequestId());
                        $subscriptionId = $msg->getSubscriptionId();
                        return $msg instanceof Thruway\Message\SubscribedMessage;
                    }
                )
            )->will($this->returnValue(null));

        $msg = new \Thruway\Message\SubscribeMessage('7777777', [], 'test.topic123');
        $router->onMessage($transport, $msg);

        //Unsubscribe
        $transport->expects($this->at(1))
            ->method('sendMessage')
            ->with(
                $this->callback(
                    function ($msg) {
                        $this->assertInstanceOf('\Thruway\Message\UnsubscribedMessage', $msg);
                        $this->assertEquals('888888', $msg->getRequestId());

                        return $msg instanceof Thruway\Message\UnsubscribedMessage;
                    }
                )
            )->will($this->returnValue(null));

        $this->assertInstanceOf('\Thruway\Subscription',
            $router->getRealmManager()->getRealm('test.realm2')->getBroker()->getSubscriptionById($subscriptionId)
        );

        $msg = new \Thruway\Message\UnsubscribeMessage('888888', $subscriptionId);
        $router->onMessage($transport, $msg);

        $this->assertFalse($router->getRealmManager()->getRealm('test.realm2')->getBroker()->getSubscriptionById($subscriptionId));

    }

    /**
     * Test onClose
     *
     * @depends testHelloMessage
     * @param $rt array
     * @return array
     */
    public function testOnClose($rt)
    {

        //Get the sessions before close
        $sessions = $rt['router']->managerGetSessions()[0];

        $rt['router']->onClose($rt['transport']);

        $this->assertEquals(count($sessions) - 1, count($rt['router']->managerGetSessions()[0]),
            "There should be one less session");

    }

    /**
     * Test Get Session By Session Id
     *
     * @depends testHelloMessage
     * @param $rt array
     * @return array
     */
    public function testGetSessionBySessionId($rt)
    {
        //Get the sessions
        $sessions = $rt['router']->managerGetSessions()[0];

        $this->assertCount(1, $sessions);

        foreach ($sessions as $s) {
            /* @var $session \Thruway\Session */
            $session = $rt['router']->getSessionBySessionId($s['id']);

            $this->assertInstanceOf('\Thruway\Session', $session);
            $this->assertEquals($session->getSessionId(), $s['id']);

        }
    }


    /**
     * Test Try Get Session By Session Id - Id Does not exist
     *
     * @depends testHelloMessage
     * @param $rt array
     * @return array
     */
    public function testGetSessionBySessionIdFalse($rt)
    {
        /* @var $session \Thruway\Session */
        $session = $rt['router']->getSessionBySessionId("12231234123412341234");

        $this->assertFalse($session);
    }

    /**
     * Abort Message
     *
     * @depends testStart
     * @param \Thruway\Peer\Router $router
     * @return array
     *
     * @see https://github.com/tavendo/WAMP/blob/master/spec/basic.md#abort
     */
    public function testAbortMessage(\Thruway\Peer\Router $router)
    {

        $transport = $this->getMock('Thruway\Transport\TransportInterface');

        // Configure the stub.
        $transport->expects($this->any())
            ->method('getTransportDetails')
            ->will($this->returnValue(["type" => "ratchet", "transportAddress" => "127.0.0.1"]));

        //No messages should be sent
        $transport->expects($this->never())
            ->method('sendMessage');

        //Simulate onOpen
        $router->onOpen($transport);

        //Simulate a AbortMessage
        $abortMessage = new \Thruway\Message\AbortMessage(["message" => "Client is shutting down"], "wamp.error.system_shutdown");
        $router->onMessage($transport, $abortMessage);

    }

    /**
     * Unhandled Message
     *
     * @depends testStart
     * @param \Thruway\Peer\Router $router
     * @return array
     */
    public function testUnhandledMessage(\Thruway\Peer\Router $router)
    {

        $transport = $this->getMock('Thruway\Transport\TransportInterface');

        // Configure the stub.
        $transport->expects($this->any())
            ->method('getTransportDetails')
            ->will($this->returnValue(["type" => "ratchet", "transportAddress" => "127.0.0.1"]));

        $transport->expects($this->once())
            ->method('sendMessage')
            ->with(
                $this->callback(
                    function (\Thruway\Message\AbortMessage $msg) {
                        $this->assertInstanceOf('\Thruway\Message\AbortMessage', $msg);
                        $this->assertEquals('wamp.error.unknown', $msg->getResponseURI());

                        return $msg instanceof Thruway\Message\AbortMessage;
                    }
                )
            )->will($this->returnValue(null));

        //Simulate onOpen
        $router->onOpen($transport);

        //Simulate a GoodbyeMessage
        $goodbyeMessage = new \Thruway\Message\GoodbyeMessage(["message" => "Client is shutting down"],
            "wamp.error.system_shutdown");
        $router->onMessage($transport, $goodbyeMessage);

    }


    /**
     * Invalid Empty Realm Name
     *
     * @depends testStart
     * @param \Thruway\Peer\Router $router
     * @return array
     */
    public function testInvalidRealm(\Thruway\Peer\Router $router)
    {

        $transport = $this->getMock('Thruway\Transport\TransportInterface');

        // Configure the stub.
        $transport->expects($this->any())
            ->method('getTransportDetails')
            ->will($this->returnValue(["type" => "ratchet", "transportAddress" => "127.0.0.1"]));

        $transport->expects($this->once())
            ->method('sendMessage')
            ->with(
                $this->callback(
                    function (\Thruway\Message\AbortMessage $msg) {
                        $this->assertInstanceOf('\Thruway\Message\AbortMessage', $msg);
                        $this->assertEquals('wamp.error.no_such_realm', $msg->getResponseURI());

                        return $msg instanceof Thruway\Message\AbortMessage;
                    }
                )
            )->will($this->returnValue(null));

        //Simulate onOpen
        $router->onOpen($transport);

        //Simulate a HelloMessage with an empty Realm
        $helloMessage = new \Thruway\Message\HelloMessage("", []);
        $router->onMessage($transport, $helloMessage);
    }

}