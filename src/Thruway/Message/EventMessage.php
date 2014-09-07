<?php

namespace Thruway\Message;


/**
 * Class EventMessage
 * @package Thruway\Message
 */
class EventMessage extends Message
{
    use ArgumentsTrait;

    /**
     * @var
     */
    private $subscriptionId;
    /**
     * @var
     */
    private $publicationId;
    /**
     * @var
     */
    private $details;

    /**
     * @param $subscriptionId
     * @param $publicationId
     * @param $details
     * @param null $arguments
     * @param null $argumentsKw
     */
    function __construct($subscriptionId, $publicationId, $details, $arguments = null, $argumentsKw = null)
    {
        parent::__construct();

        $this->setArguments($arguments);
        $this->setArgumentsKw($argumentsKw);
        $this->details = $details;
        $this->publicationId = $publicationId;
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
        if ($details === null) $details = new \stdClass();
        $details = (object)$details;

        $a = array(
            $this->getSubscriptionId(),
            $this->getPublicationId(),
            $details
        );

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
     * @param mixed $publicationId
     */
    public function setPublicationId($publicationId)
    {
        $this->publicationId = $publicationId;
    }

    /**
     * @return mixed
     */
    public function getPublicationId()
    {
        return $this->publicationId;
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


} 