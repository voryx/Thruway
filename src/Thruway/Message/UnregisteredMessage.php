<?php

namespace Thruway\Message;

use Thruway\Message\Traits\RequestTrait;

/**
 * Class UnregisteredMessage
 * Acknowledge sent by a Dealer to a Callee for successful unregistration.
 * <code>[UNREGISTERED, UNREGISTER.Request|id]</code>
 *
 * @package Thruway\Message
 */
class UnregisteredMessage extends Message
{

    use RequestTrait;

    /**
     * Constructor
     *
     * @param int $requestId
     */
    public function __construct($requestId)
    {
        $this->requestId = $requestId;
    }

    /**
     * Create Unregisterd message from unregister message
     *
     * @param \Thruway\Message\UnregisterMessage $msg
     * @return \Thruway\Message\UnregisteredMessage
     */
    public static function createFromUnregisterMessage(UnregisterMessage $msg)
    {
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

}
