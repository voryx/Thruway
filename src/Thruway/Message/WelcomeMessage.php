<?php

namespace Thruway\Message;


/**
 * Class WelcomeMessage
 * @package Thruway\Message
 */
class WelcomeMessage extends Message
{
    /**
     * @var
     */
    private $sessionId;
    /**
     * @var
     */
    private $details;

    /**
     * @param $sessionId
     * @param $details
     */
    function __construct($sessionId, $details)
    {
        $this->details = $details;
        $this->sessionId = $sessionId;
    }


    /**
     * @return int
     */
    public function getMsgCode()
    {
        return static::MSG_WELCOME;
    }

    /**
     * This is used by get message parts to get the parts of the message beyond
     * the message code
     *
     * @return array
     */
    public function getAdditionalMsgFields()
    {
        return array($this->sessionId, $this->details);
    }

    /**
     * @return mixed
     */
    public function getDetails()
    {
        return $this->details;
    }

    /**
     * @return mixed
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }


} 