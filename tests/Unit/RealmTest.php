<?php

require_once __DIR__ . '/../bootstrap.php';

class RealmTest extends PHPUnit_Framework_TestCase
{
    private $_sessions;

    /**
     *
     * @return \Thruway\Realm
     */
    public function testRealmCreate()
    {
        return new \Thruway\Realm("test_realm");
    }

    /**
     *
     * @depends testRealmCreate
     *
     * @param \Thruway\Realm $realm
     */
    public function testRealmName(\Thruway\Realm $realm)
    {
        $this->assertEquals("test_realm", $realm->getRealmName());
    }

    /**
     * @depends testRealmCreate
     *
     * @param \Thruway\Realm $realm
     * @expectedException \Thruway\Exception\InvalidRealmNameException
     */
    public function testJoinWithWrongRealmInHello(\Thruway\Realm $realm)
    {
        $session = new \Thruway\Session(new \Thruway\Transport\DummyTransport());

        $realm->onMessage($session, new \Thruway\Message\HelloMessage('incorrect_realm', []));
    }

    /**
     * @depends testRealmCreate
     *
     * @param \Thruway\Realm $realm
     * @return \Thruway\Session
     */
    public function testJoin(\Thruway\Realm $realm)
    {
        $session = new \Thruway\Session(new \Thruway\Transport\DummyTransport());

        $realm->onMessage($session, new \Thruway\Message\HelloMessage('test_realm', []));

        $this->assertInstanceOf('\Thruway\Message\WelcomeMessage', $session->getTransport()->getLastMessageSent());
        $this->assertSame($session->getRealm(), $realm);

        return $session;
    }

    /**
     * @depends testJoin
     *
     * @param \Thruway\Session $session
     */
    public function testRegister(\Thruway\Session $session)
    {
        $realm = $session->getRealm();

        $registerMessage = new \Thruway\Message\RegisterMessage(
            \Thruway\Session::getUniqueId(),
            [],
            'test_procedure'
        );

        $realm->onMessage($session, $registerMessage);

        $registrations = $realm->getDealer()->managerGetRegistrations()[0];

        $this->assertEquals(1, count($registrations));
        $this->assertEquals("test_procedure", $registrations[0]['name']);
        $this->assertInstanceOf('\Thruway\Message\RegisteredMessage',
            $session->getTransport()->getLastMessageSent());
    }



    /**
     * @depends testJoin
     *
     * @param \Thruway\Session $session
     */
    public function testGoodbyeMessage(\Thruway\Session $session)
    {
        $realm = $session->getRealm();

        $sessions = $realm->managerGetSessions();
        $this->assertEquals(1, count($sessions));


        $realm->onMessage($session, new \Thruway\Message\GoodbyeMessage([], 'some_test_reason'));

        $this->assertInstanceOf('\Thruway\Message\GoodbyeMessage', $session->getTransport()->getLastMessageSent());

        $sessions = $realm->managerGetSessions();
        $this->assertEquals(0, count($sessions));
    }


