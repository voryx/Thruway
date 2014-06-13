<?php
/**
 * Created by PhpStorm.
 * User: daviddan
 * Date: 6/12/14
 * Time: 10:54 PM
 */

namespace AutobahnPHP;


use AutobahnPHP\Message\CallMessage;
use AutobahnPHP\Message\InvocationMessage;

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