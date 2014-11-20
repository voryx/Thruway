<?php
require_once __DIR__ . '/../../bootstrap.php';

class BrokerTest extends PHPUnit_Framework_TestCase
{
    public function testUnsubscribeFromNonExistentSubscription()
    {
        $transport = $this->getMockBuilder('\Thruway\Transport\TransportInterface')
            ->getMock();

        $transport->method("getTransportDetails")->will($this->returnValue(""));

        $session = $this->getMockBuilder('\Thruway\Session')
            ->setMethods(["sendMessage"])
            ->setConstructorArgs([$transport])
            ->getMock();

        $broker = new \Thruway\Role\Broker();

        $session->expects($this->once())
            ->method("sendMessage")
            ->with($this->isInstanceOf('\Thruway\Message\ErrorMessage'));

        $unsubscribeMsg = new \Thruway\Message\UnsubscribeMessage(\Thruway\Common\Utils::getUniqueId(), 0);

        $broker->onMessage($session, $unsubscribeMsg);
    }

    public function testDoNotExcludeMe()
    {
        $transport = $this->getMockBuilder('\Thruway\Transport\TransportInterface')
            ->getMock();

        $transport->expects($this->any())->method("getTransportDetails")->will($this->returnValue(""));

        $session = $this->getMockBuilder('\Thruway\Session')
            ->setMethods(["sendMessage"])
            ->setConstructorArgs([$transport])
            ->getMock();
        /** @var $session \Thruway\Session */
        $session->setRealm(new \Thruway\Realm("testrealm"));

        $broker = new \Thruway\Role\Broker();

        $subscribeMsg = new \Thruway\Message\SubscribeMessage('\Thruway\Session', [], "test_subscription");

        /** @var \Thruway\Message\SubscribedMessage $subscribedMsg */
        $subscribedMsg = null;

        $session->expects($this->exactly(3))
            ->method("sendMessage")
            ->withConsecutive(
                [
                    $this->callback(function ($msg) use (&$subscribedMsg) {
                        $this->isInstanceOf('\Thruway\Message\SubscribedMessage');
                        $subscribedMsg = $msg;
                        return true;
                    })
                ],
                [$this->isInstanceOf('\Thruway\Message\PublishedMessage')],
                [$this->isInstanceOf('\Thruway\Message\EventMessage')]
            );

        $broker->onMessage($session, $subscribeMsg);

        $subscriptionId = $subscribedMsg->getSubscriptionId();

        $publishMsg = new \Thruway\Message\PublishMessage(
            \Thruway\Common\Utils::getUniqueId(),
            ['exclude_me' => false, 'acknowledge' => true],
            'test_subscription'
        );

        $broker->onMessage($session, $publishMsg);
    }

    /**
     * @throws Exception
     * @expectedException Exception
     */
    public function testBadMessageToOnMessage()
    {
        $transport = $this->getMockBuilder('\Thruway\Transport\TransportInterface')
            ->getMock();

        $transport->expects($this->any())->method("getTransportDetails")->will($this->returnValue(""));

        $session = $this->getMockBuilder('\Thruway\Session')
            ->setMethods(["sendMessage"])
            ->setConstructorArgs([$transport])
            ->getMock();

        $broker = new \Thruway\Role\Broker();

        $goodbyeMsg = new \Thruway\Message\GoodbyeMessage([], 'test_reason');

        $broker->onMessage($session, $goodbyeMsg);
    }

    public function testRemoveRegistration()
    {
        $transport = $this->getMockBuilder('\Thruway\Transport\TransportInterface')
            ->getMock();

        $transport->expects($this->any())->method("getTransportDetails")->will($this->returnValue(""));

        $session = $this->getMockBuilder('\Thruway\Session')
            ->setMethods(["sendMessage"])
            ->setConstructorArgs([$transport])
            ->getMock();
        /** @var $session \Thruway\Session */
        $session->setRealm(new \Thruway\Realm("testrealm"));

        $broker = new \Thruway\Role\Broker();

        $subscribeMsg = new \Thruway\Message\SubscribeMessage(\Thruway\Common\Utils::getUniqueId(), [], "test.topic");

        $broker->onMessage($session, $subscribeMsg);

        $subscriptions = $broker->getSubscriptions();
        $this->assertTrue(count($subscriptions) === 1);

        $topic = $broker->getTopicManager()->getTopic('test.topic');
        $this->assertInstanceOf('\Thruway\Topic\Topic', $topic);

        $this->assertTrue(count($topic->getSubscriptions()) === 1);

        $subscriptions = array_values($subscriptions);
        $broker->removeSubscription($subscriptions[0]);
        $this->assertTrue(count($broker->getSubscriptions()) === 0);
        $this->assertTrue(count($topic->getSubscriptions()) === 0);

    }

