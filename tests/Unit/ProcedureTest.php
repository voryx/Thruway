<?php

require_once __DIR__ . '/../bootstrap.php';

class ProcedureTest extends PHPUnit_Framework_TestCase
{

    /**
     * @var \Thruway\Procedure
     */
    private $_proc;

    /**
     * @var
     */
    private $_session;

    public function setUp()
    {
        $this->_proc = new \Thruway\Procedure("test_procedure");
        //$this->_session = new \Thruway\Session(new \Thruway\Transport\DummyTransport());
    }

    public function testProcessRegisterWithNameMismatch()
    {
        $this->_session = $this->getMockBuilder('\Thruway\Session')
                ->disableOriginalConstructor()
                ->getMock();

        $registerMsg = new \Thruway\Message\RegisterMessage(
                \Thruway\Common\Utils::getUniqueId(), [], 'different_name'
        );

        $this->_session->expects($this->once())
                ->method("sendMessage")
                ->with($this->callback(function ($msg) use ($registerMsg) {
                            $this->assertInstanceOf('\Thruway\Message\ErrorMessage', $msg);
                            $this->assertEquals($registerMsg->getRequestId(), $msg->getErrorRequestId());
                            $this->assertEquals($registerMsg->getMsgCode(), $msg->getErrorMsgCode());
                            return true;
                        }));

        $this->_proc->processRegister($this->_session, $registerMsg);

        $this->assertEquals(0, count($this->_proc->getRegistrations()));
    }

    public function testProcessRegister()
    {
        $this->_session = $this->getMockBuilder('\Thruway\Session')
                ->disableOriginalConstructor()
                ->getMock();

        $registerMsg = new \Thruway\Message\RegisterMessage(
                \Thruway\Common\Utils::getUniqueId(), [], 'test_procedure'
        );

        $this->_session->expects($this->once())
                ->method("sendMessage")
                ->with($this->isInstanceOf('\Thruway\Message\RegisteredMessage'));

        $this->_proc->processRegister($this->_session, $registerMsg);

        $this->assertEquals(1, count($this->_proc->getRegistrations()));
    }

    public function testDuplicateRegistration()
    {
        $this->_session = $this->getMockBuilder('\Thruway\Session')
                ->disableOriginalConstructor()
                ->getMock();

        $session2 = $this->getMockBuilder('\Thruway\Session')
                ->disableOriginalConstructor()
                ->getMock();

        $this->_session->expects($this->once())
                ->method("sendMessage")
                ->with($this->isInstanceOf('\Thruway\Message\RegisteredMessage'));

        $registerMsg = new \Thruway\Message\RegisterMessage(
                \Thruway\Common\Utils::getUniqueId(), [], 'test_procedure'
        );

        $this->_proc->processRegister($this->_session, $registerMsg);

        $session2->expects($this->once())
                ->method("sendMessage")
                ->with($this->isInstanceOf('\Thruway\Message\ErrorMessage'));

        $this->_proc->processRegister($session2, $registerMsg);

        $this->assertEquals(1, count($this->_proc->getRegistrations()));
    }

    public function testDuplicateRegistrationWithReplaceOrphanNoPingSupport()
    {
        $this->_session = $this->getMockBuilder('\Thruway\Session')
                ->disableOriginalConstructor()
                ->getMock();

        $session2 = $this->getMockBuilder('\Thruway\Session')
                ->disableOriginalConstructor()
                ->getMock();

        $this->_session->expects($this->once())
                ->method("sendMessage")
                ->with($this->isInstanceOf('\Thruway\Message\RegisteredMessage'));

        $registerMsg = new \Thruway\Message\RegisterMessage(
                \Thruway\Common\Utils::getUniqueId(), ['replace_orphaned_session' => true], 'test_procedure'
        );

        $this->_proc->processRegister($this->_session, $registerMsg);

        $session2->expects($this->once())
                ->method("sendMessage")
                ->with($this->isInstanceOf('\Thruway\Message\ErrorMessage'));

        $this->_session->expects($this->any())
                ->method("ping")
                ->will($this->throwException(new \Thruway\Exception\PingNotSupportedException()));

        $this->_proc->processRegister($session2, $registerMsg);

        $this->assertEquals(1, count($this->_proc->getRegistrations()));
    }

