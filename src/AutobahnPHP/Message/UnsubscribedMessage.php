<?php

namespace AutobahnPHP\Message;


class UnsubscribedMessage extends Message {
    const MSG_CODE = Message::MSG_UNSUBSCRIBED;

    function __construct($requestId)
    {
        parent::__construct();

        $this->setRequestId($requestId);
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
        return array($this->getRequestId());
    }

}