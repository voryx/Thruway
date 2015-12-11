<?php
require_once __DIR__ . '/../bootstrap.php';


class RegistrationTest extends PHPUnit_Framework_TestCase
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
        $this->_registration  = new \Thruway\Registration($this->_calleeSession, 'test_procedure');
    }

    public function testMakingCallIncrementsCallCount()
    {
        $mockSession = new \Thruway\Session(new \Thruway\Transport\DummyTransport());

        $this->assertEquals(0, $this->_registration->getCurrentCallCount());

        $callMsg = new \Thruway\Message\CallMessage(
            \Thruway\Common\Utils::getUniqueId(),
            new \stdClass(),
            'test_procedure'
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

        $registerMsg = new \Thruway\Message\RegisterMessage(
                \Thruway\Common\Utils::getUniqueId(), [ "_limit" => 0], 'rate.limit.procedure'
        );
        $procedure->processRegister($calleeSession, $registerMsg);

        $this->assertEquals(1, count($procedure->getRegistrations()));

        for ($i = 5; $i-- > 0;) {
            //send an invocation
            $callMessage = new \Thruway\Message\CallMessage(
                    \Thruway\Common\Utils::getUniqueId(), [], 'rate.limit.procedure'
            );
            $call = new \Thruway\Call($callerSession, $callMessage, $procedure);
            $procedure->processCall($callerSession, $call);
        }
        $this->assertEquals(1, count($procedure->getRegistrations()[0]->getStatistics()['invokeQueueCount']));
    }

    /**
     * xdepends testAddCall
     */
    public function testRemoveCall()
    {
        $this->assertTrue(true);
    }
} 