    public function testDuplicateRegistrationWithReplaceOrphanWithPingSupportNoTimeout()
    {
        $this->_session = $this->getMockBuilder('\Thruway\Session')
                ->disableOriginalConstructor()
                ->getMock();

        $session2 = $this->getMockBuilder('\Thruway\Session')
                ->disableOriginalConstructor()
                ->getMock();

        $this->_session->expects($this->once())
                ->method("sendMessage")
                ->with($this->isInstanceOf('\Thruway\Message\RegisteredMessage'));

        $registerMsg = new \Thruway\Message\RegisterMessage(
                \Thruway\Common\Utils::getUniqueId(), ['replace_orphaned_session' => true], 'test_procedure'
        );

        $this->_proc->processRegister($this->_session, $registerMsg);

        $session2->expects($this->once())
                ->method("sendMessage")
                ->with($this->isInstanceOf('\Thruway\Message\ErrorMessage'));

        $this->_session->expects($this->any())
                ->method("ping")
                ->will($this->returnValue(new \React\Promise\FulfilledPromise()));

        $this->_proc->processRegister($session2, $registerMsg);

        $this->assertEquals(1, count($this->_proc->getRegistrations()));
    }

    public function testDuplicateRegistrationWithReplaceOrphanWithPingSupportTimeout()
    {
        $this->_session = $this->getMockBuilder('\Thruway\Session')
                ->disableOriginalConstructor()
                ->getMock();

        $session2 = $this->getMockBuilder('\Thruway\Session')
                ->disableOriginalConstructor()
                ->getMock();

        $this->_session->expects($this->once())
                ->method("sendMessage")
                ->with($this->isInstanceOf('\Thruway\Message\RegisteredMessage'));

        $this->_session->expects($this->once())
                ->method('shutdown');


        $registerMsg = new \Thruway\Message\RegisterMessage(
                \Thruway\Common\Utils::getUniqueId(), ['replace_orphaned_session' => true], 'test_procedure'
        );

        $this->_proc->processRegister($this->_session, $registerMsg);

        $session2->expects($this->once())
                ->method("sendMessage")
                ->with($this->isInstanceOf('\Thruway\Message\RegisteredMessage'));

        $this->_session->expects($this->once())
                ->method("ping")
                ->will($this->returnValue(new \React\Promise\RejectedPromise()));

        $this->_proc->processRegister($session2, $registerMsg);

        $this->assertEquals(2, count($this->_proc->getRegistrations()));
    }

    public function testMultipleRegistrations()
    {
        $realm = $this->getMockBuilder('\Thruway\Realm')
                ->setConstructorArgs(["realm1"])
                ->setMethods(["publishMeta"])
                ->getMock();

        $realm->expects($this->exactly(4))
                ->method('publishMeta')
                ->with(
                        $this->equalTo('thruway.metaevent.procedure.congestion'), $this->equalTo([["name" => $this->_proc->getProcedureName()]])
        );

        // create 5 sessions
        $s = [];
        $currentCallCounts = [];
        $invocationToYield = null;
        for ($i = 0; $i < 5; $i++) {
            $s[$i] = $this->getMockBuilder('\Thruway\Session')
                    ->disableOriginalConstructor()
                    ->setMethods(['sendMessage', 'getRealm'])
                    ->getMock();

            if ($i < 2) {
                // TODO: without queuing
                //$s[$i]->expects($this->exactly(3))
                $s[$i]->expects($this->exactly(2))
                        ->method("sendMessage")
                        ->withConsecutive(
                                [$this->isInstanceOf('\Thruway\Message\RegisteredMessage')], [$this->isInstanceOf('\Thruway\Message\InvocationMessage')]
                                //,[$this->isInstanceOf('\Thruway\Message\InvocationMessage')]
                );
            } else {
                if ($i == 2) {
                    // TODO: without queuing
                    //$s[$i]->expects($this->exactly(4))
                    $s[$i]->expects($this->exactly(3))
                            ->method("sendMessage")
                            ->withConsecutive(
                                    [$this->isInstanceOf('\Thruway\Message\RegisteredMessage')], [$this->isInstanceOf('\Thruway\Message\InvocationMessage')], [$this->isInstanceOf('\Thruway\Message\InvocationMessage')], [$this->isInstanceOf('\Thruway\Message\InvocationMessage')]
                    );
                } else {
                    $s[$i]->expects($this->exactly(2))
                            ->method("sendMessage")
                            ->withConsecutive(
                                    [$this->isInstanceOf('\Thruway\Message\RegisteredMessage')], [$this->isInstanceOf('\Thruway\Message\InvocationMessage')]
                    );
                }
            }


            $s[$i]->method('getRealm')->will($this->returnValue($realm));

            $currentCallCounts[$i] = 0;
        }

        $registerMsg = new \Thruway\Message\RegisterMessage(
                \Thruway\Common\Utils::getUniqueId(), ['thruway_multiregister' => true], 'test_procedure'
        );

        foreach ($s as $i => $session) {
            $this->_proc->processRegister($session, $registerMsg);
        }

        $callMsg = new \Thruway\Message\CallMessage(
                \Thruway\Common\Utils::getUniqueId(), [], 'test_procedure'
        );

        // call the proc enough to get a backlog
        // should be 2,2,2,1,1 for call depth now (only if not queuing)
        for ($i = 0; $i < 8; $i++) {
            $call = new \Thruway\Call($s[0], $callMsg, $this->_proc);
            $this->_proc->processCall($s[0], $call);
        }

        for ($i = 0; $i < 5; $i++) {
            // TODO: without queuing
            //$this->assertEquals($i < 3 ? 2 : 1, $s[$i]->getPendingCallCount());
            $this->assertEquals(1, $s[$i]->getPendingCallCount());
        }

        // now reset session[2] down to zero and see if that is where the next call goes
        $s[2]->decPendingCallCount();

        // remove the call from the procedure
        // TODO: without queuing
        //$s[2]->decPendingCallCount();

        $this->assertEquals(0, $s[2]->getPendingCallCount());

        $call = new \Thruway\Call($s[0], $callMsg, $this->_proc);
        $this->_proc->processCall($s[0], $call);
    }