    private function createTransportInterfaceMock() {
        return $this->getMock('\Thruway\Transport\TransportInterface');
    }

    public function testStatelessRegistration() {
        $topicStateManager = $this->getMockBuilder('\Thruway\Topic\TopicStateManagerInterface')
            ->getMock();

        $topicStateManager->expects($this->never())
            ->method("publishState");

        $session = $this->getMockBuilder('\Thruway\Session')
            ->setMethods(['sendMessage'])
            ->setConstructorArgs([$this->createTransportInterfaceMock()])
            ->getMock();

        $session->expects($this->exactly(1))
            ->method("sendMessage")
            ->withConsecutive([$this->isInstanceOf('\Thruway\Message\SubscribedMessage')]);

        $broker = new \Thruway\Role\Broker();

        $broker->setTopicStateManager($topicStateManager);

        $subscribeMsg = new \Thruway\Message\SubscribeMessage(1234, (object)[], 'my.topic');

        // this will create a new topic - so should not have any
        // stateful stuff
        $broker->onMessage($session, $subscribeMsg);
    }

    public function testStatefulRegistration() {
        /** @var \Thruway\Subscription $subscription */
        $subscription = null;

        $topicStateManager = $this->getMockBuilder('\Thruway\Topic\TopicStateManagerInterface')
            ->getMock();

        $topicStateManager->expects($this->once())
            ->method("publishState")
            ->withConsecutive([$this->callback(function (\Thruway\Subscription $arg) {
                $this->assertInstanceOf('\Thruway\Subscription', $arg);
                $this->assertEquals("my.topic", $arg->getTopic());
                return true;
            })]);

        $session = $this->getMockBuilder('\Thruway\Session')
            ->setMethods(['sendMessage'])
            ->setConstructorArgs([$this->createTransportInterfaceMock()])
            ->getMock();

        $session->expects($this->exactly(1))
            ->method("sendMessage")
            ->withConsecutive([$this->isInstanceOf('\Thruway\Message\SubscribedMessage')]);

        $broker = new \Thruway\Role\Broker();

        $broker->setTopicStateManager($topicStateManager);

        $topic = new \Thruway\Topic\Topic("my.topic");
        $topic->setStateHandler("state.handler.for.my.topic");

        $broker->getTopicManager()->addTopic($topic);

        $subscribeMsg = new \Thruway\Message\SubscribeMessage(1234, (object)[], 'my.topic');

        // this will create a new topic - so should not have any
        // stateful stuff
        $broker->onMessage($session, $subscribeMsg);
    }

    private function assertPublicationWithId($id, $arg) {
        $this->assertInstanceOf('\Thruway\Message\EventMessage', $arg);
        $this->assertEquals($id, $arg->getPublicationId());
        return true;
    }

    public function testStateRestoreWithNoQueue() {
        /** @var \Thruway\Subscription $subscription */
        $subscription = null;

        $broker = new \Thruway\Role\Broker();

        $topicStateManager = $this->getMockBuilder('\Thruway\Topic\TopicStateManagerInterface')
            ->getMock();

        $topicStateManager->expects($this->once())
            ->method("publishState")
            ->withConsecutive([$this->callback(function (\Thruway\Subscription $arg) use ($broker, &$subscription) {
                $this->assertInstanceOf('\Thruway\Subscription', $arg);
                $this->assertEquals("my.topic", $arg->getTopic());

                $subscription = $arg;

                return true;
            })])
            ->willReturnCallback(function () use ($broker, &$subscription) {
                $subscription->pauseForState();
                $eventMsg = new \Thruway\Message\EventMessage($subscription->getId(), 1, (object)[]);
                $subscription->sendEventMessage($eventMsg);
                $subscription->unPauseForState();
                return true;
            });

        $session = $this->getMockBuilder('\Thruway\Session')
            ->setMethods(['sendMessage'])
            ->setConstructorArgs([$this->createTransportInterfaceMock()])
            ->getMock();

        $session->expects($this->exactly(2))
            ->method("sendMessage")
            ->withConsecutive(
                [$this->isInstanceOf('\Thruway\Message\SubscribedMessage')],
                [$this->callback(function ($msg) { $this->assertPublicationWithId(1, $msg); return true; })]
            );

        $broker->setTopicStateManager($topicStateManager);

        $topic = new \Thruway\Topic\Topic("my.topic");
        $topic->setStateHandler("state.handler.for.my.topic");

        $broker->getTopicManager()->addTopic($topic);

        $subscribeMsg = new \Thruway\Message\SubscribeMessage(1234, (object)[], 'my.topic');

        // this will create a new topic - so should not have any
        // stateful stuff
        $broker->onMessage($session, $subscribeMsg);
    }

