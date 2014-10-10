<?php

namespace Thruway;


use Thruway\Message\CallMessage;
use Thruway\Message\InvocationMessage;
use Thruway\Message\ResultMessage;
use Thruway\Message\YieldMessage;

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
     * @var Registration
     */
    private $registration;

    /**
     * @var string
     */
    private $callStart;

    /**
     * @param \Thruway\Message\CallMessage $callMessage
     * @param \Thruway\Session $callerSession
     * @param \Thruway\Message\InvocationMessage $invocationMessage
     * @param \Thruway\Session $calleeSession
     * @param Registration $registration
     */
    function __construct(
        CallMessage $callMessage,
        Session $callerSession,
        InvocationMessage $invocationMessage,
        Session $calleeSession,
        Registration $registration
    ) {
        $this->callMessage       = $callMessage;
        $this->callerSession     = $callerSession;
        $this->invocationMessage = $invocationMessage;
        $this->calleeSession     = $calleeSession;
        $this->isProgressive     = false;
        $this->setRegistration($registration);

        $this->callStart = microtime();
    }

    /**
     * @return string
     */
    public function getCallStart()
    {
        return $this->callStart;
    }

    public function processYield(Session $session, YieldMessage $msg) {
        $details = new \stdClass();

        $yieldOptions = $msg->getOptions();
        if (is_array($yieldOptions) && isset($yieldOptions['progress']) && $yieldOptions['progress']) {
            if ($this->isProgressive()) {
                $details = ["progress" => true];
            } else {
                // not sure what to do here - just going to drop progress
                // if we are getting progress messages that the caller didn't ask for
            }
        } else {
            $this->getRegistration()->removeCall($this);
        }

        $resultMessage = new ResultMessage(
            $this->getCallMessage()->getRequestId(),
            $details,
            $msg->getArguments(),
            $msg->getArgumentsKw()
        );

        $this->getCallerSession()->sendMessage($resultMessage);
    }

    /**
     * @return \Thruway\Message\CallMessage
     */
    public function getCallMessage()
    {
        return $this->callMessage;
    }

    /**
     * @param \Thruway\Message\CallMessage $callMessage
     */
    public function setCallMessage($callMessage)
    {
        $this->callMessage = $callMessage;
    }

    /**
     * @return \Thruway\Session
     */
    public function getCalleeSession()
    {
        return $this->calleeSession;
    }

    /**
     * @param \Thruway\Session $calleeSession
     */
    public function setCalleeSession($calleeSession)
    {
        $this->calleeSession = $calleeSession;
    }

    /**
     * @return \Thruway\Session
     */
    public function getCallerSession()
    {
        return $this->callerSession;
    }

    /**
     * @param \Thruway\Session $callerSession
     */
    public function setCallerSession($callerSession)
    {
        $this->callerSession = $callerSession;
    }

    /**
     * @return \Thruway\Message\InvocationMessage
     */
    public function getInvocationMessage()
    {
        return $this->invocationMessage;
    }

    /**
     * @param \Thruway\Message\InvocationMessage $invocationMessage
     */
    public function setInvocationMessage($invocationMessage)
    {
        $this->invocationMessage = $invocationMessage;
    }

    /**
     * @param boolean $isProgressive
     */
    public function setIsProgressive($isProgressive)
    {
        $this->isProgressive = $isProgressive;
    }

    /**
     * @return boolean
     */
    public function getIsProgressive()
    {
        return $this->isProgressive;
    }

    /**
     * @return boolean
     */
    public function isProgressive()
    {
        return $this->isProgressive;
    }

    /**
     * @return Registration
     */
    public function getRegistration()
    {
        return $this->registration;
    }

    /**
     * @param Registration $registration
     */
    private function setRegistration($registration)
    {
        $this->registration = $registration;
    }
}