    public function testMultiRegisterWithDisagreeOnDiscloseCaller()
    {
        $s1 = $this->getMockBuilder('\Thruway\Session')
                ->disableOriginalConstructor()
                ->getMock();

        $s2 = $this->getMockBuilder('\Thruway\Session')
                ->disableOriginalConstructor()
                ->getMock();

        $s1->expects($this->once())
                ->method("sendMessage")
                ->with($this->isInstanceOf('\Thruway\Message\RegisteredMessage'));

        $s2->expects($this->exactly(2))
                ->method("sendMessage")
                ->withConsecutive(
                        [$this->isInstanceOf('\Thruway\Message\ErrorMessage')], [$this->isInstanceOf('\Thruway\Message\RegisteredMessage')]
        );

        $registerMsg = new \Thruway\Message\RegisterMessage(
                \Thruway\Common\Utils::getUniqueId(), [
            'disclose_caller' => true,
            'thruway_multiregister' => true
                ], 'test_procedure'
        );

        $this->_proc->processRegister($s1, $registerMsg);

        $registerMsg->setOptions(['thruway_multiregister' => true]);
        $this->_proc->processRegister($s2, $registerMsg);

        $registerMsg->setOptions([
            'disclose_caller' => true,
            'thruway_multiregister' => true
        ]);
        $this->_proc->processRegister($s2, $registerMsg);
    }

    public function testMultiRegisterWithDisagreeOnMultiRegister()
    {
        $s1 = $this->getMockBuilder('\Thruway\Session')
                ->disableOriginalConstructor()
                ->getMock();

        $s2 = $this->getMockBuilder('\Thruway\Session')
                ->disableOriginalConstructor()
                ->getMock();

        $s1->expects($this->once())
                ->method("sendMessage")
                ->with($this->isInstanceOf('\Thruway\Message\RegisteredMessage'));

        $s2->expects($this->exactly(2))
                ->method("sendMessage")
                ->withConsecutive(
                        [$this->isInstanceOf('\Thruway\Message\ErrorMessage')], [$this->isInstanceOf('\Thruway\Message\RegisteredMessage')]
        );

        $registerMsg = new \Thruway\Message\RegisterMessage(
                \Thruway\Common\Utils::getUniqueId(), [
            'disclose_caller' => true,
            'thruway_multiregister' => true
                ], 'test_procedure'
        );

        $this->_proc->processRegister($s1, $registerMsg);

        $registerMsg->setOptions(['disclose_caller' => true]);
        $this->_proc->processRegister($s2, $registerMsg);

        $registerMsg->setOptions([
            'disclose_caller' => true,
            'thruway_multiregister' => true
        ]);
        $this->_proc->processRegister($s2, $registerMsg);
    }