    public function testStateRestoreWithQueueNullPubId() {
        /** @var \Thruway\Subscription $subscription */
        $subscription = null;

        $broker = new \Thruway\Role\Broker();

        $topicStateManager = $this->getMockBuilder('\Thruway\Topic\TopicStateManagerInterface')
            ->getMock();

        $topicStateManager->expects($this->once())
            ->method("publishState")
            ->withConsecutive([$this->callback(function (\Thruway\Subscription $arg) use ($broker, &$subscription) {
                $this->assertInstanceOf('\Thruway\Subscription', $arg);
                $this->assertEquals("my.topic", $arg->getTopic());

                $subscription = $arg;

                return true;
            })])
            ->willReturnCallback(function () use ($broker, &$subscription) {
                $subscription->pauseForState();
                // this should be queued
                $eventMsg = new \Thruway\Message\EventMessage($subscription->getId(), 1, (object)[]);
                $subscription->sendEventMessage($eventMsg);

                // this should restore state
                $eventMsg = new \Thruway\Message\EventMessage($subscription->getId(), 2, (object)[]);
                $eventMsg->setRestoringState(true);
                $subscription->sendEventMessage($eventMsg);
                $subscription->unPauseForState(null);

                return [null];
            });

        $session = $this->getMockBuilder('\Thruway\Session')
            ->setMethods(['sendMessage'])
            ->setConstructorArgs([$this->createTransportInterfaceMock()])
            ->getMock();

        $session->expects($this->exactly(3))
            ->method("sendMessage")
            ->withConsecutive(
                [$this->isInstanceOf('\Thruway\Message\SubscribedMessage')],
                [$this->callback(function ($msg) {
                    $this->assertPublicationWithId(2, $msg);
                    $this->assertTrue($msg->isRestoringState());
                    return true;
                })],
                [$this->callback(function ($msg) { $this->assertPublicationWithId(1, $msg); return true; })]
            );

        $broker->setTopicStateManager($topicStateManager);

        $topic = new \Thruway\Topic\Topic("my.topic");
        $topic->setStateHandler("state.handler.for.my.topic");

        $broker->getTopicManager()->addTopic($topic);

        $subscribeMsg = new \Thruway\Message\SubscribeMessage(1234, (object)[], 'my.topic');

        // this will create a new topic - so should not have any
        // stateful stuff
        $broker->onMessage($session, $subscribeMsg);
    }

