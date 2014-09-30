<?php

namespace Thruway\Message;

/**
 * Class UnsubscribedMessage
 * Acknowledge sent by a Broker to a Subscriber to acknowledge unsubscription.
 * <code>[UNSUBSCRIBED, UNSUBSCRIBE.Request|id]</code>
 * 
 * @package Thruway\Message
 */

class UnsubscribedMessage extends Message
{

    /**
     *
     * @var mixed
     */
    private $requestId;

    /**
     * Contructor
     * 
     * @param mixed $requestId
     */
    function __construct($requestId)
    {
        parent::__construct();

        $this->setRequestId($requestId);
    }

    /**
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
