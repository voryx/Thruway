<?php

namespace Thruway;


use Thruway\Message\CallMessage;
use Thruway\Message\InvocationMessage;

/**
 * Class Call
 *
 * @package Thruway
 */
class Call
{

    /**
     * @var \Thruway\Session
     */
    private $callerSession;

    /**
     * @var \Thruway\Session
     */
    private $calleeSession;

    /**
     * @var \Thruway\Message\CallMessage
     */
    private $callMessage;

    /**
     * @var \Thruway\Message\InvocationMessage
     */
    private $invocationMessage;

    /**
     * @var boolean
     */
    private $isProgressive;

    /**
     * Constructor
     * 
     * @param \Thruway\Message\CallMessage $callMessage
     * @param \Thruway\Session $callerSession
     * @param \Thruway\Message\InvocationMessage $invocationMessage
     * @param \Thruway\Session $calleeSession
     */
    public function __construct(
        CallMessage $callMessage,
        Session $callerSession,
        InvocationMessage $invocationMessage,
        Session $calleeSession
    ) {
        $this->callMessage       = $callMessage;
        $this->callerSession     = $callerSession;
        $this->invocationMessage = $invocationMessage;
        $this->calleeSession     = $calleeSession;
        $this->isProgressive     = false;
    }

    /**
     * Get call message
     * 
     * @return \Thruway\Message\CallMessage
     */
    public function getCallMessage()
    {
        return $this->callMessage;
    }

    /**
     * Set call message
     * 
     * @param \Thruway\Message\CallMessage $callMessage
     */
    public function setCallMessage($callMessage)
    {
        $this->callMessage = $callMessage;
    }

    /**
     * Get callee session
     * 
     * @return \Thruway\Session
     */
    public function getCalleeSession()
    {
        return $this->calleeSession;
    }

    /**
     * Set callee session
     * 
     * @param \Thruway\Session $calleeSession
     */
    public function setCalleeSession($calleeSession)
    {
        $this->calleeSession = $calleeSession;
    }

    /**
     * Get caller session
     * 
     * @return \Thruway\Session
     */
    public function getCallerSession()
    {
        return $this->callerSession;
    }

    /**
     * Set caller session
     * 
     * @param \Thruway\Session $callerSession
     */
    public function setCallerSession($callerSession)
    {
        $this->callerSession = $callerSession;
    }

    /**
     * Get InvocationMessage
     * 
     * @return \Thruway\Message\InvocationMessage
     */
    public function getInvocationMessage()
    {
        return $this->invocationMessage;
    }

    /**
     * Set Invocation message
     * 
     * @param \Thruway\Message\InvocationMessage $invocationMessage
     */
    public function setInvocationMessage($invocationMessage)
    {
        $this->invocationMessage = $invocationMessage;
    }

    /**
     * update state is progressive
     * 
     * @param boolean $isProgressive
     */
    public function setIsProgressive($isProgressive)
    {
        $this->isProgressive = $isProgressive;
    }

    /**
     * Get state is progressive
     * 
     * @return boolean
     */
    public function getIsProgressive()
    {
        return $this->isProgressive;
    }

    /**
     * Check is progressive
     * 
     * @return boolean
     */
    public function isProgressive()
    {
        return $this->isProgressive;
    }

} 