    public function testStateRestoreWithQueuePubIdNotInQueue() {
        /** @var \Thruway\Subscription $subscription */
        $subscription = null;

        $broker = new \Thruway\Role\Broker();

        $topicStateManager = $this->getMockBuilder('\Thruway\Topic\TopicStateManagerInterface')
            ->getMock();

        $topicStateManager->expects($this->once())
            ->method("publishState")
            ->withConsecutive([$this->callback(function (\Thruway\Subscription $arg) use ($broker, &$subscription) {
                $this->assertInstanceOf('\Thruway\Subscription', $arg);
                $this->assertEquals("my.topic", $arg->getTopic());

                $subscription = $arg;

                return true;
            })])
            ->willReturnCallback(function () use ($broker, &$subscription) {
                $subscription->pauseForState();
                // this should be queued
                for ($i = 1; $i < 5; $i++) {
                    $eventMsg = new \Thruway\Message\EventMessage($subscription->getId(), $i, (object)[]);
                    $subscription->sendEventMessage($eventMsg);
                }

                // this should restore state
                $eventMsg = new \Thruway\Message\EventMessage($subscription->getId(), 20, (object)[]);
                $eventMsg->setRestoringState(true);
                $subscription->sendEventMessage($eventMsg);
                $subscription->unPauseForState(0);

                return [null];
            });

        $session = $this->getMockBuilder('\Thruway\Session')
            ->setMethods(['sendMessage'])
            ->setConstructorArgs([$this->createTransportInterfaceMock()])
            ->getMock();

        $session->expects($this->exactly(6))
            ->method("sendMessage")
            ->withConsecutive(
                [$this->isInstanceOf('\Thruway\Message\SubscribedMessage')],
                [$this->callback(function ($msg) {
                    $this->assertPublicationWithId(20, $msg);
                    $this->assertTrue($msg->isRestoringState());
                    return true;
                })],
                [$this->callback(function ($msg) { $this->assertPublicationWithId(1, $msg); return true; })],
                [$this->callback(function ($msg) { $this->assertPublicationWithId(2, $msg); return true; })],
                [$this->callback(function ($msg) { $this->assertPublicationWithId(3, $msg); return true; })],
                [$this->callback(function ($msg) { $this->assertPublicationWithId(4, $msg); return true; })]
            );

        $broker->setTopicStateManager($topicStateManager);

        $topic = new \Thruway\Topic\Topic("my.topic");
        $topic->setStateHandler("state.handler.for.my.topic");

        $broker->getTopicManager()->addTopic($topic);

        $subscribeMsg = new \Thruway\Message\SubscribeMessage(1234, (object)[], 'my.topic');

        // this will create a new topic - so should not have any
        // stateful stuff
        $broker->onMessage($session, $subscribeMsg);
    }

    public function testStateRestoreWithQueueOnlyKeepSomeInQueue() {
        /** @var \Thruway\Subscription $subscription */
        $subscription = null;

        $broker = new \Thruway\Role\Broker();

        $topicStateManager = $this->getMockBuilder('\Thruway\Topic\TopicStateManagerInterface')
            ->getMock();

        $topicStateManager->expects($this->once())
            ->method("publishState")
            ->withConsecutive([$this->callback(function (\Thruway\Subscription $arg) use ($broker, &$subscription) {
                $this->assertInstanceOf('\Thruway\Subscription', $arg);
                $this->assertEquals("my.topic", $arg->getTopic());

                $subscription = $arg;

                return true;
            })])
            ->willReturnCallback(function () use ($broker, &$subscription) {
                $subscription->pauseForState();
                // this should be queued
                // notice reversed pubids for the fun of it
                for ($i = 4; $i > 0; $i--) {
                    $eventMsg = new \Thruway\Message\EventMessage($subscription->getId(), $i, (object)[]);
                    $subscription->sendEventMessage($eventMsg);
                }

                // this should restore state
                $eventMsg = new \Thruway\Message\EventMessage($subscription->getId(), 20, (object)[]);
                $eventMsg->setRestoringState(true);
                $subscription->sendEventMessage($eventMsg);
                $subscription->unPauseForState(3);

                return true;
            });

        $session = $this->getMockBuilder('\Thruway\Session')
            ->setMethods(['sendMessage'])
            ->setConstructorArgs([$this->createTransportInterfaceMock()])
            ->getMock();

        $session->expects($this->exactly(4))
            ->method("sendMessage")
            ->withConsecutive(
                [$this->isInstanceOf('\Thruway\Message\SubscribedMessage')],
                [$this->callback(function ($msg) {
                    $this->assertPublicationWithId(20, $msg);
                    $this->assertTrue($msg->isRestoringState());
                    return true;
                })],
                [$this->callback(function ($msg) { $this->assertPublicationWithId(2, $msg); return true; })],
                [$this->callback(function ($msg) { $this->assertPublicationWithId(1, $msg); return true; })]
            );

        $broker->setTopicStateManager($topicStateManager);

        $topic = new \Thruway\Topic\Topic("my.topic");
        $topic->setStateHandler("state.handler.for.my.topic");

        $broker->getTopicManager()->addTopic($topic);

        $subscribeMsg = new \Thruway\Message\SubscribeMessage(1234, (object)[], 'my.topic');

        // this will create a new topic - so should not have any
        // stateful stuff
        $broker->onMessage($session, $subscribeMsg);
    }
}