    public function testUnauthorizedActions() {
        $session = $this->getMockBuilder('\Thruway\Session')
            ->disableOriginalConstructor()
            ->setMethods(["sendMessage"])
            ->getMock();

        $authorizationManager = $this->getMockBuilder('\Thruway\Authentication\AuthorizationManagerInterface')
            ->getMock();

        $realm = new \Thruway\Realm("some_realm");
        $realm->setAuthorizationManager($authorizationManager);

        $subscribeMsg = new \Thruway\Message\SubscribeMessage(\Thruway\Session::getUniqueId(), [], "some_topic");
        $publishMsg = new \Thruway\Message\PublishMessage(\Thruway\Session::getUniqueId(), [], "some_topic");
        $registerMsg = new \Thruway\Message\RegisterMessage(\Thruway\Session::getUniqueId(), [], 'some_procedure');
        $callMsg = new \Thruway\Message\CallMessage(\Thruway\Session::getUniqueId(), [], "some_procedure");

        $authorizationManager->expects($this->exactly(4))
            ->method("isAuthorizedTo")
            ->withConsecutive(
                [$this->isInstanceOf('\Thruway\Session'), $this->isInstanceOf('\Thruway\Message\SubscribeMessage')],
                [$this->isInstanceOf('\Thruway\Session'), $this->isInstanceOf('\Thruway\Message\PublishMessage')],
                [$this->isInstanceOf('\Thruway\Session'), $this->isInstanceOf('\Thruway\Message\RegisterMessage')],
                [$this->isInstanceOf('\Thruway\Session'), $this->isInstanceOf('\Thruway\Message\CallMessage')]
            )
            ->willReturn(false);;

        $errorCheck = function ($msg) {
            $this->assertInstanceOf('\Thruway\Message\ErrorMessage', $msg);
            $this->assertEquals('wamp.error.not_authorized', $msg->getErrorUri());

            return true;
        };

        $session->expects($this->exactly(5))
            ->method("sendMessage")
            ->withConsecutive(
                [$this->isInstanceOf('\Thruway\Message\WelcomeMessage')],
                [$this->callback($errorCheck)],
                [$this->callback($errorCheck)],
                [$this->callback($errorCheck)],
                [$this->callback($errorCheck)]
            );

        $helloMsg = new \Thruway\Message\HelloMessage("some_realm", []);

        $realm->onMessage($session, $helloMsg);

        $realm->onMessage($session, $subscribeMsg);
        $realm->onMessage($session, $publishMsg);
        $realm->onMessage($session, $registerMsg);
        $realm->onMessage($session, $callMsg);
    }

    public function testImmediateAbort() {
        $realm = new \Thruway\Realm("realm1");

        $session = $this->getMockBuilder('\Thruway\Session')
            ->disableOriginalConstructor()
            ->setMethods(["sendMessage", "shutdown"])
            ->getMock();

        $session->expects($this->once())
            ->method("shutdown");

        $realm->onMessage($session, new \Thruway\Message\AbortMessage([], "some.abort.reason"));
    }

    public function testCallBeforeWelcome() {
        $realm = new \Thruway\Realm("realm1");

        $session = $this->getMockBuilder('\Thruway\Session')
            ->disableOriginalConstructor()
            ->setMethods(["sendMessage", "shutdown", "abort"])
            ->getMock();

        $session->expects($this->once())
            ->method("abort")
            ->with($this->isInstanceOf("stdClass"), $this->equalTo("wamp.error.not_authorized"));

        $realm->onMessage($session, new \Thruway\Message\CallMessage(\Thruway\Session::getUniqueId(), [], 'some_procedure'));
    }

    /**
     * This can only happen in an instance where Welcome is not sent immediately after Hello
     * (when a challenge has been sent)
     */
    public function testJoinSessionTwice() {
        $realm = new \Thruway\Realm("realm1");

        $authMgr = $this->getMockBuilder('\Thruway\Authentication\AuthenticationManagerInterface')
            ->getMock();

        $authMgr->expects($this->once())
            ->method("onAuthenticationMessage")
            ->with($this->isInstanceOf('\Thruway\Realm'),
                $this->isInstanceOf('\Thruway\Session'),
                $this->isInstanceOf('\Thruway\Message\HelloMessage')
            );

        $realm->setAuthenticationManager($authMgr);

        $session = $this->getMockBuilder('\Thruway\Session')
            ->disableOriginalConstructor()
            ->setMethods(["sendMessage", "shutdown", "abort"])
            ->getMock();

        $session->expects($this->once())
            ->method("shutdown");

        $realm->onMessage($session, new \Thruway\Message\HelloMessage('realm1', ["roles" => []]));
        $realm->onMessage($session, new \Thruway\Message\HelloMessage('realm1', ["roles" => []]));

        $authMgr->expects($this->once())
            ->method("onSessionClose")
            ->with($this->isInstanceOf('\Thruway\Session'));

        $realm->leave($session);
    }
}