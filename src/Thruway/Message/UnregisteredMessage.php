<?php

namespace Thruway\Message;

class UnregisteredMessage extends Message
{

    private $requestId;

    function __construct($requestId)
    {
        $this->requestId = $requestId;
    }


    /**
     * @return int
     */
    public function getMsgCode()
    {
        return static::MSG_UNREGISTERED;
    }

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