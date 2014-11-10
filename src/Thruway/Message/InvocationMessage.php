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
     * @var int
     */
    private $requestId;

    /**
     * @var int
     */
    private $registrationId;

    /**
     * @var mixed
     */
    private $details;

    /**
     * Constructor
     *
     * @param int $requestId
     * @param int $registrationId
     * @param mixed $details
     * @param mixed $arguments
     * @param mixed $argumentsKw
     */
    public function __construct($requestId, $registrationId, $details, $arguments = null, $argumentsKw = null)
    {
        $this->requestId      = $requestId;
        $this->registrationId = $registrationId;
        $this->details        = $details;
        $this->setArguments($arguments);
        $this->setArgumentsKw($argumentsKw);
    }

    /**
     * Get message code
     * 
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
        $details = $this->getDetails() === null ? new \stdClass() : (object)$this->getDetails();

        $a = [
            $this->requestId,
            $this->registrationId,
            $details
        ];

        $a = array_merge($a, $this->getArgumentsForSerialization());

        return $a;
    }

    /**
     * Create Invocation message from Call message and registration
     * 
     * @param \Thruway\Message\CallMessage $msg
     * @param \Thruway\Registration $registration
     * @return \Thruway\Message\InvocationMessage
     */
    public static function createMessageFrom(CallMessage $msg, Registration $registration)
    {
        $requestId = Session::getUniqueId();
        $details   = new \stdClass();

        return new static($requestId, $registration->getId(), $details, $msg->getArguments(), $msg->getArgumentsKw());
    }

    /**
     * Get request ID
     * 
     * @return int
     */
    public function getRequestId()
    {
        return $this->requestId;
    }

    /**
     * Set request ID
     * 
     * @param int $requestId
     */
    public function setRequestId($requestId)
    {
        $this->requestId = $requestId;
    }

    /**
     * Get details
     * 
     * @return mixed
     */
    public function getDetails()
    {
        return $this->details;
    }

    /**
     * Set details
     * 
     * @param mixed $details
     */
    public function setDetails($details)
    {
        if (is_array($details)) $details = (object)$details;
        $this->details = $details;
    }

    /**
     * Get Registration ID
     * 
     * @return int
     */
    public function getRegistrationId()
    {
        return $this->registrationId;
    }

    /**
     * Set Registration ID
     * 
     * @param int $registrationId
     */
    public function setRegistrationId($registrationId)
    {
        $this->registrationId = $registrationId;
    }

}
