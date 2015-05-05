<?php


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

    /**
     * xdepends testAddCall
     */
    public function testRemoveCall()
    {
        $this->assertTrue(true);
    }
} 