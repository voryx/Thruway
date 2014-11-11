<?php

namespace Thruway\Message;

use Thruway\Message\Traits\RequestTrait;

/**
 * Class UnsubscribedMessage
 * Acknowledge sent by a Broker to a Subscriber to acknowledge unsubscription.
 * <code>[UNSUBSCRIBED, UNSUBSCRIBE.Request|id]</code>
 *
 * @package Thruway\Message
 */
class UnsubscribedMessage extends Message
{

    use RequestTrait;

    /**
     * Constructor
     *
     * @param int $requestId
     */
    public function __construct($requestId)
    {
        $this->setRequestId($requestId);
    }

    /**
     * Get message code
     *
     * @return int
     */
    public function getMsgCode()
    {
        return static::MSG_UNSUBSCRIBED;
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
