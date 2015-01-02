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
        \Thruway\Logging\Logger::set(new \Psr\Log\NullLogger());

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
        $msg = new \Thruway\Message\PublishMessage(\Thruway\Common\Utils::getUniqueId(), new stdClass(), 'test.topic', ["hello world"]);
        $msg->setPublicationId('999654321');
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

        $broker = $router->getRealmManager()->getRealm('test.realm2')->getBroker();
        $this->assertFalse($broker->getSubscriptionById($subscriptionId));

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

    /**
     * Issue 53 - publishing inside of subscription event callback
     * prevents other internal clients from receiving the published event
     *
     * @depends testStart
     * @param \Thruway\Peer\Router $router
     */
    public function testIssue53(\Thruway\Peer\Router $router) {
        $this->_callCount = 0;

        $transport1 = $this->getMockBuilder('\Thruway\Transport\TransportInterface')
            ->getMock();

        $transport1->expects($this->exactly(3))
            ->method('sendMessage')
            ->withConsecutive(
                [$this->isInstanceOf('\Thruway\Message\WelcomeMessage')],
                [$this->isInstanceOf('\Thruway\Message\SubscribedMessage')],
                [$this->callback(function ($arg) use ($router, $transport1) {
                    $this->assertInstanceOf('\Thruway\Message\EventMessage', $arg);

                    // publish while in the callback
                    $publishMsg = new \Thruway\Message\PublishMessage(12346, (object)[], 'com.example.nowhere');
                    $router->onMessage($transport1, $publishMsg);

                    $this->_callCount = $this->_callCount + 1;
                    return true;
                })]
            );

        $transport2 = $this->getMockBuilder('\Thruway\Transport\TransportInterface')
            ->getMock();

        $transport2->expects($this->exactly(3))
            ->method('sendMessage')
            ->withConsecutive(
                [$this->isInstanceOf('\Thruway\Message\WelcomeMessage')],
                [$this->isInstanceOf('\Thruway\Message\SubscribedMessage')],
                [$this->isInstanceOf('\Thruway\Message\EventMessage')]
            );

        $transportPublisher = $this->getMockBuilder('\Thruway\Transport\TransportInterface')
            ->getMock();


        $router->onOpen($transport1);
        $router->onOpen($transport2);
        $router->onOpen($transportPublisher);

        // send in a few hellos
        $helloMsg = new \Thruway\Message\HelloMessage("realm_issue53", (object)[]);

        $router->onMessage($transport1, $helloMsg);
        $router->onMessage($transport2, $helloMsg);
        $router->onMessage($transportPublisher, $helloMsg);

        // subscribe
        $subscribeMsg = new \Thruway\Message\SubscribeMessage(\Thruway\Common\Utils::getUniqueId(), (object)[], "com.example.issue53");

        $router->onMessage($transport1, $subscribeMsg);
        $router->onMessage($transport2, $subscribeMsg);

        // publish to the topic from the publishing transport
        $publishMsg = new \Thruway\Message\PublishMessage(12345, (object)[], 'com.example.issue53');

        $router->onMessage($transportPublisher, $publishMsg);
    }

    /**
     * Creates a Transport Mock object using a fluent interface.
     *
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    private function createTransportMock() {
        return $this->getMockBuilder('\Thruway\Transport\TransportInterface')
            ->getMock();
    }

    private function assertEventMessageWithArgument0($arg0, $msg) {
        $this->assertInstanceOf('\Thruway\Message\EventMessage', $msg);
        $this->assertEquals($arg0, $msg->getArguments()[0]);
    }

    public function testStateHandlerStuff() {
        $router = new \Thruway\Peer\Router();

        $stateHandlerRegistry = new \Thruway\Subscription\StateHandlerRegistry('state.test.realm');

        $router->registerModule($stateHandlerRegistry);

        $router->start(false);

        $transportStateHandler = $this->createTransportMock();

        $registrationId = 0;
        $invocationReqId = 0;
        $subscriptionId = 0;

        $transportStateHandler->expects($this->exactly(7))
            ->method('sendMessage')
            ->withConsecutive(
                [$this->isInstanceOf('\Thruway\Message\WelcomeMessage')],
                [$this->callback(function (\Thruway\Message\RegisteredMessage $msg) use (&$registrationId) {
                    $registrationId = $msg->getRegistrationId();
                    return true;
                })],
                [$this->callback(function (\Thruway\Message\ErrorMessage $msg) {
                    $this->assertEquals("No uri set for state handler registration.", $msg->getArguments()[0]);
                    return true;
                })],
                [$this->callback(function (\Thruway\Message\ErrorMessage $msg) {
                    $this->assertEquals("No handler uri set for state handler registration.", $msg->getArguments()[0]);
                    return true;
                })],
                [$this->callback(function (\Thruway\Message\ErrorMessage $msg) {
                    $this->assertEquals("No uri set for state handler registration.", $msg->getArguments()[0]);
                    return true;
                })],
                [$this->callback(function (\Thruway\Message\ResultMessage $msg) {
                    return true;
                })],
                [$this->callback(function (\Thruway\Message\InvocationMessage $msg) use (&$registrationId, &$invocationReqId) {
                    $this->assertEquals($registrationId, $msg->getRegistrationId());
                    $invocationReqId = $msg->getRequestId();
                    return true;
                })]
            );

        $transportSubscriber = $this->createTransportMock();

        $transportSubscriber->expects($this->exactly(6))
            ->method("sendMessage")
            ->withConsecutive(
                [$this->isInstanceOf('\Thruway\Message\WelcomeMessage')],
                [$this->callback(function (\Thruway\Message\SubscribedMessage $msg) use (&$subscriptionId) {
                    $subscriptionId = $msg->getSubscriptionId();
                    return true;
                })],
                [$this->callback(function ($msg) { $this->assertEventMessageWithArgument0(2, $msg); return true; })],
                [$this->callback(function ($msg) { $this->assertEventMessageWithArgument0(3, $msg); return true; })],
                [$this->callback(function ($msg) { $this->assertEventMessageWithArgument0(4, $msg); return true; })],
                [$this->callback(function ($msg) { $this->assertEventMessageWithArgument0(5, $msg); return true; })]
            );

        $router->onOpen($transportStateHandler);
        $router->onOpen($transportSubscriber);

        $hello = new \Thruway\Message\HelloMessage('state.test.realm', (object)[]);

        $router->onMessage($transportStateHandler, $hello);
        $router->onMessage($transportSubscriber, $hello);

        $register = new \Thruway\Message\RegisterMessage(12345, (object)[], 'my_state_handler');

        $router->onMessage($transportStateHandler, $register);

        $call = new \Thruway\Message\CallMessage(2, (object)[], 'add_state_handler', [[]]);
        $router->onMessage($transportStateHandler, $call);
        $call->setArguments([(object)["uri" => "test.stateful.uri"]]);
        $router->onMessage($transportStateHandler, $call);
        $call->setArguments([(object)["handler_uri" => "my_state_handler"]]);
        $router->onMessage($transportStateHandler, $call);
        $call->setArguments([(object)["uri" => "test.stateful.uri", "handler_uri" => "my_state_handler"]]);
        $router->onMessage($transportStateHandler, $call);

        $subscribe = new \Thruway\Message\SubscribeMessage(2, (object)[], "test.stateful.uri");
        $router->onMessage($transportSubscriber, $subscribe);

        for ($i = 0; $i < 3; $i++) {
            $publish = new \Thruway\Message\PublishMessage($i, (object)[], "test.stateful.uri", [$i]);
            $publish->setPublicationId($i);
            $router->onMessage($transportStateHandler, $publish);
        }

        $yield = new \Thruway\Message\YieldMessage($invocationReqId, (object)[], [1]);
        $router->onMessage($transportStateHandler, $yield);

        for ($i = 3; $i < 6; $i++) {
            $publish = new \Thruway\Message\PublishMessage($i, (object)[], "test.stateful.uri", [$i]);
            $publish->setPublicationId($i);
            $router->onMessage($transportStateHandler, $publish);
        }
    }

    public function testStateRestoreWithQueuePubIdNotInQueue() {
        $router = new \Thruway\Peer\Router();

        $stateHandlerRegistry = new \Thruway\Subscription\StateHandlerRegistry('state.test.realm');

        $router->registerModule($stateHandlerRegistry);

        $router->start(false);

        $transportStateHandler = $this->createTransportMock();

        $registrationId = 0;
        $invocationReqId = 0;
        $subscriptionId = 0;

        $transportStateHandler->expects($this->exactly(7))
            ->method('sendMessage')
            ->withConsecutive(
                [$this->isInstanceOf('\Thruway\Message\WelcomeMessage')],
                [$this->callback(function (\Thruway\Message\RegisteredMessage $msg) use (&$registrationId) {
                    $registrationId = $msg->getRegistrationId();
                    return true;
                })],
                [$this->callback(function (\Thruway\Message\ErrorMessage $msg) {
                    $this->assertEquals("No uri set for state handler registration.", $msg->getArguments()[0]);
                    return true;
                })],
                [$this->callback(function (\Thruway\Message\ErrorMessage $msg) {
                    $this->assertEquals("No handler uri set for state handler registration.", $msg->getArguments()[0]);
                    return true;
                })],
                [$this->callback(function (\Thruway\Message\ErrorMessage $msg) {
                    $this->assertEquals("No uri set for state handler registration.", $msg->getArguments()[0]);
                    return true;
                })],
                [$this->callback(function (\Thruway\Message\ResultMessage $msg) {
                    return true;
                })],
                [$this->callback(function (\Thruway\Message\InvocationMessage $msg) use (&$registrationId, &$invocationReqId) {
                    $this->assertEquals($registrationId, $msg->getRegistrationId());
                    $invocationReqId = $msg->getRequestId();
                    return true;
                })]
            );

        $transportSubscriber = $this->createTransportMock();

        $transportSubscriber->expects($this->exactly(8))
            ->method("sendMessage")
            ->withConsecutive(
                [$this->isInstanceOf('\Thruway\Message\WelcomeMessage')],
                [$this->callback(function (\Thruway\Message\SubscribedMessage $msg) use (&$subscriptionId) {
                    $subscriptionId = $msg->getSubscriptionId();
                    return true;
                })],
                [$this->callback(function ($msg) { $this->assertEventMessageWithArgument0(0, $msg); return true; })],
                [$this->callback(function ($msg) { $this->assertEventMessageWithArgument0(1, $msg); return true; })],
                [$this->callback(function ($msg) { $this->assertEventMessageWithArgument0(2, $msg); return true; })],
                [$this->callback(function ($msg) { $this->assertEventMessageWithArgument0(3, $msg); return true; })],
                [$this->callback(function ($msg) { $this->assertEventMessageWithArgument0(4, $msg); return true; })],
                [$this->callback(function ($msg) { $this->assertEventMessageWithArgument0(5, $msg); return true; })]
            );

        $router->onOpen($transportStateHandler);
        $router->onOpen($transportSubscriber);

        $hello = new \Thruway\Message\HelloMessage('state.test.realm', (object)[]);

        $router->onMessage($transportStateHandler, $hello);
        $router->onMessage($transportSubscriber, $hello);

        $register = new \Thruway\Message\RegisterMessage(12345, (object)[], 'my_state_handler');

        $router->onMessage($transportStateHandler, $register);

        $call = new \Thruway\Message\CallMessage(2, (object)[], 'add_state_handler', [[]]);
        $router->onMessage($transportStateHandler, $call);
        $call->setArguments([(object)["uri" => "test.stateful.uri"]]);
        $router->onMessage($transportStateHandler, $call);
        $call->setArguments([(object)["handler_uri" => "my_state_handler"]]);
        $router->onMessage($transportStateHandler, $call);
        $call->setArguments([(object)["uri" => "test.stateful.uri", "handler_uri" => "my_state_handler"]]);
        $router->onMessage($transportStateHandler, $call);

        $subscribe = new \Thruway\Message\SubscribeMessage(2, (object)[], "test.stateful.uri");
        $router->onMessage($transportSubscriber, $subscribe);

        for ($i = 0; $i < 3; $i++) {
            $publish = new \Thruway\Message\PublishMessage($i, (object)[], "test.stateful.uri", [$i]);
            $publish->setPublicationId($i);
            $router->onMessage($transportStateHandler, $publish);
        }

        $yield = new \Thruway\Message\YieldMessage($invocationReqId, (object)[], [1234]);
        $router->onMessage($transportStateHandler, $yield);

        for ($i = 3; $i < 6; $i++) {
            $publish = new \Thruway\Message\PublishMessage($i, (object)[], "test.stateful.uri", [$i]);
            $publish->setPublicationId($i);
            $router->onMessage($transportStateHandler, $publish);
        }
    }

    public function testStateRestoreWithQueueNullPubId() {
        $router = new \Thruway\Peer\Router();

        $stateHandlerRegistry = new \Thruway\Subscription\StateHandlerRegistry('state.test.realm');

        $router->registerModule($stateHandlerRegistry);

        $router->start(false);

        $transportStateHandler = $this->createTransportMock();

        $registrationId = 0;
        $invocationReqId = 0;
        $subscriptionId = 0;

        $transportStateHandler->expects($this->exactly(7))
            ->method('sendMessage')
            ->withConsecutive(
                [$this->isInstanceOf('\Thruway\Message\WelcomeMessage')],
                [$this->callback(function (\Thruway\Message\RegisteredMessage $msg) use (&$registrationId) {
                    $registrationId = $msg->getRegistrationId();
                    return true;
                })],
                [$this->callback(function (\Thruway\Message\ErrorMessage $msg) {
                    $this->assertEquals("No uri set for state handler registration.", $msg->getArguments()[0]);
                    return true;
                })],
                [$this->callback(function (\Thruway\Message\ErrorMessage $msg) {
                    $this->assertEquals("No handler uri set for state handler registration.", $msg->getArguments()[0]);
                    return true;
                })],
                [$this->callback(function (\Thruway\Message\ErrorMessage $msg) {
                    $this->assertEquals("No uri set for state handler registration.", $msg->getArguments()[0]);
                    return true;
                })],
                [$this->callback(function (\Thruway\Message\ResultMessage $msg) {
                    return true;
                })],
                [$this->callback(function (\Thruway\Message\InvocationMessage $msg) use (&$registrationId, &$invocationReqId) {
                    $this->assertEquals($registrationId, $msg->getRegistrationId());
                    $invocationReqId = $msg->getRequestId();
                    return true;
                })]
            );

        $transportSubscriber = $this->createTransportMock();

        $transportSubscriber->expects($this->exactly(8))
            ->method("sendMessage")
            ->withConsecutive(
                [$this->isInstanceOf('\Thruway\Message\WelcomeMessage')],
                [$this->callback(function (\Thruway\Message\SubscribedMessage $msg) use (&$subscriptionId) {
                    $subscriptionId = $msg->getSubscriptionId();
                    return true;
                })],
                [$this->callback(function ($msg) { $this->assertEventMessageWithArgument0(0, $msg); return true; })],
                [$this->callback(function ($msg) { $this->assertEventMessageWithArgument0(1, $msg); return true; })],
                [$this->callback(function ($msg) { $this->assertEventMessageWithArgument0(2, $msg); return true; })],
                [$this->callback(function ($msg) { $this->assertEventMessageWithArgument0(3, $msg); return true; })],
                [$this->callback(function ($msg) { $this->assertEventMessageWithArgument0(4, $msg); return true; })],
                [$this->callback(function ($msg) { $this->assertEventMessageWithArgument0(5, $msg); return true; })]
            );

        $router->onOpen($transportStateHandler);
        $router->onOpen($transportSubscriber);

        $hello = new \Thruway\Message\HelloMessage('state.test.realm', (object)[]);

        $router->onMessage($transportStateHandler, $hello);
        $router->onMessage($transportSubscriber, $hello);

        $register = new \Thruway\Message\RegisterMessage(12345, (object)[], 'my_state_handler');

        $router->onMessage($transportStateHandler, $register);

        $call = new \Thruway\Message\CallMessage(2, (object)[], 'add_state_handler', [[]]);
        $router->onMessage($transportStateHandler, $call);
        $call->setArguments([(object)["uri" => "test.stateful.uri"]]);
        $router->onMessage($transportStateHandler, $call);
        $call->setArguments([(object)["handler_uri" => "my_state_handler"]]);
        $router->onMessage($transportStateHandler, $call);
        $call->setArguments([(object)["uri" => "test.stateful.uri", "handler_uri" => "my_state_handler"]]);
        $router->onMessage($transportStateHandler, $call);

        $subscribe = new \Thruway\Message\SubscribeMessage(2, (object)[], "test.stateful.uri");
        $router->onMessage($transportSubscriber, $subscribe);

        for ($i = 0; $i < 3; $i++) {
            $publish = new \Thruway\Message\PublishMessage($i, (object)[], "test.stateful.uri", [$i]);
            $publish->setPublicationId($i);
            $router->onMessage($transportStateHandler, $publish);
        }

        $yield = new \Thruway\Message\YieldMessage($invocationReqId, (object)[]);
        $router->onMessage($transportStateHandler, $yield);

        for ($i = 3; $i < 6; $i++) {
            $publish = new \Thruway\Message\PublishMessage($i, (object)[], "test.stateful.uri", [$i]);
            $publish->setPublicationId($i);
            $router->onMessage($transportStateHandler, $publish);
        }
    }

    public function testStateRestoreWithNoQueue() {
        $router = new \Thruway\Peer\Router();

        $stateHandlerRegistry = new \Thruway\Subscription\StateHandlerRegistry('state.test.realm');

        $router->registerModule($stateHandlerRegistry);

        $router->start(false);

        $transportStateHandler = $this->createTransportMock();

        $registrationId = 0;
        $invocationReqId = 0;
        $subscriptionId = 0;

        $transportStateHandler->expects($this->exactly(7))
            ->method('sendMessage')
            ->withConsecutive(
                [$this->isInstanceOf('\Thruway\Message\WelcomeMessage')],
                [$this->callback(function (\Thruway\Message\RegisteredMessage $msg) use (&$registrationId) {
                    $registrationId = $msg->getRegistrationId();
                    return true;
                })],
                [$this->callback(function (\Thruway\Message\ErrorMessage $msg) {
                    $this->assertEquals("No uri set for state handler registration.", $msg->getArguments()[0]);
                    return true;
                })],
                [$this->callback(function (\Thruway\Message\ErrorMessage $msg) {
                    $this->assertEquals("No handler uri set for state handler registration.", $msg->getArguments()[0]);
                    return true;
                })],
                [$this->callback(function (\Thruway\Message\ErrorMessage $msg) {
                    $this->assertEquals("No uri set for state handler registration.", $msg->getArguments()[0]);
                    return true;
                })],
                [$this->callback(function (\Thruway\Message\ResultMessage $msg) {
                    return true;
                })],
                [$this->callback(function (\Thruway\Message\InvocationMessage $msg) use (&$registrationId, &$invocationReqId) {
                    $this->assertEquals($registrationId, $msg->getRegistrationId());
                    $invocationReqId = $msg->getRequestId();
                    return true;
                })]
            );

        $transportSubscriber = $this->createTransportMock();

        $transportSubscriber->expects($this->exactly(5))
            ->method("sendMessage")
            ->withConsecutive(
                [$this->isInstanceOf('\Thruway\Message\WelcomeMessage')],
                [$this->callback(function (\Thruway\Message\SubscribedMessage $msg) use (&$subscriptionId) {
                    $subscriptionId = $msg->getSubscriptionId();
                    return true;
                })],
                [$this->callback(function ($msg) { $this->assertEventMessageWithArgument0(3, $msg); return true; })],
                [$this->callback(function ($msg) { $this->assertEventMessageWithArgument0(4, $msg); return true; })],
                [$this->callback(function ($msg) { $this->assertEventMessageWithArgument0(5, $msg); return true; })]
            );

        $router->onOpen($transportStateHandler);
        $router->onOpen($transportSubscriber);

        $hello = new \Thruway\Message\HelloMessage('state.test.realm', (object)[]);

        $router->onMessage($transportStateHandler, $hello);
        $router->onMessage($transportSubscriber, $hello);

        $register = new \Thruway\Message\RegisterMessage(12345, (object)[], 'my_state_handler');

        $router->onMessage($transportStateHandler, $register);

        $call = new \Thruway\Message\CallMessage(2, (object)[], 'add_state_handler', [[]]);
        $router->onMessage($transportStateHandler, $call);
        $call->setArguments([(object)["uri" => "test.stateful.uri"]]);
        $router->onMessage($transportStateHandler, $call);
        $call->setArguments([(object)["handler_uri" => "my_state_handler"]]);
        $router->onMessage($transportStateHandler, $call);
        $call->setArguments([(object)["uri" => "test.stateful.uri", "handler_uri" => "my_state_handler"]]);
        $router->onMessage($transportStateHandler, $call);

        $subscribe = new \Thruway\Message\SubscribeMessage(2, (object)[], "test.stateful.uri");
        $router->onMessage($transportSubscriber, $subscribe);

        $yield = new \Thruway\Message\YieldMessage($invocationReqId, (object)[]);
        $router->onMessage($transportStateHandler, $yield);

        for ($i = 3; $i < 6; $i++) {
            $publish = new \Thruway\Message\PublishMessage($i, (object)[], "test.stateful.uri", [$i]);
            $publish->setPublicationId($i);
            $router->onMessage($transportStateHandler, $publish);
        }
    }
}