<?php

namespace Thruway\Message;

/**
 * Class UnregisteredMessage
 * Acknowledge sent by a Dealer to a Callee for successful unregistration.
 * <code>[UNREGISTERED, UNREGISTER.Request|id]</code>
 *
 * @package Thruway\Message
 */

class UnregisteredMessage extends Message
{

    /**
     *
     * @var int
     */
    private $requestId;

    /**
     * Constructor
     *
     * @param int $requestId
     */
    public function __construct($requestId)
    {
        $this->requestId = $requestId;
    }

    static function createFromUnregisterMessage(UnregisterMessage $msg) {
        return new UnregisteredMessage($msg->getRequestId());
    }

    /**
     * Get message code
     * 
     * @return int
     */
    public function getMsgCode()
    {
        return static::MSG_UNREGISTERED;
    }

    /**
     * This is used by get message parts to get the parts of the message beyond
     * the message code
     *
     * @return array
     */
    public function getAdditionalMsgFields()
    {
        return [$this->getRequestId()];
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
     * Get request ID
     * 
     * @return int
     */
    public function getRequestId()
    {
        return $this->requestId;
    }

}
