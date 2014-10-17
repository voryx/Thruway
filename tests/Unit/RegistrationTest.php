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
        $mockSession = $this->getMockBuilder('\Thruway\Session')
            ->setConstructorArgs([new \Thruway\Transport\DummyTransport()])
            ->getMock();

        $this->assertEquals(0, $this->_registration->getCurrentCallCount());

        $callMsg = new \Thruway\Message\CallMessage(
            \Thruway\Session::getUniqueId(),
            new \stdClass(),
            'test_procedure'
        );

        $call = new \Thruway\Call($mockSession, $callMsg);

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