    public function testCallWithoutRegistration()
    {
        $session = $this->getMockBuilder('\Thruway\Session')
                ->disableOriginalConstructor()
                ->getMock();

        $session->expects($this->once())
                ->method("sendMessage")
                ->with($this->callback(function ($msg) {
                            $this->assertInstanceOf('\Thruway\Message\ErrorMessage', $msg);
                            $this->assertEquals('wamp.error.no_such_procedure', $msg->getErrorUri());
                            return true;
                        }));

        $callMsg = new \Thruway\Message\CallMessage(
                \Thruway\Common\Utils::getUniqueId(), [], 'test_procedure'
        );

        $call = new \Thruway\Call($session, $callMsg, $this->_proc);
        $this->_proc->processCall($session, $call);
    }

    public function testGetCallWithRequestIDAndGetRegistrationById()
    {
        $session = $this->getMockBuilder('\Thruway\Session')
                ->disableOriginalConstructor()
                ->getMock();

        $rogueSession = $this->getMockBuilder('\Thruway\Session')
                ->disableOriginalConstructor()
                ->getMock();

        $rogueSession->expects($this->exactly(1))
                ->method("sendMessage")
                ->withConsecutive(
                        //[$this->isInstanceOf('\Thruway\Message\RegisteredMessage'],
                        [$this->isInstanceOf('\Thruway\Message\ErrorMessage')]
        );

//        $rregMsg = new \Thruway\Message\RegisterMessage(
//            \Thruway\Common\Utils::getUniqueId(),
//            ['thruway_multiregister' => true],
//            'test_procedure'
//        );
//
//        $this->_proc->processRegister($rogueSession, $rregMsg);

        /** @var \Thruway\Message\RegisteredMessage $registeredMsg */
        $registeredMsg = null;
        /** @var \Thruway\Message\InvocationMessage $invocationMsg */
        $invocationMsg = null;

        $session->expects($this->exactly(4))
                ->method("sendMessage")
                ->withConsecutive(
                        [
                    $this->callback(function ($msg) use (&$registeredMsg) { // registered call
                        $this->assertInstanceOf('\Thruway\Message\RegisteredMessage', $msg);
                        $registeredMsg = $msg;

                        return true;
                    })
                        ], [
                    $this->callback(function ($msg) use (&$invocationMsg) {
                        $this->assertInstanceOf('\Thruway\Message\InvocationMessage', $msg);
                        $invocationMsg = $msg;

                        return true;
                    })
                        ], [
                    $this->callback(function ($msg) {
                        $this->assertInstanceOf('\Thruway\Message\ErrorMessage', $msg);
                        $this->assertEquals('wamp.error.no_such_registration', $msg->getErrorUri());
                        return true;
                    })
                        ], [$this->isInstanceOf('\Thruway\Message\UnregisteredMessage')]
        );

        $registerMsg = new \Thruway\Message\RegisterMessage(
                \Thruway\Common\Utils::getUniqueId(), [], 'test_procedure'
        );

        $this->_proc->processRegister($session, $registerMsg);

        $this->assertInstanceOf('\Thruway\Message\RegisteredMessage', $registeredMsg);

        $callMsg = new \Thruway\Message\CallMessage(
                \Thruway\Common\Utils::getUniqueId(), [], "test_procedure"
        );

        $call = new \Thruway\Call($session, $callMsg, $this->_proc);
        $this->_proc->processCall($session, $call);

        $this->assertInstanceOf('\Thruway\Message\InvocationMessage', $invocationMsg);

        $call = $this->_proc->getCallByRequestId($invocationMsg->getRequestId());

        $this->assertInstanceOf('\Thruway\Call', $call);
        $this->assertSame($session, $call->getCalleeSession());

        $registration = $this->_proc->getRegistrationById($registeredMsg->getRegistrationId());

        $this->assertInstanceOf('\Thruway\Registration', $registration);
        $this->assertEquals($registeredMsg->getRegistrationId(), $registration->getId());

        $unregisterMsg = new \Thruway\Message\UnregisterMessage(
                \Thruway\Common\Utils::getUniqueId(), $registration->getId());

        $this->assertEquals(1, count($this->_proc->getRegistrations()));

        // this does not get called on a mock
        $this->_proc->processUnregister($rogueSession, $unregisterMsg);

        $this->assertEquals(1, count($this->_proc->getRegistrations()));

        // try unregistering a non-existent registration
        $badUnregisterMsg = new \Thruway\Message\UnregisterMessage(\Thruway\Common\Utils::getUniqueId(), 0);
        $this->_proc->processUnregister($session, $badUnregisterMsg);

        $this->assertEquals(1, count($this->_proc->getRegistrations()));

        $this->_proc->processUnregister($session, $unregisterMsg);

        $this->assertEquals(0, count($this->_proc->getRegistrations()));
    }

