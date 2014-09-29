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
     * @var mixed
     */
    private $requestId;

    /**
     *
     * @var mixed
     */
    private $subscriptionId;

    /**
     * Contructor
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
        // TODO: Implement getAdditionalMsgFields() method.
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
