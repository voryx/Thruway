<?php

class SessionMetaTest extends PHPUnit_Framework_TestCase {
    protected $_conn;
    protected $_conn2;
    protected $_joinInfo;
    protected $_leaveInfo;
    protected $_joinFirst;
    protected $_joinCount = 0;
    protected $_leaveCount = 0;


    public function setUp()
    {
        $this->_testResult = null;
        $this->_joinInfo = [];
        $this->_leaveInfo = [];
        $this->_joinFirst = false;

        $challenge = function ($session, $method) {
            return "letMeIn";
        };

        $this->_conn = new \Thruway\Connection(
            array(
                "realm" => 'testSimpleAuthRealm',
                "url" => 'ws://127.0.0.1:8090',
                "max_retries" => 0,
                "authmethods" => ["simplysimple"],
                "onChallenge" => $challenge
            )
        );

        $loop = $this->_conn->getClient()->getLoop();

        $this->_conn2 = new \Thruway\Connection(
            array(
                "realm" => 'testSimpleAuthRealm',
                "url" => 'ws://127.0.0.1:8090',
                "max_retries" => 0,
                "authmethods" => ["simplysimple"],
                "onChallenge" => $challenge
            ),
            $loop
        );

        $this->_conn2->getClient()->setAuthId("conn2user");
    }

    public function doTheStuff($withSubscribe)
    {
        $this->_conn->on(
            'open',
            function (\Thruway\ClientSession $session) use ($withSubscribe) {
                $onJoin = function ($info) {
                    $this->_joinCount++;
                    $this->_joinInfo = $info[0];

                    //echo "JOIN :" . json_encode($info) . "\n";
                };
                $onLeave = function ($info) use ($session) {
                    $this->_leaveCount++;
                    $this->_leaveInfo = $info[0];
                    if (!empty($this->_joinInfo)) {
                        $this->_joinFirst = true;
                    }

                    //echo "LEAVE:" . json_encode($info) . "\n";
                    $session->close();
                };

                if ($withSubscribe) {
                    $session->subscribe('wamp.metaevent.session.on_join', $onJoin);
                    $session->subscribe('wamp.metaevent.session.on_leave', $onLeave);
                }

                $this->_conn2->on(
                    'open',
                    function (\Thruway\ClientSession $session2) use ($session, $withSubscribe) {
                        $session2->close();
                        if (!$withSubscribe) {
                            $session->close();
                        }
                    }
                );

                $this->_conn2->open(false);
            }
        );

        $this->_conn->open();
    }

    public function testMetaWithSubscription() {
        $this->doTheStuff(true);

        $this->assertTrue(is_object($this->_joinInfo));
        $this->assertTrue(is_object($this->_leaveInfo));
        $this->assertObjectHasAttribute("authid", $this->_joinInfo);
        $this->assertObjectHasAttribute("authid", $this->_leaveInfo);
        $this->assertObjectHasAttribute("authmethod", $this->_joinInfo);
        $this->assertObjectHasAttribute("authmethod", $this->_leaveInfo);
        $this->assertObjectHasAttribute("authrole", $this->_joinInfo);
        $this->assertObjectHasAttribute("authrole", $this->_leaveInfo);
        $this->assertObjectHasAttribute("session", $this->_joinInfo);
        $this->assertObjectHasAttribute("session", $this->_leaveInfo);
        $this->assertObjectHasAttribute("realm", $this->_joinInfo);
        $this->assertObjectHasAttribute("realm", $this->_leaveInfo);
        $this->assertObjectHasAttribute("authprovider", $this->_joinInfo);
        $this->assertObjectHasAttribute("authprovider", $this->_leaveInfo);
        $this->assertEquals("conn2user", $this->_joinInfo->authid);
        $this->assertEquals("conn2user", $this->_leaveInfo->authid);
        $this->assertEquals("testSimpleAuthRealm", $this->_joinInfo->realm);
        $this->assertEquals("testSimpleAuthRealm", $this->_leaveInfo->realm);
        $this->assertEquals("simplysimple", $this->_joinInfo->authmethod);
        $this->assertEquals("simplysimple", $this->_leaveInfo->authmethod);

        $this->assertEquals(1, $this->_joinCount);
        $this->assertEquals(1, $this->_leaveCount);
        $this->assertTrue($this->_joinFirst);
    }

    public function testMetaWithoutSubscription() {
        // TODO: this test doesn't really do anything
        // need to check at a lower level for the message
        $this->doTheStuff(false);

        $this->assertTrue(empty($this->_joinInfo));
        $this->assertTrue(empty($this->_leaveInfo));
        $this->assertEquals(0, $this->_joinCount);
        $this->assertEquals(0, $this->_leaveCount);
    }
} 