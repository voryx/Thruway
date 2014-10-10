<?php

namespace Thruway\Message;

/**
 * Class UnsubscribeMessage
 * Unsubscribe request sent by a Subscriber to a Broker to unsubscribe a subscription.
 * <code>[UNSUBSCRIBE, Request|id, SUBSCRIBED.Subscription|id]</code>
 *
 * @package Thruway\Message
 */
class UnsubscribeMessage extends Message
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
    private $subscriptionId;

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
     * Get subcription ID
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
        return static::MSG_UNSUBSCRIBE;
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
