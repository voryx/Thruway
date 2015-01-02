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
                [$this->isInstanceOf('\Thruway\Message\EventMessage')],
                [$this->isInstanceOf('\Thruway\Message\PublishedMessage')]

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

    public function testPrefixMatcher()
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

        $subscribeMsg = new \Thruway\Message\SubscribeMessage('\Thruway\Session',
            (object)["match" => "prefix"],
            "test_subscription");

        /** @var \Thruway\Message\SubscribedMessage $subscribedMsg */
        $subscribedMsg = null;

        $session->expects($this->exactly(6))
            ->method("sendMessage")
            ->withConsecutive(
                [
                    $this->callback(function ($msg) use (&$subscribedMsg) {
                        $this->isInstanceOf('\Thruway\Message\SubscribedMessage');
                        $subscribedMsg = $msg;
                        return true;
                    })
                ],
                [$this->callback(function ($val) {
                    $this->assertInstanceOf('\Thruway\Message\EventMessage', $val);
                    $this->assertEquals("test_subscription", $val->getDetails()->topic);
                    return true;
                })],
                [$this->isInstanceOf('\Thruway\Message\PublishedMessage')],
                [$this->callback(function ($val) {
                    $this->assertInstanceOf('\Thruway\Message\EventMessage', $val);
                    $this->assertEquals("test_subscription.more.uri.parts", $val->getDetails()->topic);
                    return true;
                })],
                [$this->isInstanceOf('\Thruway\Message\PublishedMessage')],
                [$this->isInstanceOf('\Thruway\Message\PublishedMessage')]

            );

        $broker->onMessage($session, $subscribeMsg);

        $subscriptionId = $subscribedMsg->getSubscriptionId();

        $publishMsg = new \Thruway\Message\PublishMessage(
            \Thruway\Common\Utils::getUniqueId(),
            ['exclude_me' => false, 'acknowledge' => true],
            'test_subscription'
        );

        $broker->onMessage($session, $publishMsg);

        $publishMsg = new \Thruway\Message\PublishMessage(
            \Thruway\Common\Utils::getUniqueId(),
            ['exclude_me' => false, 'acknowledge' => true],
            'test_subscription.more.uri.parts'
        );

        $broker->onMessage($session, $publishMsg);

        $publishMsg = new \Thruway\Message\PublishMessage(
            \Thruway\Common\Utils::getUniqueId(),
            ['exclude_me' => false, 'acknowledge' => true],
            'some.non.matching.uri'
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

        $subscriptions = array_values($subscriptions);

        $broker->onMessage($session, new \Thruway\Message\UnsubscribeMessage(\Thruway\Common\Utils::getUniqueId(), $subscriptions[0]->getId()));

        $this->assertTrue(count($broker->getSubscriptions()) === 0);

    }

    private function createTransportInterfaceMock() {
        return $this->getMock('\Thruway\Transport\TransportInterface');
    }

    public function testProcessSubscriptionAddedCalled() {
        $registry = $this->getMockBuilder('\Thruway\Subscription\StateHandlerRegistry')
            ->setConstructorArgs(['state.test.realm'])
            ->getMock();

        $registry->expects($this->once())
            ->method('processSubscriptionAdded')
            ->with($this->isInstanceOf('\Thruway\Subscription\Subscription'));

        $broker = new \Thruway\Role\Broker();

        $broker->setStateHandlerRegistry($registry);

        $session = $this->getMockBuilder('\Thruway\Session')
            ->setMethods(['sendMessage'])
            ->setConstructorArgs([$this->createTransportInterfaceMock()])
            ->getMock();

        $session->expects($this->once())
            ->method('sendMessage')
            ->withConsecutive(
                [$this->isInstanceOf('\Thruway\Message\SubscribedMessage')]
            );

        $broker->onMessage($session, new \Thruway\Message\SubscribeMessage(1, new stdClass(), 'test.topic'));
    }
}