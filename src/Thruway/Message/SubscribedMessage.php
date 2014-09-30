<?php

namespace Thruway\Message;

/**
 * Class SubscribedMessage
 * Acknowledge sent by a Broker to a Subscriber to acknowledge a subscription.
 * <code>[SUBSCRIBED, SUBSCRIBE.Request|id, Subscription|id]</code>
 *
 * @package Thruway\Message
 */
class SubscribedMessage extends Message
{

    /**
     *
     * @var mixed
     */
    private $subscriptionId;

    /**
     *
     * @var mixed
     */
    private $requestId;

    /**
     * Constructor
     *
     * @param mixed $requestId
     * @param mixed $subscriptionId
     */
    function __construct($requestId, $subscriptionId)
    {
        parent::__construct();

        $this->setRequestId($requestId);
        $this->subscriptionId = $subscriptionId;
    }

    /**
     * @param mixed $subscriptionId
     */
    public function setSubscriptionId($subscriptionId)
    {
        $this->subscriptionId = $subscriptionId;
    }

    /**
     * @return mixed
     */
    public function getSubscriptionId()
    {
        return $this->subscriptionId;
    }

    /**
     * @return int
     */
    public function getMsgCode()
    {
        return static::MSG_SUBSCRIBED;
    }

    /**
     * This is used by get message parts to get the parts of the message beyond
     * the message code
     *
     * @return array
     */
    public function getAdditionalMsgFields()
    {
        return [$this->getRequestId(), $this->getSubscriptionId()];
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
