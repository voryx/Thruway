<?php

namespace Thruway\Message;

use Thruway\Message\Traits\RequestTrait;

/**
 * Class UnsubscribeMessage
 * Unsubscribe request sent by a Subscriber to a Broker to unsubscribe a subscription.
 * <code>[UNSUBSCRIBE, Request|id, SUBSCRIBED.Subscription|id]</code>
 *
 * @package Thruway\Message
 */
class UnsubscribeMessage extends Message
{

    use RequestTrait;

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
        $this->setRequestId($requestId);
        $this->setSubscriptionId($subscriptionId);
    }

    /**
     * Set subscription ID
     *
     * @param int $subscriptionId
     */
    public function setSubscriptionId($subscriptionId)
    {
        $this->subscriptionId = $subscriptionId;
    }

    /**
     * Get subscription ID
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

}
