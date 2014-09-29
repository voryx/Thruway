<?php

namespace Thruway\Message;

/**
 * Class UnregisterMessage
 * A Callees request to unregister a previsouly established registration.
 * <code>[UNREGISTER, Request|id, REGISTERED.Registration|id]</code>
 * 
 * @package Thruway\Message
 */
class UnregisterMessage extends Message
{

    /**
     *
     * @var mixed
     */
    private $requestId;

    /**
     *
     * @var mixed
     */
    private $registrationId;

    /**
     * Contructor
     * 
     * @param mixed $requestId
     * @param mixed $registrationId
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
        return static::MSG_UNREGISTER;
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
