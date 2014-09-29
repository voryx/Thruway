<?php

namespace Thruway\Message;

use Thruway\Registration;
use Thruway\Session;

/**
 * Class InvocationMessage
 * Actual invocation of an endpoint sent by Dealer to a Callee.
 * <code>[INVOCATION, Request|id, REGISTERED.Registration|id, Details|dict]</code>
 * <code>[INVOCATION, Request|id, REGISTERED.Registration|id, Details|dict, CALL.Arguments|list]</code>
 * <code>[INVOCATION, Request|id, REGISTERED.Registration|id, Details|dict, CALL.Arguments|list, CALL.ArgumentsKw|dict]</code>
 * 
 * @package Thruway\Message
 */
class InvocationMessage extends Message
{

    /**
     * using arguments trait
     * @see \Thruway\Message\ArgumentsTrait
     */
    use ArgumentsTrait;

    /**
     * @var mixed
     */
    private $requestId;

    /**
     * @var mixed
     */
    private $registrationId;

    /**
     * @var mixed
     */
    private $details;

    /**
     * Contructor
     * 
     * @param mixed $requestId
     * @param mixed $registrationId
     * @param mixed $details
     * @param mixed $arguments
     * @param mixed $argumentsKw
     */
    function __construct($requestId, $registrationId, $details, $arguments = null, $argumentsKw = null)
    {
        $this->requestId      = $requestId;
        $this->registrationId = $registrationId;
        $this->details        = $details;
        $this->setArguments($arguments);
        $this->setArgumentsKw($argumentsKw);
    }

    /**
     * @return int
     */
    public function getMsgCode()
    {
        return static::MSG_INVOCATION;
    }

    /**
     * This is used by get message parts to get the parts of the message beyond
     * the message code
     *
     * @return array
     */
    public function getAdditionalMsgFields()
    {
        $details = $this->getDetails() === null ? new \stdClass() : (object) $this->getDetails();

        $a = [
            $this->requestId,
            $this->registrationId,
            $details
        ];

        $a = array_merge($a, $this->getArgumentsForSerialization());

        return $a;
    }

    /**
     * @param CallMessage $msg
     * @param Registration $registration
     * @return static
     */
    static function createMessageFrom(CallMessage $msg, Registration $registration)
    {
        $requestId = Session::getUniqueId();
        $details   = new \stdClass();

        return new static($requestId, $registration->getId(), $details, $msg->getArguments(), $msg->getArgumentsKw());
    }

    /**
     * @return mixed
     */
    public function getRequestId()
    {
        return $this->requestId;
    }

    /**
     * @param mixed $requestId
     */
    public function setRequestId($requestId)
    {
        $this->requestId = $requestId;
    }

    /**
     * @return mixed
     */
    public function getDetails()
    {
        return $this->details;
    }

    /**
     * @param mixed $details
     */
    public function setDetails($details)
    {
        $this->details = $details;
    }

    /**
     * @return mixed
     */
    public function getRegistrationId()
    {
        return $this->registrationId;
    }

    /**
     * @param mixed $registrationId
     */
    public function setRegistrationId($registrationId)
    {
        $this->registrationId = $registrationId;
    }

}
