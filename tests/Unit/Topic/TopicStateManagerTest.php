<?php


class TopicStateManagerTest extends PHPUnit_Framework_TestCase {
    public function testThings() {
//        $topicManager = $this->getMockBuilder('\Thruway\Topic\TopicManager')
//            ->getMock();
        $topicManager = new \Thruway\Topic\TopicManager();

        $tsManager = new \Thruway\Topic\TopicStateManager("test_realm");
        $tsManager->addTransportProvider(new \Thruway\Transport\DummyTransportProvider());

        $transport = $this->getMockBuilder('\Thruway\Transport\TransportInterface')
            ->getMock();

        /** @var \Thruway\Message\RegisterMessage $addReg */
        $addReg = null;
        /** @var \Thruway\Message\RegisterMessage $removeReg */
        $removeReg = null;
        /** @var \Thruway\Message\CallMessage $callMsg */
        $callMsg = null;

        $transport->expects($this->exactly(5))
            ->method("sendMessage")
            ->withConsecutive(
                [$this->isInstanceOf('\Thruway\Message\HelloMessage')],
                [$this->callback(function ($msg) use (&$addReg, $tsManager) {
                    /** @var \Thruway\Message\RegisterMessage $msg */
                    $this->assertInstanceOf('\Thruway\Message\RegisterMessage', $msg);
                    $this->assertEquals("add_topic_handler", $msg->getUri());
                    $addReg = $msg;
                    return true;
                })],
                [$this->callback(function ($msg) use (&$removeReg, $tsManager) {
                    /** @var \Thruway\Message\RegisterMessage $msg */
                    $this->assertInstanceOf('\Thruway\Message\RegisterMessage', $msg);
                    $this->assertEquals("remove_topic_handler", $msg->getUri());
                    $removeReg = $msg;
                    return true;
                })],
                [$this->callback(function ($msg) {
                    /** @var \Thruway\Message\YieldMessage $msg */
                    $this->assertInstanceOf('\Thruway\Message\YieldMessage', $msg);
                    return true;
                })],
                [$this->callback(function ($msg) use (&$callMsg) {
                    /** @var \Thruway\Message\CallMessage $msg */
                    $this->assertInstanceOf('\Thruway\Message\CallMessage', $msg);
                    $this->assertEquals("my_handler", $msg->getUri());

                    $callMsg = $msg;
                    return true;
                })]
            );

        $tsManager->setTopicManager($topicManager);

        $tsManager->start(false);

        $tsManager->onOpen($transport);

        $welcomeMsg = new \Thruway\Message\WelcomeMessage(1, (object)[]);

        $tsManager->onMessage($transport, $welcomeMsg);

        $registeredAddMsg = new \Thruway\Message\RegisteredMessage($addReg->getRequestId(), 1);
        $tsManager->onMessage($transport, $registeredAddMsg);

        $registeredRemoveMsg = new \Thruway\Message\RegisteredMessage($removeReg->getRequestId(), 2);
        $tsManager->onMessage($transport, $registeredRemoveMsg);

        // add a registration
        $invokeAdd = new \Thruway\Message\InvocationMessage(
            10,
            1,
            (object)[],
            [(object)["topic" => 'test.topic', "handler_uri" => 'my_handler']]);
        $tsManager->onMessage($transport, $invokeAdd);

        $subSession = $this->getMockBuilder('\Thruway\Session')
            ->setMethods(["sendMessage"])
            ->disableOriginalConstructor()
            ->getMock();

        $subSession->expects($this->exactly(3))
            ->method("sendMessage")
            ->withConsecutive(
                [$this->callback(function ($msg) {
                    /** @var \Thruway\Message\EventMessage $msg */
                    $this->assertInstanceOf('\Thruway\Message\EventMessage', $msg);
                    $this->assertEquals(2, $msg->getPublicationId());
                    return true;
                })],
                [$this->callback(function ($msg) {
                    /** @var \Thruway\Message\EventMessage $msg */
                    $this->assertInstanceOf('\Thruway\Message\EventMessage', $msg);
                    $this->assertEquals(1, $msg->getPublicationId());
                    return true;
                })],
                [$this->callback(function ($msg) {
                    /** @var \Thruway\Message\EventMessage $msg */
                    $this->assertInstanceOf('\Thruway\Message\EventMessage', $msg);
                    $this->assertEquals(3, $msg->getPublicationId());
                    return true;
                })]
            );

        $subscription = new \Thruway\Subscription("test.topic", $subSession);

        $topic = $topicManager->getTopic("test.topic");

        $this->assertInstanceOf('\Thruway\Topic\Topic', $topic);
        $this->assertEquals("my_handler", $topic->getStateHandler());

        $topic->addSubscription($subscription);
        // This pauses the subscription until the result comes back
        $tsManager->publishState($subscription);

        $eventMsg = new \Thruway\Message\EventMessage($subscription->getId(), 1, (object)[]);
        $subscription->sendEventMessage($eventMsg);

        $eventMsg = new \Thruway\Message\EventMessage($subscription->getId(), 2, (object)[]);
        $eventMsg->setRestoringState(true);
        $subscription->sendEventMessage($eventMsg);

        $eventMsg = new \Thruway\Message\EventMessage($subscription->getId(), 3, (object)[]);
        $subscription->sendEventMessage($eventMsg);

        $resultMsg = new \Thruway\Message\ResultMessage($callMsg->getRequestId(), (object)[]);
        $tsManager->onMessage($transport, $resultMsg);

        $this->assertEquals(1, count($topic->getSubscriptions()));

        $topic->removeSubscription($subscription->getId());

        $this->assertEquals(0, count($topic->getSubscriptions()));
    }
} 