<?php

namespace AutobahnPHP\Message;


class PublishedMessage extends Message {
    const MSG_CODE = Message::MSG_PUBLISHED;

    private $subscriptionId;

    private $publicationId;

    function __construct($subscriptionId, $publicationId)
    {
        $this->publicationId = $publicationId;
        $this->subscriptionId = $subscriptionId;
    }


    /**
     * @return int
     */
    public function getMsgCode() { return static::MSG_CODE; }

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

    /**
     * This is used by get message parts to get the parts of the message beyond
     * the message code
     *
     * @return array
     */
    public function getAdditionalMsgFields()
    {
        return array($this->getSubscriptionId(), $this->getPublicationId());
    }

    /**
     * @return array
     */
    public function getValidConnectionStates()
    {
        return array(Wamp2Connection::STATE_ESTABLISHED);
    }


} 