    public function testGetCallWithRequestIdFailure()
    {
        $call = $this->_proc->getCallByRequestId(0);

        $this->assertFalse($call);
    }

    public function testIsDiscloseCaller()
    {
        $disclose = $this->_proc->isDiscloseCaller();

        $this->assertFalse($disclose);
    }

    public function testIsAllowMultipleRegistrations()
    {
        $allow = $this->_proc->isAllowMultipleRegistrations();

        $this->assertFalse($allow);
    }

    public function testLeave()
    {
        $session = $this->getMockBuilder('\Thruway\Session')
                ->disableOriginalConstructor()
                ->getMock();

        $session->expects($this->once())
                ->method("sendMessage")
                ->with($this->isInstanceOf('\Thruway\Message\RegisteredMessage'));

        $registerMsg = new \Thruway\Message\RegisterMessage(
                \Thruway\Common\Utils::getUniqueId(), [], 'test_procedure'
        );

        $this->_proc->processRegister($session, $registerMsg);

        $this->assertEquals(1, count($this->_proc->getRegistrations()));

        $this->_proc->leave($session);

        $this->assertEquals(0, count($this->_proc->getRegistrations()));
    }

    public function testGetRegistrationByIdFailure()
    {
        $reg = $this->_proc->getRegistrationById(0);

        $this->assertFalse($reg);
    }

    public function testInvokeFirstRegistration()
    {
        $transportCaller = new \Thruway\Transport\DummyTransport();
        $sessionCaller = new \Thruway\Session($transportCaller);

        $transportA = new \Thruway\Transport\DummyTransport();
        $sessionA = new \Thruway\Session($transportA);

        $transportB = new \Thruway\Transport\DummyTransport();
        $sessionB = new \Thruway\Session($transportB);

        $proc = new \Thruway\Procedure('first.procedure');

        $registerMsg = new \Thruway\Message\RegisterMessage(
                \Thruway\Common\Utils::getUniqueId(), [ "invoke" => "first"], 'first.procedure'
        );

        $proc->processRegister($sessionA, $registerMsg);
        $proc->processRegister($sessionB, $registerMsg);

        $this->assertEquals(2, count($proc->getRegistrations()));

        // call a few times - make sure that we always get proc A
        for ($i = 0; $i < 10; $i++) {
            $callMessage = new \Thruway\Message\CallMessage(
                    \Thruway\Common\Utils::getUniqueId(), [], 'first.procedure'
            );

            $call = new \Thruway\Call($sessionCaller, $callMessage, $proc);

            $proc->processCall($sessionCaller, $call);

            // make sure the invocation was called on the first registration
            $this->assertEquals($call->getInvocationRequestId(), $transportA->getLastMessageSent()->getRequestId());
        }

        $this->assertEquals(10, $sessionA->getPendingCallCount());
    }

    public function testInvokeLastRegistration()
    {
        $transportCaller = new \Thruway\Transport\DummyTransport();
        $sessionCaller = new \Thruway\Session($transportCaller);

        $transportA = new \Thruway\Transport\DummyTransport();
        $sessionA = new \Thruway\Session($transportA);

        $transportB = new \Thruway\Transport\DummyTransport();
        $sessionB = new \Thruway\Session($transportB);

        $proc = new \Thruway\Procedure('some.procedure');

        $registerMsg = new \Thruway\Message\RegisterMessage(
                \Thruway\Common\Utils::getUniqueId(), [ "invoke" => "last"], 'some.procedure'
        );

        $proc->processRegister($sessionA, $registerMsg);

        $proc->processRegister($sessionB, $registerMsg);

        $this->assertEquals(2, count($proc->getRegistrations()));

        // call a few times - make sure that we always get proc B
        for ($i = 0; $i < 10; $i++) {
            $callMessage = new \Thruway\Message\CallMessage(
                    \Thruway\Common\Utils::getUniqueId(), [], 'some.procedure'
            );

            $call = new \Thruway\Call($sessionCaller, $callMessage, $proc);

            $proc->processCall($sessionCaller, $call);

            // make sure the invocation was called on the last registration
            $this->assertEquals($call->getInvocationRequestId(), $transportB->getLastMessageSent()->getRequestId());
        }

        $this->assertEquals(10, $sessionB->getPendingCallCount());
    }

