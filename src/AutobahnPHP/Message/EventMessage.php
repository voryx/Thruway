<?php

namespace AutobahnPHP\Message;


class EventMessage extends Message
{
    const MSG_CODE = Message::MSG_EVENT;

    private $subscriptionId;
    private $publicationId;
    private $details;
    private $args;
    private $argsKw;

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
        return static::MSG_CODE;
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
     * @return array
     */
    public function getValidConnectionStates()
    {
        return array(Wamp2Connection::STATE_ESTABLISHED);
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