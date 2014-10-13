<?php
require_once __DIR__ . '/../../bootstrap.php';

class BrokerTest extends PHPUnit_Framework_TestCase {
    public function testUnsubscribeFromNonExistentSubscription() {
        $transport = $this->getMockBuilder('\Thruway\Transport\TransportInterface')
            ->getMock();

        $transport->method("getTransportDetails")->will($this->returnValue(""));

        $session = $this->getMockBuilder(\Thruway\Session::class)
            ->setMethods(["sendMessage"])
            ->setConstructorArgs([$transport])
            ->getMock();

        $broker = new \Thruway\Role\Broker();

        $session->expects($this->once())
            ->method("sendMessage")
            ->with($this->isInstanceOf(\Thruway\Message\ErrorMessage::class));

        $unsubscribeMsg = new \Thruway\Message\UnsubscribeMessage(\Thruway\Session::getUniqueId(), 0);

        $broker->onMessage($session, $unsubscribeMsg);
    }

    public function testDoNotExcludeMe() {
        $transport = $this->getMockBuilder('\Thruway\Transport\TransportInterface')
            ->getMock();

        $transport->expects($this->any())->method("getTransportDetails")->will($this->returnValue(""));

        $session = $this->getMockBuilder(\Thruway\Session::class)
            ->setMethods(["sendMessage"])
            ->setConstructorArgs([$transport])
            ->getMock();

        $broker = new \Thruway\Role\Broker();

        $subscribeMsg = new \Thruway\Message\SubscribeMessage(\Thruway\Session::class, [], "test_subscription");

        /** @var \Thruway\Message\SubscribedMessage $subscribedMsg */
        $subscribedMsg = null;

        $session->expects($this->exactly(3))
            ->method("sendMessage")
            ->withConsecutive(
                [$this->callback(function ($msg) use (&$subscribedMsg) {
                    $this->isInstanceOf(\Thruway\Message\SubscribedMessage::class);
                    $subscribedMsg = $msg;
                    return true;
                })],
                [$this->isInstanceOf(\Thruway\Message\PublishedMessage::class)],
                [$this->isInstanceOf(\Thruway\Message\EventMessage::class)]
            );

        $broker->onMessage($session, $subscribeMsg);

        $subscriptionId = $subscribedMsg->getSubscriptionId();

        $publishMsg = new \Thruway\Message\PublishMessage(
            \Thruway\Session::getUniqueId(),
            ['exclude_me' => false, 'acknowledge' => true],
            'test_subscription'
        );

        $broker->onMessage($session, $publishMsg);
    }

    /**
     * @throws Exception
     * @expectedException Exception
     */
    public function testBadMessageToOnMessage() {
        $transport = $this->getMockBuilder('\Thruway\Transport\TransportInterface')
            ->getMock();

        $transport->expects($this->any())->method("getTransportDetails")->will($this->returnValue(""));

        $session = $this->getMockBuilder(\Thruway\Session::class)
            ->setMethods(["sendMessage"])
            ->setConstructorArgs([$transport])
            ->getMock();

        $broker = new \Thruway\Role\Broker();

        $goodbyeMsg = new \Thruway\Message\GoodbyeMessage([], 'test_reason');

        $broker->onMessage($session, $goodbyeMsg);
    }
} 