    public function testInvokeRoundRobinRegistration()
    {
        $transportCaller = new \Thruway\Transport\DummyTransport();
        $sessionCaller = new \Thruway\Session($transportCaller);

        $sessionsToStart = 3;

        $sessions = array();

        for ($i = $sessionsToStart; $i-- > 0;) {
            $sessions[] = new \Thruway\Session(new \Thruway\Transport\DummyTransport());
        }

        $proc = new \Thruway\Procedure('round.robin.procedure');

        foreach ($sessions as $session) {
            $registerMsg = new \Thruway\Message\RegisterMessage(
                    \Thruway\Common\Utils::getUniqueId(), [ "invoke" => "roundrobin"], 'round.robin.procedure'
            );
            $proc->processRegister($session, $registerMsg);
        }
        //make sure that the registrations have been successful
        $this->assertEquals(count($sessions), count($proc->getRegistrations()));


        foreach ($sessions as $session) {
            $callMessage = new \Thruway\Message\CallMessage(
                    \Thruway\Common\Utils::getUniqueId(), [], 'round.robin.procedure'
            );

            $call = new \Thruway\Call($sessionCaller, $callMessage, $proc);

            //lets send the call
            $proc->processCall($sessionCaller, $call);

            // make sure the invocation was called as round robin
            $this->assertEquals($call->getInvocationRequestId(), $session->getTransport()->getLastMessageSent()->getRequestId());

            //make sure that pending call count is 1
            $this->assertEquals(1, $session->getPendingCallCount());
        }
    }

    public function testInvokeRandomRegistration()
    {
        $transportCaller = new \Thruway\Transport\DummyTransport();
        $sessionCaller   = new \Thruway\Session($transportCaller);
        $sessionsToStart = 3;
        $sessions        = array ();

        for ($i = 0; $i < $sessionsToStart; $i++) {
            $sessions[] = new \Thruway\Session(new \Thruway\Transport\DummyTransport());
        }

        $proc = new \Thruway\Procedure('random.procedure');

        foreach ($sessions as $session) {
            $registerMsg = new \Thruway\Message\RegisterMessage(
              \Thruway\Common\Utils::getUniqueId(), ["invoke" => "random"], 'random.procedure'
            );
            $proc->processRegister($session, $registerMsg);
        }
        //make sure that the registrations have been successful
        $this->assertEquals(count($sessions), count($proc->getRegistrations()));

        $calls = 0;

        for ($i = 0; $i < 10; $i++) {
            $callMessage = new \Thruway\Message\CallMessage(
              \Thruway\Common\Utils::getUniqueId(), [], 'random.procedure'
            );

            $call = new \Thruway\Call($sessionCaller, $callMessage, $proc);

            //lets send the call
            $proc->processCall($sessionCaller, $call);

        }

        foreach ($sessions as $session) {
            $calls += $session->getPendingCallCount();
        }

        $this->assertEquals(10, $calls);

    }

    public function testInvokeRandomOneRegistration()
    {
        $transportCaller = new \Thruway\Transport\DummyTransport();
        $sessionCaller   = new \Thruway\Session($transportCaller);
        $sessionsToStart = 1;
        $sessions        = array ();

        for ($i = 0; $i < $sessionsToStart; $i++) {
            $sessions[] = new \Thruway\Session(new \Thruway\Transport\DummyTransport());
        }

        $proc = new \Thruway\Procedure('random.procedure');

        foreach ($sessions as $session) {
            $registerMsg = new \Thruway\Message\RegisterMessage(
              \Thruway\Common\Utils::getUniqueId(), ["invoke" => "random"], 'random.procedure'
            );
            $proc->processRegister($session, $registerMsg);
        }
        //make sure that the registrations have been successful
        $this->assertEquals(count($sessions), count($proc->getRegistrations()));

        $calls = 0;

        for ($i = 0; $i < 10; $i++) {
            $callMessage = new \Thruway\Message\CallMessage(
              \Thruway\Common\Utils::getUniqueId(), [], 'random.procedure'
            );

            $call = new \Thruway\Call($sessionCaller, $callMessage, $proc);

            //lets send the call
            $proc->processCall($sessionCaller, $call);

        }

        foreach ($sessions as $session) {
            $calls += $session->getPendingCallCount();
        }

        $this->assertEquals(10, $calls);

    }

}
