<?php


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

        $this->assertInstanceOf(\Thruway\Message\WelcomeMessage::class, $session->getTransport()->getLastMessageSent());
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
        $this->assertInstanceOf(\Thruway\Message\RegisteredMessage::class,
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

        $this->assertInstanceOf(\Thruway\Message\GoodbyeMessage::class, $session->getTransport()->getLastMessageSent());

        $sessions = $realm->managerGetSessions();
        $this->assertEquals(0, count($sessions));
    }

    /*
     * @depends testJoin
     *
     * @param \Thruway\Session $session
     */
    public function xtestSomethingElse(\Thruway\Session $session) {
        $realm = $session->getRealm();

        $this->assertEquals(1, count($realm->getSessions()));
    }
}