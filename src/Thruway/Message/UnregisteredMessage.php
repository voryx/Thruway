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
     * @var mixed
     */
    private $requestId;

    /**
     * Constructor
     *
     * @param mixed $requestId
     */
    function __construct($requestId)
    {
        $this->requestId = $requestId;
    }

    static function createFromUnregisterMessage(UnregisterMessage $msg) {
        return new UnregisteredMessage($msg->getRequestId());
    }

    /**
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
     * @param mixed $requestId
     */
    public function setRequestId($requestId)
    {
        $this->requestId = $requestId;
    }

    /**
     * @return mixed
     */
    public function getRequestId()
    {
        return $this->requestId;
    }

}
