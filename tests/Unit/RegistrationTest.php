<?php

require_once __DIR__ . '/../bootstrap.php';

class RegistrationTest extends Thruway\TestCase
{

    /**
     * @var \Thruway\Session
     */
    private $_calleeSession;

    /**
     * @var \Thruway\Session
     */
    private $_callerSession;

    /**
     * @var \Thruway\Registration
     */
    private $_registration;

    public function setup()
    {
        $this->_calleeSession = new \Thruway\Session(new \Thruway\Transport\DummyTransport());
        $this->_callerSession = new \Thruway\Session(new \Thruway\Transport\DummyTransport());
        $this->_registration = new \Thruway\Registration($this->_calleeSession, 'test_procedure');
    }

    public function testMakingCallIncrementsCallCount()
    {
        $mockSession = new \Thruway\Session(new \Thruway\Transport\DummyTransport());

        $this->assertEquals(0, $this->_registration->getCurrentCallCount());

        $callMsg = new \Thruway\Message\CallMessage(
                \Thruway\Common\Utils::getUniqueId(), new \stdClass(), 'test_procedure'
        );


        $procedure = $this->getMockBuilder('\Thruway\Procedure')->disableOriginalConstructor()->getMock();
        $call = new \Thruway\Call($mockSession, $callMsg, $procedure);

        $this->_registration->processCall($call);

        $this->assertEquals(1, $this->_registration->getCurrentCallCount());
    }

    public function testRateLimitedRegistration()
    {
        $procedure = new \Thruway\Procedure('rate.limit.procedure');

        $callerSession = new \Thruway\Session(new Thruway\Transport\DummyTransport());
        $calleeSession = new \Thruway\Session(new Thruway\Transport\DummyTransport());
        
        $calleeSession->setLoop(\React\EventLoop\Factory::create());

        $throttledRegisterMsg = new \Thruway\Message\RegisterMessage(
                \Thruway\Common\Utils::getUniqueId(), [ "_limit" => 1], 'rate.limit.procedure'
        );

        $procedure->processRegister($calleeSession, $throttledRegisterMsg);

        $this->assertEquals(1, count($procedure->getRegistrations()));

        $this->assertTrue($procedure->getRegistrations()[0]->isRateLimited());

        $callMsg = new \Thruway\Message\CallMessage(
                \Thruway\Common\Utils::getUniqueId(), new \stdClass(), 'rate.limit.procedure'
        );
        $call = new \Thruway\Call($callerSession, $callMsg, $procedure);

        $procedure->getRegistrations()[0]->processCall($call);

        $this->assertEquals(1, $procedure->getRegistrations()[0]->getStatistics()['invokeQueueCount']);
    }

    /**
     * xdepends testAddCall
     */
    public function testRemoveCall()
    {
        $this->assertTrue(true);
    }

}
