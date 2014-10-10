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
     * @var int
     */
    private $requestId;

    /**
     *
     * @var int
     */
    private $registrationId;

    /**
     * Constructor
     *
     * @param int $requestId
     * @param int $registrationId
     */
    public function __construct($requestId, $registrationId)
    {
        $this->registrationId = $registrationId;
        $this->requestId      = $requestId;
    }

    /**
     * Get message code
     * 
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
     * Get registration ID
     * 
     * @return int
     */
    public function getRegistrationId()
    {
        return $this->registrationId;
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

}
