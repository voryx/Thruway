<?php

namespace AutobahnPHP\Message;


class SubscribedMessage extends Message {
    const MSG_CODE = Message::MSG_SUBSCRIBED;

    private $subscriptionId;

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
    public function getMsgCode() { return static::MSG_CODE; }

    /**
     * This is used by get message parts to get the parts of the message beyond
     * the message code
     *
     * @return array
     */
    public function getAdditionalMsgFields()
    {
        return array($this->getRequestId(), $this->getSubscriptionId());
    }

    /**
     * @return array
     */
    public function getValidConnectionStates()
    {
        return array(Wamp2Connection::STATE_ESTABLISHED);
    }

} 