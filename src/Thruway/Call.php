<?php

namespace Thruway;


use Thruway\Message\CallMessage;
use Thruway\Message\InvocationMessage;

/**
 * Class Call
 * @package Thruway
 */
class Call
{

    /**
     * @var Session
     */
    private $callerSession;

    /**
     * @var Session
     */
    private $calleeSession;

    /**
     * @var Message\CallMessage
     */
    private $callMessage;

    /**
     * @var Message\InvocationMessage
     */
    private $invocationMessage;

    /**
     * @param CallMessage $callMessage
     * @param Session $callerSession
     * @param InvocationMessage $invocationMessage
     * @param Session $calleeSession
     */
    function __construct(CallMessage $callMessage, Session $callerSession, InvocationMessage $invocationMessage, Session $calleeSession)
    {
        $this->callMessage = $callMessage;
        $this->callerSession = $callerSession;
        $this->invocationMessage = $invocationMessage;
        $this->calleeSession = $calleeSession;
    }

    /**
     * @return CallMessage
     */
    public function getCallMessage()
    {
        return $this->callMessage;
    }

    /**
     * @param CallMessage $callMessage
     */
    public function setCallMessage($callMessage)
    {
        $this->callMessage = $callMessage;
    }

    /**
     * @return Session
     */
    public function getCalleeSession()
    {
        return $this->calleeSession;
    }

    /**
     * @param Session $calleeSession
     */
    public function setCalleeSession($calleeSession)
    {
        $this->calleeSession = $calleeSession;
    }

    /**
     * @return Session
     */
    public function getCallerSession()
    {
        return $this->callerSession;
    }

    /**
     * @param Session $callerSession
     */
    public function setCallerSession($callerSession)
    {
        $this->callerSession = $callerSession;
    }

    /**
     * @return InvocationMessage
     */
    public function getInvocationMessage()
    {
        return $this->invocationMessage;
    }

    /**
     * @param InvocationMessage $invocationMessage
     */
    public function setInvocationMessage($invocationMessage)
    {
        $this->invocationMessage = $invocationMessage;
    }




} 