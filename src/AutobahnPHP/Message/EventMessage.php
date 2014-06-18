<?php

namespace AutobahnPHP\Message;


/**
 * Class EventMessage
 * @package AutobahnPHP\Message
 */
class EventMessage extends Message
{


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
     * @var
     */
    private $args;
    /**
     * @var
     */
    private $argsKw;

    /**
     * @param $subscriptionId
     * @param $publicationId
     * @param $details
     * @param $args
     * @param $argsKw
     */
    function __construct($subscriptionId, $publicationId, $details, $args, $argsKw)
    {
        parent::__construct();

        $this->args = $args;
        $this->argsKw = $argsKw;
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
        $a = array(
            $this->getSubscriptionId(),
            $this->getPublicationId(),
            $this->getDetails()
        );

        if ($this->getArgs() != null) {
            $a = array_merge($a, array($this->getArgs()));
            if ($this->getArgsKw()) {
                $a = array_merge($a, array($this->getArgsKw()));
            }
        }

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
     * @param mixed $args
     */
    public function setArgs($args)
    {
        $this->args = $args;
    }

    /**
     * @return mixed
     */
    public function getArgs()
    {
        return $this->args;
    }

    /**
     * @param mixed $argsKw
     */
    public function setArgsKw($argsKw)
    {
        $this->argsKw = $argsKw;
    }

    /**
     * @return mixed
     */
    public function getArgsKw()
    {
        return $this->argsKw;
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