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

    public function testPrefixMatcherValidUris() {

        $prefixMatcher = new Thruway\Subscription\PrefixMatcher();
        $options = (object)[];

        $this->assertTrue($prefixMatcher->uriIsValid('', $options));
        $this->assertTrue($prefixMatcher->uriIsValid('.', $options));
        $this->assertTrue($prefixMatcher->uriIsValid('one', $options));
        $this->assertTrue($prefixMatcher->uriIsValid('one.', $options));
        $this->assertTrue($prefixMatcher->uriIsValid('one.two', $options));
        $this->assertTrue($prefixMatcher->uriIsValid('one.two.', $options));

        $this->assertFalse($prefixMatcher->uriIsValid('..', $options));
        $this->assertFalse($prefixMatcher->uriIsValid('one..', $options));
        $this->assertFalse($prefixMatcher->uriIsValid('!', $options));
        $this->assertFalse($prefixMatcher->uriIsValid('one..two', $options));
        $this->assertFalse($prefixMatcher->uriIsValid('one..two.', $options));

        $this->assertTrue($prefixMatcher->matches('a', '.', $options));
        $this->assertTrue($prefixMatcher->matches('a', '', $options));
        $this->assertTrue($prefixMatcher->matches('a.b', '.', $options));
        $this->assertTrue($prefixMatcher->matches('a.b', '', $options));
        $this->assertTrue($prefixMatcher->matches('a', 'a', $options));
        $this->assertTrue($prefixMatcher->matches('ab', 'a', $options));
        $this->assertTrue($prefixMatcher->matches('a.b', 'a', $options));
        $this->assertFalse($prefixMatcher->matches('a', 'a.', $options));
        $this->assertTrue($prefixMatcher->matches('a.b', 'a.', $options));
        $this->assertTrue($prefixMatcher->matches('a.b.c', 'a.', $options));
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

    public function testEligibleAuthroles() {
        $transport = $this->getMockBuilder('\Thruway\Transport\TransportInterface')
            ->getMock();

        $transport->method("getTransportDetails")->will($this->returnValue(""));

        $session = $this->getMockBuilder('\Thruway\Session')
            ->setMethods(["sendMessage","getAuthenticationDetails"])
            ->setConstructorArgs([$transport])
            ->getMock();

        $authDetails = new \Thruway\Authentication\AuthenticationDetails();
        $authDetails->addAuthRole("test_role1");
        $authDetails->addAuthRole("test_role2");
        $authDetails->setAuthId("test_authid");

        $session->expects($this->any())->method("getAuthenticationDetails")->willReturn($authDetails);

        $broker = new \Thruway\Role\Broker();

        $session->expects($this->exactly(4))
            ->method("sendMessage")
            ->withConsecutive(
                [$this->isInstanceOf('\Thruway\Message\SubscribedMessage')], // response to subscribe
                [$this->callback(function ($msg) {
                    $this->assertInstanceOf('\Thruway\Message\EventMessage', $msg);
                    $this->assertEquals("first publish", $msg->getArguments()[0]);
                    return true;
                })], // response to publish with no options
                // no message when we are not included in authroles
                [$this->callback(function ($msg) {
                    $this->assertInstanceOf('\Thruway\Message\EventMessage', $msg);
                    $this->assertEquals("third publish", $msg->getArguments()[0]);
                    return true;
                })], // when the first authrole is included
                [$this->callback(function ($msg) {
                    $this->assertInstanceOf('\Thruway\Message\EventMessage', $msg);
                    $this->assertEquals("fourth publish", $msg->getArguments()[0]);
                    return true;
                })] // when the second authrole is included
                // nothing on empty array
                // nothing on invalid non-array option
            );

        $subscribeMessage = new \Thruway\Message\SubscribeMessage(1, (object)[], "a.b.c");

        $broker->onMessage($session, $subscribeMessage);

        $pubSession = $this->getMockBuilder('\Thruway\Session')
            ->setMethods(["sendMessage"])
            ->setConstructorArgs([$transport])
            ->getMock();

        $pubSession->expects($this->never())
            ->method("sendMessage");

        // test regular publish
        $pubMessage = new \Thruway\Message\PublishMessage(2, (object)[], "a.b.c", ["first publish"]);
        $broker->onMessage($pubSession, $pubMessage);

        // test publish to authrole that is not us
        $pubMessage = new \Thruway\Message\PublishMessage(2, (object)[
            "_thruway_eligible_authroles" => ["alpha", "bravo", "charlie"]
        ], "a.b.c", ["second publish"]);
        $broker->onMessage($pubSession, $pubMessage);

        // test publish to our first authrole
        $pubMessage = new \Thruway\Message\PublishMessage(2, (object)[
            "_thruway_eligible_authroles" => ["alpha", "bravo", "test_role1", "charlie"]
        ], "a.b.c", ["third publish"]);
        $broker->onMessage($pubSession, $pubMessage);

        // test publish to our second authrole
        $pubMessage = new \Thruway\Message\PublishMessage(2, (object)[
            "_thruway_eligible_authroles" => ["test_role2"]
        ], "a.b.c", ["fourth publish"]);
        $broker->onMessage($pubSession, $pubMessage);

        // test publish to empty authrole
        $pubMessage = new \Thruway\Message\PublishMessage(2, (object)[
            "_thruway_eligible_authroles" => []
        ], "a.b.c", ["fifth publish"]);
        $broker->onMessage($pubSession, $pubMessage);

        // test publish to invalid authrole
        $pubMessage = new \Thruway\Message\PublishMessage(2, (object)[
            "_thruway_eligible_authroles" => "test_authrole2"
        ], "a.b.c", ["sixth publish"]);
        $broker->onMessage($pubSession, $pubMessage);
    }

    public function testEligibleAuthids() {
        $transport = $this->getMockBuilder('\Thruway\Transport\TransportInterface')
            ->getMock();

        $transport->method("getTransportDetails")->will($this->returnValue(""));

        $session = $this->getMockBuilder('\Thruway\Session')
            ->setMethods(["sendMessage","getAuthenticationDetails"])
            ->setConstructorArgs([$transport])
            ->getMock();

        $authDetails = new \Thruway\Authentication\AuthenticationDetails();
        $authDetails->addAuthRole("test_role1");
        $authDetails->addAuthRole("test_role2");
        $authDetails->setAuthId("test_authid");

        $session->expects($this->any())->method("getAuthenticationDetails")->willReturn($authDetails);

        $broker = new \Thruway\Role\Broker();

        $session->expects($this->exactly(4))
            ->method("sendMessage")
            ->withConsecutive(
                [$this->isInstanceOf('\Thruway\Message\SubscribedMessage')], // response to subscribe
                [$this->callback(function ($msg) {
                    $this->assertInstanceOf('\Thruway\Message\EventMessage', $msg);
                    $this->assertEquals("first publish", $msg->getArguments()[0]);
                    return true;
                })], // response to publish with no options
                // no message when we are not included in authroles
                [$this->callback(function ($msg) {
                    $this->assertInstanceOf('\Thruway\Message\EventMessage', $msg);
                    $this->assertEquals("third publish", $msg->getArguments()[0]);
                    return true;
                })], // when the first authrole is included
                [$this->callback(function ($msg) {
                    $this->assertInstanceOf('\Thruway\Message\EventMessage', $msg);
                    $this->assertEquals("fourth publish", $msg->getArguments()[0]);
                    return true;
                })] // when the second authrole is included
            // nothing on empty array
            // nothing on invalid non-array option
            );

        $subscribeMessage = new \Thruway\Message\SubscribeMessage(1, (object)[], "a.b.c");

        $broker->onMessage($session, $subscribeMessage);

        $pubSession = $this->getMockBuilder('\Thruway\Session')
            ->setMethods(["sendMessage"])
            ->setConstructorArgs([$transport])
            ->getMock();

        $pubSession->expects($this->never())
            ->method("sendMessage");

        // test regular publish
        $pubMessage = new \Thruway\Message\PublishMessage(2, (object)[], "a.b.c", ["first publish"]);
        $broker->onMessage($pubSession, $pubMessage);

        // test publish to authrole that is not us
        $pubMessage = new \Thruway\Message\PublishMessage(2, (object)[
            "_thruway_eligible_authids" => ["alpha", "bravo", "charlie"]
        ], "a.b.c", ["second publish"]);
        $broker->onMessage($pubSession, $pubMessage);

        // test publish to our first authrole
        $pubMessage = new \Thruway\Message\PublishMessage(2, (object)[
            "_thruway_eligible_authids" => ["alpha", "bravo", "test_authid", "charlie"]
        ], "a.b.c", ["third publish"]);
        $broker->onMessage($pubSession, $pubMessage);

        // test publish to our second authrole
        $pubMessage = new \Thruway\Message\PublishMessage(2, (object)[
            "_thruway_eligible_authids" => ["test_authid"]
        ], "a.b.c", ["fourth publish"]);
        $broker->onMessage($pubSession, $pubMessage);

        // test publish to empty authrole
        $pubMessage = new \Thruway\Message\PublishMessage(2, (object)[
            "_thruway_eligible_authids" => []
        ], "a.b.c", ["fifth publish"]);
        $broker->onMessage($pubSession, $pubMessage);

        // test publish to invalid authrole
        $pubMessage = new \Thruway\Message\PublishMessage(2, (object)[
            "_thruway_eligible_authids" => "test_authid"
        ], "a.b.c", ["sixth publish"]);
        $broker->onMessage($pubSession, $pubMessage);
    }
}