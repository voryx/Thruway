<?php
use Thruway\Message\WelcomeMessage;

/**
 * Class RouterTest
 */
class RouterTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var \Thruway\Peer\Router
     */
    private $router;

    public function setup()
    {
        \Thruway\Logging\Logger::set(new \Psr\Log\NullLogger());

        $this->router = new \Thruway\Peer\Router();
    }

    public function testLoopCreated()
    {
        $this->assertInstanceOf('\React\EventLoop\LoopInterface', $this->router->getLoop());
    }

    /**
     * Test router start
     *
     * @return \Thruway\Peer\Router
     * @throws Exception
     */
    public function testStart()
    {
        $this->router->start();

        return $this->router;
    }

    /**
     * Test ConnectionOpen
     *
     * @depends testStart
     * @param \Thruway\Peer\Router $router
     * @return array
     */
    public function testConnectionOpen(\Thruway\Peer\Router $router)
    {
        $transport = $this->createMock('Thruway\Transport\TransportInterface');

        // Configure the stub.
        $transport->expects($this->any())
            ->method('getTransportDetails')
            ->will($this->returnValue(["type" => "ratchet", "transport_address" => "127.0.0.1"]));

        $session = new \Thruway\Session($transport);

        $router->getEventDispatcher()->dispatch("connection_open", new \Thruway\Event\ConnectionOpenEvent($session));

        $this->assertGreaterThan(0, count($router->managerGetSessionCount()));

        return ['router' => $router, 'session' => $session];
    }

    /**
     * This is to help bridge a gap between 0.3 and 0.4 testing
     *
     */
    private function getNewRouterAndSession() {
        $router = new \Thruway\Peer\Router();
        $router->start(false);
        $transport = $this->createMock('Thruway\Transport\TransportInterface');

        // Configure the stub.
        $transport->expects($this->any())
            ->method('getTransportDetails')
            ->will($this->returnValue(["type" => "ratchet", "transport_address" => "127.0.0.1"]));

        $session = new \Thruway\Session($transport);

        $router->getEventDispatcher()->dispatch("connection_open", new \Thruway\Event\ConnectionOpenEvent($session));

        return ['router' => $router, 'session' => $session];
    }

    /**
     * This also is a helper to help preserve some original tests
     */
    private function getActiveRouterAndSession() {
        $rt = $this->getNewRouterAndSession();

        $helloMessage = new \Thruway\Message\HelloMessage("test.realm", []);
        $rt['session']->dispatchMessage($helloMessage);

        return $rt;
    }

    /**
     * Test Hello message
     *
     * @return array
     *
     * https://github.com/tavendo/WAMP/blob/master/spec/basic.md#hello-1
     */
    public function testHelloMessage()
    {
        $rt = $this->getNewRouterAndSession();
        /** @var \Thruway\Session $session */
        $session = $rt['session'];
        $session->getTransport()->expects($this->once())
            ->method('sendMessage')
            ->with(
                $this->callback(
                    function (WelcomeMessage $msg) {
                        $this->assertInstanceOf('\Thruway\Message\WelcomeMessage', $msg);
                        $this->assertNotEmpty($msg->getDetails());
                        $this->assertObjectHasAttribute('roles', $msg->getDetails());
                        $this->assertObjectHasAttribute('dealer', $msg->getDetails()->roles);
                        $this->assertObjectHasAttribute('broker', $msg->getDetails()->roles);

                        return $msg instanceof Thruway\Message\WelcomeMessage;
                    }
                )
            )->will($this->returnValue(null));

        $helloMessage = new \Thruway\Message\HelloMessage("test.realm", []);
        $session->dispatchMessage($helloMessage);

        return $rt;
    }

    /**
     * Test Subscribe message
     *
     * @return array
     *
     * https://github.com/tavendo/WAMP/blob/master/spec/basic.md#subscribe-1
     */
    public function testSubscribeMessage()
    {
        $rt = $this->getActiveRouterAndSession();
        /** @var \Thruway\Session $session */
        $session = $rt["session"];
        $session->getTransport()->expects($this->once())
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
        $session->dispatchMessage($msg);

        return $rt;
    }

    /**
     * Test Subscribe to an empty topic
     *
     * @return array
     *
     * https://github.com/tavendo/WAMP/blob/master/spec/basic.md#subscription-error
     */
    public function testSubscribeEmptyTopicMessage()
    {
        $rt = $this->getActiveRouterAndSession();
        /** @var \Thruway\Session $session */
        $session = $rt["session"];
        $session->getTransport()->expects($this->exactly(1))
            ->method('sendMessage')
            ->withConsecutive(
                [$this->callback(
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
                )]
            );


        $msg = new \Thruway\Message\SubscribeMessage('222222', [], '');
        $session->dispatchMessage($msg);

        return $rt;
    }

    /**
     * Test Duplicate Subscription from the same session
     *
     * @return array
     */
    public function testSubscribeDuplicateTopic()
    {
        $rt = $this->getActiveRouterAndSession();
        /** @var \Thruway\Session $session */
        $session = $rt['session'];
        $session->getTransport()->expects($this->exactly(2))
            ->method('sendMessage')
            ->withConsecutive(
                [$this->callback(
                    function ($msg) {
                        $this->assertInstanceOf('\Thruway\Message\SubscribedMessage', $msg);
                        $this->assertEquals('1111111', $msg->getRequestId());

                        return $msg instanceof Thruway\Message\SubscribedMessage;

                    }
                )],
                [$this->callback(
                    function ($msg) {

                        $this->assertInstanceOf(
                            '\Thruway\Message\SubscribedMessage',
                            $msg,
                            "Should not return an error when trying to subscribe to topic more than once"
                        );

                        $this->assertEquals('333333', $msg->getRequestId());

                        return $msg instanceof Thruway\Message\SubscribedMessage;
                    }
                )]
            )->will($this->returnValue(null));

        $msg = new \Thruway\Message\SubscribeMessage('1111111', [], 'test.topic');
        $session->dispatchMessage($msg);


        $msg = new \Thruway\Message\SubscribeMessage('333333', [], 'test.topic');
        $session->dispatchMessage($msg);

        /* @var $router \Thruway\Peer\Router */
        $router        = $rt['router'];
        $subscriptions = $router->getRealmManager()->getRealm('test.realm')->getBroker()->managerGetSubscriptions()[0];

        $this->assertEquals(2, count($subscriptions));

        return $rt;
    }


    /**
     * Test Subscribe with an invalid URI
     *
     * @return array
     */
    public function testSubscribeInvalidURI()
    {
        $rt = $this->getActiveRouterAndSession();
        /** @var \Thruway\Session $session */
        $session = $rt['session'];
        $session->getTransport()->expects($this->once())
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
        $session->dispatchMessage($msg);


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
        /** @var \Thruway\Session $session */
        $session = $rt['session'];
        $session->getTransport()->expects($this->at(1))
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
        $session->dispatchMessage($msg);

        return $rt;
    }

    /**
     * Test Publish with Acknowledge flag
     *
     * @return array
     *
     * https://github.com/tavendo/WAMP/blob/master/spec/basic.md#published-1
     */
    public function testPublishAcknowledgeMessage()
    {
        $rt = $this->getActiveRouterAndSession();
        /** @var \Thruway\Session $session */
        $session = $rt['session'];
        $session->getTransport()->expects($this->once())
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
        $session->dispatchMessage($msg);

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
        /** @var \Thruway\Session $session */
        $session = $rt['session'];
        $session->getTransport()->expects($this->atMost(2))
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

        $transport = $this->createMock('Thruway\Transport\TransportInterface');
        $session = new \Thruway\Session($transport);

        // Configure the stub.
        $transport->expects($this->any())
            ->method('getTransportDetails')
            ->will($this->returnValue(["type" => "ratchet", "transport_address" => "127.0.0.1"]));

        //Simulate onOpen
        $router->getEventDispatcher()->dispatch("connection_open", new \Thruway\Event\ConnectionOpenEvent($session));

        //Simulate a HelloMessage
        $helloMessage = new \Thruway\Message\HelloMessage("test.realm", []);
        $session->dispatchMessage($helloMessage);

        //Publish Message
        $msg = new \Thruway\Message\PublishMessage(\Thruway\Common\Utils::getUniqueId(), new stdClass(), 'test.topic', ["hello world"]);
        $msg->setPublicationId('999654321');
        $session->dispatchMessage($msg);

    }


    /**
     * Test UnSubscribe message
     *
     * @see https://github.com/tavendo/WAMP/blob/master/spec/basic.md#unsubscribe-1
     */
    public function testUnSubscribeMessage()
    {
        $router = $this->router;
        $router->start();
        $transport = $this->createMock('Thruway\Transport\TransportInterface');

        // Configure the stub.
        $transport->expects($this->any())
            ->method('getTransportDetails')
            ->will($this->returnValue(["type" => "ratchet", "transport_address" => "127.0.0.1"]));

        //Subscribe to a topic
        $subscriptionId = null;
        $transport->expects($this->any())
            ->method('sendMessage')
            ->withConsecutive(
                [$this->isInstanceOf('\Thruway\Message\WelcomeMessage')],
                [$this->callback(
                    function ($msg) use (&$subscriptionId) {
                        $this->assertInstanceOf('\Thruway\Message\SubscribedMessage', $msg);
                        $this->assertEquals('7777777', $msg->getRequestId());
                        $subscriptionId = $msg->getSubscriptionId();
                        return $msg instanceof Thruway\Message\SubscribedMessage;
                    }
                )],
                [$this->callback(
                    function ($msg) {
                        $this->assertInstanceOf('\Thruway\Message\UnsubscribedMessage', $msg);
                        $this->assertEquals('888888', $msg->getRequestId());

                        return $msg instanceof Thruway\Message\UnsubscribedMessage;
                    }
                )]
            );

        $session = new \Thruway\Session($transport);

        //Simulate onOpen
        $router->getEventDispatcher()->dispatch("connection_open", new \Thruway\Event\ConnectionOpenEvent($session));

        //Simulate HelloMessage
        $helloMessage = new \Thruway\Message\HelloMessage("test.realm2", []);
        $session->dispatchMessage($helloMessage);

        $msg = new \Thruway\Message\SubscribeMessage('7777777', [], 'test.topic123');
        $session->dispatchMessage($msg);

        $this->assertInstanceOf('\Thruway\Subscription\Subscription',
            $router->getRealmManager()->getRealm('test.realm2')->getBroker()->getSubscriptionById($subscriptionId)
        );

        $msg = new \Thruway\Message\UnsubscribeMessage('888888', $subscriptionId);
        $session->dispatchMessage($msg);

        $broker = $router->getRealmManager()->getRealm('test.realm2')->getBroker();
        $this->assertFalse($broker->getSubscriptionById($subscriptionId));

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

        $transport = $this->createMock('Thruway\Transport\TransportInterface');

        // Configure the stub.
        $transport->expects($this->any())
            ->method('getTransportDetails')
            ->will($this->returnValue(["type" => "ratchet", "transport_address" => "127.0.0.1"]));

        //No messages should be sent
        $transport->expects($this->never())
            ->method('sendMessage');

        $session = new \Thruway\Session($transport);

        //Simulate onOpen
        $router->getEventDispatcher()->dispatch("connection_open", new \Thruway\Event\ConnectionOpenEvent($session));

        //Simulate a AbortMessage
        $abortMessage = new \Thruway\Message\AbortMessage(["message" => "Client is shutting down"], "wamp.error.system_shutdown");
        $session->dispatchMessage($abortMessage);

    }

    /**
     * Unhandled Message
     *
     * @depends testStart
     * @param \Thruway\Peer\Router $router
     * @return array
     */
    public function xtestUnhandledMessage(\Thruway\Peer\Router $router)
    {
        $this->markTestSkipped();

        $transport = $this->createMock('Thruway\Transport\TransportInterface');

        // Configure the stub.
        $transport->expects($this->any())
            ->method('getTransportDetails')
            ->will($this->returnValue(["type" => "ratchet", "transport_address" => "127.0.0.1"]));

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

        $session = new \Thruway\Session($transport);

        //Simulate onOpen
        $router->getEventDispatcher()->dispatch("connection_open", new \Thruway\Event\ConnectionOpenEvent($session));

        //Simulate a GoodbyeMessage
        $goodbyeMessage = new \Thruway\Message\GoodbyeMessage(["message" => "Client is shutting down"],
            "wamp.error.system_shutdown");
        $session->dispatchMessage($goodbyeMessage);

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

        $transport = $this->createMock('Thruway\Transport\TransportInterface');

        // Configure the stub.
        $transport->expects($this->any())
            ->method('getTransportDetails')
            ->will($this->returnValue(["type" => "ratchet", "transport_address" => "127.0.0.1"]));

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

        $session = new \Thruway\Session($transport);

        //Simulate onOpen
        $router->getEventDispatcher()->dispatch("connection_open", new \Thruway\Event\ConnectionOpenEvent($session));

        //Simulate a HelloMessage with an empty Realm
        $helloMessage = new \Thruway\Message\HelloMessage("", []);
        $session->dispatchMessage($helloMessage);
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

        $session1 = new \Thruway\Session($transport1);
        $session2 = new \Thruway\Session($transport2);
        $sessionPublisher = new \Thruway\Session($transportPublisher);

        $transport1->expects($this->exactly(3))
            ->method('sendMessage')
            ->withConsecutive(
                [$this->isInstanceOf('\Thruway\Message\WelcomeMessage')],
                [$this->isInstanceOf('\Thruway\Message\SubscribedMessage')],
                [$this->callback(function ($arg) use ($router, $transport1, $session1) {
                    $this->assertInstanceOf('\Thruway\Message\EventMessage', $arg);

                    // publish while in the callback
                    $publishMsg = new \Thruway\Message\PublishMessage(12346, (object)[], 'com.example.nowhere');

                    $session1->dispatchMessage($publishMsg);

                    $this->_callCount = $this->_callCount + 1;
                    return true;
                })]
            );



        $router->getEventDispatcher()->dispatch("connection_open", new \Thruway\Event\ConnectionOpenEvent($session1));
        $router->getEventDispatcher()->dispatch("connection_open", new \Thruway\Event\ConnectionOpenEvent($session2));
        $router->getEventDispatcher()->dispatch("connection_open", new \Thruway\Event\ConnectionOpenEvent($sessionPublisher));

        // send in a few hellos
        $helloMsg = new \Thruway\Message\HelloMessage("realm_issue53", (object)[]);

        $session1->dispatchMessage($helloMsg);
        $session2->dispatchMessage($helloMsg);
        $sessionPublisher->dispatchMessage($helloMsg);

        // subscribe
        $subscribeMsg = new \Thruway\Message\SubscribeMessage(\Thruway\Common\Utils::getUniqueId(), (object)[], "com.example.issue53");

        $session1->dispatchMessage($subscribeMsg);
        $session2->dispatchMessage($subscribeMsg);

        // publish to the topic from the publishing transport
        $publishMsg = new \Thruway\Message\PublishMessage(12345, (object)[], 'com.example.issue53');

        $sessionPublisher->dispatchMessage($publishMsg);
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

        $sessionStateHandler = new \Thruway\Session($transportStateHandler);
        $sessionSubscriber = new \Thruway\Session($transportSubscriber);

        $router->getEventDispatcher()->dispatch("connection_open", new \Thruway\Event\ConnectionOpenEvent($sessionStateHandler));
        $router->getEventDispatcher()->dispatch("connection_open", new \Thruway\Event\ConnectionOpenEvent($sessionSubscriber));

        $hello = new \Thruway\Message\HelloMessage('state.test.realm', (object)[]);

        $sessionStateHandler->dispatchMessage($hello);
        $sessionSubscriber->dispatchMessage($hello);

        $register = new \Thruway\Message\RegisterMessage(12345, (object)[], 'my_state_handler');

        $sessionStateHandler->dispatchMessage($register);

        $call = new \Thruway\Message\CallMessage(2, (object)[], 'add_state_handler', [[]]);
        $sessionStateHandler->dispatchMessage($call);
        $call->setArguments([(object)["uri" => "test.stateful.uri"]]);
        $sessionStateHandler->dispatchMessage($call);
        $call->setArguments([(object)["handler_uri" => "my_state_handler"]]);
        $sessionStateHandler->dispatchMessage($call);
        $call->setArguments([(object)["uri" => "test.stateful.uri", "handler_uri" => "my_state_handler"]]);
        $sessionStateHandler->dispatchMessage($call);

        $subscribe = new \Thruway\Message\SubscribeMessage(2, (object)[], "test.stateful.uri");
        $sessionSubscriber->dispatchMessage($subscribe);


        for ($i = 0; $i < 3; $i++) {
            $publish = new \Thruway\Message\PublishMessage($i, (object)[], "test.stateful.uri", [$i]);
            $publish->setPublicationId($i);
            $sessionStateHandler->dispatchMessage($publish);
        }

        $yield = new \Thruway\Message\YieldMessage($invocationReqId, (object)[], [1]);
        $sessionStateHandler->dispatchMessage($yield);

        for ($i = 3; $i < 6; $i++) {
            $publish = new \Thruway\Message\PublishMessage($i, (object)[], "test.stateful.uri", [$i]);
            $publish->setPublicationId($i);
            $sessionStateHandler->dispatchMessage($publish);
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

        $sessionStateHandler = new \Thruway\Session($transportStateHandler);
        $sessionSubscriber = new \Thruway\Session($transportSubscriber);

        $router->getEventDispatcher()->dispatch("connection_open", new \Thruway\Event\ConnectionOpenEvent($sessionStateHandler));
        $router->getEventDispatcher()->dispatch("connection_open", new \Thruway\Event\ConnectionOpenEvent($sessionSubscriber));

        $hello = new \Thruway\Message\HelloMessage('state.test.realm', (object)[]);

        $sessionStateHandler->dispatchMessage($hello);
        $sessionSubscriber->dispatchMessage($hello);

        $register = new \Thruway\Message\RegisterMessage(12345, (object)[], 'my_state_handler');

        $sessionStateHandler->dispatchMessage($register);

        $call = new \Thruway\Message\CallMessage(2, (object)[], 'add_state_handler', [[]]);
        $sessionStateHandler->dispatchMessage($call);
        $call->setArguments([(object)["uri" => "test.stateful.uri"]]);
        $sessionStateHandler->dispatchMessage($call);
        $call->setArguments([(object)["handler_uri" => "my_state_handler"]]);
        $sessionStateHandler->dispatchMessage($call);
        $call->setArguments([(object)["uri" => "test.stateful.uri", "handler_uri" => "my_state_handler"]]);
        $sessionStateHandler->dispatchMessage($call);

        $subscribe = new \Thruway\Message\SubscribeMessage(2, (object)[], "test.stateful.uri");
        $sessionSubscriber->dispatchMessage($subscribe);

        for ($i = 0; $i < 3; $i++) {
            $publish = new \Thruway\Message\PublishMessage($i, (object)[], "test.stateful.uri", [$i]);
            $publish->setPublicationId($i);
            $sessionStateHandler->dispatchMessage($publish);
        }

        $yield = new \Thruway\Message\YieldMessage($invocationReqId, (object)[], [1234]);
        $sessionStateHandler->dispatchMessage($yield);

        for ($i = 3; $i < 6; $i++) {
            $publish = new \Thruway\Message\PublishMessage($i, (object)[], "test.stateful.uri", [$i]);
            $publish->setPublicationId($i);
            $sessionStateHandler->dispatchMessage($publish);
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

        $sessionStateHandler = new \Thruway\Session($transportStateHandler);
        $sessionSubscriber = new \Thruway\Session($transportSubscriber);

        $router->getEventDispatcher()->dispatch("connection_open", new \Thruway\Event\ConnectionOpenEvent($sessionStateHandler));
        $router->getEventDispatcher()->dispatch("connection_open", new \Thruway\Event\ConnectionOpenEvent($sessionSubscriber));

        $hello = new \Thruway\Message\HelloMessage('state.test.realm', (object)[]);

        $sessionStateHandler->dispatchMessage($hello);
        $sessionSubscriber->dispatchMessage($hello);

        $register = new \Thruway\Message\RegisterMessage(12345, (object)[], 'my_state_handler');

        $sessionStateHandler->dispatchMessage($register);

        $call = new \Thruway\Message\CallMessage(2, (object)[], 'add_state_handler', [[]]);
        $sessionStateHandler->dispatchMessage($call);
        $call->setArguments([(object)["uri" => "test.stateful.uri"]]);
        $sessionStateHandler->dispatchMessage($call);
        $call->setArguments([(object)["handler_uri" => "my_state_handler"]]);
        $sessionStateHandler->dispatchMessage($call);
        $call->setArguments([(object)["uri" => "test.stateful.uri", "handler_uri" => "my_state_handler"]]);
        $sessionStateHandler->dispatchMessage($call);

        $subscribe = new \Thruway\Message\SubscribeMessage(2, (object)[], "test.stateful.uri");
        $sessionSubscriber->dispatchMessage($subscribe);

        for ($i = 0; $i < 3; $i++) {
            $publish = new \Thruway\Message\PublishMessage($i, (object)[], "test.stateful.uri", [$i]);
            $publish->setPublicationId($i);
            $sessionStateHandler->dispatchMessage($publish);
        }

        $yield = new \Thruway\Message\YieldMessage($invocationReqId, (object)[]);
        $sessionStateHandler->dispatchMessage($yield);

        for ($i = 3; $i < 6; $i++) {
            $publish = new \Thruway\Message\PublishMessage($i, (object)[], "test.stateful.uri", [$i]);
            $publish->setPublicationId($i);
            $sessionStateHandler->dispatchMessage($publish);
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

        $sessionStateHandler = new \Thruway\Session($transportStateHandler);
        $sessionSubscriber = new \Thruway\Session($transportSubscriber);

        $router->getEventDispatcher()->dispatch("connection_open", new \Thruway\Event\ConnectionOpenEvent($sessionStateHandler));
        $router->getEventDispatcher()->dispatch("connection_open", new \Thruway\Event\ConnectionOpenEvent($sessionSubscriber));

        $hello = new \Thruway\Message\HelloMessage('state.test.realm', (object)[]);

        $sessionStateHandler->dispatchMessage($hello);
        $sessionSubscriber->dispatchMessage($hello);

        $register = new \Thruway\Message\RegisterMessage(12345, (object)[], 'my_state_handler');

        $sessionStateHandler->dispatchMessage($register);

        $call = new \Thruway\Message\CallMessage(2, (object)[], 'add_state_handler', [[]]);
        $sessionStateHandler->dispatchMessage($call);
        $call->setArguments([(object)["uri" => "test.stateful.uri"]]);
        $sessionStateHandler->dispatchMessage($call);
        $call->setArguments([(object)["handler_uri" => "my_state_handler"]]);
        $sessionStateHandler->dispatchMessage($call);
        $call->setArguments([(object)["uri" => "test.stateful.uri", "handler_uri" => "my_state_handler"]]);
        $sessionStateHandler->dispatchMessage($call);

        $subscribe = new \Thruway\Message\SubscribeMessage(2, (object)[], "test.stateful.uri");
        $sessionSubscriber->dispatchMessage($subscribe);

        $yield = new \Thruway\Message\YieldMessage($invocationReqId, (object)[]);
        $sessionStateHandler->dispatchMessage($yield);

        for ($i = 3; $i < 6; $i++) {
            $publish = new \Thruway\Message\PublishMessage($i, (object)[], "test.stateful.uri", [$i]);
            $publish->setPublicationId($i);
            $sessionStateHandler->dispatchMessage($publish);
        }
    }

    public function testRouterStop() {
        $loop = \React\EventLoop\Factory::create();
        $router = new \Thruway\Peer\Router($loop);
        $router->addTransportProvider(new \Thruway\Transport\RatchetTransportProvider("127.0.0.1", 18080));
        $loop->addTimer(.1, function () use ($router) {
            $router->stop();
            $this->_result = "Stop was called";
        });
        $router->start();
        // if the execution makes it here, stop worked
        $this->assertEquals("Stop was called", $this->_result);
    }

    public function testRouterStopWithLiveSession() {
        $loop = \React\EventLoop\Factory::create();
        $router = new \Thruway\Peer\Router($loop);
        $router->addTransportProvider(new \Thruway\Transport\RatchetTransportProvider("127.0.0.1", 18080));
        $client = new \Thruway\Peer\Client("some_realm", $loop);
        $client->on('open', function () use ($loop, $router) {
            $router->stop();
            $this->_result = "Stop was called";
        });
        $client->setAttemptRetry(false); // we are running on the same loop so if we allow retry, we will hang
        $client->addTransportProvider(new \Thruway\Transport\PawlTransportProvider("ws://127.0.0.1:18080"));
        $loop->addTimer(0.1, function () use ($client) {
            $client->start(false); // don't start loop yet
        });
        $router->start();
        // if the execution makes it here, stop worked
        $this->assertEquals("Stop was called", $this->_result);
    }
    public function testRouterStopWithRawSocketLiveSession() {
        $loop = \React\EventLoop\Factory::create();
        $router = new \Thruway\Peer\Router($loop);
        $router->addTransportProvider(new \Thruway\Transport\RawSocketTransportProvider("127.0.0.1", 18080));
        $client = new \Thruway\Peer\Client("some_realm", $loop);
        $client->on('open', function () use ($loop, $router) {
            $router->stop();
            $this->_result = "Stop was called";
        });
        $client->setAttemptRetry(false); // we are running on the same loop so if we allow retry, we will hang
        $client->addTransportProvider(new \Thruway\Transport\RawSocketClientTransportProvider("127.0.0.1", 18080));
        $loop->addTimer(0.1, function () use ($client) {
            $client->start(false); // don't start loop yet
        });
        $router->start();
        // if the execution makes it here, stop worked
        $this->assertEquals("Stop was called", $this->_result);
    }
    public function testRouterStopWithInternalClientLiveSession() {
        $loop = \React\EventLoop\Factory::create();
        $router = new \Thruway\Peer\Router($loop);
        // just so we have another transport
        $router->addTransportProvider(new \Thruway\Transport\RawSocketTransportProvider("127.0.0.1", 18080));
        $client = new \Thruway\Peer\Client("some_realm", $loop);
        $client->on('open', function () use ($loop, $router) {
            $router->stop();
            $this->_result = "Stop was called";
        });
        $client->setAttemptRetry(false); // we are running on the same loop so if we allow retry, we will hang
        $router->addInternalClient($client);
        $loop->addTimer(0.1, function () use ($client) {
            $client->start(false); // don't start loop yet
        });
        $router->start();
        // if the execution makes it here, stop worked
        $this->assertEquals("Stop was called", $this->_result);
    }

    public function testRealmJoinNoAutocreate() {
        $loop = new \React\EventLoop\StreamSelectLoop();
        $router = new \Thruway\Peer\Router($loop);

        // you have to have at least one transport for the router to start
        // internal client in this case
        $iClient = new \Thruway\Peer\Client('some_realm');
        $router->registerModule(new \Thruway\Transport\InternalClientTransportProvider($iClient));

        $router->start(false);

        $router->getRealmManager()->setAllowRealmAutocreate(false);

        $this->assertEquals(1,count($router->getRealmManager()->getRealms()));

        $transport = new \Thruway\Transport\DummyTransport();
        $session = $router->createNewSession($transport);
        $prevMsg = null;
        $router->getEventDispatcher()->dispatch("connection_open", new \Thruway\Event\ConnectionOpenEvent($session));

        $fromRouter = [];

        $toRouter = [
            new \Thruway\Message\HelloMessage("another_realm", (object)[]),
            function () use (&$fromRouter) {
                $this->assertEquals(1, count($fromRouter));
                $this->assertInstanceOf('\Thruway\Message\AbortMessage', $fromRouter[0]);
                /** @var \Thruway\Message\AbortMessage $abortMessage */
                $abortMessage = $fromRouter[0];
                $this->assertEquals("wamp.error.no_such_realm", $abortMessage->getResponseURI());
            }
        ];

        foreach ($toRouter as $msg) {
            if (is_callable($msg)) {
                $msg = $msg();
                if (!($msg instanceof \Thruway\Message\Message)) {
                    continue;
                }
            }

            $session->dispatchMessage($msg);
            if ($prevMsg !== $transport->getLastMessageSent()) {
                $fromRouter[] = $prevMsg = $transport->getLastMessageSent();
            }
        }
    }

    /**
     * @expectedException InvalidArgumentException
     * @throws \Thruway\Exception\RealmNotFoundException
     */
    public function testGetRealmWithNonscalarThrows()
    {
        $router = new \Thruway\Peer\Router();

        $router->getRealmManager()->getRealm(new stdClass());
    }

    // This came over from 0.3 but things work differently now
    // still should implement removeModule or something for the same type of thing
//    public function testRemoveInternalClient() {
//        $clientRemovalDeferred = new \React\Promise\Deferred();
//        $loop = \React\EventLoop\Factory::create();
//        $router = new \Thruway\Peer\Router($loop);
//        $client = new \Thruway\Peer\Client("some_realm", $loop);
//        $client->on('open', function (\Thruway\ClientSession $session, $transport, $details) use ($client, $loop, $router) {
//            $session->register('internal_echo', function ($args) {
//                return $args;
//            })->then(function () use ($client, $loop, $router) {
//                $loop->addTimer(0.001, function () use ($client, $router) {
//                    $router->removeInternalClient($client);
//                });
//            });
//        });
//        $client->on('close', function () use ($router) {
//            $this->_result = "Client closed";
//            $router->stop();
//        });
//        $router->addInternalClient($client);
//        // setup a real listening thing
//        $router->addTransportProvider(new \Thruway\Transport\RatchetTransportProvider());
//        $router->start();
//        $this->assertEquals("Client closed", $this->_result);
//    }
}