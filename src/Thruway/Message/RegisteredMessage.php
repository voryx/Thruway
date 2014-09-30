<?php

namespace Thruway\Message;

/**
 * Class RegisteredMessage
 * Acknowledge sent by a Dealer to a Callee for successful registration.
 * <code>[REGISTERED, REGISTER.Request|id, Registration|id]</code>
 * 
 * @package Thruway\Message
 */
class RegisteredMessage extends Message
{

    /**
     * @var mixed
     */
    private $requestId;

    /**
     * @var mixed
     */
    private $registrationId;

    /**
     * Contructor
     * 
     * @param mixed $registrationId
     * @param mixed $requestId
     */
    function __construct($requestId, $registrationId)
    {
        $this->registrationId = $registrationId;
        $this->requestId      = $requestId;
    }

    /**
     * @return int
     */
    public function getMsgCode()
    {
        return static::MSG_REGISTERED;
    }

    /**
     * This is used by get message parts to get the parts of the message beyond
     * the message code
     *
     * @return array
     */
    public function getAdditionalMsgFields()
    {
        return [$this->getRequestId(), $this->getRegistrationId()];
    }

    /**
     * @return mixed
     */
    public function getRegistrationId()
    {
        return $this->registrationId;
    }

    /**
     * @return mixed
     */
    public function getRequestId()
    {
        return $this->requestId;
    }

    
}
