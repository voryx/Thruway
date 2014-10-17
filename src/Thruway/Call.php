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
     * Constructor
     *
     * @param \Thruway\Session $callerSession
     * @param \Thruway\Message\CallMessage $callMessage
     * @param Registration $registration
     */
    public function __construct(
        Session $callerSession,
        CallMessage $callMessage,
        Registration $registration = null
    ) {
        $this->callMessage       = $callMessage;
        $this->callerSession     = $callerSession;
        $this->invocationMessage = null;
        $this->calleeSession     = null;
        $this->isProgressive     = false;
        $this->setRegistration($registration);

        $this->callStart = microtime(true);
    }

    /**
     * @return string
     */
    public function getCallStart()
    {
        return $this->callStart;
    }

    /**
     * Process Yield message
     * 
     * @param \Thruway\Session $session
     * @param \Thruway\Message\YieldMessage $msg
     */
    public function processYield(Session $session, YieldMessage $msg) 
    {
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
        if ($this->invocationMessage === null) {
            // try to create one
            if ($this->registration === null) {
                throw new \Exception("You must set the registration prior to calling getInvocationMessage");
            }

            if ($this->callMessage === null) {
                throw new \Exception("You must set the CallMessage prior to calling getInvocationMessage");
            }

            $invocationMessage = InvocationMessage::createMessageFrom($this->getCallMessage(), $this->getRegistration());

            $details = [];
            if ($this->getRegistration()->getDiscloseCaller() === true && $this->getCallerSession()->getAuthenticationDetails()) {
                $details = [
                    "caller"     => $this->getCallerSession()->getSessionId(),
                    "authid"     => $this->getCallerSession()->getAuthenticationDetails()->getAuthId(),
                    //"authrole" => $this->getCallerSession()->getAuthenticationDetails()->getAuthRole(),
                    "authmethod" => $this->getCallerSession()->getAuthenticationDetails()->getAuthMethod(),
                ];
            }

            // TODO: check to see if callee supports progressive call
            $callOptions   = $this->getCallMessage()->getOptions();
            $isProgressive = false;
            if (is_array($callOptions) && isset($callOptions['receive_progress']) && $callOptions['receive_progress']) {
                $details       = array_merge($details, ["receive_progress" => true]);
                $isProgressive = true;
            }

            // if nothing was added to details - change ot stdClass so it will serialize correctly
            if (count($details) == 0) {
                $details = new \stdClass();
            }
            $invocationMessage->setDetails($details);

            $this->setIsProgressive($isProgressive);

            $this->setInvocationMessage($invocationMessage);
        }

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

    /**
     * Get registration
     * 
     * @return Registration
     */
    public function getRegistration()
    {
        return $this->registration;
    }

    /**
     * @param Registration $registration
     */
    public function setRegistration($registration)
    {
        $this->invocationMessage = null;
        if ($registration === null) {
            $this->setCalleeSession(null);
        } else {
            $this->setCalleeSession($registration->getSession());
        }

        $this->registration = $registration;
    }
}
