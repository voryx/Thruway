<?php

namespace Thruway\Message;

class UnregisterMessage extends Message
{

    private $requestId;

    private $registrationId;

    function __construct($requestId, $registrationId)
    {
        $this->registrationId = $registrationId;
        $this->requestId = $requestId;
    }


    /**
     * @return int
     */
    public function getMsgCode()
    {
        return static::MSG_UNREGISTER;
    }

    /**
     * This is used by get message parts to get the parts of the message beyond
     * the message code
     *
     * @return array
     */
    public function getAdditionalMsgFields()
    {
        return array();
    }

    /**
     * @return mixed
     */
    public function getRegistrationId()
    {
        return $this->registrationId;
    }

    /**
     * @return mixed
     */
    public function getRequestId()
    {
        return $this->requestId;
    }



} 