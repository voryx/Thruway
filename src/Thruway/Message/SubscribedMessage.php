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
     * @var int
     */
    private $subscriptionId;

    /**
     *
     * @var int
     */
    private $requestId;

    /**
     * Constructor
     *
     * @param int $requestId
     * @param int $subscriptionId
     */
    public function __construct($requestId, $subscriptionId)
    {
        parent::__construct();

        $this->setRequestId($requestId);
        $this->subscriptionId = $subscriptionId;
    }

    /**
     * Set subcription ID
     * 
     * @param int $subscriptionId
     */
    public function setSubscriptionId($subscriptionId)
    {
        $this->subscriptionId = $subscriptionId;
    }

    /**
     * Get Subscription ID
     * 
     * @return int
     */
    public function getSubscriptionId()
    {
        return $this->subscriptionId;
    }

    /**
     * Get message code
     * 
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
