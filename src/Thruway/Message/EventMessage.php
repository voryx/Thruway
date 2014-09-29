<?php

namespace Thruway\Message;

/**
 * Class EventMessage
 * Event dispatched by Broker to Subscribers for subscription the event was matching.
 * <code>[EVENT, SUBSCRIBED.Subscription|id, PUBLISHED.Publication|id, Details|dict]</code>
 * <code>[EVENT, SUBSCRIBED.Subscription|id, PUBLISHED.Publication|id, Details|dict, PUBLISH.Arguments|list]</code>
 * <code>[EVENT, SUBSCRIBED.Subscription|id, PUBLISHED.Publication|id, Details|dict, PUBLISH.Arguments|list, PUBLISH.ArgumentsKw|dict]</code>
 * 
 * @package Thruway\Message
 */

class EventMessage extends Message
{
    /**
     * using arguments trait
     * @see \Thruway\Message\ArgumentsTrait
     */
    use ArgumentsTrait;

    /**
     * @var int
     */
    private $subscriptionId;
    /**
     * @var int
     */
    private $publicationId;
    /**
     * @var mixed
     */
    private $details;

    /**
     * @param int $subscriptionId
     * @param int $publicationId
     * @param mixed $details
     * @param mixedl $arguments
     * @param mixed $argumentsKw
     */
    function __construct($subscriptionId, $publicationId, $details, $arguments = null, $argumentsKw = null)
    {
        parent::__construct();

        $this->setArguments($arguments);
        $this->setArgumentsKw($argumentsKw);
        $this->details        = $details;
        $this->publicationId  = $publicationId;
        $this->subscriptionId = $subscriptionId;
    }

    /**
     * @return int
     */
    public function getMsgCode()
    {
        return static::MSG_EVENT;
    }

    /**
     * This is used by get message parts to get the parts of the message beyond
     * the message code
     *
     * @return array
     */
    public function getAdditionalMsgFields()
    {
        $details = $this->getDetails();
        if ($details === null) { 
            $details = new \stdClass(); 
        }
        
        $details = (object)$details;

        $a = [
            $this->getSubscriptionId(),
            $this->getPublicationId(),
            $details
        ];

        $a = array_merge($a, $this->getArgumentsForSerialization());

        return $a;
    }

    /**
     * @param PublishMessage $msg
     * @return static
     */
    static public function createFromPublishMessage(PublishMessage $msg)
    {
        return new static(
            $msg->getTopicName(),
            $msg->getRequestId(),
            new \stdClass,
            $msg->getArguments(),
            $msg->getArgumentsKw()
        );
    }

    /**
     * @param mixed $details
     */
    public function setDetails($details)
    {
        $this->details = $details;
    }

    /**
     * @return mixed
     */
    public function getDetails()
    {
        return $this->details;
    }

    /**
     * @param int $publicationId
     */
    public function setPublicationId($publicationId)
    {
        $this->publicationId = $publicationId;
    }

    /**
     * @return int
     */
    public function getPublicationId()
    {
        return $this->publicationId;
    }

    /**
     * @param int $subscriptionId
     */
    public function setSubscriptionId($subscriptionId)
    {
        $this->subscriptionId = $subscriptionId;
    }

    /**
     * @return int
     */
    public function getSubscriptionId()
    {
        return $this->subscriptionId;
    }


} 